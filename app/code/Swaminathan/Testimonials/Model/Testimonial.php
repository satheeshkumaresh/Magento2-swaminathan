<?php
namespace Swaminathan\Testimonials\Model;
use Swaminathan\Testimonials\Api\TestimonialInterface;
use Swissup\Testimonials\Model\ResourceModel\Data\Collection;
use Swissup\Testimonials\Model\DataFactory as Testimonials;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;


class Testimonial implements TestimonialInterface
{

    const TESTIMONIAL_ENABLED = 'swissup_core/notification/enabled';
    const STATUS_APPROVED = '2';
    const STATUS_AWAITING = '1';
    const STATUS_DISABLED = '3';
    /**
     * @var urlFinderInterface
     */
    private $urlFinderInterface;
    /**
     * @var pageRepository
     */
    private $pageRepository;

    public function __construct(
        
        Collection $collectionFactory,
        ScopeConfigInterface $scopeConfig,
        Testimonials $testimonials,
        DateTime $dateTime,


    ) {
        $this->collectionFactory =  $collectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->testimonials =  $testimonials;
        $this->dateTime = $dateTime;
    }

    /**
     * @return array
     */
    public function getTestimonials()
    {
        $enabled  = $this->scopeConfig->getValue( self::TESTIMONIAL_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        );
        if($enabled){
            $collection = $this->collectionFactory->load();
            $collection->addFieldToFilter('status',self::STATUS_APPROVED);
            $collection->addFieldToSelect('name');
            $collection->addFieldToSelect('date');
            $collection->addFieldToSelect('message');
            $collection->addFieldToSelect('rating');
            $data =$collection->getData();

            if(count($data)){

                $response[] = ['enabled' => 'true', 'count'=>count($data),'testimonials'=>$data];
            }else{
                $response[] = ['enabled' => 'false'];
            }



        }else{
            $response[] = ['enabled' => 'false'];
            
        }
         
        return $response;


    }

    /**
    * inserts the testimonial.
    * @param string[] $data
    * @return array
    */
   public function addTestimonials($data){

    $now = $this->dateTime->gmtDate();
    $name = trim($data['name']);
    $email = trim($data['email']);
    $rating = trim($data['rating']);
    $message = trim($data['message']);
    if(!isset($name) || $name == ''){
        $response[] = ['status'=>'false','message'=>'Name is required'] ;
    }else if(!isset($email) || $email ==''){
        $response[] = ['status'=>'false','message'=>'Email is required'] ;

    }else if(!isset($message) || $message == '' ){
        $response[] = ['status'=>'false','message'=>'Message is required'] ;

    }else if(!isset($rating) || $rating == '' ){
        $response[] = ['status'=>'false','message'=>'Rating is required'] ;

    }else{

        $testimonials =  $this->testimonials->create();
        $testimonials->setData($data);
        $testimonials->save();
        $response []= ['status'=> 'true', 'message' => 'Testimonial has been Saved Successfully'] ;
    }

    return $response;
     
   }
    
}