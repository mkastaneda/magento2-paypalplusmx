<?php


namespace qbo\PayPalPlusMx\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as PaymentHelper;

class IframeConfigProvider implements ConfigProviderInterface
{
    const XML_PATH_SAVE_CARDS_TOKEN    = 'payment/qbo_paypalplusmx/save_cards_token';
    const XML_PATH_SAVE_STATUS_PENDING = 'payment/qbo_paypalplusmx/status_pending';
    const XML_PATH_EXPERIENCE_ID       = 'payment/qbo_paypalplusmx/profile_experience_id';
    const XML_PATH_ALLOW_SPECIFIC      = 'payment/qbo_paypalplusmx/allowspecific';
    const XML_PATH_SPECIFIC_COUNTRY    = 'payment/qbo_paypalplusmx/specificcountry';
    const XML_PATH_MIN_ORDER_TOTAL     = 'payment/qbo_paypalplusmx/min_order_total';
    const XML_PATH_INSTALLMENTS        = 'payment/qbo_paypalplusmx/installments';
    const XML_PATH_INSTALLMENTS_MONTHS = 'payment/qbo_paypalplusmx/installments_months';
    const XML_PATH_IFRAME_HEIGHT       = 'payment/qbo_paypalplusmx/iframe_height';
    const XML_PATH_IFRAME_LANGUAGE     = 'general/locale/code';
    const XML_PATH_SANDBOX_MODE        = 'payment/qbo_paypalplusmx/sandbox_flag';
    const IFRAME_CONFIG_CODE_NAME      = 'paypalPlusIframe';
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
    /**
     *
     * @var \Magento\Checkout\Model\Cart 
     */
    protected $_httpConnection;

    /**
     * @param PaymentHelper $paymentHelper
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Cart $cart,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Locale\ResolverInterface $localeResolver
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->_quote = $cart->getQuote();
        $this->_logger = $logger;
        $this->_objectManager = $objectManager;
        $this->_localeResolver = $localeResolver;
    }
    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = [
            'payment' => [
                'paypalPlusIframe' => [],
            ],
        ];
        $config['payment'][self::IFRAME_CONFIG_CODE_NAME]['config']['save_cards_token']      = $this->getStoreConfig(self::XML_PATH_SAVE_CARDS_TOKEN);
        $config['payment'][self::IFRAME_CONFIG_CODE_NAME]['config']['allowspecific']         = $this->getStoreConfig(self::XML_PATH_ALLOW_SPECIFIC);
        $config['payment'][self::IFRAME_CONFIG_CODE_NAME]['config']['specificcountry']       = $this->getStoreConfig(self::XML_PATH_SPECIFIC_COUNTRY);
        $config['payment'][self::IFRAME_CONFIG_CODE_NAME]['config']['min_order_total']       = $this->getStoreConfig(self::XML_PATH_MIN_ORDER_TOTAL);
        $config['payment'][self::IFRAME_CONFIG_CODE_NAME]['config']['status_pending']        = $this->getStoreConfig(self::XML_PATH_SAVE_STATUS_PENDING);
        $config['payment'][self::IFRAME_CONFIG_CODE_NAME]['config']['profile_experience_id'] = $this->getStoreConfig(self::XML_PATH_EXPERIENCE_ID);
        $config['payment'][self::IFRAME_CONFIG_CODE_NAME]['config']['installments']          = $this->getStoreConfig(self::XML_PATH_INSTALLMENTS);
        $config['payment'][self::IFRAME_CONFIG_CODE_NAME]['config']['installments_months']   = $this->getStoreConfig(self::XML_PATH_INSTALLMENTS_MONTHS);
        $config['payment'][self::IFRAME_CONFIG_CODE_NAME]['config']['iframeHeight']          = $this->getStoreConfig(self::XML_PATH_IFRAME_HEIGHT);
        $config['payment'][self::IFRAME_CONFIG_CODE_NAME]['config']['iframeLanguage']        = $this->getStoreConfig(self::XML_PATH_IFRAME_LANGUAGE);
        $config['payment'][self::IFRAME_CONFIG_CODE_NAME]['config']['isSandbox']             = $this->getStoreConfig(self::XML_PATH_SANDBOX_MODE);
        
        return $config;
    }
    /**
     * Get payment store config
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
