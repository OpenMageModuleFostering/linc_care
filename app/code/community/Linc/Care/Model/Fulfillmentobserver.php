<?php
require_once "Linc/Care/common.php";

class Linc_Care_Model_Fulfillmentobserver
{
	public $client = null;
	public $queue = null;
	public $logname = 'fulfillment.log';

	public function log($msg)
	{
	    if (DBGLOG) Mage::log($msg, null, $this->logname, true);
        #print("<p>$msg</p>");
	}

	public function exportFulfillment(Varien_Event_Observer $observer)
	{
		$this->log("exportFulfillment started");

        # get store_id
		$track = $observer->getEvent()->getTrack();
		$shipment = $track->getShipment();
		$order = $shipment->getOrder();
		$store_id = $order->getStoreId();
		$this->log("exportFulfillment got the store id ($store_id)");
		
	    #$accessToken = Mage::getStoreConfig('linc_access_key', $store_id);
        # get access_key
        $resource = Mage::getSingleton('core/resource');
        $read = $resource->getConnection('core_read');
        $configDataTable = $read->getTableName('core_config_data');

        $accessToken = '';
        $select = $read->select()
            ->from(array('cd'=>$configDataTable))
            ->where('cd.scope=?', 'store')
            ->where("cd.scope_id=?", $store_id)
            ->where("cd.path=?", 'linc_access_key');
        $rows = $read->fetchAll($select);

        if (count($rows) > 0)
        {
            $accessToken = $rows[0]['value'];
        }
		$this->log("exportFulfillment got the accessKey ($accessToken)");
		
		if ($accessToken != null)
		{
			$this->log("exportFulfillment building the JSON");
			$post_data_json = $this->buildJson($observer);
			
			$this->log("exportFulfillment making the API call");
			$this->sendFulfillment($accessToken, $post_data_json);
			
			$this->log("exportFulfillment ending");
		}
	}
	
	public function sendFulfillment($accessToken, $postData)
	{
		$this->log("sendFulfillment started");
		
		if ($this->client == null)
		{
			$this->connectToLincCare($accessToken);
			if ($this->client != null && $this->queue != null)
			{
				foreach ($this->queue as $data)
				{
					sendFulfillment($data);
				}
				unset($this->queue);
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
			
			$temp = $response->getStatus();
			Mage::log("Linc_Care HTTP Status $temp");
			if ($response->getStatus() == 201)
			{
				$temp = $response->getBody();
				Mage::log("Linc_Care $temp");
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
		$this->log("connectToLincCare started");
		
		$this->client = new Zend_Http_Client();
        $protocol = SERVER_PROTOCOL;
        $url = SERVER_PATH;
		$this->client->setUri("$protocol://pub-api.$url/v1/fulfillment");
		
		$this->client->setConfig(array(
			'maxredirects'	=> 0,
			'timeout'		=> 30,
			'keepalive'		=> true,
	        'adapter'       => 'Zend_Http_Client_Adapter_Socket'));
	    
		$this->client->setMethod(Zend_Http_Client::POST);
		$this->client->setHeaders(array(
			'Authorization' => 'Bearer '.$accessToken,
			'Content-Type' => 'application/json'));
	}
		
	public function buildJson(Varien_Event_Observer $observer)
	{
		$this->log("buildJson started");
		
		$track = $observer->getEvent()->getTrack();
		$this->log("buildJson Got order");
		$store = $track->getStore();
		$this->log("buildJson Got order");
		$shipment  = $track->getShipment();
		$this->log("buildJson Got order");
		$order  = $shipment->getOrder();
		$this->log("buildJson Got order");
		$carrier = $order->getShippingCarrier();
		$this->log("buildJson Got order");
		$store_id = $observer->getStoreId();
		$this->log("buildJson Got order");

		$postdata = "";
		$CarrierCode = "";
	
		if ($carrier instanceof Mage_Usa_Model_Shipping_Carrier_Abstract)
		{
			$CarrierCode = $carrier->getCarrierCode();
		}
		
		$dataorder = array(
			'order_code' => $order->getIncrementId(),
			'carrier' => $CarrierCode,
			'tracking_number' => $track->getNumber(),
			'fulfill_date' => $shipment->getCreatedAt()
			);
	
		$postdata = json_encode($dataorder);
		$this->log($postdata);

		$this->log("buildJson ended");
	
		return $postdata;
	}
}

?>
