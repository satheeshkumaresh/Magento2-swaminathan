<?php
namespace Swaminathan\Cart\Model;

use Swaminathan\Checkout\Api\GuestCartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Swaminathan\Checkout\Api\CartManagementInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;

class MergeCart implements \Swaminathan\Cart\Api\MergeCartInterface
{
    protected $guestCartManagement;
    protected $cartRepository;
    protected $cartManagement;
    protected $storeManager;
    protected $quoteIdMaskFactory;
    public function __construct(
        GuestCartManagementInterface $guestCartManagement,
        CartRepositoryInterface $cartRepository,
        CartManagementInterface $cartManagement,
        StoreManagerInterface $storeManager,
        QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
        $this->guestCartManagement = $guestCartManagement;
        $this->cartRepository = $cartRepository;
        $this->cartManagement = $cartManagement;
        $this->storeManager = $storeManager;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
    }
    // merge cart items 
    public function mergeCart($param){
        try{
            $storeId = $this->storeManager->getStore()->getId();
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($param['guestCartId'], 'masked_id');
            if(!empty($quoteIdMask->getData())){
                $guestCart = $this->guestCartManagement->assignCustomer($param['guestCartId'], $param['customerId'], $storeId);
                if($guestCart == true){
                    $response[] = [
                        'code' => 200,
                        'status' => true, 
                        'data' => "Cart items merged successfully."
                    ];
                }
                else{
                    $response[] = [
                        'code' => 400,
                        'status' => false, 
                        'data' => "Error occured while merging cart items."
                    ];
                }
            }
            else{
                $response[] = [
                    'code' => 400,
                    'status' => false, 
                    'data' => "The requested quote doesn't exist."
                ];
            }
        }
        catch (\Exception $e)
        {
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => 'Error: %1.', $e->getMessage()
            ];
        }
        return $response;
    }
}
