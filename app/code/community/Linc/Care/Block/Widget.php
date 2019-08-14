<?php

class Linc_Care_Block_Widget  extends Mage_Core_Block_Abstract
{
    public $storeId = null;

    protected function _toHtml()
    {
        $html = "";
        if ($this->storeId == null)
        {
            $this->storeId = Mage::getStoreConfig('general/store_information/enter_store_id');
        }

        if ($this->storeId != null)
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
                        $query .= "&i=".$imgurl;
                        $query .= "&n=".$item->getName();
                    }
                }

                $s_addr = $order->getShippingAddress();
                if ($s_addr == null)
                {
                    /* use billing address for shipping address when the purchase is a download. */
                    $s_addr = $order->getBillingAddress();
                }

                $query .= "&a1=".$s_addr->getStreet1();
                $query .= "&a2=".$s_addr->getStreet2();
                $query .= "&au=".$s_addr->getCountry();
                $query .= "&ac=".$s_addr->getCity();
                $query .= "&as=".$s_addr->getRegion();
                $query .= "&az=".$s_addr->getPostcode();
                $query .= "&fn=".$order->getCustomerFirstname();
                $query .= "&ln=".$order->getCustomerLastname();
                $query .= "&e=".$order->getCustomerEmail();
                $query .= "&g=".$order->getGrandTotal();
                $query .= "&o=".$order->getIncrementId();
                $query .= "&osi=".$order->getIncrementId();
                $query .= "&pd=".$order->getUpdatedAt('long');
                $query .= "&ph=".$s_addr->getTelephone();
                $query .= "&shop_id=".$this->storeId;
                $query .= "&source=email";
                $query .= "&viewer=".$order->getCustomerEmail();
                $query .= "&v=2";

                $html  = "<img src='https://care.letslinc.com/user_activity?";
                $html .= $query."&activity=widget_impression' border='0' height='1' width='1'>";
                $html .= "<a href='https://care.letslinc.com/home?";
                $html .= $query."'><img src='https://care.letslinc.com/widget_image?";
                $html .= $query."&widget=0'></a>";

                //Mage::log("Widget complete - $html", null, 'order.log', true);
            }
            else
            {
                $query .= "shop_id=".$this->storeId;
                $html  = "<a href='https://care.letslinc.com/home?";
                $html .= $query."'><img src='https://care.letslinc.com/widget_image?";
                $html .= $query."&widget=0'></a>";
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
