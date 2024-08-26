<?php
namespace Swaminathan\Quatation\Model;
use Swaminathan\Quatation\Model\QuatationsFactory;
use Swaminathan\CmsPlpPdp\Model\CmsPlpPdp;
use Lof\Quickrfq\Model\QuickrfqFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Lof\Quickrfq\Model\MessageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Lof\Quickrfq\Helper\Data;

class Quatation implements \Swaminathan\Quatation\Api\QuatationInterface
{
     /**
     * Recipient email config path
     */
    const XML_PATH_EMAIL_RECIPIENT = 'quickrfq/email/recipient';

    /**
     *
     */
    const XML_PATH_EMAIL_TEMPLATE_CUSTOMER = 'quickrfq/email/template_customer';

    /**
     *
     */
    const XML_PATH_EMAIL_TEMPLATE_ADMIN = 'quickrfq/email/template';

    public function __construct(
        QuatationsFactory $quatationsFactory,
        CmsPlpPdp $cmsPlpPdp,
        QuickrfqFactory $_quickRfqFactory,
        ScopeConfigInterface $scopeConfig,
        MessageFactory $messageFactory,
        StoreManagerInterface $storeManager,
        Data $helper

    ) {
        $this->quatationsFactory = $quatationsFactory;
        $this->cmsPlpPdp = $cmsPlpPdp;
        $this->_quickRfqFactory = $_quickRfqFactory;
        $this->scopeConfig = $scopeConfig;
        $this->_messageFactory    = $messageFactory;
        $this->storeManager       = $storeManager;
        $this->_helper            = $helper;
    }
     /**
     * @param string[] $data
     * @return array
     */
        public function addQuatation($data)
    {
        $response = [];
        $priceDatas = [];
        if(!isset($data['customer_id']) || $data['customer_id'] == ""){
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Please Enter the Customer Id",
            ];
            return $response;
        }
        if(!isset($data['quote_id']) || $data['quote_id'] == ""){
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Please Enter the QuoteId",
            ];
            return $response;
        }
        if(!isset($data['productid']) || $data['productid'] == ""){
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Please Enter the ProductId",
            ];
            return $response;
        }
        if(!isset($data['productname']) || $data['productname'] == ""){
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Please Enter the productName",
            ];
            return $response;
        }
        if(!isset($data['sku']) || $data['sku'] == ""){
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Please Enter the sku",
            ];
            return $response;
        }
        if(!isset($data['qty']) || $data['qty'] == ""){
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Please Enter the Quantity",
            ];
            return $response;
        }
        if(!isset($data['price']) || $data['price'] == ""){
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Please Enter the Product Price",
            ];
            return $response;
        }
         $quoteId = $data['quote_id'];
         $sku = $data['sku'];
         $quantity = $data['qty'];
         $quotationData = $this->quatationsFactory->create()->getCollection()
                               ->addFieldToFilter("quote_id", $quoteId)
                               ->addFieldToFilter("sku", $sku);
        if($quotationData->count() < 1){
        $price  = $data['price'];
        $data['total_price'] = $quantity * $price;
        $postData = $this->quatationsFactory->create();
        $save = $postData->addData($data)->save();
        if($save){
            $response[] =[
                "code" => 200,
                "status" => true,
                "message" => "Successfully Added Quotation Cart"
            ];
        }
        }else{
           foreach($quotationData as $filterData){
                $id = $filterData['id'];
                $upadateQty = $filterData['qty']+$quantity;
                $price  = $data['price'];
                $totalPrice = $upadateQty * $price;
                $postData = $this->quatationsFactory->create();
                $save = $postData->load($id);
                        $postData->setQty($upadateQty);
                        $postData->setTotalPrice($totalPrice)->save();
               }
        $response[] =[
                "code" => 200,
                "status" => true,
                "message" => "Successfully Added Quotation Cart"
            ];

        }
         return $response;
    }
     /**
    * @inheritdoc
    */
    public function getQuoteInformation($quoteId){
        $response = [];
        $collectedData =[];
        $data = [];
        $data2 = [];
        $totalQuantity = 0;
        $quotationData = $this->quatationsFactory->create()->getCollection()
                              ->addFieldToFilter("quote_id", $quoteId);
        if($quotationData->count() > 0){
            
            foreach($quotationData as $key => $value){
                $isActive = $value->getIsActive();
                if( $isActive == 1){
                $totalQuantity += $value->getQty();
                $productid = $value->getProductid();
                $data['entity_id'] = $value->getId();
                $data1['entity_id'] = $value->getId();
                $data1['quatation_qty'] = $value->getQty();
               // $data['sku'] = $value->getSku();
               // $data['price'] = $value->getPrice();
                $data1['total_price'] =$value->getTotalPrice();
                $data2 = $this->cmsPlpPdp->getProduct($productid);
                $data['products'] = array_merge($data1,$data2);
                $collectedData[] = $data;
                  }
              }
                    $response[] = [
                        "code" => 200,
                        "status" => true,
                        "item_qty" => $totalQuantity,
                        "count" =>$quotationData->count(),
                        "message" => $collectedData
                        
                        ];
                    }else{
                        $response[] = [
                            "code" => 200,
                            "status" => false,
                            "message" => []
                            ];
                            }
        return $response;
    }
  /**
    * @inheritdoc
    */
    public function deleteAll($quoteId){
        $response = [];
        $collectedData =[];
        $quotationData = $this->quatationsFactory->create()->getCollection()
                              ->addFieldToFilter("quote_id", $quoteId);
        if($quotationData->count() > 0){
            foreach($quotationData as $key => $value){
                $id = $value->getId();
                $quatation = $this->quatationsFactory->create()->load($id);
                $result = $quatation->delete();
                }
                $response[] = [
                    "code" => 200,
                    "status" => true,
                    "message" => "Product Cleared All Successfully"
                    ];
                    }else{
                        $response[] = [
                            "code" => 400,
                            "status" => false,
                            "message" => "No Data Found"
                            ];    
           }
       return $response;
    }
    /**
    * @inheritdoc
    */
    public function deleteById($id){
        $response = [];
        $quotationData = $this->quatationsFactory->create()->getCollection()
                              ->addFieldToFilter("id", $id);
    if($quotationData->count() > 0){
        if(isset($id) || !empty($id)){
        $quatation = $this->quatationsFactory->create()->load($id);
        $result = $quatation->delete();
           }else{
            $response[] = [
                "code" => 400,
                "status" => false,
                "message" => "No Data Found"
                ];
        }
        if($result){
            $response[] = [
                "code" => 200,
                "status" => true,
                "message" => "Product Cleared Successfully"
                ];
                }else{
                    $response[] = [
                        "code" => 400,
                        "status" => false,
                        "message" => "No Data Found"
                        ];
          }
        }
        else{
            $response[] = [
                "code" => 400,
                "status" => false,
                "message" => "No Data Found"
                ];
        }
       return $response;
    }
    /**
    * @inheritdoc
    */
    public function update($id,$data){
        $response = [];
        $priceDatas = [];
        if(!isset($data['sku']) || $data['sku'] == ""){
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Please Enter the sku",
            ];
            return $response;
        }
        if(!isset($data['qty']) || $data['qty'] == ""){
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Please Enter the Quantity",
            ];
            return $response;
        }
        $quotationData = $this->quatationsFactory->create()->getCollection()
        ->addFieldToFilter("id", $id);
      if($quotationData->count() > 0){
            $quantity  = $data['qty'];
            $getQuatationData = $this->quatationsFactory->create()->load($id);
            $price =  $getQuatationData->getPrice();  
            $totalPrice = $quantity * $price;
            $postData = $this->quatationsFactory->create();
            $save = $postData->load($id);
                $postData->setQty($quantity);
                $postData->setTotalPrice($totalPrice)->save();
                if($save){
                    $response[] = [
                        "code" => 200,
                        "success" => true,
                        "message" => "Updated Successfully",
                    ];
                }else{
                    $response[] = [
                        "code" => 400,
                        "success" => false,
                        "message" => "Updated Failed",
                    ];
                    
                }
            }else{
                $response[] = [
                    "code" => 400,
                    "success" => false,
                    "message" => "No Data Found",
                ];
            }
                return $response;
    }
     /**
    * @inheritdoc
    */
    public function submit($data){
        $response = [];
        if(!isset($data['quote_id']) || $data['quote_id'] == ""){
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Please Enter the Quotation Id",
                ];
                return $response;
                }
    if(!isset($data['quote_submitted']) || $data['quote_submitted'] == ""){
                    $response[] = [
                        "code" => 400,
                        "success" => false,
                        "message" => "Please Enter the Quotation Subimit",
                        ];
                        return $response;
            }
        if(!isset($data['customer_name']) || $data['customer_name'] == ""){
                    $response[] = [
                        "code" => 400,
                        "success" => false,
                        "message" => "Please Enter the CustomerName",
                        ];
                return $response;
         }
         if(!isset($data['customer_email']) || $data['customer_email'] == ""){
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Please Enter the Customer Email",
                ];
        return $response;
        } 
        if(!isset($data['customer_phone']) || $data['customer_phone'] == ""){
                $response[] = [
                    "code" => 400,
                    "success" => false,
                    "message" => "Please Enter the Customer Phone",
                    ];
                return $response;
            }
    $quotationData = $this->quatationsFactory->create()->getCollection()
            ->addFieldToFilter("quote_id", $data['quote_id']);
if($quotationData->count() > 0){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $model = $this->_quickRfqFactory->create();
        $model->setData([
            'quote_id'         =>  $data['quote_id'],
            'contact_name'      => $data['customer_name'],
            'email'             => $data['customer_email'],
            'phone'             => $data['customer_phone'],
            'comment'           => $data['comment'],
            'customer_id'       => $data['customer_id'],
            'quote_submitted'   => $data['quote_submitted'],
            'store_id'          => $this->storeManager->getStore()->getId(),
            'store_currency_code' => $this->storeManager->getStore()->getCurrentCurrency()->getCode(),
        ])->save();
        $quatationRfqId  = $this->_quickRfqFactory->create()->getCollection()->addFieldToFilter("quote_id", $data['quote_id']);
        $templateForAdmin    = $this->scopeConfig->getValue(self::XML_PATH_EMAIL_TEMPLATE_ADMIN, $storeScope);
        $templateForCustomer = $this->scopeConfig->getValue(self::XML_PATH_EMAIL_TEMPLATE_CUSTOMER, $storeScope);
        $emailRecipientCustomer = $data['customer_email'];

        $emailRecipientAdmin = $this->scopeConfig->getValue(self::XML_PATH_EMAIL_RECIPIENT, $storeScope);
        $data['is_admin']       = false;
        $data['receiver_name']  = $emailRecipientAdmin;
        $this->_helper->sendEmail($data, $emailRecipientCustomer, $templateForCustomer);

        //send email to admin
        $data['is_admin']       = true;
        $data['receiver_name']  = $model->getContactName();
        $data['quickrfq_id'] =  $quatationRfqId->count() > 0 ? $quatationRfqId->getData()[0]['quickrfq_id']:"";
        $this->_helper->sendEmail($data, $emailRecipientAdmin, $templateForAdmin);
        if($model){
            $quotationData = $this->quatationsFactory->create()->getCollection()
                          ->addFieldToFilter("quote_id", $data['quote_id']);
          foreach($quotationData as $data){
                $id = $data->getId();
                $quationTable = $this->quatationsFactory->create()->load($id);
                $quationTable->setIsActive(0)->save();
             }
                $response[] = [
                    "code" => 200,
                    "success" => true,
                    "message" => "Quote Successfully Submitted",
                    ];
                return $response;
            }
         else{
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Quote  Submitted Failed",
                ];
            return $response;
            }
        }
     else{
        $response[] = [
                    "code" => 400,
                    "success" => false,
                    "message" => "No Data Found",
                    ];
                return $response;
    }
     return $response; 
   }
}