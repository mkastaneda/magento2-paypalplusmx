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
use Magento\Sales\Model\Order;
use Magento\Customer\Model\Session;
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
     *
     * @var type 
     */
    protected $_customerBillingAddress = null;

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
    /**
     *
     * @var type 
     */
    protected $_logger;
    /**
     *
     * @var type 
     */
    protected $_cartFactory;
    /**
     *
     * @var type 
     */
    protected $_customer;
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
            \Magento\Framework\DataObject $dataObject,
            \Psr\Log\LoggerInterface $logger,
            Session $customerSession
    ){
        $this->_data = $data;
        $this->_adressData = $address;
        $this->_cart = $cart;
        $this->_quote = $cart->getQuote();
        $this->_cartFactory = $cartFactory;
        $this->_cartPayment = $this->_cartFactory->create($this->_quote);
        $this->_customer = $customerSession->getCustomer();
        $this->_logger = $logger;
        
	$this->_customerBillingAddress = $this->_customer->getDefaultBillingAddress() ? : $cart->getQuote()->getBillingAddress();
        $this->_customerAddress = $this->_customer->getDefaultShippingAddress() ? : $cart->getQuote()->getShippingAddress();
	
        if(empty($this->_customerBillingAddress)){
            $this->_customerBillingAddress = $dataObject;
        }
        if(empty($this->_customerAddress)){
            $this->_customerAddress = $dataObject;
        }

        $this->_addressHelper = $addressHelper;
        $this->localeResolver = $localeResolver;
        $this->shippingMethodManager = $shippingMethodManager;
        $this->_storeManager = $storeManager;
        
        self::$_cancelUrl = $this->_storeManager->getStore()->getUrl('checkout/cart');
        self::$_returnUrl = $this->_storeManager->getStore()->getUrl('checkout/cart');
        self::$_notifyUrl = $this->_storeManager->getStore()->getUrl('paypal/ipn');
        
    }
    /**
    * Billing Address Getter
    */
    public function getBillingAddress()
    {
	if(empty($this->_customerBillingAddress)){
            return false;
        }
        return $this->_customerBillingAddress->toArray();
    }
    /**
    * Shipping Address Getter
    */

    public function getShippingAddress()
    {
        if(empty($this->_customerAddress)){
            return false;
        }
        return $this->_customerAddress->toArray();
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
         * If all order items are virtual, Magento checkout will not display shipping step. 
         * If no shipping step, use payment method's billing address.
         */
        foreach($this->_quote->getAllVisibleItems() as $_item) {
            if(!$_item->getProduct()->isVirtual()){
                $includeAddress = true;
                break 1;
            }
        }
        //If a default customer address exists, include address even if only virtual products on cart.
        if(!is_array($this-> _customerAddress->validate())){
            $includeAddress = true;
        }
        if($includeAddress){
            $result['shipping_address'] = $this->_getShippingAddress($this->_quote);  
        }else{
            //Use billing address in case no shipping address is required by Magento
            $result['shipping_address'] = $this->_getBillingAddress($this->_quote);
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
        if($this->_quote->getBaseGiftCardsAmount()){
            $baseSubtotal -= $this->_quote->getBaseGiftCardsAmount();
        }
        if($this->_quote->getBaseCustomerBalAmountUsed()){
            $baseSubtotal -= $this->_quote->getBaseCustomerBalAmountUsed();
        }
        
        return [
            'currency' => $this->_quote->getBaseCurrencyCode(),
            'total' => $this->_formatPrice($this->_quote->getGrandTotal()),
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
        // Calculate gift card amount 
        $this->_getGiftCardAmount($data);
        // Get Store Credit From Quote
        $this->_getStoreCreditsAmount($data);
        //If a cart discount is applied, incude it as a separate item (otherwise items amounts wil never match subtotal amount)
        $this->_getDiscountAmount($data);
        
        return $data;
    }
    
    /**
     * Get Store Credit From Quote
     * @param type $data
     */
    public function _getStoreCreditsAmount(&$data)
    {
        if ($this->_quote->getBaseCustomerBalAmountUsed()) {
                $data[] = [
                    'name' => __('Store Credit'),
                    'quantity' => 1,
                    'price' => -$this->_formatPrice($this->_quote->getBaseCustomerBalAmountUsed()),
                    'currency' => $this->_quote->getBaseCurrencyCode()
                ];
        }
    }

    /**
     * Get discount
     * 
     * @param array $data
     */
    protected function _getDiscountAmount(&$data)
    {
        $discount = $this->_cartPayment->getBaseDiscountAmount();

        if ($this->_cartPayment->getBaseDiscountAmount() && $this->_cartPayment->getBaseDiscountAmount() != 0) {
            $data[] = [
                'name' => __(self::DISCOUNT_ITEM_NAME),
                'quantity' => 1,
                'price' => $this->_formatPrice($discount),
                'currency' => $this->_quote->getBaseCurrencyCode()
            ];
        }
    }

    /**
     * 
     * @param array $data
     */
    protected function _getGiftCardAmount(&$data)
    {
        if ($this->_quote->getBaseGiftCardsAmount()) {
            $giftCard = unserialize($this->_quote->getGiftCards());
            if (is_array($giftCard)) {
                foreach ($giftCard as $gC) {
                    if (isset($gC['a'])) {
                        $data[] = [
                            'name' => __('Gift Card'),
                            'sku' => $gC['c'],
                            'quantity' => 1,
                            'price' => -$this->_formatPrice($gC['a']),
                            'currency' => $this->_quote->getBaseCurrencyCode()
                        ];
                    }
                }
            }
        }
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
        $address = $this->_prepareAddressLines($this->_customerAddress);

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
     * Get shipping address request data
     *
     * @param \Magento\Framework\DataObject $address
     * @return array
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _getBillingAddress()
    {
        $region = $this->_customerBillingAddress->getRegionCode() ?  $this->_customerBillingAddress->getRegionCode() :  $this->_customerBillingAddress->getRegion();
        $address = $this->_prepareAddressLines($this->_customerBillingAddress);

        $request = array(
            'recipient_name' => $this->_customerBillingAddress->getFirstname() . " " .$this->_customerBillingAddress->getLastname(),
            'city' => $this->_customerBillingAddress->getCity(),
            'state' => $region ? : 'n/a',
            'postal_code' => $this->_customerBillingAddress->getPostcode(),
            'country_code' => $this->_customerBillingAddress->getCountryId(),
            'line1' => $address['line1'],
            'line2' => $address['line2'],
            'phone' => $this->_customerBillingAddress->getTelephone(),
        );
        
        return $request;
    }
    /**
     * Convert streets to tow lines format
     * 
     * @return array $address
     */
    protected function _prepareAddressLines($address)
    {
        $street = $this->_addressHelper->convertStreetLines($address->getStreet(), 2);
        $_address['line1'] = isset($street[0]) ? $street[0] : '';
        $_address['line2'] = isset($street[1]) ? $street[1] : '';
        
        return $_address;
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
