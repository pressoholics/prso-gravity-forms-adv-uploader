<?php
/*
 * Plugin Name: Gravity Forms Advanced File Uploader
 * Plugin URI: https://github.com/pressoholics/prso-gravity-forms-adv-uploader
 * Description: Multiple file uploader with advanced options for Gravity Forms plugin.
 * Author: Benjamin Moody
 * Version: 1.25
 * Author URI: http://www.benjaminmoody.com
 * License: GPL2+
 * Text Domain: prso_gforms_adv_uploader_plugin
 * Domain Path: /languages/
 */

//Define plugin constants
define( 'PRSOGFORMSADVUPLOADER__MINIMUM_WP_VERSION', '3.0' );
define( 'PRSOGFORMSADVUPLOADER__VERSION', '1.25' );
define( 'PRSOGFORMSADVUPLOADER__DOMAIN', 'prso_gforms_adv_uploader_plugin' );

//Plugin admin options will be available in global var with this name, also is database slug for options
define( 'PRSOGFORMSADVUPLOADER__OPTIONS_NAME', 'prso_gforms_adv_uploader_options' );

define( 'PRSOGFORMSADVUPLOADER__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PRSOGFORMSADVUPLOADER__PLUGIN_URL', plugin_dir_url( __FILE__ ) );

//Include plugin classes
require_once( PRSOGFORMSADVUPLOADER__PLUGIN_DIR . 'class.prso-gravity-forms-adv-uploader.php'               );

//Set Activation/Deactivation hooks
register_activation_hook( __FILE__, array( 'PrsoGformsAdvUploader', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'PrsoGformsAdvUploader', 'plugin_deactivation' ) );

//Set plugin config
$config_options = array();

//Instatiate plugin class and pass config options array
new PrsoGformsAdvUploader( $config_options );

/* Display a notice that can be dismissed */
add_action('admin_notices', 'prso_gformsadv_admin_notice');
function prso_gformsadv_admin_notice() {
	global $current_user ;
    $user_id = $current_user->ID;
    
    /* Check that the user hasn't already clicked to ignore the message */
	if ( ! get_user_meta($user_id, 'prso_gformsadv_ignore_notice') ) {
        echo '<div class="updated"><p>'; 
        printf(__('<strong>New</strong>: GravityForms Advanced Uploads Email Intergration now available. <a href="%1$s" target="_blank">Learn More</a> | <a href="%2$s">Hide Notice</a>'), 'http://benjaminmoody.com/downloads/gravity-forms-adv-uploads-email-tag-addon/', home_url('/wp-admin/index.php').'?prso_gformsadv_ignore_notice=0');
        echo "</p></div>";
	}
}

add_action('admin_init', 'prso_gformsadv_nag_ignore');
function prso_gformsadv_nag_ignore() {
	global $current_user;
	
    $user_id = $current_user->ID;
    /* If user clicks to ignore the notice, add that to their user meta */
    if ( isset($_GET['prso_gformsadv_ignore_notice']) && '0' == $_GET['prso_gformsadv_ignore_notice'] ) {
         add_user_meta($user_id, 'prso_gformsadv_ignore_notice', 'true', true);
	}
}