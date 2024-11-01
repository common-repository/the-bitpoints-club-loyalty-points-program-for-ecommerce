<?php
/*
 * WooCommerce Hooks
 */
 //Cart - Use points 
function BitPointsClub_WooCommerce_custom_cart_field( $checkout ) {
    //Logged in
    if(BitPointsClub_loggedin()) {
    
        //Get updated points balance
	    $object = BitPointsClub_API_RefreshCustomer($_SESSION['bitPoints_CustomerId']);
	    if(isset($object) && property_exists($object, 'customer_id') && $object->customer_id > 0) 
            BitPointsClub_UpdateSession($object);

        //Has minimum points value
	    $Min_Points_Value = get_option( 'BitPointsClub_Min_Points_Value' );
        if(!is_numeric($Min_Points_Value) || $Min_Points_Value == 0) $Min_Points_Value = 10;
        $Cart_Use_Points_Text = get_option( 'BitPointsClub_Cart_Use_Points_Text' );
        if(!isset($Cart_Use_Points_Text) || $Cart_Use_Points_Text == "") $Cart_Use_Points_Text = "Use Points?";
	    $Cart_Insufficient_Points_Text = get_option( 'BitPointsClub_Cart_Insufficient_Points_Text' );
        if(!isset($Cart_Insufficient_Points_Text) || $Cart_Insufficient_Points_Text == "") $Cart_Insufficient_Points_Text = "Use points? Sorry, you do not have enough points to redeem yet";

        if(isset($_SESSION['bitPoints_CustomerValue']) && (float)$_SESSION['bitPoints_CustomerValue'] >= (float)$Min_Points_Value) {
            echo '<div class="bitpoints-use-points-header bitpoints-use-points-header-default">
            <form id="BitPointsClub_UsePoints_form" method="post">
            <label style="display: block;">
	            <input 
                    type="checkbox" 
                    id="BitPointsClub_UsePoints" 
                    name="BitPointsClub_UsePoints" 
                    value="1"
                    '.(isset($_SESSION['bitPoints_UsePoints']) && (bool)$_SESSION['bitPoints_UsePoints'] ? 'checked' : '').' 
                    onchange="document.getElementById(\'BitPointsClub_UsePoints_IsSet\').value = (document.getElementById(\'BitPointsClub_UsePoints\').checked ? \'true\' : \'false\'); document.getElementById(\'BitPointsClub_UsePoints_form\').action=document.URL;document.getElementById(\'BitPointsClub_UsePoints_form\').submit();" />
	            <span class="bitpoints-use-points-checkbox">'.$Cart_Use_Points_Text.'</span>
            </label>
            <input type="hidden" name="BitPointsClub_UsePoints_IsSet" id="BitPointsClub_UsePoints_IsSet" />
            </form>
        </div>';

        //Else message
        } else 
            echo '<div class="bitpoints-use-points-header">'.$Cart_Insufficient_Points_Text.'</div>';
    }
}

//Cart - Process
function BitPointsClub_WooCommerce_cart_field_process() { 
    BitPointsClub_API_ErrorLog("BitPointsClub_WooCommerce_cart_field_process"); 
    //Logged in
    if(BitPointsClub_loggedin()) {  
        BitPointsClub_API_ErrorLog("logged in");
        if(isset($_POST['BitPointsClub_UsePoints_IsSet']) && $_POST['BitPointsClub_UsePoints_IsSet'] == "true") {
            $_SESSION['bitPoints_UsePoints'] = true;            
            BitPointsClub_API_ErrorLog("bitPoints_UsePoints true");
        } else if(isset($_POST['BitPointsClub_UsePoints_IsSet']) && $_POST['BitPointsClub_UsePoints_IsSet'] == "false") {
            $_SESSION['bitPoints_UsePoints'] = false;
            BitPointsClub_API_ErrorLog("bitPoints_UsePoints false");
        }
    } else {
        BitPointsClub_API_ErrorLog("Not logged in");
        $_SESSION['bitPoints_UsePoints'] = false;
    }
}

//Cart - Total calculation
function BitPointsClub_WooCommerce_calculate_cart_total( $cart_object ) {    
    global $woocommerce;
    $Points_Text = get_option( 'BitPointsClub_Points_Text' );
    if(!isset($Points_Text) || $Points_Text == "") $Points_Text = "Points";
    BitPointsClub_API_ErrorLog("BitPointsClub_WooCommerce_calculate_cart_total"); 
     
    //Remove if found
    $fees = $cart_object->get_fees();
    $newfees = array();
    foreach ($fees as $fee) {
        if (strrpos($fee->name, $Points_Text, -strlen($fee->name)) !== false) {} //Don't add Points to new fees array
        else {$newfees[] = $fee;}
    }
    WC()->session->set('fees', $newfees);

    //Logged in
    if(BitPointsClub_loggedin()) {   
        BitPointsClub_API_ErrorLog("BitPointsClub_loggedin");    

        //Add if set    
        BitPointsClub_WooCommerce_cart_field_process();    
        if(isset($_SESSION['bitPoints_UsePoints']) && (bool)$_SESSION['bitPoints_UsePoints']) {
            BitPointsClub_API_ErrorLog("bitPoints_UsePoints");    
            $pointsVals = "";
            $pointsBals = "";
            if(isset($_SESSION['bitPoints_CustomerValue']))  $pointsVals = $_SESSION['bitPoints_CustomerValue'];            
            if(isset($_SESSION['bitPoints_CustomerBalance'])) $pointsBals = $_SESSION['bitPoints_CustomerBalance'];
            $pointsVal = 0;
            $pointsBal = 0;
            if($pointsVals != "") $pointsVal = (float)$pointsVals;
            if($pointsBals != "") $pointsBal = (int)$pointsBals;
            $rate = 0;
            if($pointsVal > 0) $rate = round($pointsBal / $pointsVal, 0);
            $pointsUsed = 0;
            if($cart_object->cart_contents_total < $pointsVal) $pointsVal = $cart_object->cart_contents_total;
            if($rate > 0) $pointsUsed = round($pointsVal * $rate, 0);            
	        $setting = get_option( 'BitPointsClub_Min_Points_Value' );
            if(!is_numeric($setting) || $setting == 0) $setting = 10;

            BitPointsClub_API_ErrorLog("pointsVal: $pointsVal BitPointsClub_Min_Points_Value: $setting");    
            if(!empty($pointsVal) && $pointsVal != 0 && $pointsVal >= $setting) {
                $pointsVal *= -1; // convert positive to negative fees
                $woocommerce->cart->add_fee($Points_Text.' ('.number_format($pointsUsed).')', $pointsVal, true, ''); // add negative points value
                $cart_object->cart_contents_total = $cart_object->cart_contents_total + $pointsVal;
            } else
                $_SESSION['bitPoints_UsePoints'] = false;
        }
    }
}

//Checkout - Process
function BitPointsClub_WooCommerce_checkout_process() { 
    global $woocommerce;
    $Points_Text = get_option( 'BitPointsClub_Points_Text' );
    if(!isset($Points_Text) || $Points_Text == "") $Points_Text = "Points";

    //Logged in
    if(BitPointsClub_loggedin()) {  
        //Use points?
        if(isset($_SESSION['bitPoints_UsePoints']) && (bool)$_SESSION['bitPoints_UsePoints']) {

            //Get updated points balance
	        $object = BitPointsClub_API_RefreshCustomer($_SESSION['bitPoints_CustomerId']);
	        if(isset($object) && property_exists($object, 'customer_id') && $object->customer_id > 0) {
                BitPointsClub_UpdateSession($object);
                
                //Find points discount
                $cart = $woocommerce->cart;
                $pointsVal = $object->balance + 1; //fail value if not found
                $fees = $cart->get_fees();
                foreach ($fees as $fee) {
                    if (strrpos($fee->name, $Points_Text, -strlen($fee->name)) !== false)
                        $pointsVal = (float)$fee->amount;
                }
                
                if($pointsVal > $object->value)
                    wc_add_notice( __( $Points_Text.' balance has changed since you started the checkout process, please update your cart.' ), 'error' );
	        } else
                wc_add_notice( __( 'Unable to validate '.$Points_Text.' balance.' ), 'error' );
        }

    //check if points has been used an dsession timed out
    } else { 
        
        //Remove if found   
        $cart_object = $woocommerce->cart;
        $pointsUsed = false;        
        $fees = $cart_object->get_fees();
        $newfees = array();
        foreach ($fees as $fee) {
            if (strrpos($fee->name, $Points_Text, -strlen($fee->name)) !== false) { $pointsUsed = true; }
            else {$newfees[] = $fee;}
        }
        WC()->session->set('fees', $newfees);
        if($pointsUsed)
            wc_add_notice( __( 'Unable to validate '.$Points_Text.' balance.' ), 'error' );
    }
}

//Checkout - Complete
function BitPointsClub_WooCommerce_order_status_changed($order_id, $old_status, $new_status) {   
    global $wpdb;
    global $current_user;
    BitPointsClub_API_ErrorLog("BitPointsClub_WooCommerce_order_status_changed");
    if(!$order_id) return;  
    BitPointsClub_API_ErrorLog("orderid: $order_id");  
    $Points_Text = get_option( 'BitPointsClub_Points_Text' );
    if(!isset($Points_Text) || $Points_Text == "") $Points_Text = "Points";    
	$PointsStatus = get_option( 'BitPointsClub_Assign_Points_Status' );
    if(!isset($PointsStatus) || $PointsStatus == "") $PointsStatus = "Completed";
    
    //processing or completed from pending
    BitPointsClub_API_ErrorLog("old_status: $old_status new_status: $new_status");  
    if(strtoupper($old_status) == strtoupper($PointsStatus)) {
        $order = new WC_Order($order_id);
        $fees = $order->get_fees();
        $current_user = wp_get_current_user();

        //just be sure the order is for the logged in user
        $object = null;
        $customerid = 0;
        if(isset($_SESSION['bitPoints_CustomerId']) && (int)$_SESSION['bitPoints_CustomerId'] > 0) 
            $customerid = (int)$_SESSION['bitPoints_CustomerId'];
        if($current_user->ID != $order->user_id) {
	        $user_email = $wpdb->get_var($wpdb->prepare("SELECT user_email FROM $wpdb->users WHERE ID = '%s'", $order->user_id)); 
	        $password = $wpdb->get_var($wpdb->prepare("SELECT user_pass FROM $wpdb->users WHERE ID = '%s'", $order->user_id));  
            $display_name = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM $wpdb->users WHERE ID = '%s'", $order->user_id)); 
	        $object = BitPointsClub_API_FindCustomer($user_email, $password, $display_name);

	        if(isset($object) && property_exists($object, 'customer_id') && $object->customer_id > 0) 
                $customerid = $object->customer_id;
        }
        BitPointsClub_API_ErrorLog("customerid: $customerid");  

        //Post Earn
        if(isset($customerid) && $customerid > 0)
            $object = BitPointsClub_API_Earn($customerid, $order->get_total(), "Order #".$order_id);

        //post Redeem?
        $pointsVal = 0;
        foreach ($fees as $fee) {
            if (strrpos($fee['name'], $Points_Text, -strlen($fee['name'])) !== false) 
                $pointsVal = abs((float)$fee['line_total']);
        }
        if($pointsVal > 0 && isset($customerid) && $customerid > 0) 
            $object = BitPointsClub_API_Redeem($customerid, $pointsVal, "Redemption for order #".$order_id);   
        
        //update customer balance
        if($current_user->ID == $order->user_id) {
            if(isset($object) && property_exists($object, 'customer_id') && $object->customer_id > 0) BitPointsClub_UpdateSession($object);
        }
    }
    
    //cancelled or refunded or failed
    else if(strtoupper($new_status) == strtoupper("cancelled") || strtoupper($new_status) == strtoupper("refunded")) {
        $order = new WC_Order($order_id);
        $fees = $order->get_fees();
        $current_user = wp_get_current_user();

        //find order user
        $object = null;
        $customerid = 0;
	    $user_email = $wpdb->get_var($wpdb->prepare("SELECT user_email FROM $wpdb->users WHERE ID = '%s'", $order->user_id)); 
	    $password = $wpdb->get_var($wpdb->prepare("SELECT user_pass FROM $wpdb->users WHERE ID = '%s'", $order->user_id));  
        $display_name = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM $wpdb->users WHERE ID = '%s'", $order->user_id)); 
	    $object = BitPointsClub_API_FindCustomer($user_email, $password, $display_name);
        if(isset($object) && property_exists($object, 'customer_id') && $object->customer_id > 0) {
            $customerid = $object->customer_id;
            
            //Post Credit
            $refund = $order->get_total_refunded();
            //if($pointsVal == 0) $pointsVal = $order->get_total();
            if(isset($customerid) && $customerid > 0)
                $object = BitPointsClub_API_Credit($customerid, abs($refund), "Order #".$order_id);

            //points redeemed with order?
            $pointsVal = 0;
            foreach ($fees as $fee) {
                if (strrpos($fee['name'], $Points_Text, -strlen($fee['name'])) !== false)
                    $pointsVal = abs((float)$fee['line_total']);
            }
            if($pointsVal > 0) {
                $object = BitPointsClub_API_Refund($customerid, $pointsVal, $Points_Text." refund for order #".$order_id);   
        
                //update customer balance if same session
                if($current_user->ID == $order->user_id) 
                    if(isset($object) && property_exists($object, 'customer_id') && $object->customer_id > 0) BitPointsClub_UpdateSession($object);
            }
        }
    }
}

function BitPointsClub_after_my_account() {
    if(BitPointsClub_loggedin()) {
        $Transaction_History_Fields = get_option( 'BitPointsClub_Transaction_History_Fields' );
        if(trim(strlen($Transaction_History_Fields)) > 0) {
            $Points_Text = get_option( 'BitPointsClub_Points_Text' );
            if(!isset($Points_Text) || $Points_Text == "") $Points_Text = "Points";

            echo '</article><header class="entry-header"><h1 class="entry-title">'.$Points_Text.' Points Due to Expire</h1></header><article class="post-5 page type-page status-publish hentry">'.do_shortcode('[bitpoints-due-to-expire]');        
            echo '</article><header class="entry-header"><h1 class="entry-title">'.$Points_Text.' Last 20 Transactions</h1></header><article class="post-5 page type-page status-publish hentry">'.do_shortcode('[bitpoints-transaction-history]');
        }
    }
} 