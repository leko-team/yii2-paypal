# leko-team/yii2-paypal
### Description
TODO: based on https://github.com/marciocamello/yii2-paypal
### Installation
* install curl:

```sh
$ sudo apt-get install libcurl3 php5-curl
```
* add to your composer.json:
```
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/leko-team/yii2-paypal.git"
    }
],
"require": {
    "leko-team/yii2-paypal": "dev-master"
},
```
* run composer update:
```sh
$ composer update
```
### Usage
* add to your Yii2 config file this part with component settings:
```php
'paypal' => [
    'class'        => 'leko\paypal',
    'clientId'     => 'you_client_id',
    'clientSecret' => 'you_client_secret',
    'isProduction' => false,
    // This is config file for the PayPal system
    'config' => [
        'http.ConnectionTimeOut' => 30,
        'http.Retry'             => 1,
        'mode'                   => leko\paypal\Paypal::MODE_SANDBOX, // development (sandbox) or production (live) mode
        'log.LogEnabled'         => YII_DEBUG ? 1 : 0,
        'log.FileName'           => '@runtime/logs/paypal.log',
        'log.LogLevel'           => leko\paypal\Paypal::LOG_LEVEL_FINE,
    ],
],
```
