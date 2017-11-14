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

// @codingStandardsIgnoreFile

namespace qbo\PayPalPlusMx\Model;
use Magento\Framework\App\ProductMetadataInterface;


/**
 * Config model that is aware of all \Magento\Paypal payment methods
 * Works with PayPal-specific system configuration
 * 
 * Added PayPal Plus MX IPN Support
 */
class Config extends \Magento\Paypal\Model\Config
{
    /**
     * @var string 
     */
    private static $bnCodeMx = 'PPP_SI_Custom_%s';
    /**
     * @var string 
     */
    protected $_metaDataInterface;
    /**
     * @var string 
     */
    const METHOD_PAYPALPLUS = 'qbo_paypalplusmx';
    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Payment\Model\Source\CctypeFactory $cctypeFactory
     * @param CertFactory $certFactory
     * @param array $params
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Payment\Model\Source\CctypeFactory $cctypeFactory,
        \Magento\Paypal\Model\CertFactory $certFactory,
        ProductMetadataInterface $metadataInterface,
        $params = []
    ) {
        $this->_metaDataInterface = $metadataInterface;
        parent::__construct($scopeConfig, $directoryHelper, $storeManager, $cctypeFactory, $certFactory, $params);
    }
    /**
     * Return list of allowed methods for specified country iso code
     *
     * @param string|null $countryCode 2-letters iso code
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getCountryMethods($countryCode = null)
    {
        $countryMethods = [
            'other' => [
                self::METHOD_WPP_EXPRESS,
                self::METHOD_BILLING_AGREEMENT,
                /** @modification */
                self::METHOD_PAYPALPLUS
                /** @end mofidication */
            ],
            'US' => [
                self::METHOD_PAYFLOWADVANCED,
                self::METHOD_PAYFLOWPRO,
                self::METHOD_PAYFLOWLINK,
                self::METHOD_WPP_EXPRESS,
                self::METHOD_WPP_BML,
                self::METHOD_BILLING_AGREEMENT,
                self::METHOD_WPP_PE_EXPRESS,
                self::METHOD_WPP_PE_BML,
                /** @modification */
                self::METHOD_PAYPALPLUS
            ],
            'ES' => [
                self::METHOD_HOSTEDPRO,
                self::METHOD_WPP_EXPRESS,
                self::METHOD_BILLING_AGREEMENT, 
                /** @modification */
                self::METHOD_PAYPALPLUS
            ]
        ];
        if ($countryCode === null) {
            return parent::getCountryMethods($countryCode);
        }
        if(isset($countryMethods[$countryCode])){
            return $countryMethods[$countryCode];
        }else{
            return $countryMethods['other'];
        }
        return parent::getCountryMethods($countryCode);    
    }
    
   /**
     * Map any supported payment method into a config path by specified field name
     *
     * @param string $fieldName
     * @return string|null
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _getSpecificConfigPath($fieldName)
    {
        $path = null;
        switch ($this->_methodCode) {
            /**
             * @modification
             */
            case self::METHOD_PAYPALPLUS: 
                $path = $this->_mapPayPalPlusFieldset($fieldName);
                break;
            /** @end mofidication */
        }
        if ($path === null) {
            return parent::_getSpecificConfigPath($fieldName);
        }
        return $path;
    }
    /**
     * Map PayPal Plus  config fields (MX Edition)
     *
     * @param string $fieldName
     * @return string|null
     */
    protected function _mapPayPalPlusFieldset($fieldName)
    {
        switch ($fieldName) {
            case 'sandbox_flag':
            case 'business_account':
                return "payment/" . self::METHOD_PAYPALPLUS . "/{$fieldName}";
            default:
                return null;
        }
    }
    /**
     * BN code getter
     *
     * @return string
     */
    public function getBuildNotationCode()
    {
        return sprintf(self::$bnCodeMx, $this->_metaDataInterface->getEdition());
    }
}
