<?php
/*
 * Hooks
 */
//login
function BitPointsClub_wp_login($user_login, $user) {  
    global $wpdb;
	$user_email = $wpdb->get_var($wpdb->prepare("SELECT user_email FROM $wpdb->users WHERE user_login = '%s'", $user_login)); 
	$password = $wpdb->get_var($wpdb->prepare("SELECT user_pass FROM $wpdb->users WHERE user_login = '%s'", $user_login));  
    $display_name = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM $wpdb->users WHERE user_login = '%s'", $user_login)); 
    
	$object = BitPointsClub_API_FindCustomer($user_email, $password, $display_name);
	if(isset($object) && property_exists($object, 'customer_id') && $object->customer_id > 0) 
        BitPointsClub_UpdateSession($object);
}
add_action('wp_login', 'BitPointsClub_wp_login', 10, 2);

//Register
function BitPointsClub_user_register($user_login ) { 
    global $wpdb;
	$user_email = $wpdb->get_var($wpdb->prepare("SELECT user_email FROM $wpdb->users WHERE user_login = '%s'", $user_login)); 
	$password = $wpdb->get_var($wpdb->prepare("SELECT user_pass FROM $wpdb->users WHERE user_login = '%s'", $user_login));  
    $display_name = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM $wpdb->users WHERE user_login = '%s'", $user_login)); 

	$object = BitPointsClub_API_FindCustomer($user_email, $password, $display_name);
	if(isset($object) && property_exists($object, 'customer_id') && $object->customer_id > 0) 
		BitPointsClub_UpdateSession($object);
}
add_action('user_register', 'BitPointsClub_user_register');

//Update profile
function BitPointsClub_profile_update($user_id, $old_user_data) {
    global $wpdb;
	$user_email = $wpdb->get_var($wpdb->prepare("SELECT user_email FROM $wpdb->users WHERE user_login = '%s'", $user_id)); 
	$password = $wpdb->get_var($wpdb->prepare("SELECT user_pass FROM $wpdb->users WHERE user_login = '%s'", $user_id));  
    $display_name = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM $wpdb->users WHERE user_login = '%s'", $user_id)); 

	if(BitPointsClub_loggedin()) {
		$object = BitPointsClub_API_FindCustomer((int)$_SESSION['bitPoints_CustomerId'], $user_email, $password, $display_name);
		if(isset($object) && property_exists($object, 'customer_id') && $object->customer_id > 0) 
			BitPointsClub_UpdateSession($object);
	}
	BitPointsClub_API_UpdateCustomer();
}
add_action('profile_update', 'BitPointsClub_profile_update', 10, 2);


/*
 * eCommerce Hooks
 */
//Initialize eCommerce
function BitPointsClub_init_eCommerce() {
    //settings_fields( 'BitPointsClub_Configuration' );
	$setting = get_option('BitPointsClub_ECommerce_Plugin');

    switch($setting) {
        //WooCommerce
        default:
            require_once('Init_WooCommerce.php'); 
            add_action('woocommerce_cart_totals_before_order_total', 'BitPointsClub_WooCommerce_custom_cart_field');
            add_action('woocommerce_calculate_totals', 'BitPointsClub_WooCommerce_calculate_cart_total');
            add_action('woocommerce_cart_updated', 'BitPointsClub_WooCommerce_cart_field_process');
            add_action('woocommerce_checkout_process', 'BitPointsClub_WooCommerce_checkout_process');
            add_action( 'woocommerce_order_status_changed', 'BitPointsClub_WooCommerce_order_status_changed', 10, 3 ); 
            add_action('woocommerce_before_my_account','BitPointsClub_after_my_account');
            break;
    }
}
add_action('init', 'BitPointsClub_init_eCommerce');