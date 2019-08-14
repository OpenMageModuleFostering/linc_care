<?php
require_once "Linc/Care/common.php";

class Linc_Care_Model_Fulfillmentobserver
{
	public $client = null;
	public $queue = null;

	public $store_id = null;
	public $accessToken = null;
	
	public $track = null;
	public $store = null;
	public $shipment = null;
	public $order = null;
	public $carrier = null;
	
	public function exportFulfillment(Varien_Event_Observer $observer)
	{
		if (DBGLOG) Mage::log("Fulfillmentobserver::exportFulfillment started", null, 'fullfillment.log', true);
		
		$this->initializeObjects($observer);
		if ($this->accessToken != null)
		{
			if (DBGLOG) Mage::log("Fulfillmentobserver::exportFulfillment building the JSON", null, 'fullfillment.log', true);
			$post_data_json = $this->buildJson($observer);
			
			if (DBGLOG) Mage::log("Fulfillmentobserver::exportFulfillment making the API call", null, 'fullfillment.log', true);
			$this->sendFulfillment($post_data_json);
			
			if (DBGLOG) Mage::log("Fulfillmentobserver::exportFulfillment ending", null, 'fullfillment.log', true);
		}
	}
	
	public function initializeObjects($observer)
	{
		if (DBGLOG) Mage::log("Fulfillmentobserver::initializeObjects getting the track", null, 'fullfillment.log', true);
		$this->track = $observer->getTrack();
		
		if (DBGLOG) Mage::log("Fulfillmentobserver::initializeObjects getting the store", null, 'fullfillment.log', true);
		$this->store = $this->track->getStore();
			
		if (DBGLOG) Mage::log("Fulfillmentobserver::initializeObjects getting shipment", null, 'fullfillment.log', true);
		$this->shipment  = $this->track->getShipment();
		
		if (DBGLOG) Mage::log("Fulfillmentobserver::initializeObjects getting order", null, 'fullfillment.log', true);
		$this->order  = $this->shipment->getOrder();
		
		if (DBGLOG) Mage::log("Fulfillmentobserver::initializeObjects getting carrier", null, 'fullfillment.log', true);
		$this->carrier = $this->order->getShippingCarrier();

		if (DBGLOG) Mage::log("Fulfillmentobserver::initializeObjects getting the store id", null, 'fullfillment.log', true);
		$this->store_id = $this->store->getConfig('general/store_information/enter_store_id');

		if (DBGLOG) Mage::log("Fulfillmentobserver::exportFulfillment getting the token", null, 'fullfillment.log', true);
	    $this->accessToken = Mage::getStoreConfig('linc_access_key', $store_id);
	}
	
	public function sendFulfillment($postData)
	{
		if (DBGLOG) Mage::log("Fulfillmentobserver::sendFulfillment started", null, 'fullfillment.log', true);
		
		if ($this->client == null)
		{
			$this->connectToLincCare();
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
			//Mage::log("Linc_Care HTTP Status $temp", null, 'fullfillment.log', true);
			if ($response->getStatus() == 201)
			{
				$temp = $response->getBody();
				//Mage::log("Linc_Care $temp", null, 'fullfillment.log', true);
			}
			else
			{
				$this->client = null;
				array_push($this->queue, $postData);
			}
		}
	}
	
	public function connectToLincCare()
	{
		if (DBGLOG) Mage::log("Fulfillmentobserver::connectToLincCare started", null, 'fullfillment.log', true);
		
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
			'Authorization' => 'Bearer '.$this->accessToken,
			'Content-Type' => 'application/json'));
	}
		
	public function buildJson(Varien_Event_Observer $observer)
	{
		if (DBGLOG) Mage::log("Fulfillmentobserver::buildJson started", null, 'fullfillment.log', true);
		
		$postdata = "";
		$CarrierCode = "";
	
		if ($this->carrier instanceof Mage_Usa_Model_Shipping_Carrier_Abstract)
		{
			$CarrierCode = $this->carrier->getCarrierCode();
		}
		
		$dataorder = array(
			'order_code' => $this->order->getIncrementId(),
			'carrier' => $CarrierCode,
			'tracking_number' => $this->track->getNumber(),
			'fulfill_date' => $this->shipment->getCreatedAt()
			);
	
		$postdata = json_encode($dataorder);
		if (DBGLOG) Mage::log($postdata, null, 'fullfillment.log', true);

		if (DBGLOG) Mage::log("buildJson ended", null, 'fullfillment.log', true);
	
		return $postdata;
	}
}

?>
