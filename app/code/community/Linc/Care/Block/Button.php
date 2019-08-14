<?php
 
class Linc_Care_Block_Button extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        
        /* This is necessary because the config data hasn't been loaded when this is run */
        $resource = Mage::getSingleton('core/resource');  
        $read = $resource->getConnection('core_read');  
        $select = "SELECT value FROM `core_config_data` WHERE path = 'general/store_information/enter_store_id' LIMIT 1";
        $sid = $read->fetchOne($select);

        $url = "https://care.letslinc.com/merchants";
        if ($sid == NULL or $sid == "")
        {
            $url .= "/register";
        }
        $url .= "?magento_shop=" . Mage::helper('core')->escapeHtml(Mage::getBaseUrl('web'));
        $url .= "&magento_email=" . Mage::helper('core')->escapeHtml(Mage::getStoreConfig('trans_email/ident_general/email'));
        
        Mage::log("Linc_Care button url $url", null, 'order.log', true);

        $button = $this->getLayout()->createBlock('adminhtml/widget_button');
        $button->setType('button');
        $button->setClass('scalable');
        $button->setLabel('Go to Linc Care');
        $button->setOnClick("setLocation('$url')");
        $html = $button->toHtml();

        return $html;
    }
}

?>
