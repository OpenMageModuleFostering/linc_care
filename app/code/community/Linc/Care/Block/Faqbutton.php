<?php
require_once "Linc/Care/common.php";

class Linc_Care_Block_FAQButton extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        
        $url = SERVER_PATH;
        $url = "http://$url/business/";
        
        $html = $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setType('button')
                    ->setClass('scalable')
                    ->setLabel('Find out more...')
                    ->setOnClick("popWin('$url', '_blank')")
                    ->toHtml();
                    
        return $html;
    }
}

?>
