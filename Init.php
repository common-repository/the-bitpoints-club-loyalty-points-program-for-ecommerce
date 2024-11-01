<?php
/*
Plugin Name: BitPoints Club
Plugin URI:  https://bitpoints.club/apiwordpresss_v1.html
Description: Integrate a wordpress site to the BitPoints.Club platform via the BitPoints.Club API.
Version:     1.0
Author:      BitPoints Club
Author URI:  https://bitpoints.club
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

require_once('BitPointsClubAPI.php'); 
require_once('Init_ShortCodes.php'); 
require_once('Init_Hooks.php'); 

/*
 * BitPoints Settings
 */
 //Update Customer
function BitPointsClub_UpdateSession($object) {
	$_SESSION['bitPoints_CustomerId'] = $object->customer_id;
	$_SESSION['bitPoints_CustomerBalance'] = $object->balance;
	$_SESSION['bitPoints_CustomerValue'] = $object->value;
}

 //logged in
function BitPointsClub_loggedin() {    
    global $wpdb;
    global $current_user;
      
    if (!is_user_logged_in()) {
        $_SESSION['bitPoints_CustomerId'] = null;
        $_SESSION['bitPoints_CustomerBalance'] = null;
        $_SESSION['bitPoints_CustomerValue'] = null;
        $_SESSION['bitPoints_Log'] = null;
        $_SESSION['bitPoints_UsePoints'] = null;
        $_SESSION['bitPoints_ProgramId'] = null;
        $_SESSION['bitPoints_History'] = null;        
        $_SESSION['bitPoints_DueToExpire'] = null;
    } else if(isset($_SESSION['bitPoints_CustomerId']) && (int)$_SESSION['bitPoints_CustomerId'] > 0
        && isset($_SESSION['bitPoints_ProgramId']) && (int)$_SESSION['bitPoints_ProgramId'] > 0) 
        return true;
    else if(!isset($_SESSION['bitPoints_CustomerId_checked'])) {
        $_SESSION['bitPoints_CustomerId_checked'] = true;

        $current_user = wp_get_current_user();
	    $user_email = $wpdb->get_var($wpdb->prepare("SELECT user_email FROM $wpdb->users WHERE user_login = '%s'", $current_user->user_login)); 
	    $password = $wpdb->get_var($wpdb->prepare("SELECT user_pass FROM $wpdb->users WHERE user_login = '%s'", $current_user->user_login));  
        $display_name = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM $wpdb->users WHERE user_login = '%s'", $current_user->user_login)); 
    
	    $object = BitPointsClub_API_FindCustomer($user_email, $password, $display_name);
	    if(isset($object) && property_exists($object, 'customer_id') && $object->customer_id > 0) {
            BitPointsClub_UpdateSession($object);
            if(isset($_SESSION['bitPoints_CustomerId']) && (int)$_SESSION['bitPoints_CustomerId'] > 0
                && isset($_SESSION['bitPoints_ProgramId']) && (int)$_SESSION['bitPoints_ProgramId'] > 0) 
                return true;
        }
    }
    return false;
}

//Admin menu
function BitPointsClub_admin_menu() {
    add_menu_page(
        'BitPoints Club Configuration',
        'BitPoints Club',
        'manage_options',
        'BitPointsClub-Quick-Access', 
        'BitPointsClub_quick_access_page',
        '',
        58
    );
    add_submenu_page( 
        'BitPointsClub-Quick-Access', 
        'BitPoints Club Quick Access',
        'Quick Access',
        'manage_options', 
        'BitPointsClub-Quick-Access', 
        'BitPointsClub_quick_access_page'
    );
    add_submenu_page( 
        'BitPointsClub-Quick-Access', 
        'BitPoints Club Configuration',
        'Configuration',
        'manage_options', 
        'BitPointsClub-Configuration', 
        'BitPointsClub_configuration_page'
    );
    add_submenu_page( 
        'BitPointsClub-Quick-Access', 
        'BitPoints Club Options',
        'Options',
        'manage_options', 
        'BitPointsClub-Options', 
        'BitPointsClub_options_page'
    );
    add_submenu_page( 
        'BitPointsClub-Quick-Access', 
        'BitPoints Club Manual Adjustments',
        'Manual Adjustments',
        'manage_options', 
        'BitPointsClub-Manual-Adjustments', 
        'BitPointsClub_manual_adjustments_page'
    );
}
add_action( 'admin_menu', 'BitPointsClub_admin_menu' );

//Admin icon
function BitPointsClub_set_custom_font_icon() {
?>
    <style type="text/css">
            /* for top level menu pages replace `{menu-slug}` with the slug name passed to `add_menu_page()` */
            #toplevel_page_BitPointsClub-Quick-Access .wp-menu-image:before {
                    font-family: BitPoints !important;
                    content: '\42' !important;
            }
    </style>
<?php
}
add_action('admin_head', 'BitPointsClub_set_custom_font_icon');

function BitPointsClub_enqueue_font() {
    wp_enqueue_style( 'BitPoints', plugins_url( 'BitPoints.css' , __FILE__ ), false, null );
}
add_action('wp_enqueue_scripts', 'BitPointsClub_enqueue_font');

//Register settings
function BitPointsClub_register_settings() { 
    //BitPointsClub_API_URL
	register_setting(
		'BitPointsClub_Configuration',
		'BitPointsClub_API_URL'
	);
	
    //BitPointsClub_API_KEY
	register_setting(
		'BitPointsClub_Configuration',
		'BitPointsClub_API_KEY'
	);
	
    //BitPointsClub_Program_Name
	register_setting(
		'BitPointsClub_Configuration',
		'BitPointsClub_Program_Name'
	);
	
    //BitPointsClub_ECommerce_Plugin
	register_setting(
		'BitPointsClub_Configuration',
		'BitPointsClub_ECommerce_Plugin'
	);
	
    //BitPointsClub_Min_Points_Value
	register_setting(
		'BitPointsClub_Options',
		'BitPointsClub_Min_Points_Value'
	);
	
    //BitPointsClub_Transaction_History_Fields
	register_setting(
		'BitPointsClub_Options',
		'BitPointsClub_Transaction_History_Fields'
	);
	
    //BitPointsClub_Transaction_Type_Translations
	register_setting(
		'BitPointsClub_Options',
		'BitPointsClub_Transaction_Type_Translations'
	);
	
    //BitPointsClub_Points_Text
	register_setting(
		'BitPointsClub_Options',
		'BitPointsClub_Points_Text'
	);
	
    //BitPointsClub_Cart_Use_Points_Text
	register_setting(
		'BitPointsClub_Options',
		'BitPointsClub_Cart_Use_Points_Text'
	);
	
    //BitPointsClub_Cart_Insufficient_Points_Text
	register_setting(
		'BitPointsClub_Options',
		'BitPointsClub_Cart_Insufficient_Points_Text'
	);
	
    //BitPointsClub_Cart_Insufficient_Points_Text
	register_setting(
		'BitPointsClub_Options',
		'BitPointsClub_Assign_Points_Status'
	);
}
add_action( 'admin_init', 'BitPointsClub_register_settings' );

//BitPointsClub_Configuration section callback function
function BitPointsClub_Configuration_callback_function() {
	echo 
		'<p>
			Integrate a wordpress site to the BitPoints.Club platform via the BitPoints.Club API.
		</p>
		<p>
			<b>Prerequisites</b><br/>
			<ol>
                <li><b>A BitPoints.Club account API Key</b><br>this can be obtained by registering for a free demo account (up to 5 customers) here: <a href="https://bitpoints.club" target="_new">https://bitpoints.club</a> (select the Sign Up menu item).</li>
                <li><b>One of the following supported eCommerce WordPress plugins</b><br>
                    WooCommerce
                </li>
            </ol>
		</p>
		<p>
			<b>Documentation</b><br>
            <a href="https://bitpoints.club/apiwordpresss_v1.html" target="_new">See our WordPress documentation here</a>
		</p>
		<p>
			<b>Promotions</b><br>
            Promotions must be setup from the <a href="https://bitpoints.club/" target="_new">https://bitpoints.club</a> site
		</p>';
}

//BitPointsClub_quick_access section callback function
function BitPointsClub_quick_access_callback_function($email) {

	echo 
		'<p>
            <b>Quick Access</b>
        </p>
        <p>
            Automatic integration of your WordPress site to the BitPoints.Club platform. Clicking "Automatic Configuration" below will register the email address below for a free demo account with BitPoints.Club and automatically configure the BitPoints Club plugin settings.
        </p>
        <p>
			<b>Please note</b><br><br>
            To add a points balance on the site please use the short code <b>[bitpoints-points-balance] (<a href="https://bitpoints.club/apiwordpresss_v1.html" target="_new">see full documentation here</a>)</b><br><br>
            Demo accounts allow full access to the BitPoints.Club platform but limit you to 5 customers to let you expore the platform for free, <a href="https://bitpoints.club#pricing" target="_new">see our standard out-the-box pricing here</a> <br><br>
            If you have already registered via the <a href="https://bitpoints.club/" target="_new">https://bitpoints.club</a> site clicking "Automatic Configuration" below will still automatically configure the BitPoints Club plugin settings (please set the email and password below to what you registered with)<br><br>
            <b>Promotions</b> must be setup from the <a href="https://bitpoints.club/" target="_new">https://bitpoints.club</a> site
		</p>';
}
 
//BitPointsClub_Options section callback function
function BitPointsClub_Options_callback_function() {
	echo 
		'<p>
			Edit optional UI settings 
		</p>';
}
 
//BitPointsClub_Options section callback function
function BitPointsClub_Manual_Adjustment_callback_function() {
	echo 
		'<p>
			Add/Remove customer points manually
		</p>';
}

//Callback function for BitPointsClub_API_URL 
function BitPointsClub_API_URL_callback_function() {
	$setting = get_option( 'BitPointsClub_API_URL' );
	if($setting == "") $setting = "https://bitpoints.club/api/v1/";
	echo "<input type='text' name='BitPointsClub_API_URL' style='width: 400px' value='$setting' />";
}
 
//Callback function for BitPointsClub_API_KEY
function BitPointsClub_API_KEY_callback_function() {
	$setting = get_option( 'BitPointsClub_API_KEY' );
	echo "
<input type='text' name='BitPointsClub_API_KEY' value='$setting' style='width: 400px' /><br/>
<label for='BitPointsClub_API_KEY'>The API Key obtained from https://bitpoints.club</label>";
}
 
//Callback function for BitPointsClub_Program_Name
function BitPointsClub_Program_Name_callback_function() {
	$setting = get_option( 'BitPointsClub_Program_Name' );
	echo "
<input type='text' name='BitPointsClub_Program_Name' value='$setting' style='width: 200px' /><br/>
<label for='BitPointsClub_Program_Name'>The program name as you entered it on https://bitpoints.club</label>";
}
 
//Callback function for BitPointsClub_ECommerce_Plugin
function BitPointsClub_ECommerce_Plugin_callback_function() {
	$setting = get_option( 'BitPointsClub_ECommerce_Plugin' );
	echo '
<select name="BitPointsClub_ECommerce_Plugin" id="BitPointsClub_ECommerce_Plugin" style="width: 200px">
    <option value="WooCommerce" '.($setting == 'WooCommerce' ? 'selected' : '').' >Woo Commerce</option>
</select><br/>
<label for="BitPointsClub_ECommerce_Plugin">We have built cart/checkout logic hooks for the following eCommerce plugins</label>';
}
 
//Callback function for BitPointsClub_Min_Points_Value
function BitPointsClub_Min_Points_Value_callback_function() {
	$setting = get_option( 'BitPointsClub_Min_Points_Value' );
    if(!isset($setting) || $setting == "") $setting = 10;
	echo "
$<input type='text' name='BitPointsClub_Min_Points_Value' value='$setting' style='width: 100px' /><br/>
<label for='BitPointsClub_Min_Points_Value'>Customers must have a points $ value >= to this setting to be able to use points</label>";
}
 
//Callback function for BitPointsClub_Min_Points_Value
function BitPointsClub_Transaction_History_Fields_callback_function() {
	$setting = get_option( 'BitPointsClub_Transaction_History_Fields' );
    if(!isset($setting) || $setting == "") $setting = "created=Date; description=Description; transaction_type=Type; amount=Amount; points=Points";
	echo "
<textarea type='text' name='BitPointsClub_Transaction_History_Fields'style='width: 400px' rows='4'>$setting</textarea><br/>
<label for='BitPointsClub_Transaction_History_Fields'>Used in the [bitpoints-transaction-history] short code (available fields: created, description, transaction_type, amount, points, expiry), syntax: [field value]=[display value]</label>";
}
 
//Callback function for BitPointsClub_Min_Points_Value
function BitPointsClub_Transaction_Type_Translations_callback_function() {
	$setting = get_option( 'BitPointsClub_Transaction_Type_Translations' );
    if(!isset($setting) || $setting == "") $setting = "Join=Join; Earn=Purchase; Credit=Refund; Redeem=Redemption; Refund=Refund; Promotion=Promotion; Expired=Expired";
	echo "
<textarea type='text' name='BitPointsClub_Transaction_Type_Translations' style='width: 400px' rows='4'>$setting</textarea><br/>
<label for='BitPointsClub_Transaction_Type_Translations'>Used in the [bitpoints-transaction-history] short code for the transaction_type field's display value, syntax: [field value]=[display value]</label>";
}
 
//Callback function for BitPointsClub_Min_Points_Value
function BitPointsClub_Points_Text_callback_function() {
	$setting = get_option( 'BitPointsClub_Points_Text' );
    if(!isset($setting) || $setting == "") $setting = "Points";
	echo "
<textarea type='text' name='BitPointsClub_Points_Text' style='width: 400px' rows='4'>$setting</textarea><br/>
<label for='BitPointsClub_Points_Text'>Cart/Order points display text.</label>";
}
 
//Callback function for BitPointsClub_Min_Points_Value
function BitPointsClub_Cart_Use_Points_Text_callback_function() {
	$setting = get_option( 'BitPointsClub_Cart_Use_Points_Text' );
    if(!isset($setting) || $setting == "") $setting = "Use Points?";
	echo "
<textarea type='text' name='BitPointsClub_Cart_Use_Points_Text' style='width: 400px' rows='4'>$setting</textarea><br/>
<label for='BitPointsClub_Cart_Use_Points_Text'>When a customer has sufficient points to redeem this a \"Use Points?\" check box is displayed. Use this setting to change the check box text.</label>";
}
 
//Callback function for BitPointsClub_Min_Points_Value
function BitPointsClub_Cart_Insufficient_Points_Text_callback_function() {
	$setting = get_option( 'BitPointsClub_Cart_Insufficient_Points_Text' );
    if(!isset($setting) || $setting == "") $setting = "Use points? Sorry, you do not have enough points to redeem yet";
	echo "
<textarea type='text' name='BitPointsClub_Cart_Insufficient_Points_Text' style='width: 400px' rows='4'>$setting</textarea><br/>
<label for='BitPointsClub_Cart_Insufficient_Points_Text'>Displayed on the cart page when the customer does not have enough points to redeem.</label>";
}
 
//Callback function for BitPointsClub_Min_Points_Value
function BitPointsClub_Assign_Points_Status_callback_function() {
	$setting = get_option( 'BitPointsClub_Assign_Points_Status' );
    if(!isset($setting) || $setting == "") $setting = "Completed";
	echo "
<input type='text' name='BitPointsClub_Assign_Points_Status' value='$setting' style='width: 100px' /><br/>
<label for='BitPointsClub_Assign_Points_Status'>Points are issued when the order status changes to this</label>";
}

function BitPointsClub_quick_access_page_post() { 
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $_SESSION['BitPointsClub_API_User'] = $_POST["BitPointsClub_API_User"];
        $_SESSION['BitPointsClub_API_Password'] = $_POST["BitPointsClub_API_Password"];
        $_SESSION['BitPointsClub_API_Error'] = "";
        $_SESSION['BitPointsClub_API_Success'] = "";
    }
                    
    if(!filter_var($_POST["BitPointsClub_API_User"], FILTER_VALIDATE_EMAIL) || strLen($_POST["BitPointsClub_API_Password"]) == 0)
        $_SESSION['BitPointsClub_API_Error'] = 'BitPoints.Club registration requires an Email and Password, please enter these fields below.';
    else if (strlen(get_bloginfo('name')) == 0)
        $_SESSION['BitPointsClub_API_Error'] = 'You have not set your WordPress Site Title yet, please do this first then setup the BitPoints Club plugin.';
    else {                        
        $Validate = false;

        try {
            //get
            $Authorization = 'Basic '.base64_encode($_SESSION['BitPointsClub_API_User'].":".$_SESSION['BitPointsClub_API_Password']);
            $response = BitPointsClub_API_HTTP('GET', 'account', '', $Authorization);

            update_option('BitPointsClub_API_KEY', $response->api_guid);

            if(strlen($response->program_name) > 0) {
                update_option('BitPointsClub_Program_Name', $response->program_name);
                $Validate = true;
            } else {
                try {
                    $response = BitPointsClub_API_HTTP('PUT', 'account', '{"program_name":"'.get_bloginfo('name').'"}', $Authorization);
                    update_option('BitPointsClub_Program_Name', $response->program_name);
                    $Validate = true;
                } catch (Exception $err) {
	                $API_Settings_Error = $err->getMessage();
                    if ($err->getMessage() == "Account suspended") $API_Settings_Error = $API_Settings_Error.". Login to <a href='https://bitpoints.club' target='_new'>bitpoints.club</a> to correct.";
                }
            }
        } catch (Exception $err) {
            try {
                if ($err->getMessage() == "Authorization header not passed or invalid") {
                    $parts = explode(" ", $_SESSION['BitPointsClub_API_Name']);
                    $lastname = array_pop($parts);
                    $firstname = implode(" ", $parts);
                    $response = BitPointsClub_API_HTTP('POST', 'account', '{"program_name":"'.get_bloginfo('name').'","first_name":"'.$firstname.'","last_name":"'.$lastname.'"}', $Authorization);
                    update_option('BitPointsClub_API_KEY', $response->api_guid);
                    update_option('BitPointsClub_Program_Name', $response->program_name);       
                    $Validate = true;
                } else {
	                $API_Settings_Error = $err->getMessage();
                    if ($err->getMessage() == "Account suspended") $API_Settings_Error = $API_Settings_Error.". Login to <a href='https://bitpoints.club' target='_new'>bitpoints.club</a> to correct.";
                }
            } catch (Exception $err) {
                if($err->getMessage() == "Email already registered") {
	                $_SESSION['BitPointsClub_API_Error'] = 'Email address is already registered but does not match this password, please edit password and try again.';
                    wp_redirect( $_SERVER['HTTP_REFERER'] );
                    exit();
                } else {
	                $API_Settings_Error = $err->getMessage();
                    if ($err->getMessage() == "Account suspended") $API_Settings_Error = $API_Settings_Error.". Login to <a href='https://bitpoints.club' target='_new'>bitpoints.club</a> to correct.";
                }
            }
        }
        
	    $setting = get_option( 'BitPointsClub_Transaction_History_Fields' );
        if(!isset($setting) || $setting == "") update_option('BitPointsClub_Transaction_History_Fields', "created=Date; description=Description; transaction_type=Type; amount=Amount; points=Points");
        $setting = get_option( 'BitPointsClub_Min_Points_Value' );
        if(!isset($setting) || $setting == "") update_option('BitPointsClub_Min_Points_Value', "10");
        $setting = get_option( 'BitPointsClub_Points_Text' );
        if(!isset($setting) || $setting == "") update_option('BitPointsClub_Points_Text', "Points");
        $setting = get_option( 'BitPointsClub_Transaction_Type_Translations' );
        if(!isset($setting) || $setting == "") update_option('BitPointsClub_Transaction_Type_Translations', "Join=Join; Earn=Purchase; Credit=Refund; Redeem=Redemption; Refund=Refund; Promotion=Promotion; Expired=Expired");
        $setting = get_option( 'BitPointsClub_Cart_Use_Points_Text' );
        if(!isset($setting) || $setting == "") update_option('BitPointsClub_Cart_Use_Points_Text', "Use Points?");
        $setting = get_option( 'BitPointsClub_Cart_Insufficient_Points_Text' );
        if(!isset($setting) || $setting == "") update_option('BitPointsClub_Cart_Insufficient_Points_Text', "Use points? Sorry, you do not have enough points to redeem yet");
        $setting = get_option( 'BitPointsClub_Assign_Points_Status' );
        if(!isset($setting) || $setting == "") update_option('BitPointsClub_Assign_Points_Status', "Completed");             
                                 
        if ($Validate) {
            $API_Settings_Error = "API accessed but program name not set.";
            $BitPointsClub_Program_Name = esc_attr(get_option('BitPointsClub_Program_Name' ));
            if(strlen($BitPointsClub_Program_Name) > 0) {
                $API_Settings_Error = "API accessed but a points type program called '$BitPointsClub_Program_Name' not found.";
                try {
                    $objects = BitPointsClub_API_HTTP('GET', 'program/List?program_type={"eq":"Points"}', '');
                    if(isset($objects) && count($objects) > 0) {
                        foreach ($objects as $object) {
                            if(strtoupper($object->program_name) == strtoupper($BitPointsClub_Program_Name)) {
                                $_SESSION['bitPoints_ProgramId'] = $object->program_id;
                                $API_Settings_Error = "";
                            }
                        }
                    }
                } catch (Exception $err) {
	                $API_Settings_Error = "Failed to find Program: ".$err->getMessage();
                    if ($err->getMessage() == "Account suspended") $API_Settings_Error = $API_Settings_Error.". Login to <a href='https://bitpoints.club' target='_new'>bitpoints.club</a> to correct.";
                }
            }
        }

        if(strlen($API_Settings_Error) > 0) 
            $_SESSION['BitPointsClub_API_Error'] = 'BitPoints Club Configuration Saved but settings not valid: '.$API_Settings_Error;
        else
            $_SESSION['BitPointsClub_API_Success'] = 'BitPoints Club is now configured, please review Configuration and Option sections.';
    }
    
    wp_redirect( $_SERVER['HTTP_REFERER'] );
    exit();
}
add_action('admin_post_bitpointsclub_quick_access_page_post','BitPointsClub_quick_access_page_post');

//Quick access page
function BitPointsClub_quick_access_page() {
    global $wpdb;
    global $current_user;

    if (!isset($_REQUEST['settings-updated']))
          $_REQUEST['settings-updated'] = false; 
              
    if (!isset($_SESSION['BitPointsClub_API_User'])) {
        $current_user = wp_get_current_user();
	    $_SESSION['BitPointsClub_API_User'] = $wpdb->get_var($wpdb->prepare("SELECT user_email FROM $wpdb->users WHERE user_login = '%s'", $current_user->user_login)); 
	    $_SESSION['BitPointsClub_API_Password'] = $wpdb->get_var($wpdb->prepare("SELECT user_pass FROM $wpdb->users WHERE user_login = '%s'", $current_user->user_login));  
        $_SESSION['BitPointsClub_API_Name'] = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM $wpdb->users WHERE user_login = '%s'", $current_user->user_login)); 
        $_SESSION['BitPointsClub_API_Error'] = "";
        $_SESSION['BitPointsClub_API_Success'] = "";
    }
    ?>
 
     <div class="wrap">
 
          <?php  if(isset($_SESSION['BitPointsClub_API_Error']) && strlen($_SESSION['BitPointsClub_API_Error']) > 0) { ?>
            <div class="notice notice-error fade"><p><strong><?php echo _e( $_SESSION['BitPointsClub_API_Error'], 'BitPoints Club' ); ?></strong></p></div>
           <?php
                     $_SESSION['BitPointsClub_API_Error'] = ""; 
                } else if(isset($_SESSION['BitPointsClub_API_Success']) && strlen($_SESSION['BitPointsClub_API_Success']) > 0) { ?>      
            <div class="updated fade"><p><strong><?php _e( $_SESSION['BitPointsClub_API_Success'], 'BitPoints Club' ); ?></strong></p></div>
           <?php 
                    $_SESSION['BitPointsClub_API_Success'] = "";
                 } ?>
           
          <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
           
          <div id="poststuff">
               <div id="post-body">
                <?php BitPointsClub_quick_access_callback_function($_SESSION['BitPointsClub_API_User'] ); ?>
                    <div id="post-body-content">
                         <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                            <table class="form-table">
                                <tr valign="top"><th scope="row">Email:</th>
                                    <td>
                                        <input type='text' name='BitPointsClub_API_User' style='width: 400px' autocomplete="off" value='<?php  echo $_SESSION['BitPointsClub_API_User']; ?>' />
                                    </td>
                                </tr>
                                <tr valign="top"><th scope="row">Password:</th>
                                    <td>
                                        <input type='password' name='BitPointsClub_API_Password' style='width: 400px' autocomplete="off" value='<?php echo $_SESSION['BitPointsClub_API_Password']; ?>' />
                                    </td>
                                </tr>
                                </table>
                                <input name='action' type="hidden" value='bitpointsclub_quick_access_page_post'>
                            <?php submit_button('Automatic Configuration'); ?>
                         </form>
                    </div> 
               </div>
          </div> 
     </div>
     <!-- 
     <?php if(true === WP_DEBUG) { echo $_SESSION['bitPoints_Log']; } ?>
     -->
<?php 
}

//Build the Configuration page
function BitPointsClub_configuration_page() {
     if ( ! isset( $_REQUEST['settings-updated'] ) )
          $_REQUEST['settings-updated'] = false; ?>
 
     <div class="wrap">
 
          <?php if ( false !== $_REQUEST['settings-updated'] ) {
                    $API_Settings_Error = "API accessed but program name not set.";
                    $BitPointsClub_Program_Name = esc_attr(get_option('BitPointsClub_Program_Name' ));
                    if(strlen($BitPointsClub_Program_Name) > 0) {
                        $API_Settings_Error = "API accessed but a points type program called '$BitPointsClub_Program_Name' not found.";
                        try {
                            $objects = BitPointsClub_API_HTTP('GET', 'program/List?program_type={"eq":"Points"}', '');
                            if(isset($objects) && count($objects) > 0) {
                                foreach ($objects as $object) {
                                    if(strtoupper($object->program_name) == strtoupper($BitPointsClub_Program_Name)) {
                                        $_SESSION['bitPoints_ProgramId'] = $object->program_id;
                                        $API_Settings_Error = "";
                                    }
                                }
                            }
                        } catch (Exception $err) {
	                        $API_Settings_Error = "Failed to find Program: ".$err->getMessage();
                            if ($err->getMessage() == "Account suspended") $API_Settings_Error = $API_Settings_Error.". Login to <a href='https://bitpoints.club' target='_new'>bitpoints.club</a> to correct.";
                        }
                    }

                    if(strlen($API_Settings_Error) > 0) { ?>
               <div class="notice notice-error fade"><p><strong><?php echo _e( 'BitPoints Club Configuration Saved but settings not valid: '.$API_Settings_Error, 'BitPoints Club' ); ?></strong></p></div>
              <?php } else { ?>      
               <div class="updated fade"><p><strong><?php _e( 'BitPoints Club Configuration Saved', 'BitPoints Club' ); ?></strong></p></div>
              <?php } ?>
          <?php 
                } else {                
                    $urlSetting = get_option( 'BitPointsClub_API_URL' );
                    $apiSetting = get_option( 'BitPointsClub_API_KEY' );
                    $nameSetting = get_option( 'BitPointsClub_Program_Name' );

                    if(strlen($urlSetting) > 0 && strlen($apiSetting) > 0 && strlen($nameSetting) > 0 ) {
                        try {
                            $API_Settings_Error = "API accessed but a points type program called '$BitPointsClub_Program_Name' not found.";
                            $objects = BitPointsClub_API_HTTP('GET', 'program/List?program_type={"eq":"Points"}', '');
                            if(isset($objects) && count($objects) > 0) {
                                foreach ($objects as $object) {
                                    if(strtoupper($object->program_name) == strtoupper($BitPointsClub_Program_Name)) {
                                        $_SESSION['bitPoints_ProgramId'] = $object->program_id;
                                        $API_Settings_Error = "";
                                    }
                                }
                            }
                        } catch (Exception $err) {
	                        $API_Settings_Error = $err->getMessage();
                            if ($err->getMessage() == "Account suspended") $API_Settings_Error = $API_Settings_Error.". Login to <a href='https://bitpoints.club' target='_new'>bitpoints.club</a> to correct.";
                        }
                        
                        if(strlen($API_Settings_Error) > 0) { ?>
               <div class="notice notice-error fade"><p><strong><?php echo _e( 'BitPoints Club Configuration invalid: '.$API_Settings_Error, 'BitPoints Club' ); ?></strong></p></div>
              <?php     }
                    }
                }
            ?>
           
          <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
           
          <div id="poststuff">
               <div id="post-body">
                <?php BitPointsClub_Configuration_callback_function(); ?>
                    <div id="post-body-content">
                         <form method="post" action="options.php">
                            <?php settings_fields( 'BitPointsClub_Configuration' ); ?>
                            <?php do_settings_sections( 'BitPointsClub_Configuration' ); ?>
                            <table class="form-table">
                                <tr valign="top"><th scope="row"><?php _e( 'API URL', 'BitPoints Club' ); ?></th>
                                    <td>
                                            <?php BitPointsClub_API_URL_callback_function(); ?> 
                                    </td>
                                </tr>
                                <tr valign="top"><th scope="row"><?php _e( 'API Key', 'BitPoints Club' ); ?></th>
                                    <td>
                                            <?php BitPointsClub_API_KEY_callback_function(); ?> 
                                    </td>
                                </tr>
                                <tr valign="top"><th scope="row"><?php _e( 'Program Name', 'BitPoints Club' ); ?></th>
                                    <td>
                                            <?php BitPointsClub_Program_Name_callback_function(); ?> 
                                    </td>
                                </tr>
                                <tr valign="top"><th scope="row"><?php _e( 'eCommerce Plugin', 'BitPoints Club' ); ?></th>
                                    <td>
                                            <?php BitPointsClub_ECommerce_Plugin_callback_function(); ?> 
                                    </td>
                                </tr>
                                </table>
                            <?php submit_button(); ?>
                         </form>
                    </div> 
               </div>
          </div> 
     </div><?php 
}

//Build the Options page
function BitPointsClub_options_page() {
     if ( ! isset( $_REQUEST['settings-updated'] ) )
          $_REQUEST['settings-updated'] = false; ?>
 
     <div class="wrap">
 
          <?php if ( false !== $_REQUEST['settings-updated'] ) : ?>
               <div class="updated fade"><p><strong><?php _e( 'BitPoints Club Options Saved', 'BitPoints Club' ); ?></strong></p></div>
          <?php endif; ?>
           
          <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
           
          <div id="poststuff">
               <div id="post-body">
                <?php BitPointsClub_Options_callback_function(); ?>
                    <div id="post-body-content">
                         <form method="post" action="options.php">
                            <?php settings_fields( 'BitPointsClub_Options' ); ?>
                            <?php do_settings_sections( 'BitPointsClub_Options' ); ?>
                            <table class="form-table">
                                <tr valign="top"><th scope="row"><?php _e( 'Minimum Redemption Value', 'BitPoints Club' ); ?></th>
                                    <td>
                                            <?php BitPointsClub_Min_Points_Value_callback_function(); ?> 
                                    </td>
                                </tr>
                                <tr valign="top"><th scope="row"><?php _e( 'Points Text', 'BitPoints Club' ); ?></th>
                                    <td>
                                            <?php BitPointsClub_Points_Text_callback_function(); ?> 
                                    </td>
                                </tr>
                                <tr valign="top"><th scope="row"><?php _e( 'Transaction History Fields', 'BitPoints Club' ); ?></th>
                                    <td>
                                            <?php BitPointsClub_Transaction_History_Fields_callback_function(); ?> 
                                    </td>
                                </tr>
                                <tr valign="top"><th scope="row"><?php _e( 'Transaction Type Translations', 'BitPoints Club' ); ?></th>
                                    <td>
                                            <?php BitPointsClub_Transaction_Type_Translations_callback_function(); ?> 
                                    </td>
                                </tr>
                                <tr valign="top"><th scope="row"><?php _e( 'Cart Use Points Text', 'BitPoints Club' ); ?></th>
                                    <td>
                                            <?php BitPointsClub_Cart_Use_Points_Text_callback_function(); ?> 
                                    </td>
                                </tr>
                                <tr valign="top"><th scope="row"><?php _e( 'Cart Insufficient Points Text', 'BitPoints Club' ); ?></th>
                                    <td>
                                            <?php BitPointsClub_Cart_Insufficient_Points_Text_callback_function(); ?> 
                                    </td>
                                </tr>
                                <tr valign="top"><th scope="row"><?php _e( 'Assign Points Order Status', 'BitPoints Club' ); ?></th>
                                    <td>
                                            <?php BitPointsClub_Assign_Points_Status_callback_function(); ?> 
                                    </td>
                                </tr>                                
                                </table>
                            <?php submit_button(); ?>
                         </form>
                    </div> 
               </div>
          </div> 
     </div><?php 
}

//Build the Manual Adjustment page
function BitPointsClub_manual_adjustments_page() {    
    global $wpdb;
    global $current_user;

    $valid = true;
    $BitPointsClub_Manual_Adjustment = "";
    $BitPointsClub_Manual_Adjustment_User = "";
    $BitPointsClub_Manual_Adjustment_Points = "";
    $BitPointsClub_Manual_Adjustment_Description = "";

    $BitPointsClub_Manual_Adjustment_error = "";
    $BitPointsClub_Manual_Adjustment_Points_error = "";
    $BitPointsClub_Manual_Adjustment_User_error = "";
    $BitPointsClub_Manual_Adjustment_Description_User_error = "";

    if($_SERVER['REQUEST_METHOD'] == "POST") {
        if(isset($_POST['BitPointsClub_Manual_Adjustment_User'])) $BitPointsClub_Manual_Adjustment_User = $_POST['BitPointsClub_Manual_Adjustment_User'];
        if(isset($_POST['BitPointsClub_Manual_Adjustment_Points'])) $BitPointsClub_Manual_Adjustment_Points = $_POST['BitPointsClub_Manual_Adjustment_Points'];
        if(isset($_POST['BitPointsClub_Manual_Adjustment_Description'])) $BitPointsClub_Manual_Adjustment_Description = $_POST['BitPointsClub_Manual_Adjustment_Description'];

        if (strlen($BitPointsClub_Manual_Adjustment_User) == 0 || $BitPointsClub_Manual_Adjustment_User == 'Please select...' || $BitPointsClub_Manual_Adjustment_User == '-1') 
            $BitPointsClub_Manual_Adjustment_User_error = "You must select a customer to adjust";
    
        if (strlen($BitPointsClub_Manual_Adjustment_Points) == 0) 
            $BitPointsClub_Manual_Adjustment_Points_error = "You must enter the points to adjust (positive or negative)";
        else if(!ctype_digit($BitPointsClub_Manual_Adjustment_Points))
            $BitPointsClub_Manual_Adjustment_Points_error = "Points to adjust must be numeric with no decimal places";
        
        if (strlen($BitPointsClub_Manual_Adjustment_Description) == 0) 
            $BitPointsClub_Manual_Adjustment_Description_User_error = "You must enter a description for this adjustment";

        if(strlen($BitPointsClub_Manual_Adjustment_User_error) > 0 || strlen($BitPointsClub_Manual_Adjustment_Points_error) > 0 || 
                strlen($BitPointsClub_Manual_Adjustment_Description_User_error) > 0) {
            $valid = false;
            $BitPointsClub_Manual_Adjustment_error = "Please review the values you entered below.";
        } else {        
	        $user_email = $wpdb->get_var($wpdb->prepare("SELECT user_email FROM $wpdb->users WHERE ID = '%s'", $BitPointsClub_Manual_Adjustment_User)); 
	        $password = $wpdb->get_var($wpdb->prepare("SELECT user_pass FROM $wpdb->users WHERE ID = '%s'", $BitPointsClub_Manual_Adjustment_User));  
            $display_name = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM $wpdb->users WHERE ID = '%s'", $BitPointsClub_Manual_Adjustment_User)); 
	        $object = BitPointsClub_API_FindCustomer($user_email, $password, $display_name);
            if(isset($object) && property_exists($object, 'customer_id') && $object->customer_id > 0) {
                $customerid = $object->customer_id;

                try {
                    $bitPoints_ProgramId = BitPointsClub_API_GetProgramId();

                    //add refund transaction (and assign it to program_id)
                    BitPointsClub_API_HTTP('POST', 'program/'.$bitPoints_ProgramId.'/customer/'.$customerid.'/transaction', 
                        '[{'.
                        '"transaction_type":"Adjustment",'.
                        '"points":"'.$BitPointsClub_Manual_Adjustment_Points.'",'.
                        '"description":"'.$BitPointsClub_Manual_Adjustment_Description
                    .'"}]');
                    $valid = true;
                    
                    //update customer balance
                    $current_user = wp_get_current_user();
                    if($current_user->ID == $BitPointsClub_Manual_Adjustment_User) {
                        $object = BitPointsClub_API_RefreshCustomer($customerid);     
                        if(isset($object) && property_exists($object, 'customer_id') && $object->customer_id > 0) BitPointsClub_UpdateSession($object);
                        $_SESSION['bitPoints_History'] = null;
                    }
                } catch (Exception $err) {
                    $valid = false;
	                $BitPointsClub_Manual_Adjustment_error = "BitPointsClub - Failed to apply adjustment: ".$err->getMessage();
                }
            } else {
                $valid = false;
	            $BitPointsClub_Manual_Adjustment_error = "BitPointsClub - Failed to apply adjustment: User details not found";
            }
        }
    }
 ?>
     <div class="wrap">
 
          <?php if($_SERVER['REQUEST_METHOD'] == "POST" && $valid) { ?>
               <div class="updated fade"><p><strong><?php _e( 'BitPointsClub Manual Adjustment Applied', 'BitPoints Club' ); ?></strong></p></div>
          <?php } else if($_SERVER['REQUEST_METHOD'] == "POST" && !$valid) {  ?>
               <div class="notice notice-error fade"><p><strong><?php echo $BitPointsClub_Manual_Adjustment_error; ?></strong></p></div>
          <?php }   ?>
           
          <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
           
          <div id="poststuff">
               <div id="post-body">
                <?php BitPointsClub_Manual_Adjustment_callback_function(); ?>
                    <div id="post-body-content">
                         <form method="post">
                            <table class="form-table">
                                <tr valign="top"><th scope="row"><?php _e( 'Customer', 'BitPoints Club' ); ?></th>
                                    <td>
                                        <?php wp_dropdown_users(array('name' => 'BitPointsClub_Manual_Adjustment_User', 'selected' => $BitPointsClub_Manual_Adjustment_User, 'show' => 'user_email', 'show_option_none' => 'Please select...')); ?><br/>
                                        <?php if(strlen($BitPointsClub_Manual_Adjustment_User_error) == 0) { ?>
                                        <label for='BitPointsClub_Manual_Adjustment_User'>Select a Customer</label>
                                        <?php } else { ?>
                                        <label for='BitPointsClub_Manual_Adjustment_User' style="color: red;"><?php echo $BitPointsClub_Manual_Adjustment_User_error; ?></label>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <tr valign="top"><th scope="row"><?php _e( 'Points', 'BitPoints Club' ); ?></th>
                                    <td>
                                        <input type='text' name='BitPointsClub_Manual_Adjustment_Points' value='<?php echo $BitPointsClub_Manual_Adjustment_Points; ?>' style='width: 200px' /><br/>
                                        <?php if(strlen($BitPointsClub_Manual_Adjustment_Points_error) == 0) { ?>
                                        <label for='BitPointsClub_Manual_Adjustment_Points'>Enter the points to adjust (positive or negative)</label>
                                        <?php } else { ?>
                                        <label for='BitPointsClub_Manual_Adjustment_Points' style="color: red;"><?php echo $BitPointsClub_Manual_Adjustment_Points_error; ?></label>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <tr valign="top"><th scope="row"><?php _e( 'Description', 'BitPoints Club' ); ?></th>
                                    <td>
                                        <textarea type='text' name='BitPointsClub_Manual_Adjustment_Description' style='width: 400px' rows="4"><?php echo $BitPointsClub_Manual_Adjustment_Description; ?></textarea><br/>
                                        <?php if(strlen($BitPointsClub_Manual_Adjustment_Description_User_error) == 0) { ?>
                                        <label for='BitPointsClub_Manual_Adjustment_Description'>Enter adjustment description</label>
                                        <?php } else { ?>
                                        <label for='BitPointsClub_Manual_Adjustment_Description' style="color: red;"><?php echo $BitPointsClub_Manual_Adjustment_Description_User_error; ?></label>
                                        <?php } ?>
                                    </td>
                                </tr>
                                </table>
                            <input type="submit" value="Apply Adjustment" class="button button-primary button-large">
                         </form>
                    </div> 
               </div>
          </div> 
     </div><?php 
}