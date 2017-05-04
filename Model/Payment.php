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
 */

namespace qbo\PayPalPlusMx\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use qbo\PayPalPlusMx\Model\Http\Api;
use qbo\PayPalPlusMx\Model\Http\Payment as PaymentObject;

class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE                              = 'qbo_paypalplusmx';
    const PAYMENT_REVIEW_STATE              = 'pending';
    const PENDING_PAYMENT_NOTIFICATION      = 'This order is on hold due to a pending payment. The order will be processed after the payment is approved at the payment gateway.';
    const DECLINE_ERROR_MESSAGE             = 'Declining Pending Payment Transaction as configured in PPPlus module.';
    const GATEWAY_ERROR_MESSAGE             = 'Payement has been declined by Payment Gateway';
    const DENIED_ERROR_MESSAGE              = 'Gateway response error';
    const COMPLETED_SALE_CODE               = 'completed';
    const DENIED_SALE_CODE                  = 'denied';
    const REFUNDED_SALE_CODE                = 'refunded';
    const FAILED_STATE_CODE                 = 'failed';
    const XML_PATH_EMAIL_PENDING_PAYMENT    = 'payment/qbo_paypalplusmx/pending_payment_email_template';
    const XML_PATH_EMAIL_SUPPORT_EMAIL      = 'trans_email/ident_sales/email';
    const XML_PATH_EMAIL_SUPPORT_PHONE      = 'general/store_information/phone';
    const XML_PATH_STORE_NAME               = 'general/store_information/name';
    
    protected $_code = self::CODE;
    protected $_infoBlockType               = 'qbo\PayPalPlusMx\Block\Payment\Info';
    protected $_api;
    protected $_paymentObject;
    protected $_response;
    protected $_scopeConfig;
    protected $_storeManager;
    protected $_objectManager               = null;
    protected $_isGateway                   = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;
    protected $_canHandlePendingStatus      = true;
    protected $_payment                     = false;
    protected $_order                       = false;
    protected $_emailTransport              = false;
    protected $_countryFactory;
    protected $_supportedCurrencyCodes      = array('USD', 'MXN');
    protected $_supportedCountryCodes       = array();
    protected $_logger;
    protected $_debugReplacePrivateDataKeys = ['number', 'exp_month', 'exp_year', 'cvc'];
    protected $_successCodes                = ['200', '201'];
    protected $_badSaleCodes                = ['partially_refunded', 'pending', 'refunded', 'denied'];

    /**
     * Constructor method
     * 
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param Api $api
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Payment\Model\Method\Logger $paymentLogger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        PaymentObject $paymentObject,
        Api $api
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $paymentLogger
        );

        $this->_api = $api;
        $this->_paymentObject = $paymentObject;
        $this->_emailTransport = $transportBuilder;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_logger = $context->getLogger();

    }
    /**
     * Assign corresponding data
     *
     * @param \Magento\Framework\DataObject|mixed $data
     * @return $this
     * @throws LocalizedException
     */
    public function assignData(\Magento\Framework\DataObject $data) 
    {
        parent::assignData($data);
        
        $authData     = $data->getData('additional_data') ? : $data->getData();
        $infoInstance = $this->getInfoInstance();
        $infoInstance->setAdditionalInformation('payer_id',
            isset($authData['payer_id'])     ? $authData['payer_id']: ''
        );
        $infoInstance->setAdditionalInformation('payment_id',
            isset($authData['payment_id'])   ? $authData['payment_id'] : ''
        );
        $infoInstance->setAdditionalInformation('execute_url',
            isset($authData['execute_url']) ? $authData['execute_url'] : ''
        );
        $infoInstance->setAdditionalInformation('access_token',
            isset($authData['access_token']) ? $authData['access_token'] : ''
        );
        $infoInstance->setAdditionalInformation('terms',
            isset($authData['terms']) ? $authData['terms'] : ''
        );
        $infoInstance->setAdditionalInformation('handle_pending_payment', isset($authData['handle_pending_payment'])? $authData['handle_pending_payment'] : 0);
        
        return $this;
    }
    /*
     * Payment capturing
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Validator\Exception
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->_canHandlePendingStatus = (bool)$payment->getAdditionalInformation('handle_pending_payment');
        $accessToken = $payment->getAdditionalInformation('access_token');
        $executeUrl  = $payment->getAdditionalInformation('execute_url');
        $payerId     = $payment->getAdditionalInformation('payer_id');

        /** @var \Magento\Sales\Model\Order $order */
        $this->_order = $payment->getOrder();
        $data = $this->_getPaymentData($payerId);
        /**
         *  Call PayPal API to Execute Payment
         *  @var qbo\PayPalPlusMx\Model\Http\Api 
         */
        $this->_response = $this->_api->_executePayment($data, $executeUrl, $accessToken);
        
        try {
            $this->_processTransaction($payment);
        } 
        catch (\Exception $e) {
            $this->debugData(['request' => $data, 'exception' => $e->getMessage()]);
            $this->_logger->error(__('Payment capturing error.'));
            //throw new CouldNotSaveException(__($e->getMessage()), $e);
            throw new \Magento\Framework\Exception\LocalizedException(__(self::GATEWAY_ERROR_MESSAGE));
        }

        return $this;
    }
    /**
     * Get payment data array to be sent over api
     * 
     * @return type
     */
    protected function _getPaymentData($payerId)
    {
        return array(
            'payer_id' => $payerId,
            'transactions' => array(
                array(
                    'notify_url' => $this->_storeManager->getStore()->getUrl('paypal/ipn'),
                    'amount'     => $this->_paymentObject->_getTransactionAmounts()
                )
            )
        );
    }
    /**
     * Process Payment Transaction based on response data
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return \Magento\Payment\Model\InfoInterface $payment
     */
    protected function _processTransaction(&$payment)
    {
        if (!in_array($this->_response->getHttpStatus(), $this->_successCodes)) {
            throw new \Exception(__('Gateway error. Reason: %s', $this->_response->getMessage()));
        }
        $state = $this->_response->getState();
        $saleState = $this->_response->getSaleState();

        if ($state == self::FAILED_STATE_CODE){
            throw new \Exception(__(self::GATEWAY_ERROR_MESSAGE));
        }
        
        if (in_array($saleState, $this->_badSaleCodes)) {
            switch($saleState){
                case  self::PAYMENT_REVIEW_STATE: 
                    if(!$this->_canHandlePendingStatus) {
                        throw new \Exception(__(self::DECLINE_ERROR_MESSAGE));
                    }
                    $this->setComments($this->_order, __(self::PENDING_PAYMENT_NOTIFICATION), false);
                    $payment->setTransactionId($this->_response->getSaleId())
                            ->setIsTransactionPending(true)
                            ->setIsTransactionClosed(false);
                    
                    $this->_sendPendingPaymentEmail();
                    break;
                case self::DENIED_SALE_CODE :
                    throw new \Exception(__(self::DENIED_ERROR_MESSAGE));    
                default: 
                    $payment->setIsTransactionPending(true); 
                    break;
                }
                
        }else if($saleState == self::COMPLETED_SALE_CODE){
            $payment->setTransactionId($this->_response->getSaleId())
                    ->setIsTransactionClosed(true);
        }
        return $payment;
    }
    /**
     * Set order comments
     * 
     * @param type $order
     * @param type $comment
     * @param type $isCustomerNotified
     * @return type
     */
    public function setComments(&$order, $comment, $isCustomerNotified)
    {
        $history = $order->addStatusHistoryComment($comment, false);
        $history->setIsCustomerNotified($isCustomerNotified);
        
        return $order;
    }
    /**
     * Send Pending Payment Email
     */
    protected function _sendPendingPaymentEmail()
    {
        try {
            $templateId = $this->getTemplateId(self::XML_PATH_EMAIL_PENDING_PAYMENT);
            $templateParams = $this->getTemplateParams();
            $storeId = $this->getStore()->getStoreId(); 
            
            $senderInfo = array(
                'name' => $this->getStoreName(),
                'email' => $this->getStoreEmail(),
            );
            
            /** @var \Magento\Framework\Mail\Template\TransportBuilder */
            $transport = $this->_emailTransport
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions(['area' => 'frontend', 'store' => $storeId])
                ->setTemplateVars($templateParams)
                ->setFrom($senderInfo)
                ->addTo($this->_order->getCustomerEmail())
                ->setReplyTo($this->getStoreEmail())
                ->getTransport();
            
            $transport->sendMessage();
            
        } catch (\Exception $e) {
            $this->_logger->log(100, "Unable to send pending payment email: " . $e->getMessage());
        } catch(\Magento\Framework\Exception\MailException $ex){
            $this->_logger->log(100, "Unable to send pending payment email: " . $ex->getMessage());
        }
    }
    /**
     * Determine method availability based on quote amount and config data
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (!$this->getConfigValue('payment/qbo_paypalplusmx/client_id')) {
            return false;
        }
        if (!$this->getConfigValue('payment/qbo_paypalplusmx/client_secret')) {
            return false;
        }

        return parent::isAvailable($quote);
    }
    /**
     * Get payment store config
     * 
     * @return string
     */
    public function getTemplateParams()
    {
        return array(
            'order' => $this->_order,
            'store_email' => $this->getStoreEmail(),
            'store_phone' => $this->getStorePhone()
        );
    }
    /**
     * Get payment store config
     * 
     * @return string
     */
    public function getConfigValue($configPath)
    {
        $value =  $this->_scopeConfig->getValue(
            $configPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ); 
        return $value;
    }
    /**
     * Return store 
     *
     * @return Store
     */
    public function getStore()
    {
        return $this->_storeManager->getStore();
    }
    /**
     * Get Store email contact
     * 
     * @return type
     */
    public function getStoreEmail()
    {
        return $this->getConfigValue(self::XML_PATH_EMAIL_SUPPORT_EMAIL);
    }
    /**
     * Get Store email contact
     * 
     * @return type
     */
    public function getStoreName()
    {
        return $this->getConfigValue(self::XML_PATH_STORE_NAME);
    }
    /**
     * Get Store Email
     * 
     * @return type
     */
    public function getStorePhone()
    {
        return $this->getConfigValue(self::XML_PATH_EMAIL_SUPPORT_PHONE);
    }
    /**
     * Return template id according to store
     *
     * @return mixed
     */
    public function getTemplateId($xmlPath)
    {
        return $this->getConfigValue($xmlPath);
    }
    /**
     * Payment refund
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Validator\Exception
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $transactionId = $payment->getParentTransactionId();

        try {
           // qbo\PayPalPlusMx\Model\Charge::retrieve($transactionId)->refund();
        } catch (\Exception $e) {
            $this->debugData(['transaction_id' => $transactionId, 'exception' => $e->getMessage()]);
            $this->_logger->error(__('Payment refunding error.'));
            throw new \Magento\Framework\Validator\Exception(__('Payment refunding error.'));
        }

        $payment
            ->setTransactionId($transactionId . '-' . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND)
            ->setParentTransactionId($transactionId)
            ->setIsTransactionClosed(1)
            ->setShouldCloseParentTransaction(1);

        return $this;
    }
}
