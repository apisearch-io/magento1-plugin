<?php
class Bumon_Apisearch_Model_Observer extends Bumon_Apisearch_Model_ApisearchClient
{
	protected $host = 'http://apisearch.local';
    protected $version = 'v1';

	public function __construct(){
		$this->connection();
		parent::__construct($this->host,$this->version);
	}

	public function connection()
    {
		Mage::log("Connection [START]", null, 'apisearch.log', true);
		$helperapi = Mage::helper('apisearch');

        $appUUID = $helperapi->getApiKey();
        $indexUUID = $helperapi->getIndexKey();
        $tokenUUID = $helperapi->getTokenKey();

        $this->setCredentials($appUUID,$indexUUID,$tokenUUID);
		Mage::log("Connection [END]", null, 'apisearch.log', true);
    }

	public function indexMetadata($product)
    {
        $filterableAttr = $product->getAttributes();
        $filterable = array();
        foreach ($filterableAttr as $attr) {
            if ($product->getData($attr->getName()) != null) {
                    $value = $attr->getFrontend()->getValue($product);
					if($attr->getName() == "occasion" || $attr->getName() == "width" || $attr->getName() == "shoe_type"){
						$filterable[$attr->getName()] = $value;	
					}
            }
        }
        return $filterable;
    }

	public function updateItem($product)
    {
		Mage::log("updateItem [START]", null, 'apisearch.log', true);
		$filterable = $this->indexMetadata($product);
		$this->productDataPush($product,$filterable);
		Mage::log("updateItem [END]", null, 'apisearch.log', true);
	}

	public function updateProduct($observer)
	{
		Mage::log("updateProduct [START]", null, 'apisearch.log', true);
		$product = $observer->getEvent()->getProduct();
		$filterable = $this->indexMetadata($product);
		$this->productDataPush($product,$filterable);
		Mage::log("updateProduct [END]", null, 'apisearch.log', true);
	}

	public function removeProduct($observer)
    {
		Mage::log("removeProduct [START]", null, 'apisearch.log', true);
		$product = $observer->getEvent()->getProduct();
        $productData = array(
                "type"=> "product",
                "id"=> $product->getId(),
        );

        $this->deleteItem($productData);
        $this->flush();
		Mage::log("removeProduct [END]", null, 'apisearch.log', true);
    }

	public function productDataPush ($product,$filterable){
		Mage::log("productDataPush [START]", null, 'apisearch.log', true);
		
		//HEADER //'uid|type|title|description|extra_description|link|image|brand|keyword|suggest|categories|alternative_categories|reference|alternative_reference|price|reduced_price|reduced_price_percent|stock|on_offer|coordinate|attributes';
		//BODY  //$product->getId().'|product|"'.htmlentities ($product->getName()).'"|"'.htmlentities ($product->getDescription()).'"|"'.htmlentities ($product->getDescription()).'"|"'.$product->getUrlPath().'"|"'.Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/product'.$product->getImage().'"|"'.htmlentities ($product->getManufacturer()).'"|"'.htmlentities ($product->getgetMetaKeyword()).'"|{"'.htmlentities ($product->getName()).'","'.htmlentities ($product->getSku()).'"}|""|""|"'.htmlentities ($product->getSku()).'"|"'.htmlentities ($product->getSku()).'"|"'.$product->getPrice().'"|"'.$product->getSpecialPrice().'"|"'.$product->getDiscountAmount().'"|"'.$product->getQty().'"|""|""|{"size":[{"id":"'.$size.'","name":"'.$size.'"}],"color":[{"id":"'.$color.'","name":"'.$color.'"}]}';

		//uuid|metadata|indexed_metadata|searchable_metadata|exact_matching_metadata|suggest|coordinate
		
		$id = $product->getId();
		$name = $product->getName();
		$sku = $product->getSku();
		$shortDescription = $product->getShortDescription();
		$description = $product->getDescription();
		$price = $product->getPrice();
		$stock = $product->getQty();
		$image = $product->getImage();
		$imagethumb = $product->getThumbnail();
		$imagesmall = $product->getSmallImage();
		$size = $product->getResource()->getAttribute('size')->getFrontend()->getValue($product);
        $color =  $product->getResource()->getAttribute('color')->getFrontend()->getValue($product);
		$url = $product->getUrlPath();
		$rating = null;

		try{
			$productData = array(
				"uuid" => [
					"id"=> $id,
					"type"=> "product",
				],
				"metadata" => [
					"sku" => $sku,
					"name" => $name,
					"description" => $description,
					"short_description" => $shortDescription,
					"image" => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/product'.$imagethumb,
					"url" => $url
				],
				"indexed_metadata" => $filterable,
				"searchable_metadata" => [
					"name" => $name
				],
				"exact_matching_metadata" => [
					$sku
				],
				"suggests" => [
					$name
				]
			);

			Mage::log("productData >> ", null, 'apisearch.log', true);
			Mage::log($productData, null, 'apisearch.log', true);

			$this->putItem($productData);
			$this->flush(1,false);

			Mage::log("productDataPush Product: ".$product->getId()." UPDATED", null, 'apisearch.log', true);
		} catch (Exception $e) {
			Mage::log("productDataPush ERROR: ".$e->getMessage(), null, 'apisearch.log', true);
		}

		Mage::log("productDataPush [END]", null, 'apisearch.log', true);
	}

	public function fullUpdate()
    {
		Mage::log("fullUpdate [START]", null, 'apisearch.log', true);
		$collection = Mage::getModel('catalog/product')->getCollection();
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter('status', ['eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED]);
        foreach ($collection as $item) {
            $this->updateItem($item);
        }
        $this->flush(100,false);
		Mage::log("fullUpdate [END]", null, 'apisearch.log', true);
    }
}