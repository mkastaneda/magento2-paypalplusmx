<?php
/**
 * MMDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDMMM
 * MDDDDDDDDDDDDDNNDDDDDDDDDDDDDDDDD=.DDDDDDDDDDDDDDDDDDDDDDDMM
 * MDDDDDDDDDDDD===8NDDDDDDDDDDDDDDD=.NDDDDDDDDDDDDDDDDDDDDDDMM
 * DDDDDDDDDN===+N====NDDDDDDDDDDDDD=.DDDDDDDDDDDDDDDDDDDDDDDDM
 * DDDDDDD$DN=8DDDDDD=~~~DDDDDDDDDND=.NDDDDDNDNDDDDDDDDDDDDDDDM
 * DDDDDDD+===NDDDDDDDDN~~N........8$........D ........DDDDDDDM
 * DDDDDDD+=D+===NDDDDDN~~N.?DDDDDDDDDDDDDD:.D .DDDDD .DDDDDDDN
 * DDDDDDD++DDDN===DDDDD~~N.?DDDDDDDDDDDDDD:.D .DDDDD .DDDDDDDD
 * DDDDDDD++DDDDD==DDDDN~~N.?DDDDDDDDDDDDDD:.D .DDDDD .DDDDDDDN
 * DDDDDDD++DDDDD==DDDDD~~N.... ...8$........D ........DDDDDDDM
 * DDDDDDD$===8DD==DD~~~~DDDDDDDDN.IDDDDDDDDDDDNDDDDDDNDDDDDDDM
 * NDDDDDDDDD===D====~NDDDDDD?DNNN.IDNODDDDDDDDN?DNNDDDDDDDDDDM
 * MDDDDDDDDDDDDD==8DDDDDDDDDDDDDN.IDDDNDDDDDDDDNDDNDDDDDDDDDMM
 * MDDDDDDDDDDDDDDDDDDDDDDDDDDDDDN.IDDDDDDDDDDDDDDDDDDDDDDDDDMM
 * MMDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDMMM
 *
 * @author José Castañeda <jose@qbo.tech>
 * @category qbo
 * @package qbo\PayPalPlusMx\
 * 
 * © 2016 QBO DIGITAL SOLUTIONS. 
 *
 */

namespace qbo\PayPalPlusMx\Model\Http;

use \Magento\Framework\App\Config\ScopeConfigInterface;
/**
 * Class Config
 *
 * @package PayPalPlusMx
 */
class Config
{
    //Headers conf
    const HEADER_SEPARATOR = ';';
    const CREDENTIAL_SEPARATOR = ':';
    const BODY_SEPARATOR = '=';
    const HTTP_GET = 'GET';
    const HTTP_POST = 'POST';
    const GRANT_TYPE = 'client_credentials';
    const TOKEN   = 'token';
    const PAYMENT = 'payment';
    const PATCH   = 'patch';
    const LOOKUP   = 'lookup';
    const XP      = 'xp';
    
    //Service URLs
    const ACCESS_TOKEN_SANDBOX_URL     = 'https://api.sandbox.paypal.com/v1/oauth2/token';
    const ACCESS_TOKEN_URL             = 'https://api.paypal.com/v1/oauth2/token';
    
    const PAYMENT_SANDBOX_URL  = 'https://api.sandbox.paypal.com/v1/payments/payment/';
    const PAYMENT_URL          = 'https://api.paypal.com/v1/payments/payment/';
    
    const PAYMENT_REQUEST_SANDBOX_URL  = 'https://api.sandbox.paypal.com/v1/payments/payment';
    const PAYMENT_REQUEST_URL          = 'https://api.paypal.com/v1/payments/payment';
    
    const PATCH_REQUEST_SANDBOX_URL    = 'https://api.sandbox.paypal.com/v1/payments/payment/';
    const PATCH_REQUEST_URL            = 'https://api.paypal.com/v1/payments/payment/';
   
    const XP_REQUEST_SANDBOX_URL       = 'https://api.sandbox.paypal.com/v1/payment-experience/web-profiles';
    const XP_REQUEST_URL               = 'https://api.paypal.com/v1/v1/payment-experience/web-profiles';
    
    const XML_PATH_CLIENT_ID           = 'payment/qbo_paypalplusmx/client_id';
    const XML_PATH_CLIENT_SECRET       = 'payment/qbo_paypalplusmx/client_secret';
    const XML_PATH_SANDBOX_MODE        = 'payment/qbo_paypalplusmx/sandbox_flag';
    
    private $headers = array();
    private $curlOptions;
    private $url;
    private $method;
    /***
     * Number of times to retry a failed HTTP call
     */
    private $retryCount = 1;
    /**
     * Default Constructor
     *
     * @param string $url
     * @param string $method HTTP method (GET, POST etc) defaults to POST
     * @param array $configs All Configurations
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig, 
        $method = self::HTTP_POST
    ) {
        $this->method = $method;
        $this->scopeConfig = $scopeConfig;
    }
    /**
     * Gets Url
     *
     * @return null|string
     */
    public function getUrl($type, $paymentId = false)
    {
        switch($type) {
            case self::TOKEN   : $this->url = $this->getSandBoxFlag() ? self::ACCESS_TOKEN_SANDBOX_URL : self::ACCESS_TOKEN_URL; 
                break;
            case self::PAYMENT : $this->url = $this->getSandBoxFlag() ? self::PAYMENT_REQUEST_SANDBOX_URL : self::PAYMENT_REQUEST_URL; 
                break;
            case self::PATCH   : $this->url = $this->getSandBoxFlag() ? self::PATCH_REQUEST_SANDBOX_URL . $paymentId : self::PATCH_REQUEST_URL . $paymentId; 
                break;
            case self::LOOKUP  : $this->url = $this->getSandBoxFlag() ? self::PAYMENT_SANDBOX_URL . $paymentId : self::PAYMENT_URL . $paymentId; 
                break;
            case self::XP      : $this->url = $this->getSandBoxFlag() ? self::XP_REQUEST_SANDBOX_URL : self::XP_REQUEST_URL; 
                break;
            default: false;
        }
        return $this->url;
    }
    /**
     * Get is sandbox mode is enabled
     * 
     * @return type
     */
    public function getSandBoxFlag()
    {
       return  $this->scopeConfig->getValue(
            self::XML_PATH_SANDBOX_MODE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ); 
    }
   /**
     * Get frame action URL
     *
     * @param string $code
     * @return string
     */
    public function getCredentialsConfig()
    {
        $config = array();
        
        $config['ClientId'] = $this->scopeConfig->getValue(
            self::XML_PATH_CLIENT_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $config['ClientSecret'] =  $this->scopeConfig->getValue(
            self::XML_PATH_CLIENT_SECRET,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
         return array(
            'user'     => $config['ClientId'], 
            'password' => $config['ClientSecret']
        );
    }

    /**
     * Get request body data
     */
    public function getBody()
    {
        return array('grant_type' => self::GRANT_TYPE);
    }

    /**
     * Gets Method
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }
    /**
     * Gets all Headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
    /**
     * Get Header by Name
     *
     * @param $name
     * @return string|null
     */
    public function getHeader($name)
    {
        if (array_key_exists($name, $this->headers)) {
            return $this->headers[$name];
        }
        return null;
    }
    /**
     * Sets Url
     *
     * @param $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }
    /**
     * Set Headers
     *
     * @param array $headers
     */
    public function setHeaders(array $headers = array())
    {
        $this->headers = array_merge($headers, $this->headers);
    }
    /**
     * Adds a Header
     *
     * @param      $name
     * @param      $value
     * @param bool $overWrite allows you to override header value
     */
    public function addHeader($name, $value, $overWrite = true)
    {
        if (!array_key_exists($name, $this->headers) || $overWrite) {
            $this->headers[$name] = $value;
        } else {
            $this->headers[$name] = $this->headers[$name] . self::HEADER_SEPARATOR . $value;
        }
    }
    /**
     * Removes a Header
     *
     * @param $name
     */
    public function removeHeader($name)
    {
        unset($this->headers[$name]);
    }
    /**
     * Gets all curl options
     *
     * @return array
     */
    public function getCurlOptions()
    {
        return $this->curlOptions;
    }
    /**
     * Add Curl Option
     *
     * @param string $name
     * @param mixed  $value
     */
    public function addCurlOption($name, $value)
    {
        $this->curlOptions[$name] = $value;
    }
    /**
     * Removes a curl option from the list
     *
     * @param $name
     */
    public function removeCurlOption($name)
    {
        unset($this->curlOptions[$name]);
    }
    /**
     * Set Curl Options. Overrides all curl options
     *
     * @param $options
     */
    public function setCurlOptions($options)
    {
        $this->curlOptions = $options;
    }
    /**
     * Set ssl parameters for certificate based client authentication
     *
     * @param      $certPath
     * @param null $passPhrase
     */
    public function setSSLCert($certPath, $passPhrase = null)
    {
        $this->curlOptions[CURLOPT_SSLCERT] = realpath($certPath);
        if (isset($passPhrase) && trim($passPhrase) != "") {
            $this->curlOptions[CURLOPT_SSLCERTPASSWD] = $passPhrase;
        }
    }
    /**
     * Set connection timeout in seconds
     *
     * @param integer $timeout
     */
    public function setHttpTimeout($timeout)
    {
        $this->curlOptions[CURLOPT_CONNECTTIMEOUT] = $timeout;
    }
    /**
     * Set HTTP proxy information
     *
     * @param string $proxy
     * @throws PayPalConfigurationException
     */
    public function setHttpProxy($proxy)
    {
        $urlParts = parse_url($proxy);
        if ($urlParts == false || !array_key_exists("host", $urlParts)) {
            throw new PayPalConfigurationException("Invalid proxy configuration " . $proxy);
        }
        $this->curlOptions[CURLOPT_PROXY] = $urlParts["host"];
        if (isset($urlParts["port"])) {
            $this->curlOptions[CURLOPT_PROXY] .= ":" . $urlParts["port"];
        }
        if (isset($urlParts["user"])) {
            $this->curlOptions[CURLOPT_PROXYUSERPWD] = $urlParts["user"] . ":" . $urlParts["pass"];
        }
    }
    /**
     * Set Http Retry Counts
     *
     * @param int $retryCount
     */
    public function setHttpRetryCount($retryCount)
    {
        $this->retryCount = $retryCount;
    }
    /**
     * Get Http Retry Counts
     *
     * @return int
     */
    public function getHttpRetryCount()
    {
        return $this->retryCount;
    }
    /**
     * Sets the User-Agent string on the HTTP request
     *
     * @param string $userAgentString
     */
    public function setUserAgent($userAgentString)
    {
        $this->curlOptions[CURLOPT_USERAGENT] = $userAgentString;
    }

}
