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

namespace qbo\PayPalPlusMx\Controller\Payment;

use qbo\PayPalPlusMx\Model\Http\Api;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as PaymentHelper;

class Index extends \Magento\Framework\App\Action\Action
{

    const IFRAME_CODE_NAME            = 'paypalPlusIframe';
    const XML_PATH_EXPERIENCE_ID      = 'payment/qbo_paypalplusmx/profile_experience_id';
    const SESSION_INSTANCE            = 'Magento\Customer\Model\Session';
    const CUSTOMER_INSTANCE           = 'Magento\Customer\Model\Customer';
    const PAYMENT_NO_READY_CODE       = 'payment_not_ready';

    /** 
     * @var string[]
     */
    protected $code = 'qbo_paypalplusmx';

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    protected $methods = [];

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;
    /**
     * @var Connection
     */
    protected $_quote;
    protected $_logger;
    protected $_objectManager;
    protected $_localeResolver;
    protected $_resultJsonFactory;
    protected $_encryptor;

    /**
     *
     * @var \Magento\Checkout\Model\Cart 
     */
    protected $_api;
    /**
     * Constructor method
     * 
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param Api $api
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context, 
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Api $api, 
        \Magento\Checkout\Model\Cart $cart,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->_api = $api;
        $this->_quote = $cart->getQuote();
        $this->_logger = $logger;
        $this->_objectManager = $context->getObjectManager();
        $this->_localeResolver = $localeResolver;
        $this->_resultJsonFactory = $resultJsonFactory;  
        
        parent::__construct($context);
    }
    /**
     * This is the ajax call that gets called when user arrives to the checkout
     * 
     * Get and validate Payment data and send it to PayPal API.
     * If we get no errors, return data to window.checkoutconfig so 
     * it can be accessed by the method-renderer.js
     * 
     * {@inheritdoc}
     */
    public function execute()
    {
        $resultJson = $this->_resultJsonFactory->create();
        $config = array();
        if ($this->getRequest()->isXmlHttpRequest()) { 
            /**
             * Validate that shipping address exists.
             * If the address is invalid (or does not exist yet) but all products in quote are virtual, proceed.
             * Let the user specify the address on the payment method form
             */
            if(!is_array($this->_quote->getShippingAddress()->validate() || $this->getIsVirtualOrder()))
            {
                $this->_api->setProfileId($this->getStoreConfig(self::XML_PATH_EXPERIENCE_ID));
                $payment = $this->_api->initPayment();
                
                if(!$payment['success']){
                    return $resultJson->setHttpResponseCode(400)
                            ->setData(array(
                                'error' => true, 
                                'reason' => $payment['reason'])
                            ); 
                }
                if(!$this->_api->getIframeUrl()){
                    $config['isQuoteReady'] = false;
                    $config['reason'] = self::PAYMENT_NO_READY_CODE;
                } else{
                    $config['shippingData']  = $this->_api->getPaymentRequest()->getShippingAddress();                        
                    $config['billingData']   = $this->_api->getPaymentRequest()->getBillingAddress();;                        
                    $config['isQuoteReady'] = true;
                    $config['actionUrl']    = $this->_api->getIframeUrl();
                    $config['executeUrl']   = $this->_api->getExecuteUrl();
                    $config['accessToken']  = $this->_api->getAccessToken();
                    $config['paymentId']    = $this->_api->getPaymentId();
                    $config['card_token']   = $this->getLoggedInCustomerToken() ? : null;
                    $config['error']        = false;
                }
            }else{
                $config['isQuoteReady'] = false;
                $config['error'] = false;
            }
            $response = json_encode($config);
            return $resultJson->setData($response);
        }
        
        return $resultJson->setHttpResponseCode(400);
        
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
    /**
     * Get Customer Card Token
     * 
     * @return string/bool
     */
    public function getLoggedInCustomerToken()
    {
        $customerSession = $this->_objectManager->create(self::SESSION_INSTANCE);

        if ($customerSession->isLoggedIn()) {
            $customer = $this->_objectManager->create(self::CUSTOMER_INSTANCE);
            $customerId = $customerSession->getCustomerId(); 
            $customer->load($customerId);
            
           // $this->_logger->log(100, $customer->getCardTokenId());
            return $customer->getCardTokenId();
        } 
        return false;
    }
    /**
     * If order contains one non-virtual product, is not a virtual order.
     * Magento wont show the shipping address if there are only virtual products in cart
     * 
     * @return boolean
     */
    public function getIsVirtualOrder()
    {
        $isVirtual = true;
        
        foreach($this->_quote->getAllVisibleItems() as $_item) {
            if(!$_item->getProduct()->isVirtual()){
                $isVirtual = false;
                break 1;
            }
        }
        return $isVirtual;
    }
}
