<?php
require_once "Linc/Care/common.php";

class Linc_Care_Block_Adminhtml_System_Config_Form_Register extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _construct()
    {
        if (DEBUG) Mage::log("construct called", null, 'register.log', true);

        parent::_construct();
        $this->setTemplate('care/system/config/register.phtml');
        
        $store_id = 0;
        if (strlen($code = Mage::getSingleton('adminhtml/config_data')->getStore())) // store level
        {
            $store_id = Mage::getModel('core/store')->load($code)->getId();
        }
        Mage::getConfig()->saveConfig('linc_current_store', $store_id, 'default', 0);
    }
    
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        if (DEBUG) Mage::log("getElementHtml called", null, 'register.log', true);
        
        return $this->_toHtml();
    }

    public function getAjaxRegisterUrl()
    {
        if (DEBUG) Mage::log("getAjaxRegisterUrl called", null, 'register.log', true);

        $url = Mage::app()->getStore()->getUrl('linccare/care/register');

        if (DEBUG) Mage::log("getAjaxRegisterUrl $url", null, 'register.log', true);

        return $url;
    }
    
    public function getMerchantOnboardUrl()
    {
        if (DEBUG) Mage::log("getMerchantOnboardUrl called", null, 'register.log', true);

        $protocol = SERVER_PROTOCOL;
        $path = SERVER_PATH;
        $url = "$protocol://care.$path/merchants";
        
        if (DEBUG) Mage::log("getMerchantOnboardUrl returning $url", null, 'register.log', true);

        return $url;
    }

    public function getButtonHtml()
    {
        if (DEBUG) Mage::log("getButtonHtml called", null, 'register.log', true);

        $resource = Mage::getSingleton('core/resource');
        $read = $resource->getConnection('core_read');
        $configDataTable = $read->getTableName('core_config_data');

        # get store_id
        $store_id = '1';
        $select = $read->select()
            ->from(array('cd'=>$configDataTable))
            ->where("cd.path=?", 'linc_current_store');
        $rows = $read->fetchAll($select);

        if (count($rows) > 0)
        {
            $store_id = $rows[0]['value'];
        }
        
        # get shop_id
        $shop_id = '';
        $select = $read->select()
            ->from(array('cd'=>$configDataTable))
            ->where('cd.scope=?', 'store')
            ->where("cd.scope_id=?", $store_id)
            ->where("cd.path=?", 'linc_shop_id');
        $rows = $read->fetchAll($select);

        if (count($rows) > 0)
        {
            $shop_id = $rows[0]['value'];
        }
        
        # get url
        $shop_url = '';
        $select = $read->select()
            ->from(array('cd'=>$configDataTable))
            ->where('cd.scope=?', 'store')
            ->where("cd.scope_id=?", $store_id)
            ->where("cd.path=?", 'linc_url');
        $rows = $read->fetchAll($select);

        if (count($rows) > 0)
        {
            $shop_url = $rows[0]['value'];
        }
        else
        {
            $shop_url = Mage::getStoreConfig('web/unsecure/base_url');
        }
        
        # get email
        $email = '';
        $select = $read->select()
            ->from(array('cd'=>$configDataTable))
            ->where('cd.scope=?', 'store')
            ->where("cd.scope_id=?", $store_id)
            ->where("cd.path=?", 'linc_email');
        $rows = $read->fetchAll($select);

        if (count($rows) > 0)
        {
            $email = $rows[0]['value'];
        }
        else
        {
            $email = Mage::getStoreConfig('trans_email/ident_support/email');
        }
	    
        $button = $this->getLayout()->createBlock('adminhtml/widget_button');
        if ($shop_id == "")
        {
            $button->setData(array(
                'id'        => 'linccare_register',
                'label'     => $this->helper('adminhtml')->__('Register'),
                'onclick'   => 'javascript:register(); return false;'));
        }
        else
        {
            $protocol = SERVER_PROTOCOL;
            $path = SERVER_PATH;
            $url = "$protocol://care.$path/merchants";

            $url .= "?magento_shop=" . $shop_id;
            $url .= "&magento_email=" . $email;
            $button->setData(array(
                'id'        => 'linccare_register',
                'label'     => $this->helper('adminhtml')->__('Log in'),
                'onclick'   => "popWin('$url', '_blank')"));
        }

        return $button->toHtml();
    }
 }

?>
