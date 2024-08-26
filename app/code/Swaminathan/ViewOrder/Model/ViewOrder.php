<?php
namespace Swaminathan\ViewOrder\Model;

use Psr\Log\LoggerInterface;

class ViewOrder 
{
    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $resultPageFactory;
    protected $orderRepository;


    public function __construct(
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        array $data = []
        ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->orderRepository = $orderRepository;

        }

    /**
    * @inheritdoc
    */

    public function viewOrder($orderId)
    {
        
        $response = ['success' => false];

        try {
            $order = $this->orderRepository->get($orderId);
            $object['order_info'] = $order->getData();
            $object['payment_info'] =$order->getPayment()->getData();
            $object['shipping_info'] =$order->getShippingAddress()->getData();
            $object['billing_info'] =$order->getBillingAddress()->getData();
            $resul=array();
            foreach ($order->getAllItems() as $item)
                {
                //fetch whole item information
                $resul= $item->getData();

                }
            $object['items'] = $resul;
            $response = $object;

        } catch (\Exception $e)
       {
            $response = ['success' => false, 'message' => $e->getMessage()];
        }
        $returnArray = $response;
        return $returnArray; 
    }
}