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
 * 
 * © 2016 QBO DIGITAL SOLUTIONS. 
 *
 */
namespace qbo\PayPalPlusMx\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use qbo\PayPalPlusMx\Model\Http\Api;

/**
 * PayPalPlus module observer
 */
class clearPaymentDataAfterSaveOrder implements ObserverInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    /**
     *
     * @var qbo\PayPalPlusMx\Model\Http\Api 
     */
    protected $_api;
    /**
     * @var string
     */
    const METHOD_CODE = 'qbo_paypalplusmx';

    /**
     * Constructor
     * 
     * @param \Psr\Log\LoggerInterface $logger
     * @param Api $api
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        Api $api
    ) {
        $this->_logger = $logger;
        $this->_api = $api;
    }

    /**
     * Clear payment data only after order is saved.
     * This is because if any error occures while saving the order after payment is executed,
     * the user will try to reorder, and if payment data is cleared before this, user would be charged twice.
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        $event = $observer->getEvent();
        /* @var $order \Magento\Sales\Model\Order */
        $order = $event->getOrder();

        if ($order && $order->getId() && $order->getPayment()->getMethod() == self::METHOD_CODE) {
            $this->_api->clearPaymentData();
        }
    }
}
