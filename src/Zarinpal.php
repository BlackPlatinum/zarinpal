<?php

namespace BlackPlatinum\Zarinpal;

class Zarinpal
{
    /**
     * @var bool Determine if we are in developing mode
     */
    private $isDevelopingMode = false;

    /**
     * @var string The unique zarinpal merchant id
     */
    private $merchantId;

    /**
     * @var int The total price
     */
    private $price;

    /**
     * @var string The transaction description
     */
    private $description;

    /**
     * @var string The route that user will be redirected after payment
     */
    private $callbackUrl;

    /**
     * @var string The payment request url to check the information
     */
    private $paymentRequest;

    /**
     * @var string The gateway url
     */
    private $gateWayUrl;

    /**
     * @var string The authority code
     */
    private $authority;

    /**
     * @var string The request mode (payment request or payment response)
     */
    private $mod;

    /**
     * @var string Request mode alias name
     */
    private const REQUEST = 'request';

    /**
     * @var string Response mode alias name
     */
    private const RESPONSE = 'response';

    /**
     * @var array Defined modes
     */
    private const MODES = [self::REQUEST, self::RESPONSE];

    /**
     * Create an instance of Zarinpal
     *
     * @param  string  $mode
     * @param  array  $paymentData
     * @param  bool  $enableSandbox
     */
    public function __construct($mode, array $paymentData, $enableSandbox = false)
    {
        if (!$this->isValidMode($mode)) {
            throw new \RuntimeException("$mode is not a predefined mode.");
        }

        if (!$this->isValidPaymentData($mode, $paymentData) || $paymentData === []) {
            throw new \RuntimeException("payment data array is not valid.");
        }

        if ($mode === self::REQUEST) {
            $this->mod = $mode;
            $this->merchantId = env('MERCHANT_ID');
            $this->price = $paymentData['price'];
            $this->description = $paymentData['description'];
            $this->callbackUrl = env('APP_URL').'/'.$paymentData['callbackUri'].'/'.$paymentData['orderId'];
            $this->paymentRequest = 'https://www.zarinpal.com/pg/services/WebGate/wsdl';
            $this->gateWayUrl = 'https://www.zarinpal.com/pg/StartPay/';
        }

        if ($mode === self::RESPONSE) {
            $this->mod = $mode;
            $this->merchantId = env('MERCHANT_ID');
            $this->price = $paymentData['price'];
            $this->authority = $paymentData['authority'];
            $this->paymentRequest = 'https://www.zarinpal.com/pg/services/WebGate/wsdl';
        }

        if ($enableSandbox) {
            $this->enableSandBox();
        }
    }

    /**
     * Validates request mode
     *
     * @param  string  $mode
     * @return bool
     */
    private function isValidMode($mode)
    {
        return in_array(strtolower($mode), self::MODES, true);
    }

    /**
     * Validates payment data array
     *
     * @param  string  $mode
     * @param  array  $paymentData
     * @return bool
     */
    private function isValidPaymentData($mode, array $paymentData)
    {
        $keys = array_keys($paymentData);

        if ($mode === self::REQUEST) {
            return array_diff($keys, ['price', 'description', 'callbackUri', 'orderId']) === Array();
        }

        return array_diff($keys, ['price', 'authority']) === Array();
    }

    /**
     * Enables sandbox mode (testing)
     *
     * @return void
     */
    private function enableSandBox()
    {
        if ($this->mod === self::REQUEST) {
            $this->isDevelopingMode = true;
            $this->paymentRequest = 'https://sandbox.zarinpal.com/pg/services/WebGate/wsdl';
            $this->gateWayUrl = 'https://sandbox.zarinpal.com/pg/StartPay/';
            return;
        }

        $this->paymentRequest = 'https://sandbox.zarinpal.com/pg/services/WebGate/wsdl';
        return;
    }

    /**
     * Set system merchant id
     *
     * @param  string  $merchantId
     * @return Zarinpal
     */
    public function setMerchantId($merchantId)
    {
        $this->merchantId = $merchantId;

        return $this;
    }

    /**
     * Sends payment information to zarinpal gateway to check qualification
     *
     * @throws \SoapFault
     */
    public function sendPaymentInfoToGateway()
    {
        if ($this->mod !== self::REQUEST) {
            throw new \RuntimeException("You can not call this method on $this->mod mode.");
        }

        $client = new \SoapClient($this->paymentRequest, ['encoding' => 'UTF-8']);

        return $client->PaymentRequest(
            [
                'MerchantID' => $this->merchantId,
                'Amount' => $this->price,
                'Description' => $this->description,
                'CallbackURL' => $this->callbackUrl
            ]
        );
    }

    /**
     * Links your application to zarinpal gateway payment information is true
     *
     * @param $authority
     * @return string
     */
    public function linkToGateway($authority)
    {
        if ($this->mod !== self::REQUEST) {
            throw new \RuntimeException("You can not call this method on $this->mod mode.");
        }

        if ($this->isDevelopingMode) {
            return $this->gateWayUrl.$authority;
        }

        return $this->gateWayUrl.$authority.'/ZarinGate';
    }

    /**
     * Receive payment information from zarinpal gateway
     *
     * @param  string  $status
     * @return object|array|bool
     * @throws \SoapFault
     */
    public function receivePaymentInfoFromGateway($status)
    {
        if ($this->mod !== self::RESPONSE) {
            throw new \RuntimeException("You can not call this method on $this->mod mode.");
        }

        if ($status === 'OK') {
            $client = new \SoapClient($this->paymentRequest, ['encoding' => 'UTF-8']);

            return $client->PaymentVerification(
                [
                    'MerchantID' => $this->merchantId,
                    'Authority' => $this->authority,
                    'Amount' => $this->price
                ]
            );
        }

        return false;
    }
}
