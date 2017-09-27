<?php

namespace qbo\PayPalPlusMx\Model\Config\Backend;

/**
 * Generate ExperienceProfile Backend Model
 *
 * @author kasta
 */
class ExperienceProfile extends \Magento\Framework\App\Config\Value  {

    /**
     * @var \qbo\PayPalPlusMx\Model\Http\Api 
     */
    protected $_api;

    /**
     * Constructor method
     * 
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param \qbo\PayPalPlusMx\Model\Http\Api $api
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \qbo\PayPalPlusMx\Model\Http\Api $api,
        array $data = []
    ) {
        $this->_api = $api;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Generate Experience profile ID if empty on admin panel
     *
     * @param string $value
     * @return string
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        
        if(!$value || !strlen($value)){
            $xpId = $this->_api->getProfileExperienceId();
            if($xpId['success']){
                $this->setValue($xpId['id']);
            }else{
                throw new \Exception($xpId['error']);
            }
        }
   }

}
