<?php
namespace Swaminathan\PrintOrder\Controller\Index;

use Magento\Framework\App\ResponseInterface;
use Mpdf\Mpdf as PDF;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Swaminathan\HomePage\Model\HomePage;
use Swaminathan\Cart\Model\ShippingBillingAddress;

class PrintPdf extends \Magento\Framework\App\Action\Action
{

    const ADMIN_RESOURCE = 'Magento_Sales::sales_order';

    protected $fileFactory;

    protected $resultRedirectFactory;

    protected $orderRepository;

    protected $date;

    protected $pdf;

    protected $homepage;

    protected $shippingBillingAddress;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        PDF $pdf,
        HomePage $homepage,
        ShippingBillingAddress $shippingBillingAddress
    ) {
        parent::__construct($context);
        $this->fileFactory = $fileFactory;
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        $this->orderRepository = $orderRepository;
        $this->date = $date;
        $this->pdf = $pdf;
        $this->homepage = $homepage;
        $this->shippingBillingAddress = $shippingBillingAddress;
    }

    public function execute()
    {
        
        $orderId = $this->getRequest()->getParam('order_id');
        if ($orderId) {
            $order = $this->orderRepository->get($orderId);
            
            if ($order->getEntityId()) {
                $orderIncrementId = $order->getIncrementId();
                $date = $this->date->date('Y-m-d_H-i-s');
                $fileContent = $this->getPoPdf();
                return $this->fileFactory->create(
                    __('order') . '_' . $orderIncrementId . '.pdf',
                    $fileContent,
                    \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR,
                    'application/pdf'
                );
            }
        }
        // return $this->resultRedirectFactory->create()->setPath('sales/*/view');
    }
    public function getAllData($orderId)
	{
        $productDetails_html = "";
        $counter = 0;
        $order = $this->orderRepository->get($orderId);
        $orderInfo = $order->getData();
        $wordConvert = new \NumberFormatter("en", \NumberFormatter::SPELLOUT);
        $grandTotalWords = strtoupper($wordConvert->format($orderInfo['grand_total']));
        $paymentInfo =$order->getPayment()->getData();
        $shippingInfo =$order->getShippingAddress()->getData();
        $billingInfo =$order->getBillingAddress()->getData();
        $resul=array();
        $currencySymbol = $this->homepage->getCurrency();
        foreach ($order->getAllItems() as $item)
        {
            //fetch whole item information
            $result= $item->getData();

            $productDetails_html = $productDetails_html . '
                                <tr>
                                    <td style="text-align:center;">' . ++$counter . '</td>
                                    <td>' . $result['sku'] . '</td>
                                    <td>' . $result['name'] .'<br>'. '</td>
                                    <td style="text-align:center;">' . $currencySymbol. ' '. number_format($result['price'],2) . '</td>
                                    <td style="text-align:center;">' . round($result['qty_ordered'])  . '</td>
                                    <td style="text-align:center;">' . $currencySymbol .' '. number_format($result['row_total'],2) . '</td>
                                </tr> ';
        }
        $logoUrl = $this->homepage->getDesktopLogo();
        $pageHeader_htmlrep = '<div class="section header-section">
                                <table class="pdf-header" style="width:100%; margin:0px !important;padding:0px !important;border:none;">
                                    <thead>
                                        <tr>
                                            <th style="width:100%;text-align:center">
                                                <img src="'.$logoUrl.'" style="width:100%; max-width:250px;"/>
                                            </th>
                                           
                                        </tr>
                                    </thead>
                                </table>
                              </div>';
        
        $html = '<!DOCTYPE html>
        <html>
        <head>
        <title>Order # '.$orderInfo['increment_id'].'</title>
        <style>
            body{
                font-family: -apple-system,BlinkMacSystemFont,
                Segoe UI,Roboto,Oxygen,Ubuntu,Cantarell,Fira Sans,
                Droid Sans,Helvetica Neue,Source Sans Pro,Open Sans,
                sans-serif;
                font-size:14px;
                color:#282D31 !important;
                line-height:1.3em !important;
            }
            table, th{
                border: 0;
                border-collapse: collapse;
                line-height:1.5em;
                vertical-align: top;
            }
            .pdf-header td{
                border: 0 !important;
            }
            table, td {
                border: 1px solid #00000;
                border-collapse: collapse;
                line-height:1.5em;
                vertical-align: top;
            }
            th, td {
                padding: 15px;
                text-align:left;
            }
            .tableHeader-title{
                color:#002D56;
                font-size:25px;
                vertical-align: middle;
            }
            .primary-color{
                color:#002D56;
            }
            .gap{
                visibility:hidden;
                line-height:0.3em !important;
            }
            .bg-section{
                background-color:#641233;
                padding:20px;
                width:100%;
                color:#fff !important;
            }
            p{
                margin:0px;
                margin-bottom:15px !important;
            }
            .last-para{
                margin-bottom:0px !important;
            }
            
            .address-section{
                margin-top:20px;
            }
            .address-section thead th{
                padding: 8px 15px;
                color: #641233;
            }
            .address-section tbody tr{
                background-color: #641233;
            }
            .address-section tbody tr td{
                color: #fff !important;
            }
            .address-section tbody tr td{
                border-right-color:#B5BABD;
            }
            .address-section tbody tr td.last{
                border-right-color:#00000;
            }
            .table-section thead tr{
                background-color: #641233;
            }
            .table-section thead tr th{
                color: #fff !important;
                border-right-color:#B5BABD;
            }
            .table-section thead tr th.last-head{
                border-right-color:#00000;
            }
            .table-section .price-block-row1,
            .table-section .grand-total{
                text-align:right;
                border:1px solid #00000;
                border-top:0px solid #00000;
                padding-bottom:10px;
            }
            .table-section th,.table-section td {
                padding: 10px;
            }
            .table-section .price-block-row1 .title,
            .table-section .grand-total .title{
                float:left;
                width:83%;
                padding: 10px 10px 0px 0px;
            }
            .table-section .price-block-row1 .value,
            .table-section .grand-total .value{
                float:right;
                width:11%;
                padding: 10px 10px 0px 0px;
            }
            .table-section .rupees-in-words{
                padding: 10px;
                text-align:center;
                border:1px solid #00000;
                border-top:0px solid #00000;
            }
            .table-section1{
                margin-top : 20px;
            }
            .note-section{
                padding:15px 0px;
            }
            .terms-conditions p.title{
                font-size: 15px;
                margin-top:20px;
            }
            .footer-content{
                text-align:center;
                color:gray;
            }
            tr.price-block-row .title{
                width:80%;
                border-right:0px solid #00000 !important;
            }
            tr.price-block-row .value{
                width:14%;
                white-space:nowrap;
                border-left:0px solid #00000 !important;
            }
            tr.price-block-row.rupees-in-word .title{
                border-left:1px solid #00000 !important;
                width:100%;
            }

        </style>
        </head>
        <body>
        '.$this->pdf->SetHTMLHeader($pageHeader_htmlrep).'
            <div>
            </div> 
            <div class="section bg-section">
                <p style="margin:0px; margin-bottom: 5px;">Order # '.$orderInfo['increment_id'].'</p>
                <p style="margin:0px; margin-bottom: 5px;">Ordered Date # '.$orderInfo['created_at'].'</p>
                <p style="margin:0px; margin-bottom: 5px;">Order Status # '.$orderInfo['status'].' </p>
            </div>

            <div class="section address-section">
                <table style="width:100%;">

                    <thead>
                        <tr>
                            <th style="width:50%"> Billing Address </th>
                            <th style="width:50%;"> Shipping Address </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td>
                            <p>
                                '.$billingInfo['firstname']. '  '.$billingInfo['lastname'].'
                            </p>
                            <p>
                                '.$billingInfo['street']. ' 
                            </p>
                            <p>
                                '.$billingInfo['city']. ' 
                            </p>
                            <p>
                                '.$billingInfo['region']. ' 
                            </p>
                            <p>
                                '.$this->shippingBillingAddress->getCountryname($billingInfo['country_id']). ' 
                            </p>
                            <p>
                                T:'.$billingInfo['telephone']. ' 
                            </p>
                            </td>
                            <td class="last">
                                <p>
                                    '.$shippingInfo['firstname']. '  '.$shippingInfo['lastname'].'
                                </p>
                                <p>
                                    '.$shippingInfo['street']. ' 
                                </p>
                                <p>
                                    '.$shippingInfo['city']. ' 
                                </p>
                                <p>
                                    '.$shippingInfo['region']. ' 
                                </p>
                                <p>
                                    '.$this->shippingBillingAddress->getCountryname($shippingInfo['country_id']). ' 
                                </p>
                                <p>
                                    T:'.$shippingInfo['telephone']. ' 
                                </p>
                            </td>
                        </tr>
                    </tbody>

                </table>
            </div>
            <div class="section address-section">
                <table style="width:100%;">

                    <thead>
                        <tr>
                            <th style="width:50%"> Payment Method </th>
                            <th style="width:50%;"> Shipping Method </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td>
                            <p>
                                '.$paymentInfo['additional_information']['method_title'].'
                            </p>
                            </td>
                            <td class="last">
                            <p>
                                '.$orderInfo['shipping_description']. ' 
                            </p>
                            <p>
                                (Total Shipping Charges '.$currencySymbol.' '. number_format($orderInfo['base_shipping_amount'],2).') 
                            </p>
                            </td>
                        </tr>
                    </tbody>

                </table>

            </div>
            <div class="section table-section table-section1">
            <table style="width:100%;">
                <thead>
                    <tr>
                        <th style="text-align:center;"> SR. NO. </th>
                        <th> SKU/Part Number </th>
                        <th> Product Name </th>
                        <th style="text-align:center;"> Price </th>
                        <th style="text-align:center;"> Quantity </th>
                        <th style="text-align:center;" class="last-head"> Subtotal </th>
                    </tr>
                </thead>

                <tbody>
                    '.$productDetails_html.'
                </tbody>

        </table>
            <table style="width:100%;">
                    <tbody>

                        <tr class="price-block-row">
                            <td class="title" style="text-align:right;">Subtotal:</td>
                            <td class="value primary-color" style="text-align:right;"><b>'.$currencySymbol.' '. number_format($orderInfo['base_subtotal'],2) .'</b></td>
                        </tr>

                        <tr class="price-block-row">
                            <td class="title" style="text-align:right;">Shipping Handling:</td>
                            <td class="value primary-color" style="text-align:right;"><b>'.$currencySymbol.' '. number_format($orderInfo['base_shipping_amount'],2) .'</b></td>
                        </tr>

                        <tr class="price-block-row">
                            <td class="title" style="text-align:right;">Tax:</td>
                            <td class="value primary-color" style="text-align:right;"><b>'.$currencySymbol.' '. number_format($orderInfo['tax_amount'],2) .'</b></td>
                        </tr>

                        <tr class="price-block-row">
                            <td class="title" style="text-align:right;">Discount :</td>
                            <td class="value primary-color" style="text-align:right;"><b>'.$currencySymbol.' '. number_format($orderInfo['discount_amount'],2) .'</b></td>
                        </tr>

                        <tr class="price-block-row">
                            <td class="title" style="text-align:right;">Grand Total:</td>
                            <td class="value primary-color" style="text-align:right;"><b>'.$currencySymbol.' '. number_format($orderInfo['grand_total'],2) .'</b></td>
                        </tr>

                        <tr class="price-block-row rupees-in-word">
                            <td class="title" colspan="2" style="text-align:left;">Amount Chargeable (in words):'.$grandTotalWords.'</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </body>
        </html>';
        $poPdf = [];
        $poPdf = ["content"=>$html];
        return $poPdf;
    }
    public function getPoPdf(){
        $pdfData =  $this->getAllData($this->getRequest()->getParam("order_id"));
        $this->pdf->SetHTMLFooter('<p class="footer-content">Note: This is an electronically generated document and does not required a signature</p>');
        $this->pdf->AddPage('', // L - landscape, P - portrait 
        '', '', '', '',
        8, // margin_left
        8, // margin right
        50+0, // margin top
        30, // margin bottom
        15, // margin headers
        10); // margin footer
        $this->pdf->WriteHTML($pdfData['content']);
        $orderId = $this->getRequest()->getParam('order_id');
        $orderData = $this->orderRepository->get($orderId);
        $incrementId = $orderData->getData()['increment_id'];
        $this->pdf->Output("order_".$incrementId.".pdf");
        $this->pdf->Output();
    }
}
