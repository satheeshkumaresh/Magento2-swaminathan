<?php
namespace Swaminathan\Checkout\Cron;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Framework\App\ResourceConnection;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;


class Updatesalableqty
{
        public function __construct(
            OrderRepositoryInterface $OrderRepositoryInterface,
            Order $Order,
            OrderCollection $OrderCollection,
            ResourceConnection $resourceConnection,
            Session $checkoutSession,
            QuoteFactory $quoteFactory,
            OrderFactory $orderFactory,
            DateTime $DateTime,
            TimezoneInterface $timezoneInterface,
            LoggerInterface $logger,

            ) {
             $this->orderCollection =  $OrderCollection;
             $this->order = $Order;
             $this->orderRepository = $OrderRepositoryInterface;
             $this->resourceConnection = $resourceConnection;
             $this->checkoutSession = $checkoutSession;
             $this->quoteFactory = $quoteFactory;
             $this->orderFactory = $orderFactory;
             $this->DateTime = $DateTime;
             $this->timezoneInterface = $timezoneInterface;
             $this->logger = $logger;
            }
        public function execute(){
            $endDate = $this->timezoneInterface->date(null, null, false)->format('Y-m-d H:i:s');
            $dateTime = $this->timezoneInterface->date(null, null, false);
            $dateTime->sub(new \DateInterval('PT10M')); // Subtract 5 minutes
            $startDate = $dateTime->format('Y-m-d H:i:s');
            $orderCollection = $this->orderCollection;
            $orderCollection->addFieldToFilter( 'status', ['eq' => "pending"]);
            $orderCollection->addFieldToFilter( 'state', ['eq' => "new"]);
            $orderCollection->addFieldToFilter('created_at', ['from' => $startDate, 'to' => $endDate]);
            $count = count($orderCollection->getData());
           if($count == 0){
            $this->logger->info("Orderdata empty already reverted salable quantity");
                    die;
            }
            foreach($orderCollection as $orderdata){    
              $orderid =  $orderdata->getId();
              $orderInc = $orderdata->getIncrementId();  
              $orderModel = $this->orderFactory->create()->load($orderid);
              $state = $orderdata->getState();
              $status = $orderdata->getStatus();
              $order = $this->order->load($orderid)->getPayment()->getLastTransId();
          if(empty(($order))){   
                 if ($this->order->canCancel()) {
                        $connection = $this->resourceConnection->getConnection();
                        $tableName = $connection->getTableName('inventory_reservation');
                        $select = $connection->select()
                            ->from($tableName)->where('metadata LIKE ?','%'.$orderInc.'%');
                        $results = $connection->fetchAll($select);
                        foreach($results as $data){
                                $reservationId=$data['reservation_id'];
                            if($state != "canceled" && $status != "canceled"){
                                $delete = $connection->delete($tableName, ['reservation_id = ?' => $reservationId]);
                                //not canceling order as cancled order can't be used again for order processing.
                                $orderModel->setStatus('canceled');
                                $orderModel->save();
                                $this->logger->info('Successfully reverted  salableQuantity'); 
                              }else{
                                $this->logger->info('Already reverted salableQuantity'); 
                              }
                        }                                 
                    }
              }
          else{
            $this->logger->info('did not received payment id');
           } 
         }
     }
 }