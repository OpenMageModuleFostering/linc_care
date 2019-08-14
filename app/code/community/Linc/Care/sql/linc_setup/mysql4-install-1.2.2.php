<?php
require_once "Linc/Care/common.php";

$installer = $this;
$installer->startSetup();

$client = new Zend_Http_Client();
$protocol = SERVER_PROTOCOL;
$url = SERVER_PATH;
$client->setUri("$protocol://pub-api.$url/v1/install");

$client->setConfig(array(
    'maxredirects' => 0,
    'timeout'      => 30,
    'keepalive'    => true,
    'adapter'      => 'Zend_Http_Client_Adapter_Socket'));

$client->setMethod(Zend_Http_Client::POST);
$client->setHeaders(array('Content-Type' => 'application/json'));

$resource = Mage::getSingleton('core/resource');
$read = $resource->getConnection('core_read');
$configDataTable = $read->getTableName('core_config_data');

$dataorder = array(
    'ecommerce' => 'magento',
    'product' => 'Linc Care Extension',
    'version' => (string) Mage::getConfig()->getNode()->modules->Linc_Care->version,
    'store_name' => Mage::app()->getStore()->getName(),
    'general_name' => Mage::getStoreConfig('trans_email/ident_general/name'),
    'general_email' => Mage::getStoreConfig('trans_email/ident_general/email'),
    'sales_name' => Mage::getStoreConfig('trans_email/ident_sales/name'),
    'sales_email' => Mage::getStoreConfig('trans_email/ident_sales/email'),
    'support_name' => Mage::getStoreConfig('trans_email/ident_support/name'),
    'support_email' => Mage::getStoreConfig('trans_email/ident_support/email'),
    'url' => Mage::app()->getStore()->getHomeUrl(),
    'phone' => Mage::getStoreConfig('general/store_information/phone'));
		
$postdata = json_encode($dataorder);
$client->setRawData($postData, 'application/json');
$response = $client->request();

$installer->endSetup();
?>
