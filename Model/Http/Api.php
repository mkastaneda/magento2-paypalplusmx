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
 * @copyright   qbo (http://www.qbo.tech)
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * 
 * © 2016 QBO DIGITAL SOLUTIONS. 
 *
 */
namespace qbo\PayPalPlusMx\Model\Http;

use qbo\PayPalPlusMx\Model\Http\Config;
use qbo\PayPalPlusMx\Model\Http\Payment;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\DataObject;
use qbo\PayPalPlusMx\Helper\Profile;
use qbo\PayPalPlusMx\Model\Config as PayPalConfig;
/**
 * PayPal Plus API Client
 * 
 * @author José Catsañeda <jose@qbo.tech>
 */
class Api 
{
    const XML_PATH_STORE_NAME    = 'general/store_information/name';
    const XML_PATH_DEBUG_MODE    = 'payment/qbo_paypalplusmx/debug';
    const XML_PATH_PROFILE_NAME  = 'payment/qbo_paypalplusmx/profile_name';
    const XP_VALIDATION_ERROR    = 'VALIDATION_ERROR';
    const XP_URL_CODE            = 'xp';
    const PAYMENT                = 'payment';
    const EXECUTE                = 'execute';
    const APPROVAL_URL_CODE      = 'approval_url';
    const TOKEN                  = 'token';
    const PATCH                  = 'patch';
    const LOOKUP                 = 'lookup';
    const ACCESS_TOKEN           = 'access_token';
    const EXPIRES_IN             = 'expires_in';
    const DATE_FORMAT            = 'Y-m-d H:i:s';
    const VALID_UNTIL            = 'valid_until';
    const ACCEPT_HEADERS         = 'application/json';
    const LOG_LEVEL              = '100';
    const LINKS                  = 'links';
    const IFRAME_REL             = 'href';
    const APPROVED_PAYMENT       = 'approved';
    const CREATED_PAYMENT        = 'created';
    const DUPLICATED_PAYMENT     = "We're sorry, your payment has been already processed.";
    const PAYMENT_ALREADY_DONE   = 'PAYMENT_ALREADY_DONE';


    /**
     * @var ZendClientFactory
     */
    protected $httpClientFactory;
    /**
     * @var PayPalHttpConfig
     */
    private $httpConfig;
    /**
     * @var type 
     */
    public $_accessToken = false;
    /**
     * @var type 
     */
    private $paymentObject = array();
    /**
     * @var Magento\Checkout\Model\Cart 
     */
    protected $_cart;
    /**
     * @var DataObject
     */
    protected $_request;
    /**
     * @var qbo\PayPalPlusMx\Model\Http\PaymentRequest
     */
    protected $_paymentRequest;
    /**
     * @var string 
     */
    protected $_iframeUrl = false;
    /**
     * @var string 
     */
    protected $_executeUrl = false;
    /**
     * @var string 
     */
    protected $_paymentId = false;
    /**
     *
     * @var type 
     */
    protected $_profileId = false;
    /**
     * HTTP status codes for which a retry must be attempted
     * retry is currently attempted for Request timeout, Bad Gateway,
     * Service Unavailable and Gateway timeout errors.
     */
    private static $retryCodes = array('408', '502', '503', '504', '500');
    /**
     * LoggingManager
     *
     * @var PayPalLoggingManager
     */
    protected $logger;
    /**
     *
     * @var \Magento\Checkout\Model\Session 
     */
    protected $_checkoutSession;
    /**
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface 
     */
    protected $scopeConfig;
    /**
     *
     * @var qbo\PayPalPlusMx\Helper\Profile 
     */
    protected $_profileHelper;
    /**
     *
     * @var int 
     */
    protected $_debugMode = false;
    protected $_config = false;

    /**
     * Default Constructor
     * 
     * @param Config $httpConfig
     * @param Payment $paymentRequest
     * @param ZendClientFactory $httpClientFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param DataObject $request
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param Profile $profileHelper
     */
    public function __construct(
            Config $httpConfig,
            Payment $paymentRequest,
            ZendClientFactory $httpClientFactory,
            \Psr\Log\LoggerInterface $logger,
            DataObject $request,
            \Magento\Checkout\Model\Session $checkoutSession,
            \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
            Profile $profileHelper,
            PayPalConfig $config
    ){
 
        $this->httpConfig = $httpConfig;
        $this->httpConfig->setHeaders(
            array(
                'Accept' => self::ACCEPT_HEADERS
            )
        );

        $this->logger =   $logger;
        $this->httpClientFactory = $httpClientFactory;
        $this->_request = $request;
        $this->_paymentRequest = $paymentRequest;
        $this->_checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
        $this->_profileHelper = $profileHelper;
        $this->_debugMode = $this->getStoreConfig(self::XML_PATH_DEBUG_MODE);
        $this->_config = $config;
    }
    /**
     * Initialize payment
     * a) Request Acess Toekn
     * b) Create or patch payment to be executed later
     */
    public function initPayment()
    {
        try{
            if(!$this->getAccessToken() || $this->isTokenExpired()){
                $this->_requestAccessToken();
            }
            if($this->getPaymentId()){
                if(!$this->_patchPayment()){
                    $this->_createPayment();
                }
            }else{
                $this->_createPayment();
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return array(
                'success' => false, 
                'reason' => $e->getMessage()
            );
        }
        return array('success' => true);
    }
    public function getPaymentRequest()
    {
        return $this->_paymentRequest;
    }
    /**
     * Get Iframe URL from session or locally
     * 
     * @return string 
     */
    public function getIframeUrl() 
    {
        $iframeUrl = $this->_checkoutSession->getIframeUrl();
        
        if($iframeUrl) {
            return $iframeUrl;
        }
        return $this->_iframeUrl;
    }
    /**
     * Retrieve Access token to make payment requests
     * 
     * @return string
     */
    public function getAccessToken() 
    {
        $accessToken = $this->_checkoutSession->getAccessToken();
        
        if($accessToken) {
            return $accessToken;
        }
        return $this->_accessToken;
    }
    /**
     * Retrieve Payment ID
     * 
     * @return string
     */
    public function getPaymentId() 
    {
        $paymentId = $this->_checkoutSession->getPaymentId();
        
        if($paymentId) {
            return $paymentId;
        }
        return $this->_paymentId;
    }
    /**
     * Return execute URL to capture payment
     * 
     * @return string
     */
    public function getExecuteUrl() 
    {
        $executeUrl = $this->_checkoutSession->getExecuteUrl();
        
        if($executeUrl) {
            return $executeUrl;
        }
        return $this->_executeUrl;
    }
    /**
     * Get Experience Profile ID
     * 
     * @return string
     */
    public function getProfileId()
    {
        return $this->_profileId;
    }
    /**
     * Set Experience Profile ID
     * 
     * @param type $profileId
     */
    public function setProfileId($profileId)
    {
        $this->_profileId = $profileId;
    }
    /**
     * Generate and save profile experiecne ID
     * This is triggered when the config filed is empty on admin panel
     * 
     *  @return array
     */
    public function getProfileExperienceId()
    {        
        $this->_requestAccessToken();
        
        $merchantName = $this->getStoreConfig(self::XML_PATH_STORE_NAME);
        $profileName  = $this->_profileHelper->generateRandomProfileName();
        $profileData  = $this->_profileHelper->buildProfileRequest($profileName, $merchantName);
        
        $url = $this->httpConfig->getUrl(self::XP_URL_CODE);
        
        $result = $this->postRequest($url, json_encode($profileData));
        
        if(isset($result['name']) && $result['name'] == self::XP_VALIDATION_ERROR){
            return array(
                'success' => false,
                'error'   => $result['details'][0]['issue']
            );
        }
        return array(
            'success' => true,
            'id'      => $result['id']
        );
    }
    /**
     * Request a new Access Token
     * 
     */
    public function _requestAccessToken()
    {
        $url = $this->httpConfig->getUrl(self::TOKEN);  
        
        $response = $this->postRequest(
           $url, 
           $this->httpConfig->getBody(), 
           $this->httpConfig->getCredentialsConfig()
        );
       
        $this->_accessToken = $response[self::ACCESS_TOKEN];
        $this->_checkoutSession->setAccessToken($this->_accessToken);
        $this->_checkoutSession->setAccessTokenExpires(
            date(self::DATE_FORMAT, strtotime("+" . $response[self::EXPIRES_IN] . " seconds"))
        );
    }
    /**
     * Check if token expired
     * 
     * @return bool
     */
    public function isTokenExpired()
    {
        return strtotime(date(self::DATE_FORMAT)) > strtotime($this->_checkoutSession->getAccessTokenExpires()) ? true : false;
    }
    /**
     * Check if Payment is expired
     *  
     * @return bool
     */
    public function isPaymentExpired()
    {
        return strtotime(date(self::DATE_FORMAT)) > strtotime($this->_checkoutSession->getPaymentIdExpires()) ? true : false;
    }
    /**
     * Create a New Payment
     */
    public function _createPayment() 
    {
        $url  = $this->httpConfig->getUrl(self::PAYMENT);
        $data = $this->_paymentRequest->getPaymentObject($this->getProfileId());
        //$this->_debug($data);
        
        $this->paymentObject = $this->postRequest($url, json_encode($data));
        
        if(isset($this->paymentObject[self::LINKS]))
        {
            foreach($this->paymentObject[self::LINKS] as $data)
            {
                if($data['rel'] == self::APPROVAL_URL_CODE){
                    $this->_iframeUrl = $data[self::IFRAME_REL];
                    $this->_checkoutSession->setIframeUrl($this->_iframeUrl);
                }
                if($data['rel'] ==  self::EXECUTE){
                    $this->_executeUrl = $data[self::IFRAME_REL];
                    $this->_checkoutSession->setExecuteUrl($this->_executeUrl);
                }
            }
            $this->_paymentId = $this->paymentObject['id'];
            $this->_checkoutSession->setPaymentId($this->_paymentId);
        } else{
            //throw new \Exception(__('This payment method is currently unavailable.'));
        }       
    }
    /**
     * Look Up an existing payment
     */
    public function _lookUpPayment()
    {
        $url = $this->httpConfig->getUrl(self::LOOKUP, $this->getPaymentId());
        $this->paymentObject = $this->postRequest(
            $url, 
            false, 
            false, 
            false, 
            \Zend_Http_Client::GET
        ); 
        $this->_checkoutSession->setPaymentIdExpires($this->paymentObject[self::VALID_UNTIL]);
    }
    /**
     * Update a payment if quote changed
     * 
     * PATCH is executed via curl because there seems
     * to be a bug with the Zend CLient with PATCH requests.
     * (Getting "Unable to get response, or response is empty")
     * 
     * @return bool
     */
    public function _patchPayment()
    {
        $url  = $this->httpConfig->getUrl(self::PATCH, $this->getPaymentId());
        $data = $this->_paymentRequest->getPatchPaymentObject();
        $this->_debug($data);
        $encodedData = str_replace('\\/', '/', json_encode($data));
        
        $this->paymentObject =  $this->_patchRequest(
            $encodedData, 
            $url
        ); 
        
        if($this->paymentObject->getState() == self::CREATED_PAYMENT){
            return true;
        }else if($this->paymentObject->getState() == self::APPROVED_PAYMENT ||
                 $this->paymentObject->getState() == self::PAYMENT_ALREADY_DONE){
            /**
             * If Payment is "approved" already, it means that payment has been captured, 
             * but something went wrong while saving the order.
             * 
             * TODO: notify store owner about incident
             */
            throw new \Exception(__(self::DUPLICATED_PAYMENT));
        }
        return false;
    }
   /**
    * Execute payment (capture payment)
    * 
    * @param type $data
    * @param type $executeUrl
    * @param type $accessToken
    * @return type
    */
    public function _executePayment($data, $executeUrl, $accessToken = false)
    {
        if($accessToken){
            $this->_accessToken = $accessToken;
        }
        return $this->postRequest($executeUrl, json_encode($data));
    }
    /**
     * Gets all Http Headers
     *
     * @return array
     */
    public function getHttpHeaders()
    {
        if($this->getAccessToken()){
            $this->httpConfig->setHeaders(
                    array(
                        'Authorization' => "Bearer {$this->getAccessToken()}",
                        'Content-Type' => self::ACCEPT_HEADERS,
                        //Add Build Notation Code header to identify transactions from PPPlus
                        'PayPal-Partner-Attribution-Id' => $this->_config->getBuildNotationCode() //Do not alter or change this !
                    )
                );
        }
        return $this->httpConfig->getHeaders();
    }
    /**
     * Unset payment data from session after order is placed.
     */
    public function clearPaymentData()
    {
        $this->_checkoutSession->setPaymentId(false);
        $this->_checkoutSession->setIframeUrl(false);
        $this->_checkoutSession->setExecuteUrl(false);
        $this->_checkoutSession->setPaymentIdExpires(false);
    }
    /**
     * Send Request To Paypal REST API
     * a) Get Token
     * b) Create Payment
     * c) Execute Payment
     * d) Patch Payment
     * 
     * @param type $url
     * @param type $data
     * @param type $credentials
     * @return DataObject
     * @throws \Exception
     */
    public function postRequest(
            $url, 
            $data = false,
            $credentials = false,
            $accessToken = false, 
            $httpVerb = \Zend_Http_Client::POST
    )
    {        
        if($accessToken){
            $this->_accessToken = $accessToken;
        }

        /** @var ZendClient $client */
        $client = $this->httpClientFactory->create();

        // If requesting access token
        if($credentials) {
            $client->setAuth($credentials['user'], $credentials['password']);
            $client->setParameterPost($data);
        }else{
            $client->setRawData($data, self::ACCEPT_HEADERS);
        }
        $client->setConfig(array(
            'timeout' => 3000, 
            'keepalive'=> true
            )
        );
        $client->setUri($url);
        $client->setMethod($httpVerb);
        $client->setHeaders($this->getHttpHeaders());
        $client->setUrlEncodeBody(false);
        
        /** @var Magento\Framework\DataObject */
        $result = new DataObject();
        $this->_sendPost($client, $result);
        
        //Retry request in case of server errors.
        $retries = 0;
        if (in_array($result->getStatus(), self::$retryCodes) && $this->httpConfig->getHttpRetryCount() != null) {
            do {
                $this->_debug("Got {$result->getStatus()} response from server. Retrying.....");
                $result = $this->_sendPost($client, $result);
            } while (in_array($result->getStatus(), self::$retryCodes) && (++$retries < $this->httpConfig->getHttpRetryCount()));
        }
        
        return $result;
    }
    /**
     * Get response
     * 
     * @param type $client
     * @param type $result
     */
    protected function _sendPost(&$client, &$result)
    {
        try{
            $this->_debug($client);
            $response = $client->request();
            $responseBody = json_decode($response->getBody(), true);
            
            $result->setData($responseBody);
            $result->setHttpStatus($response->getStatus());
            $result->setMessage($response->getMessage());
            $this->_debug($result);

            if(isset($responseBody['state'])){
                $result->setState($responseBody['state']);
            }
            if(isset($responseBody['transactions'][0]['related_resources'][0]['sale'])){
                $result->setSaleState($responseBody['transactions'][0]['related_resources'][0]['sale']['state']);
                $result->setSaleId($responseBody['transactions'][0]['related_resources'][0]['sale']['id']);
            }
        } catch (\Exception $ex) {
            $result->setHttpStatus(500)
                   ->setStatus(500) 
                   ->setResponseCode(-1) 
                   ->setResponseReasonCode($ex->getCode())
                   ->setMessage($ex->getMessage());
            $debugData['result'] = $result->getData();
            $this->_debug($debugData);
        }
        return $result;
    }
    /**
     * Executes an HTTP PATCH request to update the payment everytime the user
     * changes something on quote (coupone, cart rule, address, etc) or checkout page is reloaded.
     *
     * @param string $data query string OR POST content as a string
     * @return mixed
     * @throws PayPalConnectionException
     */
    public function _patchRequest($data, $url)
    {
        $result = new DataObject();

        $responseBody = $this->_execCurl($url, $data);
        //Retry request in case of server errors.
        $retries = 0;
        if(isset($responseBody['error_code'])){
            if (in_array($responseBody['error_code'], self::$retryCodes) && $this->httpConfig->getHttpRetryCount() != null) {
                do {
                    $this->_debug("Got {$responseBody['error_code']} response from server. Retrying.....");
                    $responseBody =  $this->_execCurl($url, $data);
                } while (in_array($responseBody['error_code'], self::$retryCodes) && (++$retries < $this->httpConfig->getHttpRetryCount()));
            }
        }
        $this->_debug($responseBody);
        $result->setData($responseBody);
        //$result->setHttpStatus($httpStatus);

        if(isset($responseBody['state'])){
            $result->setState($responseBody['state']);
        }
        if(isset($responseBody['transactions'][0]['related_resources'][0]['sale'])){
            $result->setSaleState($responseBody['transactions'][0]['related_resources'][0]['sale']['state']);
            $result->setSaleId($responseBody['transactions'][0]['related_resources'][0]['sale']['id']);
        }
        //Retrieve Response Status
        $this->_debug($result);
        return $result;
    }
    /**
     * Get Raw HTTP headers for patch request
     */
    protected function _getRawHeaders()
    {
        $headers = array();
        foreach ($this->getHttpHeaders() as $k => $v) {
            $headers[] =  $k  . ":" . $v;
        }
        return $headers;
    }
    /**
     * Init curl for PATCH requests
     * 
     * @return string
     */
    protected function _execCurl($url, $data)
    {
        $responseBody = array();
        $httpStatus = false;
        try{   
            $ch = curl_init($url);
            $headers = $this->_getRawHeaders();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, \Zend_Http_Client::PATCH);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            //Execute Curl Request
            $response = curl_exec($ch);

            $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $this->_debug("Http PATCH status: " . $httpStatus);
            $responseHeaderSize = strlen($response) - curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);

            $result = substr($response, $responseHeaderSize);
            $responseBody = json_decode($result, true);
            //Close the cu$responseBody['state']rl request
            curl_close($ch);

        } catch (\Exception $ex) {
            $this->logger->critical($ex->getMessage());
            $responseBody['state'] = isset($responseBody['name']) ? $responseBody['name'] : null;
            $responseBody['error_code'] =  $ex->getCode();
        }
        return $responseBody;
    }
    /**
     * Debug - log data
     * 
     * @param string $data
     */
    protected function _debug($data)
    {
        if($this->_debugMode) {
            $this->logger->log(self::LOG_LEVEL, print_r($data, true));
        }
    }
    /**
     * Get payment store config
     * 
     * @return string
     */
    public function getStoreConfig($configPath)
    {
        $value =  $this->scopeConfig->getValue(
                $configPath,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ); 
        return $value;
    }
}
