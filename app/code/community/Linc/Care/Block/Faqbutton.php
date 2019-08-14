<?php
 
class Linc_Care_Block_FAQButton extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        
        $url = "http://www.letslinc.com/business/";
        
        $html = $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setType('button')
                    ->setClass('scalable')
                    ->setLabel('Find out more...')
                    ->setOnClick("setLocation('$url')")
                    ->toHtml();

        return $html;
    }
}

?>
