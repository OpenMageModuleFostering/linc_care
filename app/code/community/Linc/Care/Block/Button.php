<?php
 
class Linc_Care_Block_Button extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        
        $sid = Mage::getStoreConfig('general/store_information/enter_store_id', Mage::app()->getStore());
        $url = "";
        
        if ($sid == NULL or $sid == "") {
            $url = "https://care.letslinc.com/merchants/register";
        }
        else {
            $url = "https://care.letslinc.com/merchants/";
        }
        
        Mage::log("Linc_Care button url $url", null, 'order.log', true);

        $html = $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setType('button')
                    ->setClass('scalable')
                    ->setLabel('Go to Linc Care')
                    ->setOnClick("setLocation('$url')")
                    ->toHtml();

        return $html;
    }
}

?>
