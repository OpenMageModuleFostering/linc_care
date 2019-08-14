<?php
require_once "Linc/Care/common.php";

class Linc_Care_Block_Url extends Mage_Adminhtml_Block_System_Config_Form_Field
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
        
        # get url
        $url = '';
        $select = $read->select()
            ->from(array('cd'=>$configDataTable))
            ->where('cd.scope=?', 'store')
            ->where("cd.scope_id=?", $store_id)
            ->where("cd.path=?", 'linc_url');
        $rows = $read->fetchAll($select);

        if (count($rows) > 0)
        {
            $url = $rows[0]['value'];
        }
        else
        {
            $url = Mage::getStoreConfig('web/unsecure/base_url');
        }
        
        $disabled = "";
        if ($shop_id != "")
        {
            $disabled = "disabled";
        }

        if (DEBUG) Mage::log("Url - $store_id; $url; $shop_id", null, 'register.log', true);

        $html = parent::_getElementHtml($element);
        $html .= "<input type=text class='input-text required-entry validate-url' id=linccaresection_linccaregroup_url maxlength=2000 value='$url' $disabled />";
        
        return $html;
    }
}

?>
