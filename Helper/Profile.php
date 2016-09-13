<?php

namespace qbo\PayPalPlusMx\Helper;

/**
 * Profile Experience Helper
 *
 * @author kasta
 */
class Profile extends \Magento\Framework\App\Helper\AbstractHelper
{
    const RANDOMIZE_STRING       = '0123456789ABCDEFGHIJK';
    const PROFILE_PREFIX         = "Profile-";
   /**
     * Generate Randome Profile name
     * 
     * @return type
     */
    public function generateRandomProfileName()
    {
        $result = "";
        $charArray = str_split(self::RANDOMIZE_STRING);
        
        for($i = 0; $i < 8; $i++){
            $randItem = array_rand($charArray);
            $result .= "". $charArray[$randItem];
        }
        return self::PROFILE_PREFIX . $result;
    }
    /**
     * 
     * @param type $profileName
     * @param type $merchantName
     * @return type
     */
    public function buildProfileRequest($profileName, $merchantName)
    {
        return array(
            'name' => $profileName,
            'presentation' => array(
                'brand_name' => $merchantName,
            ),
            'input_fields' => array(    
                'no_shipping' => 1,
                'address_override' => 1
            )
        );
    }
}
