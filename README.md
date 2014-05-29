# Introduction

Sms Service is a Symfony2 bundle to handle sms sending process by some different operators.
It can be used as a Symfony2 service.
The supported operators are:

* smshosting
* tol
* gatewaysms
* smsmarket
* mobyt
* 9net

In addition it can be specified a custom url (for not supported operators).

It also supports Portech devices.


# Usage

```
$sms_serv = $this->get('nethesis_sms.sms');

try {
    $sms_serv->sendAction(
        '<sms provider>',
        '<user>',
        '<pass>',
        '<caller>',
        '<custom url>',
        '<dest>',
        '<SMS text message>',
        '<portech ip, if used>'
} catch (\Exception $e) {
    ...
}
```

# Recommendations

Most operators request the complete cellular number with prefix (e.g. +39 or 39 before the number).
