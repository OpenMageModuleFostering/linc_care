<?php
require_once "Linc/Care/common.php";

class Linc_Care_Model_Fulfillmentobserver
{
	public $client = null;
	public $queue = null;

	public function exportFulfillment(Varien_Event_Observer $observer)
	{
		if (DBGLOG) Mage::log("exportFulfillment started", null, 'fulfillment.log', true);

        # get store_id
		$track = $observer->getEvent()->getTrack();
		$shipment = $track->getShipment();
		$order = $shipment->getOrder();
		$store_id = $order->getStoreId();
		if (DBGLOG) Mage::log("exportFulfillment got the store id ($store_id)", null, 'fulfillment.log', true);
		
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
		if (DBGLOG) Mage::log("exportFulfillment got the accessKey ($accessToken)", null, 'fulfillment.log', true);
		
		if ($accessToken != null)
		{
			if (DBGLOG) Mage::log("exportFulfillment building the JSON", null, 'fulfillment.log', true);
			$post_data_json = $this->buildJson($observer);
			
			if (DBGLOG) Mage::log("exportFulfillment making the API call", null, 'fulfillment.log', true);
			$this->sendFulfillment($accessToken, $post_data_json);
			
			if (DBGLOG) Mage::log("exportFulfillment ending", null, 'fulfillment.log', true);
		}
	}
	
	public function sendFulfillment($accessToken, $postData)
	{
		if (DBGLOG) Mage::log("sendFulfillment started", null, 'fulfillment.log', true);
		
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
			Mage::log("Linc_Care HTTP Status $temp", null, 'fulfillment.log', true);
			if ($response->getStatus() == 201)
			{
				$temp = $response->getBody();
				Mage::log("Linc_Care $temp", null, 'fulfillment.log', true);
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
		if (DBGLOG) Mage::log("connectToLincCare started", null, 'fulfillment.log', true);
		
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
		if (DBGLOG) Mage::log("buildJson started", null, 'fulfillment.log', true);
		
		$track = $observer->getEvent()->getTrack();
		if (DBGLOG) Mage::log("buildJson Got order", null, 'fulfillment.log', true);
		$store = $track->getStore();
		if (DBGLOG) Mage::log("buildJson Got order", null, 'fulfillment.log', true);
		$shipment  = $track->getShipment();
		if (DBGLOG) Mage::log("buildJson Got order", null, 'fulfillment.log', true);
		$order  = $shipment->getOrder();
		if (DBGLOG) Mage::log("buildJson Got order", null, 'fulfillment.log', true);
		$carrier = $order->getShippingCarrier();
		if (DBGLOG) Mage::log("buildJson Got order", null, 'fulfillment.log', true);
		$store_id = $observer->getStoreId();
		if (DBGLOG) Mage::log("buildJson Got order", null, 'fulfillment.log', true);

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
		if (DBGLOG) Mage::log($postdata, null, 'fulfillment.log', true);

		if (DBGLOG) Mage::log("buildJson ended", null, 'fulfillment.log', true);
	
		return $postdata;
	}
}

?>
