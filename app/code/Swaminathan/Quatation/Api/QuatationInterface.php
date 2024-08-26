<?php 
namespace Swaminathan\Quatation\Api;
 

interface  QuatationInterface 
{
    /**
     * @param string[] $data
     * @return array
     */
    public function addQuatation($data);
    /**
     * 
     * @param string $quoteId
     * @return array
     */
    public function getQuoteInformation($quoteId);
    /**
     * 
     * @param string $quoteId
     * @return array
     */
    public function deleteAll($quoteId);
    /**
     * 
     * @param string $id
     * @return array
     */
    public function deleteById($id);
    /**
     * 
     * @param string $id
     * @param string[] $data
     * @return array
     */
    public function update($id,$data);
    
    /**
     * @param string[] $data
     * @return array
     */
    public function submit($data);

}