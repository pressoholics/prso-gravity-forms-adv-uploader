<?php
class PrsoGformsAdvUploader {
	
	protected static $class_config 				= array();
	protected $current_screen					= NULL;
	protected $plugin_ajax_nonce				= 'prso_gforms_adv_uploader-ajax-nonce';
	protected $plugin_path						= PRSOGFORMSADVUPLOADER__PLUGIN_DIR;
	protected $plugin_url						= PRSOGFORMSADVUPLOADER__PLUGIN_URL;
	protected $plugin_textdomain				= PRSOGFORMSADVUPLOADER__DOMAIN;
	
	function __construct( $config = array() ) {
		
		//Cache plugin congif options
		self::$class_config = $config;
		
		//Set textdomain
		add_action( 'after_setup_theme', array($this, 'plugin_textdomain') );
		
		//Init plugin
		add_action( 'init', array($this, 'init_plugin'), 999 );
		
		//Init plugin core
		$core_include = $this->plugin_path . 'class.core.init-uploader.php';
		include_once( $core_include );
		$PluginInit = new PrsoGformsAdvUploaderInit();
		
	}
	
	/**
	 * Attached to activate_{ plugin_basename( __FILES__ ) } by register_activation_hook()
	 * @static
	 */
	public static function plugin_activation( $network_wide ) {
		
	}

	/**
	 * Attached to deactivate_{ plugin_basename( __FILES__ ) } by register_deactivation_hook()
	 * @static
	 */
	public static function plugin_deactivation( ) {
		
	}
	
	/**
	 * Setup plugin textdomain folder
	 * @public
	 */
	public function plugin_textdomain() {
		
		load_plugin_textdomain( $this->plugin_textdomain, FALSE, $this->plugin_path . '/languages/' );
		
	}
	
	/**
	* init_plugin
	* 
	* Used By Action: 'init'
	* 
	*
	* @access 	public
	* @author	Ben Moody
	*/
	public function init_plugin() {
		
		//Init vars
		$options 		= self::$class_config;
		$PluginInit		= NULL;
		
		if( is_admin() ) {
		
			//PLUGIN OPTIONS FRAMEWORK -- comment out if you dont need options
			$this->load_redux_options_framework();
			
		}
		
		//Add shortcodes
		add_shortcode('get_adv_uploads', array($this, 'adv_post_attachments_shortcode'));
		
	}
		
	/**
	* load_redux_options_framework
	* 
	* Loads Redux options framework as well as the unique config file for this plugin
	*
	* NOTE!!!!
	*			You WILL need to make sure some unique constants as well as the class
	*			name in the plugin config file 'inc/ReduxConfig/ReduxConfig.php'
	*
	* @access 	public
	* @author	Ben Moody
	*/
	protected function load_redux_options_framework() {
		
		//Init vars
		$framework_inc 		= $this->plugin_path . 'inc/ReduxFramework/ReduxCore/framework.php';
		$framework_config	= $this->plugin_path . 'inc/ReduxConfig/ReduxConfig.php';
		
		//Try and load redux framework
		if ( !class_exists('ReduxFramework') && file_exists($framework_inc) ) {
			require_once( $framework_inc );
		}
		
		//Try and load redux config for this plugin
		if ( file_exists($framework_config) ) {
			require_once( $framework_config );
		}
		
	}
	
	/**
	* adv_post_attachments_shortcode
	* 
	* Shortcode 'get_adv_uploads'
	*
	* Builds a simple html ul list of all files attached to the post being displayed
	*
	* Filters: 
	*	'prso_gform_pluploader_shortcode_attach_title' - filter individual attachment title
	*	'prso_gform_pluploader_shortcode'	- Filter shortcode output
	*
	* @access 	public
	* @author	Ben Moody
	*/
	public function adv_post_attachments_shortcode( $attr ) {
		
		//Init vars
		global $post;
		$attachments = NULL;
		$output = NULL;
		
		extract(shortcode_atts(array(
			'order'      	=> 'ASC',
			'orderby'    	=> 'menu_order ID',
			'id'         	=> $post ? $post->ID : 0,
			'exclude'    	=> '',
			'target'		=> '_blank'
		), $attr, 'get_adv_uploads'));
		
		$attachments = get_children( 
			array(
				'post_parent' 		=> $id, 
				'exclude' 			=> $exclude, 
				'post_status' 		=> 'inherit', 
				'post_type' 		=> 'attachment',
				'order' 			=> $order, 
				'orderby' 			=> $orderby
			) 
		);
		
		//Loop attachments and build html list
		if( !empty($attachments) ) {
			$output = '<ul class="gforms-adv-uploader-attachments">';
			foreach( $attachments as $attachment_id => $Attachment ) {
				
				$attachment_title = apply_filters( 'prso_gform_pluploader_shortcode_attach_title', $Attachment->post_title . ' ('. $Attachment->post_mime_type .')', $Attachment );
				
				$output.= '<li>';
					$output.= '<a href="'. $Attachment->guid .'" target="'. $target .'">';
						$output.= $attachment_title;
					$output.= '</a>';
				$output.= '</li>';
			}
			$output.= '</ul>';
		}
		
		return apply_filters( 'prso_gform_pluploader_shortcode', $output, $attachments );
	}
	
}



