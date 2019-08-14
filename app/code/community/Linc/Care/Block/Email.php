<?php
require_once "Linc/Care/common.php";

class Linc_Care_Block_Email extends Mage_Adminhtml_Block_System_Config_Form_Field
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
	    
        $disabled = "";
        if ($shop_id != "")
        {
            $disabled = "disabled";
        }
        $email = str_replace("'", "&#39;", $email);

        $html = parent::_getElementHtml($element);
        $html .= "<input type=text class='input-text required-entry validate-email' id=linccaresection_linccaregroup_email maxlength=256 value='$email' $disabled />";

        return $html;
    }
}

?>
