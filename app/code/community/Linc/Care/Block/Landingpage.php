<?php
require_once "Linc/Care/common.php";

class Linc_Care_Block_Landingpage extends Mage_Adminhtml_Block_System_Config_Form_Field
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
        
        # get landing page
        $page = '';
        $select = $read->select()
            ->from(array('cd'=>$configDataTable))
            ->where('cd.scope=?', 'store')
            ->where("cd.scope_id=?", $store_id)
            ->where("cd.path=?", 'linc_landing_page');
        $rows = $read->fetchAll($select);

        if (count($rows) > 0)
        {
            $page = $rows[0]['value'];
        }
        else
        {
            $protocol = SERVER_PROTOCOL;
            $url = SERVER_PATH;
            $page = "$protocol://care.$url";
        }
        
        $disabled = "";
        if ($shop_id != "")
        {
            $disabled = "disabled";
        }

        if (DBGLOG) Mage::log("Landing page - $store_id; $page $shop_id", null, 'register.log', true);
        $page = str_replace("'", "&#39;", $page);

        $html = parent::_getElementHtml($element);
        $html .= "<input type=text class='input-text required-entry validate-url' id=linccaresection_linccaregroup_landingpage maxlength=2000 value='$page' $disabled />";
        
        return $html;
    }
}

?>
