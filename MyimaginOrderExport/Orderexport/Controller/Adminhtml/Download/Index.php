<?php

namespace MyimaginOrderExport\Orderexport\Controller\Adminhtml\Download;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;

class Index extends \Magento\Backend\App\Action
{
    
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Filesystem $filesystem
    ) {
        $this->_fileFactory = $fileFactory;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        parent::__construct($context);
    }
    
    public function execute()
    {
        
        $name = date('m_d_Y_H_i_s');
        $filepath = 'export/custom' . $name . '.csv';
        $this->directory->create('export');
        /* Open file */
        $stream = $this->directory->openFile($filepath, 'w+');
        $stream->lock();
        $columns = $this->getColumnHeader();
        foreach ($columns as $column) {
            $header[] = $column;
        }
        /* Write Header */
        $stream->writeCsv($header);

        /*Custom Order Script */
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();  
        $request = $objectManager->get('Magento\Framework\App\Request\Http');  
        $dateFrom = explode('/',$request->getParam('datepicker'));
        $_finaldatefrom = $dateFrom[2].'-'.$dateFrom[1].'-'.$dateFrom[0].' '.'00:00:00';

        $dateTo = explode('/', $request->getParam('datepickerto'));
        $_finaldateto = $dateTo[2].'-'.$dateTo[1].'-'.$dateTo[0].' '.'00:00:00';
        
        // Order Range Filter

        $OrderFactory = $objectManager->create('Magento\Sales\Model\ResourceModel\Order\CollectionFactory');
        $orderCollection = $OrderFactory->create()->addFieldToSelect(array('*'));
        $orderCollection->addFieldToFilter('created_at', array('from' => $_finaldatefrom, 'to' => $_finaldateto));
        //$products = $orderCollection->getData();
        if(count($orderCollection->getData())){
        foreach ($orderCollection as $item) {
            
            
            $itemData = [];
            $stateName = '';
            if ($billingAddress = $item->getBillingAddress()){
				$billingStreet = $billingAddress->getStreet();
				$stateName = $billingAddress->getRegion();
			}
			if ($shippingAddress = $item->getShippingAddress()){
				$shippingStreet = $shippingAddress->getStreet();
			}
			$address_details = $billingStreet[0];
            $_customer_contact_no = $billingAddress->getTelephone();
            $city = $billingAddress->getCity();
            $pincode = $billingAddress->getPostcode();
            $status = $item['status'];
            $orderDate = $item['created_at'];
            
            // Product information
			foreach ($item->getAllItems() as $itemId => $itemproduct){
				$product_code = $itemproduct->getSku();
				$product_desc = $itemproduct->getDescription();	
			}
			$amount = $item->getTotalDue();
			
			
            
            $itemData[] = $item->getIncrementId();
            $itemData[] = $item->getCustomerFirstname().' '.$item->getCustomerLastname();
            $itemData[] = $_customer_contact_no;
            $itemData[] = $item->getCustomerEmail();
            $itemData[] = $city;
            $itemData[] = $pincode;
            $itemData[] = $stateName;
            $itemData[] = $item->getPayment()->getMethodInstance()->getTitle();
            $itemData[] = $product_code;
            $itemData[] = $product_desc;
            $itemData[] = $address_details;
            $itemData[] = $amount;
            $itemData[] = $status;
            $itemData[] = $orderDate;            
            
            /*echo "<pre>";
            print_r($itemData);
            die;*/
            
            $stream->writeCsv($itemData);
        }
        }else{
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            return $resultRedirect;
        }
        $content = [];
        $content['type'] = 'filename'; // must keep filename
        $content['value'] = $filepath;
        $content['rm'] = '1'; //remove csv from var folder

        $csvfilename = 'Product.csv';
        return $this->_fileFactory->create($csvfilename, $content, DirectoryList::VAR_DIR);
	}
	
	/* Header Columns */
    public function getColumnHeader() {
        $headers = ['Order Id','Customer Name','Customer Contact No','email','City','Pincode','State','Payment Method','Product Code','Product Desc','Address details','Amount','Status','Order Date'];
        return $headers;
    }
}

?>