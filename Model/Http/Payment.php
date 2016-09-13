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

use Magento\Framework\DataObject;
use \Magento\Sales\Model\Order;
use Magento\Quote\Api\ShippingMethodManagementInterface as ShippingMethodManager;
/**
 * PaymentRequest Model sent to PayPal API
 *
 */
class Payment {
    /**
     *
     * @var DataObject 
     */
    protected $_data;
    /**
     *
     * @var \Magento\Checkout\Model\Quote
     */
    protected $_quote;
    /**
     *
     * @var \Magento\Checkout\Model\Cart 
     */
    protected $_cart;

    /**
     * Request's order model
     *
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;
    /**
     * Customer address
     *
     * @var \Magento\Customer\Helper\Address
     */
    protected $_addressHelper   = null;
    /**
     *
     * @var type 
     */
    protected $_customerAddress = null;

    /**
     * Locale Resolver
     *
     * @var \Magento\Framework\Locale\Resolver
     */
    protected $localeResolver;
    /**
     *
     * @var type 
     */
    protected $_totals;
    /**
     *
     * @var type 
     */
    protected $_storeManager;
    /**
     *
     * @var Magento\Quote\Api\ShippingMethodManagementInterface 
     */
    protected $_shippingMethodManager;
    
    protected $_logger;
    
    protected $_cartFactory;
    /**
     *
     * @var string
     */
    public static $_cancelUrl;
    public static $_returnUrl;
    public static $_notifyUrl;
    /**
     * @var string
     */
    const PAYMENT_METHOD = 'paypal';
    
    const ALLOWED_PAYMENT_METHOD = 'IMMEDIATE_PAY';
    
    const DISCOUNT_ITEM_NAME = 'Discount Item';

    /**
     * @param DataObject $data
     * @param \Magento\Framework\Locale\Resolver $localeResolver
     * @param \Magento\Customer\Helper\Address $customerAddress
     * @param \Magento\Checkout\Model\Cart $cart
     */
    public function __construct(
            DataObject $data,
            \Magento\Framework\Locale\Resolver $localeResolver,
            \Magento\Customer\Helper\Address $customerAddress,
            \Magento\Framework\DataObject $address,
            \Magento\Customer\Helper\Address $addressHelper,
            \Magento\Checkout\Model\Cart $cart,
            ShippingMethodManager $shippingMethodManager,
            \Magento\Store\Model\StoreManagerInterface $storeManager,
            \Magento\Payment\Model\Cart\SalesModel\Factory $cartFactory,
            \Psr\Log\LoggerInterface $logger
    ){
        $this->_data = $data;
        $this->_adressData = $address;
        $this->_cart = $cart;
        $this->_quote = $cart->getQuote();
        $this->_cartFactory = $cartFactory;
        $this->_cartPayment = $this->_cartFactory->create($this->_quote);
        $this->_customerAddress = $cart->getQuote()->getShippingAddress();
        $this->_addressHelper = $addressHelper;
        $this->localeResolver = $localeResolver;
        $this->shippingMethodManager = $shippingMethodManager;
        $this->_storeManager = $storeManager;
        $this->_logger = $logger;
        
        self::$_cancelUrl = $this->_storeManager->getStore()->getUrl('checkout/cart');
        self::$_returnUrl = $this->_storeManager->getStore()->getUrl('checkout/cart');
        self::$_notifyUrl = $this->_storeManager->getStore()->getUrl('paypal/ipn');
        
    }
    /**
     * Build and get cart variables to be sent to PayPal.
     * 
     * @return array $data
     */
    public function getPaymentObject($profileId)
    {   
        $this->_quote->collectTotals();
        $this->_totals = $this->_quote->getTotals();
        
        $result = array(
            'intent' => 'sale',
            'experience_profile_id' => $profileId,
            'payer' => 
                array('payment_method' => self::PAYMENT_METHOD),
            'transactions' => 
                array (
                   0 => 
                    array(
                        'amount' => $this->_getTransactionAmounts($this->_quote),
                        'payment_options' => 
                        array(
                          'allowed_payment_method' => self::ALLOWED_PAYMENT_METHOD,
                        ),
                        'item_list' => $this->getItemList(),
                        'notify_url' => self::$_notifyUrl
                    ),
                ),           
            'redirect_urls' => 
                array(
                  'return_url' => self::$_returnUrl,
                  'cancel_url' => self::$_cancelUrl,
                )
        );
        return $result; 
    }
    /**
     * Build and get cart variables to be sent to PayPal
     * 
     * @return array $data
     */
    public function getPatchPaymentObject()
    {   
        $this->_quote->collectTotals();
        $this->_totals = $this->_quote->getTotals();
        
        $result[] = array(
            'op' => 'replace',
            'path' => '/transactions/0/amount',
            'value' => $this->_getTransactionAmounts($this->_quote) 
        );
        $result[] = array(
            'op' => 'replace',
            'path' => '/transactions/0/item_list/items',
            'value' => $this->_getLineItems($this->_quote) 
        );
        $result[] = array(
            'op' => 'replace',
            'path' => '/transactions/0/item_list/shipping_address',
            'value' => $this->_getShippingAddress($this->_quote)
        );
        return $result; 
    }
    /**
     * Get Item list array
     * 
     * @return array
     */
    public function getItemList()
    {
        $includeAddress = false;
        $result = array(
            'items' => $this->_getLineItems($this->_quote),
        );
        /**
         * If any virtual item is present, do not send shipping address on payment request, 
         * because Magento checkout will not display shipping step. 
         * If no shipping step, no address will be available. Therefore we cant include shipping address.
         */
        foreach($this->_quote->getAllVisibleItems() as $_item){
            if(!$_item->getProduct()->isVirtual()){
                $includeAddress = true;
            }
        }
        //If a default customer address exists, inclide address.
        if($this->_customerAddress){
            $includeAddress = true;
        }
        if($includeAddress){
            $result['shipping_address'] = $this->_getShippingAddress($this->_quote);  
        }
        return $result;
    }
    /**
     * Get payment amount data with excluded tax
     * 
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function _getTransactionAmounts()
    {
        //If Subtotal + Shipping + Tax not equals Grand Total, a disscount might be applying, get Subtotal with disscount then. 
        //$baseSubtotal = $this->_quote->getBaseSubtotalWithDiscount() ? : $this->_quote->getBaseSubtotal();
        $baseSubtotal =  $this->_cartPayment->getBaseSubtotal() + $this->_cartPayment->getBaseDiscountAmount();
        //$shippingMethod = $this->shippingMethodManager->get($this->_quote->getId());
        //$shippingAmmount = $shippingMethod->getAmount();
         
        return [
            'currency' => $this->_quote->getBaseCurrencyCode(),
            'total' => $this->_formatPrice( $this->_quote->getGrandTotal()),
            'details' => array(
                'shipping' => $this->_formatPrice($this->_cartPayment->getBaseShippingAmount()),
                'subtotal' => $this->_formatPrice($baseSubtotal),
                'tax'      => $this->_formatPrice($this->_cartPayment->getBaseTaxAmount())
            )
        ];
    }
    /**
     * Get Line items
     * 
     * @return int
     */
    protected function _getLineItems()
    {
        foreach($this->_quote->getAllVisibleItems() as $_item){
            $data[] = [
                'name' => $_item->getName(),
                'description' => $_item->getDescription(),
                'quantity' => $_item->getQty(),
                'price' => $this->_formatPrice($_item->getPrice()),
                'sku' => $_item->getSku(),
                'currency' => $this->_quote->getBaseCurrencyCode()
            ];
        }
        //If a cart discount is applied, incude it as a separate item (otherwise items amounts wil never match subtotal amount)
        $discount = $this->_cartPayment->getBaseDiscountAmount();
        
        if($this->_cartPayment->getBaseDiscountAmount() && $this->_cartPayment->getBaseDiscountAmount() != 0) {
             $data[] = [
                'name' =>  __(self::DISCOUNT_ITEM_NAME),
                'quantity' => 1,
                'price' => $this->_formatPrice($discount),
                'currency' => $this->_quote->getBaseCurrencyCode()
            ];
        }
        return $data;
    }
    /**
     * Get shipping address request data
     *
     * @param \Magento\Framework\DataObject $address
     * @return array
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _getShippingAddress()
    {
        $region = $this->_customerAddress->getRegionCode() ?  $this->_customerAddress->getRegionCode() :  $this->_customerAddress->getRegion();
        $address = $this->_prepareAddressLines();

        $request = array(
            'recipient_name' => $this->_customerAddress->getFirstname() . " " .$this->_customerAddress->getLastname(),
            'city' => $this->_customerAddress->getCity(),
            'state' => $region ? : 'n/a',
            'postal_code' => $this->_customerAddress->getPostcode(),
            'country_code' => $this->_customerAddress->getCountryId(),
            'line1' => $address['line1'],
            'line2' => $address['line2'],
            'phone' => $this->_customerAddress->getTelephone(),
        );
        
        return $request;
    }
    /**
     * Convert streets to tow lines format
     * 
     * @return array $address
     */
    protected function _prepareAddressLines()
    {
        $street = $this->_addressHelper->convertStreetLines($this->_customerAddress->getStreet(), 2);
        $address['line1'] = isset($street[0]) ? $street[0] : '';
        $address['line2'] = isset($street[1]) ? $street[1] : '';
        
        return $address;
    }
    /**
     * Format price string
     *
     * @param mixed $string
     * @return string
     */
    protected function _formatPrice($string)
    {
        return sprintf('%.2F', $string);
    }

}