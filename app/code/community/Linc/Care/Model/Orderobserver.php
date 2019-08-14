<?php
require_once "Linc/Care/common.php";

class Linc_Care_Model_Orderobserver
{
	public $client = null;
	public $queue = null;
	public $logname = 'order.log';
	
	public function log($msg)
	{
	    if (DBGLOG) Mage::log($msg, null, $this->logname, true);
        #print("<p>$msg</p>");
	}

	public function exportOrder(Varien_Event_Observer $observer)
	{
        $this->log("Beginning exportOrder");
        
        $store_id = $observer->getEvent()->getOrder()->getStoreId();
        #$this->log($observer->debug());
        $this->log("Got store ID - " . $store_id);

        # get access key
        $resource = Mage::getSingleton('core/resource');
        $this->log("Got Resource");
        
        $read = $resource->getConnection('core_read');
        $this->log("Got Read");
        
        $configDataTable = $read->getTableName('core_config_data');
        $this->log("Got config table");

        $accessToken = '';
        $select = $read->select()
            ->from(array('cd'=>$configDataTable))
            ->where('cd.scope=?', 'store')
            ->where("cd.scope_id=?", $store_id)
            ->where("cd.path=?", 'linc_access_key');
        $this->log("Got Select");
        
        $rows = $read->fetchAll($select);
        
        if (count($rows) > 0)
        {
            $accessToken = $rows[0]['value'];
            $this->log("Got access token - $accessToken");
        }
        else
        {
            $this->log("Got config table");
        }

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
			$this->log("Connecting to Linc Care");
			$this->connectToLincCare($accessToken);
			if ($this->client != null && $this->queue != null)
			{
    			$this->log("Processing the queue");
				$sendQueue = $this->queue;
				unset($this->queue);
				$this->queue = null;
				
				foreach ($sendQueue as $data)
				{
					sendOrder($data);
				}
			}
			else
			{
    			$this->log("Saving to queue");
				if ($this->queue == null)
				{
					$this->queue = array();
				}
				
				array_push($this->queue, $postData);
			}
		}

		if ($this->client != null)
		{
			$this->log("Building request");
			
			$this->client->setRawData($postData, 'application/json');
			$response = $this->client->request();
			
			$temp = $response->getStatus();
			$this->log("Linc_Care HTTP Status $temp");
			
			if ($response->getStatus() == 201)
			{
				$temp = $response->getBody();
				$this->log("Linc_Care $temp");
			}
			else
			{
			    $adapter = $this->client->getAdapter();
			    $adapter->close();
				$this->client = null;
				array_push($this->queue, $postData);
			}
		}
	}
	
	public function connectToLincCare($accessToken)
	{
        $protocol = SERVER_PROTOCOL;
        $url = SERVER_PATH;
        
		$this->client = new Zend_Http_Client();
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
		$this->log("exportOrder started");

		$order = $observer->getEvent()->getOrder();
		$this->log("exportOrder Got order");
		$orderdata  = $order->getData();
		$this->log("exportOrder Got data");
		
		$b_addr = $order->getBillingAddress();
		if (DBGLOG) 
		{
			$temp = json_encode($b_addr->getData());
			$this->log("exportOrder got billing address $temp");
		}
	
		$s_addr = $order->getShippingAddress();
		if (DBGLOG && $s_addr != null)
		{
			$temp = json_encode($s_addr->getData());
			$this->log("exportOrder got shipping address $temp");
		}
		else
		{
			/* use billing address for shipping address when the purchase is a download. */
			$s_addr = $b_addr;
		}
		
		$phone = $b_addr->getTelephone();
		$this->log("exportOrder got phone $phone");
		
		$items = $order->getItemsCollection();
		$this->log("exportOrder got item collection");
		
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
				
			  #if (DBGLOG)
			  {
				  $temp = json_encode($dataitem);
				  $this->log("exportOrder built an item $temp");
			  }
			
			  array_push($dataitems, $dataitem);
			}
		}
		
		$this->log("exportOrder built items");
		
		$user = array (
			'user_id'    => $order->getCustomerEmail(),
			'first_name' => $order->getCustomerFirstname(),
			'last_name'  => $order->getCustomerLastname(),
			'email'      => $order->getCustomerEmail(),
			'phone'      => $phone);
			
		if (DBGLOG)
		{
			$temp = json_encode($user);
			$this->log("exportOrder built user $temp");
		}
		
		#$country = Mage::getModel('directory/country')->loadByCode($b_addr->getCountry());
		$addrB = array(
			'address'		=> $b_addr->getStreet1(),
			'address2'	    => $b_addr->getStreet2(),
			'city'			=> $b_addr->getCity(),
			'state'			=> $b_addr->getRegion(),
			'country_code'	=> $b_addr->getCountry(),
			#'country'		=> $country->getName(),
			'zip'			=> $b_addr->getPostcode());
			
		if (DBGLOG)
		{
			$temp = json_encode($addrB);
			$this->log("exportOrder built billing address $temp");
		}
		
		#$country = Mage::getModel('directory/country')->loadByCode($s_addr->getCountry());
		$addrS = array(
			'address'		=> $s_addr->getStreet1(),
			'address2'  	=> $s_addr->getStreet2(),
			'city'			=> $s_addr->getCity(),
			'state'			=> $s_addr->getRegion(),
			'country_code'	=> $s_addr->getCountry(),
			#'country'		=> $country->getName(),
			'zip'			=> $s_addr->getPostcode());

		if (DBGLOG)
		{
			$temp = json_encode($addrS);
			$this->log("exportOrder built shipping address $temp");
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
		$this->log($postdata);

		$this->log("exportOrder ended");
		
		return $postdata;
	}
}

?>
