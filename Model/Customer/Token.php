<?php
/**
 * Copyright © 2016 qbo. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace qbo\PayPalPlusMx\Model\Customer;

use Magento\Framework\Exception\LocalizedException;

/**
 * Customer Card Token attribute backend
 */
class Token extends \Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend
{
    /**
     * @var \Magento\Framework\Encryption\Encryptor 
     */
    protected $_encryptor;

    /**
     * Constructor method
     * 
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     */
    public function __construct(\Magento\Framework\Encryption\Encryptor $encryptor) 
    {
        $this->_encryptor = $encryptor;
    }
    /**
     * Encrypt Credit Card Token before save
     *
     * @param \Magento\Framework\DataObject $object
     * @return void
     */
    public function beforeSave($object)
    {
        if(!is_null($object->getCardTokenId())) {
            $encryptedToken = $this->_encryptor->encrypt($object->getCardTokenId());
            $object->setCardTokenId($encryptedToken);
        }
    }

    /**
     * @param \Magento\Framework\DataObject $object
     * @return void
     */
    public function afterLoad($object)
    {
        if(!is_null($object->getCardTokenId())) {
            $decryptedToken = $this->_encryptor->decrypt($object->getCardTokenId());
            $object->setCardTokenId($decryptedToken);
        }
    }
}
