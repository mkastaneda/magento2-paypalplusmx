<?php
/**
 * Payment CC Types Source Model
 *
 * @category    qbo
 * @package     qbo_PayPalPlusMx
 * @author José Catsañeda <jose@qbo.tech>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace qbo\PayPalPlusMx\Model\Source;

class Months implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function  toOptionArray()
    {
        return [
            ['value' => 1, 'label' => 1],
            ['value' => 3, 'label' => 3],
            ['value' => 6, 'label' => 6],
            ['value' => 9, 'label' => 9],
            ['value' => 12, 'label' => 12],
        ];
    }
}
