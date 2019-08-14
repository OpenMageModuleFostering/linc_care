<?php
require_once "Linc/Care/common.php";
 
class Linc_Care_Block_Note extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $element->setDisabled('disabled');
        
        $html = parent::_getElementHtml($element);
        $html .= "<p>Linc Care is a per-store extension. That means you need to select a store to configure. Set the Current Configuration Scope to the store you want to configure.</p>";
        
        return $html;
    }
}

?>
