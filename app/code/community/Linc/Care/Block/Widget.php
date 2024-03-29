<?php
require_once "Linc/Care/common.php";

class Linc_Care_Block_Widget  extends Mage_Core_Block_Abstract
{
    public $shop_id = null;

    protected function _toHtml()
    {
        $html = "";
        
        $resource = Mage::getSingleton('core/resource');
        $read = $resource->getConnection('core_read');
        $configDataTable = $read->getTableName('core_config_data');

        $order = $this->getOrder();
        if (is_null($order))
        {
            $resource = Mage::getSingleton('core/resource');
            $read = $resource->getConnection('core_read');
            $configDataTable = $read->getTableName('core_config_data');

            # get shop_id
            $shop_id = '';
            $select = $read->select()
                ->from(array('cd'=>$configDataTable))
                ->where('cd.scope=?', 'store')
                ->where("cd.path=?", 'linc_shop_id');
            $rows = $read->fetchAll($select);

            if (count($rows) > 0)
            {
                $shop_id = $rows[0]['value'];
            }
    
            # get landing page
            $page_id = '';
            $select = $read->select()
                ->from(array('cd'=>$configDataTable))
                ->where('cd.scope=?', 'store')
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
                $page = '$protocol://care.$url';
            }
    
            $query = "shop_id=".$shop_id;
            $html  = "<a href='$page/home?";
            $html .= $query."'><img src='$page/widget_image?";
            $html .= $query."&v=2&widget=0'></a>";
        }
        else
        {
            $store_id = $order->getStoreId();
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
            $page_id = '';
            $select = $read->select()
                ->from(array('cd'=>$configDataTable))
                ->where('cd.scope=?', 'store')
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
                $page = '$protocol://care.$url';
            }
    
            $items = $order->getItemsCollection();

            $query = "shop_id=" . urlencode($shop_id);
            $query .= "&o="     . urlencode($order->getIncrementId());
            $query .= "&e="     . urlencode($order->getCustomerEmail());
            $query .= "&osi="   . urlencode($order->getIncrementId());
            
            $baseurl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/product';
            foreach ($items as $item)
            {
                if ($item && $item->getProduct())
                {
                    $product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());
                    if ($product->isVisibleInSiteVisibility())
                    {
                        $imgurl = $baseurl.$product->getImage();

                        $query .= "&q=".$item->getQtyOrdered();
                        $query .= "&p=".$product->getId();
                        $query .= "&pp=".$item->getPrice();
                        $query .= "&w=".$item->getWeight();
                        $query .= "&i=".urlencode($imgurl);
                        $query .= "&n=".urlencode($item->getName());
                    }
                }
            }

            $s_addr = $order->getShippingAddress();
            if ($s_addr == null)
            {
                /* use billing address for shipping address when the purchase is a download. */
                $s_addr = $order->getBillingAddress();
            }

            $query .= "&a1=" . urlencode($s_addr->getStreet1());
            $query .= "&a2=" . urlencode($s_addr->getStreet2());
            $query .= "&au=" . urlencode($s_addr->getCountry());
            $query .= "&ac=" . urlencode($s_addr->getCity());
            $query .= "&as=" . urlencode($s_addr->getRegion());
            $query .= "&az=" . urlencode($s_addr->getPostcode());
            $query .= "&fn=" . urlencode($order->getCustomerFirstname());
            $query .= "&ln=" . urlencode($order->getCustomerLastname());
            $query .= "&g="  . urlencode($order->getGrandTotal());
            $query .= "&pd=" . urlencode($order->getUpdatedAt('long'));
            $query .= "&ph=" . urlencode($s_addr->getTelephone());
            $query .= "&source=email";
            $query .= "&viewer=" . urlencode($order->getCustomerEmail());
            $query .= "&v=2";

            $protocol = SERVER_PROTOCOL;
            $url = SERVER_PATH;
            $html  = "<img src='$page/user_activity?";
            $html .= $query . "&activity=widget_impression' border='0' height='1' width='1'>";
            $html .= "<a href='$page/home?";
            $html .= "$query'><img src='$page/widget_image?";
            $html .= "$query&widget=0'></a>";

            //Mage::log("Widget complete - $html", null, 'order.log', true);
        }

        return $html;
    }
}

?>
