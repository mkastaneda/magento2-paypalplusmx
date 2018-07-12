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
namespace Qbo\PayPalPlusMx\Controller\Payment;

use \Magento\Framework\Json\Helper\Data;

 
class Cards extends \Magento\Framework\App\Action\Action
{
    protected $_resultJsonFactory;
    protected $_request;
    protected $_helper;
    protected $_objectManager;
    protected $_customerRepository;
    protected $_session;
    protected $_logger;
    
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Json\Helper\Data $helper
     * @param \Magento\Customer\Model\Session $session
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
            \Magento\Framework\App\Action\Context $context, 
            \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
            \Magento\Framework\Json\Helper\Data $helper,
            \Magento\Customer\Model\Session $session,
            \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
            \Psr\Log\LoggerInterface $logger

    ){
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_helper = $helper;     
        $this->_objectManager = $context->getObjectManager();
        $this->_customerRepository = $customerRepository;
	    $this->_session = $session;
        $this->_logger = $logger;
        parent::__construct($context);
    }
    /**
     * Save tokenized cards
     * @param token
     */
    public function execute()
    {
        $resultJson = $this->_resultJsonFactory->create();
        $httpBadRequestCode = '400';
        $httpErrorCode = '500';
        
        try {
            $requestData = $this->_helper->jsonDecode($this->getRequest()->getContent());
        } catch (\Exception $e) {
            $resultJson->setData(array('reason' => $e->getMessage()));
            return $resultJson->setHttpResponseCode($httpErrorCode);
        }
        if ($this->getRequest()->getMethod() !== 'POST' || !$this->getRequest()->isXmlHttpRequest()) {
            return $resultJson->setHttpResponseCode($httpBadRequestCode);
        }
        
        $tokenId = isset($requestData['token_id']) ? $requestData['token_id'] : false; 

        if(!$tokenId || empty($tokenId)){
            return $resultJson->setHttpResponseCode($httpBadRequestCode);
        }
        try{
            $customerSession = $this->_session; 
            if($customerSession->isLoggedIn()) 
            {
                $customerId = $customerSession->getCustomerId();
                $customer = $this->_customerRepository->getById($customerId);
                $customer->setCustomAttribute('card_token_id', $tokenId);
                $this->_customerRepository->save($customer);
            }
        } catch (Exception $e) {
            $resultJson->setData($e->getMessage());
            return $resultJson->setHttpResponseCode($httpErrorCode);
        }
        
        $response = json_encode(['success' => true]);
        return $resultJson->setData($response);
    }
}