<?php
require_once "Linc/Care/common.php";

class Linc_Care_Block_Confirm extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
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
        
        # get confirm
        $confirm = '';
        $select = $read->select()
            ->from(array('cd'=>$configDataTable))
            ->where('cd.scope=?', 'store')
            ->where("cd.scope_id=?", $store_id)
            ->where("cd.path=?", 'linc_confirm');
        $rows = $read->fetchAll($select);

        if (count($rows) > 0)
        {
            $confirm = $rows[0]['value'];
        }	    
        $disabled = "";
        if ($shop_id != "")
        {
            $disabled = "disabled";
        }
        
        $confirm = str_replace("'", "&#39;", $confirm);

        $html = parent::_getElementHtml($element);
        $html .= "<input type=password class='input-text required-entry validate-cpassword' id=linccaresection_linccaregroup_confirm maxlength=2000 value='$confirm' $disabled />";
        
        return $html;
    }
}

?>
