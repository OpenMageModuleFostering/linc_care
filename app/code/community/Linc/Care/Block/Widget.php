<?php
require_once "Linc/Care/common.php";

class Linc_Care_Block_Widget  extends Mage_Core_Block_Abstract
{
    public $shop_id = null;

    protected function _toHtml()
    {
        $html = "";
        
        $store_id = Mage::app->GetCurrentStore()->getId();
        $shop_id = Mage::getStoreConfig('linc_shop_id', $store_id);

        if ($shop_id != null)
        {
            $order = $this->getOrder();
            if ($order)
            {
                $items = $order->getItemsCollection();

                $query = "";
                $baseurl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/product';
                foreach ($items as $item)
                {
                    $product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());
                    if ($product->isVisibleInSiteVisibility())
                    {
                        $imgurl = $baseurl.$product->getImage();

                        if ($query != "")
                        {
                            $query .= "&";
                        }

                        $query .= "q=".$item->getQtyOrdered();
                        $query .= "&p=".$product->getId();
                        $query .= "&pp=".$item->getPrice();
                        $query .= "&w=".$item->getWeight();
                        $query .= "&i=".$this->urlencode($imgurl);
                        $query .= "&n=".$this->urlencode($item->getName());
                    }
                }

                $s_addr = $order->getShippingAddress();
                if ($s_addr == null)
                {
                    /* use billing address for shipping address when the purchase is a download. */
                    $s_addr = $order->getBillingAddress();
                }

                $query .= "&a1=".$this->urlencode($s_addr->getStreet1());
                $query .= "&a2=".$this->urlencode($s_addr->getStreet2());
                $query .= "&au=".$this->urlencode($s_addr->getCountry());
                $query .= "&ac=".$this->urlencode($s_addr->getCity());
                $query .= "&as=".$this->urlencode($s_addr->getRegion());
                $query .= "&az=".$this->urlencode($s_addr->getPostcode());
                $query .= "&fn=".$this->urlencode($order->getCustomerFirstname());
                $query .= "&ln=".$this->urlencode($order->getCustomerLastname());
                $query .= "&e=".$this->urlencode($order->getCustomerEmail());
                $query .= "&g=".$this->urlencode($order->getGrandTotal());
                $query .= "&o=".$this->urlencode($order->getIncrementId());
                $query .= "&osi=".$this->urlencode($order->getIncrementId());
                $query .= "&pd=".$this->urlencode($order->getUpdatedAt('long'));
                $query .= "&ph=".$this->urlencode($s_addr->getTelephone());
                $query .= "&shop_id=".$this->urlencode($shop_id);
                $query .= "&source=email";
                $query .= "&viewer=".$this->urlencode($order->getCustomerEmail());
                $query .= "&v=2";

                $protocol = SERVER_PROTOCOL;
                $url = SERVER_PATH;
                $html  = "<img src='$protocol://care.$url/user_activity?";
                $html .= $query."&activity=widget_impression' border='0' height='1' width='1'>";
                $html .= "<a href='$protocol://care.$url/home?";
                $html .= $query."'><img src='$protocol://care.$url/widget_image?";
                $html .= $query."&widget=0'></a>";

                //Mage::log("Widget complete - $html", null, 'order.log', true);
            }
            else
            {
                $protocol = SERVER_PROTOCOL;
                $url = SERVER_PATH;
                $query .= "shop_id=".$shop_id;
                $html  = "<a href='$protocol://care.$url/home?";
                $html .= $query."'><img src='$protocol://care.$url/widget_image?";
                $html .= $query."&v=2&widget=0'></a>";
            }
        }
        else
        {
            $html = "<p></p>";
        }

        return $html;
    }
}

?>
