Zarinpal payment gateway for Laravel
=======

Installation
------------
Use [Composer] to install the package:

```
$ composer require blackplatinum/zarinpal
```

How To Use:
-------

In your config folder open app.php and add following lines:
```php
'providers' => [
    ...........
    BlackPlatinum\Zarinpal\ZarinpalServiceProvider::class,
],

'aliases' => [
    ...........
    'Zarinpal' => BlackPlatinum\Zarinpal\Zarinpal::class,
],
```
 
Then add your Merchant ID in .env like this:
```php
MERCHANT_ID=XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX
```
In the end restart your server. (Also you can ignore adding merchant id to .env and use it in your
source code with setMerchantId())

How To Run:
-----------

```php

use BlackPlatinum\Zarinpal\Zarinpal;

/**
 * This is for sending your request to the bank for your payment
 */
$paymentRequest = new Zarinpal(
 'request',
    [
        'price' => 100, // Toman
        'description' => 'درگاه پرداخت زرین پال',
        'callbackUri' => 'your_uri', // Without '/'
        'orderId' => 1 // Your order id
    ], true // Enables sandbox mode
);
$result = $paymentRequest->sendPaymentInfoToGateway();
if ($result->Status == 100) {
    // The information that you have sent was out of mistakes and you are gonna
    // redirect to zarinpal gateway 
    return redirect()->to($paymentRequest->linkToGateway($result->Authority));
}

// There is something wrong about your request and you are not qualify to redirect
// to zarinpal gateway 
return redirect('your_failure_url_in_payment');

--------------------------------------------------------------------------------------------------

/**
 * This is for receiving your response from the bank about your payment request in previous code
 */
$paymentResponse = new Zarinpal(
'response',
    [
        'price' => 100, // Toman
        'authority' => $request->Authority
    ], true // Enables sandbox mode
);
$result = $paymentResponse->receivePaymentInfoFromGateway($request->Status);
if ($result) {
    // Yor payment done successfully
    $authority = $request->input('Authority');
    $status = $request->input('Status');
    $refId = $result->RefID;
    // Do the rest...
}
else {
    // Yor payment failed or you canceled payment
}
```

Authors
-------

* [BlackPlatinum Developers]
* E-Mail: [blackplatinum2019@gmail.com]

License
-------

All contents of this component are licensed under the [MIT license].
