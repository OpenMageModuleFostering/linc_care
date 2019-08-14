<?php
require_once "Linc/Care/common.php";

class Linc_Care_CareController extends Mage_Adminhtml_Controller_Action
{
	public $client = null;
	public $queue = null;

    public function registerAction()
    {
        if (DBGLOG) Mage::log("Care::registerAction called", null, 'register.log', true);
        
        $url = $this->getRequest()->getParam('url');
        $email = $this->getRequest()->getParam('email');
        $password = $this->getRequest()->getParam('password');
        $confirm = $this->getRequest()->getParam('confirm');
        $ecommerce = $this->getRequest()->getParam('ecommerce');
        
		$post_data_json = $this->buildJson($url, $email, $password, $confirm, $ecommerce);
		$this->sendRegister($post_data_json);
	}
	
    public function preDispatch()
    {
        // override admin store design settings via stores section
        Mage::getDesign()
            ->setArea($this->_currentArea)
            ->setPackageName((string)Mage::getConfig()->getNode('stores/admin/design/package/name'))
            ->setTheme((string)Mage::getConfig()->getNode('stores/admin/design/theme/default'))
        ;
        foreach (array('layout', 'template', 'skin', 'locale') as $type) 
        {
            if ($value = (string)Mage::getConfig()->getNode("stores/admin/design/theme/{$type}"))
            {
                Mage::getDesign()->setTheme($type, $value);
            }
        }

        $this->getLayout()->setArea($this->_currentArea);
        $_isValidFormKey = true;
        $_isValidSecretKey = true;
/*
        Mage::dispatchEvent('adminhtml_controller_action_predispatch_start', array());
        parent::preDispatch();
        $_keyErrorMsg = '';
        if (Mage::getSingleton('admin/session')->isLoggedIn()) 
        {
            if ($this->getRequest()->isPost()) 
            {
                $_isValidFormKey = $this->_validateFormKey();
                $_keyErrorMsg = Mage::helper('adminhtml')->__('Invalid Form Key. Please refresh the page.');
            }
            elseif (Mage::getSingleton('adminhtml/url')->useSecretKey()) 
            {
                $_isValidSecretKey = $this->_validateSecretKey();
                $_keyErrorMsg = Mage::helper('adminhtml')->__('Invalid Secret Key. Please refresh the page.');
            }
        }
*/
        if (!$_isValidFormKey || !$_isValidSecretKey) 
        {
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            $this->setFlag('', self::FLAG_NO_POST_DISPATCH, true);
            if ($this->getRequest()->getQuery('isAjax', false) || $this->getRequest()->getQuery('ajax', false)) 
            {
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                    'error' => true,
                    'message' => $_keyErrorMsg
                )));
            }
            else
            {
                $this->_redirect( Mage::getSingleton('admin/session')->getUser()->getStartupPageUrl() );
            }
            return $this;
        }

        if ($this->getRequest()->isDispatched()
            && $this->getRequest()->getActionName() !== 'denied'
            && !$this->_isAllowed())
        {
            $this->_forward('denied');
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            return $this;
        }

        if (!$this->getFlag('', self::FLAG_IS_URLS_CHECKED)
            && !$this->getRequest()->getParam('forwarded')
            && !$this->_getSession()->getIsUrlNotice(true)
            && !Mage::getConfig()->getNode('global/can_use_base_url')) 
        {
            //$this->_checkUrlSettings();
            $this->setFlag('', self::FLAG_IS_URLS_CHECKED, true);
        }
        
        if (is_null(Mage::getSingleton('adminhtml/session')->getLocale())) 
        {
            Mage::getSingleton('adminhtml/session')->setLocale(Mage::app()->getLocale()->getLocaleCode());
        }

        return $this;
    }

	
	public function sendRegister($postData)
	{
		if ($this->client == null)
		{
			$this->connectToLincCare();
			if ($this->client != null && $this->queue != null)
			{
				$sendQueue = $this->queue;
				unset($this->queue);
				
				foreach ($sendQueue as $data)
				{
					sendRegister($data);
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
	        # get store_id
            $resource = Mage::getSingleton('core/resource');
            $read = $resource->getConnection('core_read');
            $configDataTable = $read->getTableName('core_config_data');

            $store_id = '0';
            $select = $read->select()
                ->from(array('cd'=>$configDataTable))
                ->where("cd.path=?", 'linc_current_store');
            $rows = $read->fetchAll($select);

            if (count($rows) > 0)
            {
                $store_id = $rows[0]['value'];
            }

			$this->client->setRawData($postData, 'application/json');
			$response = $this->client->request();
			
			$temp = $response->getStatus();			
			$this->getResponse()->setHeader('HTTP/1.1', $temp);
			
			if (DBGLOG) 
			{
				Mage::log("Care::register HTTP Status $temp", null, 'register.log', true);
			}
			
			if ($temp < 400)
			{
				$temp = $response->getBody();
				if (DBGLOG) Mage::log("Care::register $temp", null, 'register.log', true);
				
				$array = Mage::helper('core')->jsonDecode($temp);
				
                # write values into core_config_data
                Mage::getConfig()->saveConfig('linc_url', $array['url'], 'store', $store_id);
                Mage::getConfig()->saveConfig('linc_email', $array['email'], 'store', $store_id);
                Mage::getConfig()->saveConfig('linc_password', $array['password'], 'store', $store_id);
                Mage::getConfig()->saveConfig('linc_confirm', $array['confirm'], 'store', $store_id);
                Mage::getConfig()->saveConfig('linc_shop_id', $array['store_id'], 'store', $store_id);
                Mage::getConfig()->saveConfig('linc_access_key', $array['access_key'], 'store', $store_id);
                
                $this->getResponse()->setHeader('Content-type', 'application/json', true);
                $this->getResponse()->setBody($temp);
			}
			else
			{
				$temp = $response->getBody();
				preg_match('/<pre class="exception_value">(.*?)<\/pre>/', $temp, $m);
				 
				if (count($m) >= 2)
				{
				    $this->getResponse()->setBody($m[1]);
				}
				else
				{
				    Mage::log("Care::register unknown error - $temp", null, 'register.log', true);
				    $this->getResponse()->setBody('The server had an unknown error. Please contact support@letslinc.com');
				}
			}
		}
	}
	
	public function connectToLincCare()
	{
		$this->client = new Zend_Http_Client();
        $protocol = SERVER_PROTOCOL;
        $url = SERVER_PATH;
		$this->client->setUri("$protocol://pub-api.$url/v1/register");
		
		$this->client->setConfig(array(
            'maxredirects' => 0,
            'timeout'      => 30,
            'keepalive'    => true,
            'adapter'      => 'Zend_Http_Client_Adapter_Socket'));
	    
		$this->client->setMethod(Zend_Http_Client::POST);
		$this->client->setHeaders(array(
			'Content-Type' => 'application/json'));
	}
		
	public function buildJson($url, $email, $password, $confirm, $ecommerce)
	{
		$dataorder = array(
			'email' => $email,
			'password' => $password,
			'url' => $url,
			'confirm' => $confirm,
			'ecommerce' => $ecommerce
		);
		
		$postdata = json_encode($dataorder);

		if (DBGLOG) Mage::log("Care::register buildJson ended", null, 'register.log', true);
		if (DBGLOG) Mage::log($postdata, null, 'register.log', true);
		
		return $postdata;
	}
}

?>
