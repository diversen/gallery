<?php
/*
    public function displayItem ($id){
        if (isset($_POST['add_order'])){
            $add_order = filter_var($_POST['add_order'], FILTER_VALIDATE_INT);
        } else if (isset($_COOKIE['order_items'][$id])){
            $order_items = unserialize($_COOKIE['order_items']);
            if(isset($order_items[$id]))
                $add_order = $order_items[$id];
            else
                $add_order = 0;
        } else {
            $add_order = 0;
        }

        if ($add_order == 0){
            $add_order = '';
        }

        $val = $this->getItem($id);
        print "<hr id=\"thick_hr\" />";
        print $val['item_name'];
        print "<hr id=\"thick_hr\" />";
        print "<form name=\"bla\" method =\"post\">\n";
        print "Enter Amount: <input id=\"order_amount\" maxlength=\"1\" name=\"add_order\" align=\"left\" size=\"1\" value=\"$add_order\" />\n";
        print "<input type =\"submit\" name = \"order_submit\" value=\"Add\" /> ";
        print lang::translate('Price') . ' ' . $val['price'] . ' ' . lang::translate('locale.amount');
        print "</form>\n";
        print "<hr id=\"thick_hr\" />";
        print "<div id =\"order_item\">\n";
        print $link = create_image_link("/order/item/$val[id]", "/order/download/$val[id]/full", lang::translate('Add To Cart'));
        print "</div>\n";
        print $val['item_description'];
        //print $link = create_link('/order/item', $val['item_name']);
        if (isset($_SESSION['admin'])){
            print "<br />" . $link = create_link("/order/edit/$val[id]", lang::translate('Edit'));
            print " | " . $link = create_link("/order/delete/$val[id]", lang::translate('Delete'));
        }
    }*/