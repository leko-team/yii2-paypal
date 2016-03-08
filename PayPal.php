<?php
namespace leko\paypal;

define('PP_CONFIG_PATH', __DIR__);

use Yii;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\base\ErrorException;
use PayPal\Api\Amount;
use PayPal\Api\Address;
use PayPal\Api\CreditCard;
use PayPal\Api\Details;
use PayPal\Api\FundingInstrument;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

/**
 * @todo make description
 * 
 * PayPal class
 */
class PayPal extends yii\base\Component
{
    /**
     * Payment modes.
     *
     * Mode production.
     * 
     * @var string
     */
    const MODE_SANDBOX = 'sandbox';

    /**
     * Mode development.
     * 
     * @var string
     */
    const MODE_LIVE = 'live';

    /**
     * @todo make description
     * 
     * Log levels.
     * 
     * Logging level can be one of FINE, INFO, WARN or ERROR.
     * Logging is most verbose in the 'FINE' level and decreases as you proceed towards ERROR.
     *
     * Level "fine".
     *
     * @var string
     */
    const LOG_LEVEL_FINE  = 'FINE';

    /**
     * Level "info".
     *
     * @var string
     */
    const LOG_LEVEL_INFO  = 'INFO';

    /**
     * Level "warn".
     *
     * @var string
     */
    const LOG_LEVEL_WARN  = 'WARN';

    /**
     * Level "error".
     *
     * @var string
     */
    const LOG_LEVEL_ERROR = 'ERROR';

    /**
     * @todo make description
     * 
     * API settings.
     *
     * Client ID.
     *
     * @var string
     */
    public $clientId;

    /**
     * Client secret key.
     * 
     * @var string
     */
    public $clientSecret;

    /**
     * Application mode type.
     * 
     * @var boolean
     */
    public $isProduction = false;

    /**
     * Payment currency code.
     * 
     * @var string
     */
    public $currency = 'USD';

    /**
     * Array of config.
     * 
     * @var array
     */
    public $config = [];

    /**
     * Array of params.
     * 
     * @var array
     */
    public $params = [];

    /**
     * @todo make description
     * 
     * ApiContext.
     * 
     * @var null
     */
    private $_apiContext = null;

    /**
     * [$_redirectUrls description]
     * @var null
     */
    private $_redirectUrls = null;

    /**
     * Model initialization.
     */
    public function init()
    {
        $this->setConfig();
        $this->setParams();
    }

    /**
     * @todo make description
     * 
     * @inheritdoc
     *
     * @return [type] [<description>]
     */
    private function setConfig()
    {
        // ### Api context
        // Use an ApiContext object to authenticate
        // API calls. The clientId and clientSecret for the
        // OAuthTokenCredential class can be retrieved from
        // developer.paypal.com
        $this->setApiContext(
            new ApiContext(
                new OAuthTokenCredential(
                    $this->clientId,
                    $this->clientSecret
                )
            )
        );

        // #### SDK configuration
        // Comment this line out and uncomment the PP_CONFIG_PATH
        // 'define' block if you want to use static file
        // based configuration
        $this->getApiContext()->setConfig(ArrayHelper::merge([
            'mode'                      => self::MODE_SANDBOX, // development (sandbox) or production (live) mode
            'http.ConnectionTimeOut'    => 30,
            'http.Retry'                => 1,
            'log.LogEnabled'            => YII_DEBUG ? 1 : 0,
            'log.FileName'              => Yii::getAlias('@runtime/logs/paypal.log'),
            'log.LogLevel'              => self::LOG_LEVEL_FINE,
            'validation.level'          => 'log',
            'cache.enabled'             => 'true'
        ], $this->config));

        // Set file name of the log if present
        if (isset($this->config['log.FileName'])
            && isset($this->config['log.LogEnabled'])
            && ((bool) $this->config['log.LogEnabled'] == true)
        ) {
            $logFileName = \Yii::getAlias($this->config['log.FileName']);
            if ($logFileName) {
                if (!file_exists($logFileName)) {
                    if (!touch($logFileName)) {
                        throw new ErrorException('Can\'t create paypal.log file at: ' . $logFileName);
                    }
                }
            }
            $this->config['log.FileName'] = $logFileName;
        }
        return $this->getApiContext();
    }

    /**
     * @todo make description
     * 
     * @inheritdoc
     *
     * @return [type] [<description>]
     */
    private function setParams()
    {
        if ($this->setReturnUrl() && $this->setCancelUrl()) {
            $this->_redirectUrls = $this->getRedirectUrls();
        }
    }

    /**
     * [setRedirectUrls description]
     */
    private function setReturnUrl()
    {
        if (isset($this->params['returnUrl'])
            && !empty($this->params['returnUrl'])
            && is_string($this->params['returnUrl'])
        ) {
            $this->params['returnUrl'] = Url::to([$this->params['returnUrl']], true);
            return true;
        }
    }

    /**
     * [getReturnUrl description]
     * @return [type] [description]
     */
    public function getReturnUrl()
    {
        return $this->params['returnUrl'];
    }

    /**
     * [setCancelUrl description]
     */
    private function setCancelUrl()
    {
        if (isset($this->params['cancelUrl'])
            && !empty($this->params['cancelUrl'])
            && is_string($this->params['cancelUrl'])
        ) {
            $this->params['cancelUrl'] = Url::to([$this->params['cancelUrl']], true);
            return true;
        }
    }

    /**
     * [getCancelUrl description]
     * @return [type] [description]
     */
    public function getCancelUrl()
    {
        return $this->params['cancelUrl'];
    }

    /**
     * [setRedirectUrls description]
     */
    public function setRedirectUrls()
    {
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl($this->getReturnUrl());
        $redirectUrls->setCancelUrl($this->getCancelUrl());
        return $redirectUrls;
    }

    /**
     * [getRedirectUrls description]
     * @return [type] [description]
     */
    private function getRedirectUrls()
    {
        return $this->setRedirectUrls();
    }

    /**
     * [getApiContext description]
     * 
     * @return [type] [description]
     */
    public function setApiContext($apiContext)
    {
        $this->_apiContext = $apiContext;
    }

    /**
     * [getApiContext description]
     * 
     * @return [type] [description]
     */
    public function getApiContext()
    {
        return $this->_apiContext;
    }

    /**
     * @todo make description
     * 
     * Demo payment.
     *
     * @return [type] [<description>]
     */
    public function payDemo()
    {
        $addr = new Address();
        $addr->setLine1('52 N Main ST');
        $addr->setCity('Johnstown');
        $addr->setCountryCode('US');
        $addr->setPostalCode('43210');
        $addr->setState('OH');

        $card = new CreditCard();
        $card->setNumber('4627583228000410');
        $card->setType('visa');
        $card->setExpireMonth('04');
        $card->setExpireYear('2021');
        $card->setCvv2('874');
        $card->setFirstName('Joe');
        $card->setLastName('Shopper');
        $card->setBillingAddress($addr);

        $fi = new FundingInstrument();
        $fi->setCreditCard($card);

        $payer = new Payer();
        $payer->setPaymentMethod('credit_card');
        $payer->setFundingInstruments(array($fi));

        // $amountDetails = new Details();
        // $amountDetails->setSubtotal('15.99');
        // $amountDetails->setTax('0.03');
        // $amountDetails->setShipping('0.03');

        $amount = new Amount();
        $amount->setCurrency('USD');
        $amount->setTotal('7.47');
        // $amount->setDetails($amountDetails);

        $transaction = new Transaction();
        $transaction->setAmount($amount);
        $transaction->setDescription('This is the payment transaction description.');

        $payment = new Payment();
        $payment->setIntent('sale');
        $payment->setPayer($payer);
        $payment->setTransactions(array($transaction));
        // if isset urls in component params
        if ($this->_redirectUrls) $payment->setRedirectUrls($this->_redirectUrls);

        return $payment->create($this->getApiContext());
    }
}
