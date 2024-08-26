<?php
namespace Swaminathan\ProductAlert\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\ProductAlert\Model\StockFactory;
use Swaminathan\ProductAlert\Api\ProductAlertManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;

class ProductAlert implements ProductAlertManagementInterface
{
    protected $productRepository;
    private $storeManager;
    protected $stockFactory;
    protected $customerRepository;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        StockFactory $stockFactory,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->stockFactory = $stockFactory;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Get Customer Id By Email ID
     * @param string $email
     * @return int|null
     */
    
    public function getCustomerIdByEmail(string $email)
    {
        $customerId = null;
        try {
            $customerData = $this->customerRepository->get($email);
            $customerId = (int)$customerData->getId();
        }catch (NoSuchEntityException $noSuchEntityException){
        }
        return $customerId;
    }

    /**
     * Return true if product Added to Alert.
     *
     * @param string $customerEmail
     * @param int $productId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */

    public function addProductAlertStock($customerEmail, $productId)
    {
        $customerId = $this->getCustomerIdByEmail($customerEmail);
        if($customerId != ""){
            try {
            
                /* @var $product \Magento\Catalog\Model\Product */
                $product = $this->productRepository->getById($productId);
                $store = $this->storeManager->getStore();
                /** @var \Magento\ProductAlert\Model\Stock $model */
                $model = $this->stockFactory->create()
                    ->setCustomerId($customerId)
                    ->setProductId($product->getId())
                    ->setWebsiteId($store->getWebsiteId())
                    ->setStoreId($store->getId());
                $datasave = $model->save();
                if($datasave){
                    $response[] = [
                        "code" => 200,
                        "status" => true,
                        "message" => "Alert subscription has been saved."
                    ];
                }
                else{
                    $response[] = [
                        "code" => 400,
                        "status" => false,
                        "message" => "Error saving data."
                    ];
                }
            } catch (NoSuchEntityException $noEntityException) {
                $response[] = [
                    "code" => 400,
                    "status" => false,
                    "message" => $noEntityException
                ];
            }
        }
        else{
            $response[] = [
                "code" => 400,
                "status" => false,
                "message" => "Email Address in not registered with Sri Swaminathan & Co."
            ];
        }
        return $response;
    }
}
