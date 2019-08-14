<?php
require_once "Linc/Care/common.php";

class Linc_Care_Model_Orderobserver
{
	public $client = null;
	public $queue = null;

	public function exportOrder(Varien_Event_Observer $observer)
	{
        $store_id = $observer->getStoreId();
	    $accessToken = Mage::getStoreConfig('linc_access_key', $store_id);
		if ($accessToken != null)
		{
			$post_data_json = $this->buildJson($observer);
			$this->sendOrder($accessToken, $post_data_json);
		}
	}
	
	public function sendOrder($accessToken, $postData)
	{
		if ($this->client == null)
		{
			$this->connectToLincCare($accessToken);
			if ($this->client != null && $this->queue != null)
			{
				$sendQueue = $this->queue;
				unset($this->queue);
				
				foreach ($sendQueue as $data)
				{
					sendOrder($data);
				}
			}
			else
			{
				if ($this->queue == null)
				{
					$this->queue = array();
				}
				
				array_push($this->queue, $postData);
			}
		}

		if ($this->client != null)
		{
			$this->client->setRawData($postData, 'application/json');
			$response = $this->client->request();
			
			if (DEBUG) {
				$temp = $response->getStatus();
				Mage::log("Linc_Care HTTP Status $temp", null, 'order.log', true);
			}
			
			if ($response->getStatus() == 201)
			{
				$temp = $response->getBody();
				if (DEBUG) Mage::log("Linc_Care $temp", null, 'order.log', true);
			}
			else
			{
				$this->client = null;
				array_push($this->queue, $postData);
			}
		}
	}
	
	public function connectToLincCare($accessToken)
	{
		$this->client = new Zend_Http_Client();
        $protocol = SERVER_PROTOCOL;
        $url = SERVER_PATH;
		$this->client->setUri("$protocol://pub-api.$url/v1/order");
		
		$this->client->setConfig(array(
            'maxredirects' => 0,
            'timeout'      => 30,
            'keepalive'    => true,
            'adapter'      => 'Zend_Http_Client_Adapter_Socket'));
	    
		$this->client->setMethod(Zend_Http_Client::POST);
		$this->client->setHeaders(array(
			'Authorization' => 'Bearer '.$accessToken,
			'Content-Type' => 'application/json'));
	}
		
	public function buildJson(Varien_Event_Observer $observer)
	{
		if (DEBUG) Mage::log("exportOrder started", null, 'order.log', true);

		$order = $observer->getEvent()->getOrder();
		if (DEBUG) Mage::log("exportOrder Got order", null, 'order.log', true);
		$orderdata  = $order->getData();
		if (DEBUG) Mage::log("exportOrder Got data", null, 'order.log', true);
		
		$b_addr = $order->getBillingAddress();
		if (DEBUG) 
		{
			$temp = json_encode($b_addr->getData());
			Mage::log("exportOrder got billing address $temp", null, 'order.log', true);
		}
	
		$s_addr = $order->getShippingAddress();
		if (DEBUG && $s_addr != null)
		{
			$temp = json_encode($s_addr->getData());
			Mage::log("exportOrder got shipping address $temp", null, 'order.log', true);
		}
		else
		{
			/* use billing address for shipping address when the purchase is a download. */
			$s_addr = $b_addr;
		}
		
		$phone = $b_addr->getTelephone();
		if (DEBUG) Mage::log("exportOrder got phone $phone", null, 'order.log', true);
		
		$items = $order->getItemsCollection();
		if (DEBUG) Mage::log("exportOrder got item collection", null, 'order.log', true);
		
		$dataitems = array();
		foreach ($items as $item)
		{
			$product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());

			if ($product->isVisibleInSiteVisibility())
			{
			  $dataitem = array(
				  'title'       => $item->getName(),
				  'description' => $item->getDescription(),
				  'thumbnail'   => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage(),
				  'price'       => $item->getPrice(),
				  'weight'      => $item->getWeight());
				
			  #if (DEBUG)
			  {
				  $temp = json_encode($dataitem);
				  Mage::log("exportOrder built an item $temp", null, 'order.log', true);
			  }
			
			  array_push($dataitems, $dataitem);
			}
		}
		
		if (DEBUG) Mage::log("exportOrder built items", null, 'order.log', true);
		
		$user = array (
			'user_id'    => $order->getCustomerId(),
			'first_name' => $order->getCustomerFirstname(),
			'last_name'  => $order->getCustomerLastname(),
			'email'      => $order->getCustomerEmail(),
			'phone'      => $phone);
			
		if (DEBUG)
		{
			$temp = json_encode($user);
			Mage::log("exportOrder built user $temp", null, 'order.log', true);
		}
		
		$country = Mage::getModel('directory/country')->loadByCode($b_addr->getCountry());
		$addrB = array(
			'address'		=> $b_addr->getStreet1(),
			'address2'	    => $b_addr->getStreet2(),
			'city'			=> $b_addr->getCity(),
			'state'			=> $b_addr->getRegion(),
			'country_code'	=> $b_addr->getCountry(),
			'country'		=> $country->getName(),
			'zip'			=> $b_addr->getPostcode());
			
		if (DEBUG)
		{
			$temp = json_encode($addrB);
			Mage::log("exportOrder built billing address $temp", null, 'order.log', true);
		}
		
		$country = Mage::getModel('directory/country')->loadByCode($s_addr->getCountry());
		$addrS = array(
			'address'		=> $s_addr->getStreet1(),
			'address2'  	=> $s_addr->getStreet2(),
			'city'			=> $s_addr->getCity(),
			'state'			=> $s_addr->getRegion(),
			'country_code'	=> $s_addr->getCountry(),
			'country'		=> $country->getName(),
			'zip'			=> $s_addr->getPostcode());

		if (DEBUG)
		{
			$temp = json_encode($addrS);
			Mage::log("exportOrder built shipping address $temp", null, 'order.log', true);
		}
		
		$dataorder = array(
			'user' => $user,
			'order_code' => $order->getIncrementId(),
			'billing_address' => $addrB,
			'shipping_address' => $addrS,
			'purchase_date' => $order->getUpdatedAt(),
			'grand_total' => $order->getGrandTotal(),
			'total_taxes' => $order->getTaxAmount(),
			'products' => $dataitems);
		
		$postdata = json_encode($dataorder);
		if (DEBUG) Mage::log($postdata, null, 'order.log', true);

		if (DEBUG) Mage::log("exportOrder ended", null, 'order.log', true);
		
		return $postdata;
	}
}

?>
