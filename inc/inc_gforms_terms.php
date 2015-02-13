<?php

class PrsoGformsTermsFunctions {
	
	
	//*** PRSO PLUGIN FRAMEWORK METHODS - Edit at your own risk (go nuts if you just want to add to them) ***//
	
	function __construct() {
 		
 		//Hook into WP admin_init
 		$this->admin_init();
 		
	}
	
	/**
	* admin_init
	* 
	* Called in __construct() to fire any methods for
	* WP Action Hook 'admin_init'
	* 
	* @access 	private
	* @author	Ben Moody
	*/
	private function admin_init() {
		
		//*** PRSO PLUGIN CORE ACTIONS ***//
		
		//Register scripts
		//add_action( 'init', array($this, 'register_scripts') );
		
		//Enqueue any custom scripts or styles
		//add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		//Add any custom actions
		add_action( 'init', array( $this, 'add_actions' ) );
		
		//Add any custom filter
		add_action( 'after_setup_theme', array( $this, 'add_filters' ) );
		
		
		//*** ADD CUSTOM ACTIONS HERE ***//

		
	}
	
	/**
	* register_scripts
	* 
	* Called by $this->admin_init() to queue any custom scripts or stylesheets
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function register_scripts() {
		
		//Init vars
		$plugin_file_path 			= NULL;
		
		
	}
	
	/**
	* enqueue_scripts
	* 
	* Called by $this->admin_init() to queue any custom scripts or stylesheets
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function enqueue_scripts( $activate_fine_uploader = FALSE ) {
		
		
		
	}
	
	/**
	* add_actions
	* 
	* Called in $this->admin_init() to add any custom WP Action Hooks
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function add_actions() {
		
		// Adds the input area to the external side
		add_action( "gform_field_input" , array($this, "wps_tos_field_input"), 10, 5 );
		
		// Now we execute some javascript technicalitites for the field to load correctly
		add_action( "gform_editor_js", array($this, "wps_gform_editor_js") );
		
		// Add a custom setting to the tos advanced field
		add_action( "gform_field_advanced_settings" , array($this, "wps_tos_settings") , 10, 2 );
		
		// Add a script to the display of the particular form only if tos field is being used
		add_action( 'gform_enqueue_scripts' , array($this, 'wps_gform_enqueue_scripts') , 10 , 2 );
		
		// Add a custom class to the field li
		add_action("gform_field_css_class", array($this, "custom_class"), 10, 3);

		
	}
	
	/**
	* add_filters
	* 
	* Called in $this->admin_init() to add any custom WP Filter Hooks
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function add_filters() {
		
		// Add a custom field button to the advanced to the field editor
		add_filter( 'gform_add_field_buttons', array($this, 'wps_add_tos_field') );

		// Adds title to GF custom field
		add_filter( 'gform_field_type_title' , array($this, 'wps_tos_title'), 10, 2 );
		
		//Filter to add a new tooltip
		add_filter('gform_tooltips', array($this, 'wps_add_tos_tooltips') );
		
	}
	
	
	//*** CUSTOM METHODS SPECIFIC TO THIS PLUGIN ***//
	
	public function wps_add_tos_field( $field_groups ) {

	    foreach( $field_groups as &$group ){
	
	        if( $group["name"] == "advanced_fields" ){ // to add to the Advanced Fields
	
	        //if( $group["name"] == "standard_fields" ){ // to add to the Standard Fields
	
	        //if( $group["name"] == "post_fields" ){ // to add to the Standard Fields
	
	            $group["fields"][] = array(
	
	                "class"=>"button",

	                "value" => __("Terms of Service", "gravityforms"),
	
	                "onclick" => "StartAddField('tos');"
	
	            );
	
	            break;
	
	        }
	
	    }
	
	    return $field_groups;
	
	}
	
	function wps_tos_title( $title, $field_type ) {

	    if ( $field_type === 'tos' ) {
	        $title = __( 'Terms of Service' , 'gravityforms' );
	    }
		
		return $title;
	}
	
	function wps_tos_field_input ( $input, $field, $value, $lead_id, $form_id ){
		
		//Init vars
		$default_tos_value = "Terms of Service -- Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";
		
	    if ( $field["type"] == "tos" ) {
			
			if( empty($value) ) {
				$value = $default_tos_value;
			}
			
	        $max_chars = "";
	
	        if(!IS_ADMIN && !empty($field["maxLength"]) && is_numeric($field["maxLength"]))
	
	            $max_chars = self::get_counter_script($form_id, $field_id, $field["maxLength"]);
	
	  
	
	        $input_name = $form_id .'_' . $field["id"];
	
	        $tabindex = GFCommon::get_tabindex();
	
	        $css = isset( $field['cssClass'] ) ? $field['cssClass'] : '';
	        
	        $input = sprintf("<div id='gform-tos-container' class='ginput_container'><textarea readonly name='input_%s' id='%s' class='textarea gform_tos %s' $tabindex rows='10' cols='50'>%s</textarea></div>{$max_chars}", $field["id"], 'tos-'.$field['id'] , $field["type"] . ' ' . esc_attr($css) . ' ' . $field['size'] , esc_html($value));
	        
	        //Apply fitlers to allow devs to move the tos conainter to another location in the dom - e.g. modal box
	        $input = apply_filters( 'prso_gform_tos_container', $input, $field, $form_id );
	
	    }	  
	
	    return $input;
	
	}
	
	function wps_gform_editor_js(){
	
	?>
	<script type='text/javascript'>
	
	    jQuery(document).ready(function($) {
	
	        //Add all textarea settings to the "TOS" field plus custom "tos_setting"
	
	        // fieldSettings["tos"] = fieldSettings["textarea"] + ", .tos_setting"; // this will show all fields that Paragraph Text field shows plus my custom setting
	  
	
	        // from forms.js; can add custom "tos_setting" as well
	        fieldSettings["tos"] = ".label_setting, .description_setting, .admin_label_setting, .size_setting, .default_value_textarea_setting, .error_message_setting, .css_class_setting, .visibility_setting, .tos_setting"; //this will show all the fields of the Paragraph Text field minus a couple that I didn't want to appear.
	
	  
	        //binding to the load field settings event to initialize the checkbox
	
	        $(document).bind("gform_load_field_settings", function(event, field, form){
	
	            jQuery("#field_tos").attr("checked", field["field_tos"] == true);
	
	            $("#field_tos_value").val(field["tos"]);
	
	        });
	
	    });
	
	</script>
	<?php
	
	}

	function wps_tos_settings( $position, $form_id ){

	    // Create settings on position 50 (right after Field Label)
	    if( $position == 50 ){
	    ?>

	    <li class="tos_setting field_setting">
	
	  
	
	        <input type="checkbox" id="field_tos" onclick="SetFieldProperty('field_tos', this.checked);" />
	
	        <label for="field_tos" class="inline">
	
	            <?php _e("Disable Submit Button", "gravityforms"); ?>
	
	            <?php gform_tooltip("form_field_tos"); ?>
	
	        </label>
	
	    </li>
	
	    <?php
	
	    }
	
	}

	function wps_add_tos_tooltips($tooltips){

	   $tooltips["form_field_tos"] = "<h6>Disable Submit Button</h6>Check the box if you would like to disable the submit button.";
	
	   $tooltips["form_field_default_value"] = "<h6>Default Value</h6>Enter the Terms of Service here.";
	
	   return $tooltips;
	
	}
	
	function wps_gform_enqueue_scripts( $form, $ajax ) {
		
		//Init vars
		
	    // cycle through fields to see if tos is being used
	    if( isset($form['fields']) && is_array($form['fields']) ) {
		    foreach ( $form['fields'] as $field ) {
	
		        if( ($field['type'] == 'tos') && (isset($field['field_tos'])) ) {
		
		            $url = plugins_url( '/js/gform_tos.js' , __FILE__ );
		            
		            //Filter script url allowing devs to override the tos behaviour
		            $url = apply_filters( 'prso_gform_tos_script_url', $url, $form, $ajax );
		            
		            wp_enqueue_script( "gform_tos_script", $url , array(), '1.0', TRUE );
		
		            break;
		
		        }
		
		    }
	    }
	
	}

	function custom_class($classes, $field, $form){

	    if( $field["type"] == "tos" ){
	
	        $classes .= " gform_tos";
	
	    }
	
	    return $classes;
	
	}

	
}