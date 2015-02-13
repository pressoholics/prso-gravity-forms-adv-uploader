<?php
/**
 *	Class - PrsoAdvVideoUploader
 *	
 * 	Starts the video upload process based on selected api in options
 *	Handles file attachment preparation and validation.
 *	Inits the required API class and passed the validated attachment data to the
 *	API class for upload.
 *
 *	Takes data for any attachments uploaded and then updates their data in wordpress.
 *	Also updates the Gravity Forms entry view to show links to file at video service e.g. Youtube.
 *
 *	Actions:
 *	'prso_gform_pluploader_processed_uploads'
 *	'wp_ajax_nopriv_prso_gforms_youtube_upload_init'
 *	'wp_ajax_nopriv_prso_gforms_youtube_upload_save_data'
 *	'prso_gform_youtube_uploader_pre_get_attachment_data'	-	Allow devs to hook in before getting attachment data
 *
 *	Filters:
 *	'prso_gform_pluploader_entry_attachment_links'
 *
 */
class PrsoAdvVideoUploader {
	
	/**
	* The full path to the directory which holds "includes", WITHOUT a trailing DS.
	*
	*/
	protected $plugin_includes = NULL;
	
	protected 	$plugin_path			= PRSOGFORMSADVUPLOADER__PLUGIN_DIR;
	protected 	$data 					= array();
	protected	$plugin_options_slug	= PRSOGFORMSADVUPLOADER__OPTIONS_NAME;
	private		$selected_api 			= NULL;
	
	//*** PRSO PLUGIN FRAMEWORK METHODS - Edit at your own risk (go nuts if you just want to add to them) ***//
	
	function __construct() {
 		
 		$this->plugin_includes = plugin_dir_path( __FILE__ ) . 'includes';
 		
 		//Hook into WP init
 		$this->wp_init();
 		
	}
	
	/**
	* wp_init
	* 
	* Called in __construct() to fire any methods for
	* WP Action Hook 'init'
	* 
	* @access 	private
	* @author	Ben Moody
	*/
	private function wp_init() {
		
		//*** PRSO PLUGIN CORE ACTIONS ***//
		
		//Enqueue any custom scripts or styles
		//add_action( 'init', array( $this, 'enqueue_scripts' ) );
		
		//Add any custom actions
		add_action( 'init', array( $this, 'add_actions' ) );
		
		//Add any custom filter
		add_action( 'init', array( $this, 'add_filters' ) );
		
		
		//*** ADD CUSTOM ACTIONS HERE ***//

	}
	
	/**
	* enqueue_scripts
	* 
	* Called by $this->admin_init() to queue any custom scripts or stylesheets
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function enqueue_scripts() {
		
		
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
		
		//Hook into gravity forms after submission hook to fire actions
		//After a form has been submitted succesfully
		//add_action( 'prso_gform_pluploader_processed_uploads', array( $this, 'process_wp_attachments' ), 10, 3 );
		add_action( 'prso_gform_pluploader_processed_uploads', array( $this, 'background_init' ), 10, 3 );
		
		//Add wp ajax hook for method to init processing wp attachments
		add_action( 'wp_ajax_nopriv_prso_gforms_youtube_upload_init', array($this, 'init_attachment_process') );
		
		add_action( 'wp_ajax_nopriv_prso_gforms_youtube_upload_save_data', array($this, 'save_video_data') );
		
		//Add custom script to gravity forms enqueue
		add_action( 'gform_enqueue_scripts', array($this, 'gforms_enqueue_scripts'), 10, 2 );
		
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
		
		//Filter links to attachments in gforms entry
		add_filter( 'prso_gform_pluploader_entry_attachment_links', array($this, 'get_video_link'), 10, 3 );
			
		//Gravity forms filter for submit button render
		add_filter( 'gform_submit_button', array($this, 'gforms_submit_button'), 10, 2 );
		
	}
	
	
	
	
	
	
	//*** CUSTOM METHODS SPECIFIC TO THIS PLUGIN ***//
	
	/**
	* background_init
	* 
	* Called By Action: prso_gform_pluploader_processed_uploads
	* 
	* Hooks into custom action for Gravity Forms Advanced Uploader plugin.
	* Once all attachments have been processed by the plugin and added to wordpress
	* media library. 
	*
	* The array of attachments, Gravity Forms Entry & Form data are prepared to be sent
	* to the next function via a CURL request.
	*
	* Why CURL? - To make sure the upload process to the video hosting service is
	* asyncronous, thus the user will not have to sit and wait for the file to be uploaded
	* before getting some feedback from the form. 
	*
	* Note that the curl request works like a wordpress Ajax request see the 'action' element
	* of $fields array.
	*
	* @param	Array	$wp_attachment_data
	* @param	Array	$entry
	* @param	Array	$form
	* @access 	public
	* @author	Ben Moody
	*/
	public function background_init( $wp_attachment_data, $entry, $form ) {
		
		//Init vars
		$shell_exec_path 	= '';
		$command			= '';
		$ajax_hook_slug		= '';		
		$plugin_options		= array();
		
		//First try and get the plugin options
		if( isset($this->plugin_options_slug) ) {
			$plugin_options = get_option( $this->plugin_options_slug );
		}
		
		if( $plugin_options !== FALSE && isset($plugin_options['api_select']) ) {
			
			//Set the api requested
			$this->selected_api = esc_attr( $plugin_options['api_select'] );
			
			//Cache the slug of our wp ajax hook to run our process
			$ajax_hook_slug = 'prso_gforms_youtube_upload_init';
			
			//** Set Post Vars **//
			
			//Set wp ajax action slug
			$fields['action'] = $ajax_hook_slug;
			
			//Cache selected API
			$fields['api'] = $this->selected_api;
			
			//Serialize wp attachments array
			$fields['wp_attachment_data'] 	= maybe_serialize($wp_attachment_data);
			
			//Set the entry array from gravity forms
			$fields['entry'] = urlencode( json_encode($entry) );
			
			
			//Set form array from gravity forms
			$fields['form'] = urlencode( json_encode($form) );
			
			//Set nonce
			$fields['nonce_key'] = $this->curl_create_nonce( 'adv-video-nonce' );
			
			//** Init curl request - note this is asynchronous **//
			$this->init_curl( $fields );
			
		} else {
			$this->plugin_error_log( 'Main Plugin:: Can\'t access plugin options' );
		}
		
	}
	
	/**
	* init_curl
	* 
	* Helper to make a curl request
	* 
	* @param	Array	$post_fields
	* @access 	public
	* @author	Ben Moody
	*/
	private function init_curl( $post_fields ) {
		
		//** Init curl request - note this is asynchronous **//
		$ch = curl_init();
		
		//Cache path to wp ajax script
		$wp_ajax_url = admin_url('admin-ajax.php');
		
		curl_setopt($ch, CURLOPT_URL, $wp_ajax_url);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);

		curl_exec($ch);
		curl_close($ch);
		
	}
	
	/**
	* curl_create_nonce
	* 
	* Create nonce. Use custom method due to fact that curl request will not be logged in
	* and user id will not match when it comes to checking nonce
	* 
	* @param	Array	$post_fields
	* @access 	public
	* @author	Ben Moody
	*/
	protected function curl_create_nonce( $action ) {
		
		$uid = 1;
		
		//2 min life span for nonce
		$i = $this->curl_nonce_tick();
		
		return substr(wp_hash($i . $action . $uid, 'nonce'), -12, 10);
		
	}
	
	/**
	* curl_check_nonce
	* 
	* Check nonce. Use custom method due to fact that curl request will not be logged in
	* and user id will not match when it comes to checking nonce
	* 
	* @param	Array	$post_fields
	* @access 	public
	* @author	Ben Moody
	*/
	protected function curl_check_nonce( $nonce, $action ) {
		
		$uid = 1;
		
		$i = $this->curl_nonce_tick();
		
		// Nonce generated 0-2 mins ago
		if ( substr(wp_hash($i . $action . $uid, 'nonce'), -12, 10) === $nonce )
		        return 1;
		        
		// Invalid nonce
		return false;
		
	}
	
	function curl_nonce_tick() {	
		return ceil(time() / ( 120 / 2 ));
	}
	
	/**
	* init_attachment_process
	* 
	* Called by WP Ajax Action: prso_gforms_youtube_upload_init
	*
	* Kicks off the whole video upload process
	* Decodes attachments, entry and form data and passes it to method
	* to process the uploads
	*
	* Note: attempts to increase php max execution time to allow for the
	* aysyncornous upload process to complete. This may not work on all
	* server environments so some people may have problems with large files :(
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function init_attachment_process() {
		
		//Init vars
		$wp_attachment_data = array();
		$entry 				= array();
		$form 				= array();
		
		if( !$this->curl_check_nonce($_POST['nonce_key'],'adv-video-nonce') ) {
			die();
		}
		
		//Try to increase php max execution time
		ini_set( 'max_execution_time', 600 );
		
		//Try to increase mysql timeout
		ini_set('mysql.connect_timeout', 600);
		
		//Get post vars
		if( isset($_POST['api'], $_POST['wp_attachment_data'], $_POST['entry'], $_POST['form']) ) {
			
			//Cache selected api
			$this->selected_api = $_POST['api'];
			
			//Unserialize wp attachment data array
			$wp_attachment_data = maybe_unserialize($_POST['wp_attachment_data']);
			
			//Cache entry id and form id passed from gravity forms
			$entry	=  urldecode( $_POST['entry'] );
			$entry	=  json_decode( $entry, TRUE );
			
			//Decode form array from gravity forms
			$form	=  urldecode( $_POST['form'] );
			$form	=  json_decode( $form, TRUE );
			
			//Call method to process attachments
			$this->process_wp_attachments( $wp_attachment_data, $entry, $form );
			
			//Convert the fields array back into an object as the video uploader changes it into an array
			if( isset($form['fields']) ) {
				foreach( $form['fields'] as $key => $field ) {
					$form['fields'][ $key ] = GF_Fields::create( $field );
				}
			}
			
			do_action( 'prso_gform_pluploader_videos_uploads_end', $entry, $form, $wp_attachment_data );
			
		}
		
		die();
	}
	
	/**
	* process_wp_attachments
	* 
	* Loops array of attaments and tries to cache data on each one:
	*	- wordpress attachment id
	*	- mime_type
	* 	- file_path
	*
	* This data is then passed to $this->validate_video_files() for validation
	* If some files pass validation a meta title is generated for the each file
	* then the array of files is padded to upload api for upload
	*
	* Files that are sucessfuly upload via the api are then passed to $this->background_save_data
	* which replaces the wordpress attachment id with the one returned by the api and then
	* deletes the original video file from the wordpress media linrary and server.
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function process_wp_attachments( $wp_attachment_data, $entry, $form ) {
		
		//Init vars
		$wp_attachment_file_info = array(
			'field_id'	=>	array(
				array(
					'mime_type'			=>	'',
					'wp_attachement_id'	=>	'',
					'file_path'			=>	''
				)
			)
		);
		$validated_attachments = array();
		$upload_result = NULL;
		
		$wp_attachment_file_info = array();
		
		if( !empty($wp_attachment_data) && !empty($entry) ) {
			
			//Cache entry and form data from gravity forms
			$this->data['gforms_entry'] = $entry;
			$this->data['gforms_form']	= $form;
			$this->data['attachments']	= $wp_attachment_data;
			
			//Loop attachment ID's and cache info on files
			if( is_array($wp_attachment_data) ) {
				
				//Loop each fields attachments as there maybe multiple pluploader fields in a form
				foreach( $wp_attachment_data as $field_id => $wp_attachments ) {
					
					//Loop attachments for this field
					foreach( $wp_attachments as $key => $attachment_id ) {
						
						//Init vars
						$file_path 	= NULL;
						$mime_type	= NULL;
						
						//Allow devs to hook in before getting attachment data
						do_action( 'prso_gform_youtube_uploader_pre_get_attachment_data', $wp_attachment_data, $field_id, $attachment_id );
						
						//Get file path for current wp attachment
						$file_path = get_attached_file( $attachment_id );
						
						//Get mime type for current wp attachment
						$mime_type = NULL;
						
						//First check which php tools we have
						if( function_exists('finfo_open') ) {
						
							$finfo 		= finfo_open(FILEINFO_MIME_TYPE);
							$mime_type	= finfo_file($finfo, $file_path);
							finfo_close($finfo);
					        
						} elseif( function_exists('mime_content_type') ) {
						
							$mime_type	= mime_content_type( $file_path );
							
						} else {
							
							$mime_type = get_post_mime_type( $attachment_id );
							
						}
						
						//$this->plugin_error_log( $mime_type );
						
						if( !empty($file_path) && $mime_type !== FALSE ) {
						
							$wp_attachment_file_info[$field_id][$key] = array(
								'wp_attachement_id'	=>	$attachment_id,
								'mime_type'			=>	$mime_type,
								'file_path'			=>	$file_path
							);
							
						} else {
							$this->plugin_error_log( 'Main Plugin:: Attachment file path empty OR mime type == false' );
						}
						
					}
					
					
				}
				
			} else {
				$this->plugin_error_log( 'Main Plugin:: wp attachment data not an array' );
			}
			
		} else {
			$this->plugin_error_log( 'Main Plugin:: wp attachment data or gforms entry data empty' );
		}
		
		//Pass array of processed attachments to validation method
		if( !empty($wp_attachment_file_info) ) {
			
			//$this->plugin_error_log( $wp_attachment_file_info );
			
			$validated_attachments = $this->validate_video_files( $wp_attachment_file_info );
			
			//Check that there are some valid video attachments still in array
			if( !empty($validated_attachments) && is_array($validated_attachments) ) {
				
				//Loop each video and cache a human readable title for each based on
				//form id, entry id, and attachment number
				$validated_attachments = $this->generate_file_titles( $validated_attachments );
				
				//Pass array of videos to api helper function to init the upload
				$upload_result = $this->init_api_uploads( $validated_attachments );
				
				//Overwrite wp attachment ids with video id's from api
				//and delete the wp attachments from the server
				//$this->save_video_data( $upload_result ); 
				$this->background_save_data( $upload_result );
				
			} else {
				$this->plugin_error_log( 'Main Plugin:: No valid videos found in attachment array' );
			}
			
		} else {
			$this->plugin_error_log( 'Main Plugin:: Processed attachment array empty' );
		}
		
		
	} 
	
	/**
	* validate_video_files
	* 
	* Validates all files attachments to make sure they are a valid video type.
	* Any non-videos are just ignored
	* 
	* @param	array	$wp_attachment_file_info -  array of file attachments
	* @access 	public
	* @author	Ben Moody
	*/
	private function validate_video_files( $wp_attachment_file_info = array() ) {
		
		//Init vars
		$video_mimes = array();
		
		//Cache array of accepted video mimes
		$video_mimes = array(
			'video/quicktime', 'video/x-quicktime',
			'video/mp4', 'video/avi', 'video/msvideo', 'video/x-msvideo',
			'video/x-m4v', 'video/x-flv', 'video/3gpp', 'video/webm',
			'video/x-ms-wmv', 'video/mpeg', 'video/x-ms-wmx', 'video/x-ms-wm', 'video/x-ms-asf',
			'video/divx', 'video/ogg', 'video/x-matroska'
		);
		
		if( !empty($wp_attachment_file_info) && is_array($wp_attachment_file_info) ) {
			
			//Loop each attachment and unset invalid file types
			foreach( $wp_attachment_file_info as $field_id => $attachments ) {
			
				foreach( $attachments as $key => $attachment_data ) {
					
					if( isset($attachment_data['mime_type']) && !empty($attachment_data['mime_type']) ) {
					
						if( !in_array($attachment_data['mime_type'], $video_mimes) ) {
						
							//$this->plugin_error_log( 'Video Uploader: Not Video Mime - ' . $video_mimes );
							
							//Unset this attachment from array
							unset( $wp_attachment_file_info[$field_id][$key] );
						} else {
							//$this->plugin_error_log( 'Validate Video Files:: File validated succesfully' );
						}
						
					} else {
						//Unset this attachment from array
						unset( $wp_attachment_file_info[$field_id][$key] );
						
						$this->plugin_error_log( 'Validate Video Files:: Attachment mime type not set' );
					}
					
				}
				
			}
			
		} else {
			$this->plugin_error_log( 'Validate Video Files:: Attachment info array empty' );
		}
		
		return $wp_attachment_file_info;
	}
	
	/**
	* generate_file_titles
	* 
	* Helper to generate some file meta for attachments uploaded via api
	* 
	* @access 	private
	* @author	Ben Moody
	*/
	private function generate_file_titles( $validated_attachments ) {
		
		//Init vars
		$entry_id 		= NULL;
		$form_id		= NULL;
		$title			= NULL;
		$description	= NULL;
		
		//Check that our global data from gravity forms is available
		if( isset($this->data['gforms_entry']['id'], $this->data['gforms_entry']['form_id']) ) {
			
			$form_id 	= (int) $this->data['gforms_entry']['form_id'];
			$entry_id	= (int) $this->data['gforms_entry']['id'];
			
			//Loop each attachment video and cache the title and description for each
			foreach( $validated_attachments as $field_id => $attachments ) {
				
				foreach( $attachments as $key => $attachment_data ) {
				
					$title = sprintf( __('Uploaded File: Form %1$s, Entry %2$s, File %3$s', 'gforms-youtube-upload'), 
						$form_id,
						$entry_id,
						$key
					);
					
					$description = sprintf( __('This video was uploaded from your website, here are the details: Form %1$s, Entry %2$s, File %3$s', 'gforms-youtube-upload'), 
						$form_id,
						$entry_id,
						$key
					);
					
					//Cache the title and description
					$validated_attachments[$field_id][$key]['title'] 		= $title;
					$validated_attachments[$field_id][$key]['description'] 	= $description;
					
				}
				
			}
			
		}
		
		return $validated_attachments;
	}
	
	/**
	* init_api_uploads
	* 
	* Initiates upload process for api selected in options
	* 
	* @access 	private
	* @author	Ben Moody
	*/
	private function init_api_uploads( $validated_attachments = array() ) {
		
		//Init vars
		$uploaded_files = array();
		$ApiObj			= NULL;	
		
		if( !empty($validated_attachments) && is_array($validated_attachments) ) {
			
			//Get an instance of API object
			$ApiObj = $this->load_selected_api();
			
			if( method_exists($ApiObj, 'init_api') ) {
				//Call init_api method to upload videos and return video data, id's ect
				$uploaded_files = $ApiObj->init_api( $validated_attachments );
			}
			
		}
		
		return $uploaded_files;
	}
	
	/**
	* background_save_data
	* 
	* Calls Ajax Action: prso_gforms_youtube_upload_save_data
	*
	* Updates meta data for attachments which have been uplaoded via api
	*
	* NOTE: Asynchronous
	* 
	* @access 	private
	* @author	Ben Moody
	*/
	private function background_save_data( $upload_result_data ) {
		
		//Init vars
		$ajax_hook_slug		= '';
		$post_data			= array();
		
		//Cache the slug of our wp ajax hook to run our process
		$ajax_hook_slug = 'prso_gforms_youtube_upload_save_data';
		
		//Cache wp ajax action slug in post data
		$postdata['action'] = $ajax_hook_slug;
		
		//Cache wp attachmetns arrau in post data
		$postdata['wp_attachments'] = maybe_serialize( $this->data['attachments'] );
		
		//Json encode and cache uploaded video data array into post data
		$postdata['upload_result']	= urlencode( json_encode($upload_result_data) );
		
		//Json encode and cache gravity forms entry array into post
		$postdata['gforms_entry']	= urlencode( json_encode($this->data['gforms_entry']) );

		//Json encode and cache gravity forms Form array into post
		$postdata['gforms_form']	= urlencode( json_encode($this->data['gforms_form']) );
		
		//** Init curl request - note this is asynchronous **//
		$this->init_curl( $postdata );
		
	}
	
	/**
	* save_video_data
	* 
	* Called by Ajax Action: 'prso_gforms_youtube_upload_save_data'
	*
	* Loops array of attachments that were uploaded via api and updates
	* post meta for each.
	*
	* @access 	public
	* @author	Ben Moody
	*/
	public function save_video_data() {
		
		//Init vars
		$original_wp_attachments = array();	
		
		//Get post vars
		$original_wp_attachments = maybe_unserialize( $_POST['wp_attachments'] );
		
		//Url decode json
		$upload_result = urldecode( $_POST['upload_result'] );
		
		//Json decode uploads array
		$upload_result = json_decode( $upload_result, TRUE );
		
		//Json decode gravity forms entry array
		$this->data['gforms_entry'] = urldecode( $_POST['gforms_entry'] );
		$this->data['gforms_entry']	= json_decode( $this->data['gforms_entry'], TRUE );
		
		//Json decode gravity forms Form array
		$this->data['gforms_form'] 	= urldecode( $_POST['gforms_form'] );
		$this->data['gforms_form']	= json_decode( $this->data['gforms_form'], TRUE );
		
		if( isset($original_wp_attachments) && !empty($upload_result) ) {
			
			//Cache the array of original wp attachment id's
			//$original_wp_attachments = $this->data['attachments'];
			
			//Loop each wp attachment in the array and replace value
			//with one from upload results only where the array key's match
			foreach( $original_wp_attachments as $field_id => $wp_attachments ) {
				
				foreach( $wp_attachments as $key => $wp_attachment_id ) {
					
					//If this attachment had a video uploaded then replace data
					if( isset($upload_result[$field_id][$key]) ) {
						
						//Write new data
						$original_wp_attachments[$field_id][$key] = $upload_result[$field_id][$key];
						
						//Delete the wp attachment for this video
						$_save_video_file = FALSE;
						if( isset($this->plugin_options_slug) ) {
							$plugin_options = get_option( $this->plugin_options_slug );
							if( isset($plugin_options['save_video_file_on_server']) ) {
								$_save_video_file = (bool) $plugin_options['save_video_file_on_server'];
							}
						}
						
						if( $_save_video_file === FALSE ) {
							wp_delete_attachment( $wp_attachment_id, TRUE );
						}
						
						
					}
					
				}
				
			}
			
			//Update gravity forms data for this entry
			$this->update_gforms_entry_meta( $original_wp_attachments );
			
		}
		
		die();
		
	}
	
	/**
	* update_gforms_entry_meta
	* 
	* Updates form entry data to show api video uploads in entries view
	* 
	* @access 	private
	* @author	Ben Moody
	*/
	private function update_gforms_entry_meta( $field_values = array() ) {
		
		//Init vars
		global $wpdb;
		$lead_details_table_name		= NULL;
		$lead_details_long_table_name	= NULL;
		$entry_id 						= NULL;
		$results						= NULL;
		
		
		
		if( !empty($field_values) && isset($this->data['gforms_entry']['id']) ) {
			
			//Cache entry data provided from gravity forms
			$entry_id = $this->data['gforms_entry']['id'];
			
			//Allow devs to hook before we get the gravity form table names ect
			do_action('prso_gform_youtube_uploader_pre_update_meta', $field_values, $this->data);
			
			//Get gravity forms table names
			$lead_details_table_name 		=  RGFormsModel::get_lead_details_table_name();
		    $lead_details_long_table_name 	=  RGFormsModel::get_lead_details_long_table_name();
			
			//Loop the array of field values and update the gravity forms meta for each field
			foreach( $field_values as $field_id => $value ) {
				
				//Get the lead detail id required to find the our value in gforms meta table
		        $results = $wpdb->get_results(
		        	"SELECT lead_detail_id FROM {$lead_details_table_name} d
		        	 INNER JOIN {$lead_details_long_table_name} l ON d.id = l.lead_detail_id
		        	 WHERE lead_id = {$entry_id} AND field_number = {$field_id}"
		        );
		        
		        //Update the gforms meta values with our new data including the video api data
				if( isset($results[0]->lead_detail_id) ) { //Update upload details
		        	
		        	//Serialize array
		        	$value = maybe_serialize( $value );
		        	
			        //As gforms only looks at details long table if value maxs out the std table
			        //Update std table before long table - value will be truncated by mysql
			        //No probs as it will be stored ok in long table next
			        $wpdb->query(
			        	$wpdb->prepare(
			        		"UPDATE $lead_details_table_name SET value = %s 
			        		 WHERE id = %d", $value, $results[0]->lead_detail_id
			        	)
			        );
			        
			        $wpdb->query(
			        	$wpdb->prepare(
			        		"UPDATE $lead_details_long_table_name SET value = %s 
			        		 WHERE lead_detail_id = %d", $value, $results[0]->lead_detail_id
			        	)
			        );
			        
		        }
				
			}
			
		}
		
	}
	
	/**
	* get_video_link
	* 
	* Helper to get video link for files uplaoded via api
	* Note that this tries to call a method called 'get_video_url'
	* which is declared in the specific video api class. This is the method
	* which actually gets and returns the link.
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function get_video_link( $file_url, $file_data, $post ) {
		
		//Init vars
		$video_link 	= NULL;
		$ApiObj			= NULL;
		$video_id		= NULL;
		
		//First check if file_data is an array or an int
		//If it's an array then this is data added by our video api
		//so we need to output a link for it
		if( is_array($file_data) ) {
			
			//Get and instance of the selected API
			$ApiObj = $this->load_selected_api();
			
			if( isset($file_data['video_id']) ) {
				$video_id = $file_data['video_id'];
			}
			
			//Look for a video_id and request that the video api class return a link
			if( method_exists($ApiObj, 'get_video_url') ) {
			
				$video_link = $ApiObj->get_video_url( $video_id );
				
				$file_url = esc_url( $video_link );
				
			}
			
			
		}
		
		return $file_url;
	}
	
	/**
	* load_selected_api
	* 
	* Helper to load the correct api class based on the 'api_select' plugin option
	* 
	* @access 	private
	* @author	Ben Moody
	*/
	private function load_selected_api() {
		
		//Init vars
		$selected_api	= NULL;
		$result 		= FALSE;
		$plugin_options	= array();
		
		//First try and get the plugin options
		if( isset($this->plugin_options_slug) ) {
			$plugin_options = get_option( $this->plugin_options_slug );
		}
		
		if( $plugin_options !== FALSE && isset($plugin_options['api_select']) ) {
		
			//Cache api selection
			$selected_api = $plugin_options['api_select'];
			
			//Load instance of selected API
			if( isset($selected_api) ) {
				
				//Detect and include api
				switch( $selected_api ) {
					case 'youtube':
						
						//Include API file for current api being used
						include_once( $this->plugin_includes . '/inc_youtube_api.php' );
						
						//Call YouTube API - change this based on the api required
						$result = new PrsoAdvYoutubeApi();
						
						break;
					case 'brightcove_ftp':
						
						//Include API file for current api being used
						include_once( $this->plugin_includes . '/inc_brightcove_ftp.php' );
						
						//Call YouTube API - change this based on the api required
						$result = new PrsoAdvBrightCoveFtp();
						
						break;
					default:
						
						//Get instance of other api's via addons
						$result = apply_filters( 'prso_gform_pluploader_video_api_instance', $result, $selected_api, $plugin_options );
						
						break;
				}
				
			}
			
		}
		
		return $result;	
	}
	
	/**
	* gforms_enqueue_scripts
	* 
	* Enqueues script for use with form submission.
	* Handles things such as disabling the submit button and showing user
	* a loading message.
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function gforms_enqueue_scripts( $form, $is_ajax ) {
		
		//Init vars
		$form_id = NULL;
		$plugin_script_obj = array();
		
		if( isset($form['id']) ) {
			$form_id = 'gform_' . $form['id'];
		}
		
		//Enqueue plugin script for form page
		if( !is_admin() ) {
			
			//Enqueue plugin script
			wp_enqueue_script( 'prso-gforms-api-upload', plugins_url('js/gforms-api-upload.js', __FILE__) );
			
			//Form plugin js object
			$plugin_script_obj['gform_id'] 	= $form_id; 
			$plugin_script_obj['images'] 	= plugins_url('images', __FILE__);
			
			//Localize plugin js object
			wp_localize_script( 'prso-gforms-api-upload', 'prso_gforms_api_upload_vars', $plugin_script_obj );
			
		}
		
	}
	
	/**
	* gforms_submit_button
	* 
	* Called by Gravity Forms Filter: gform_submit_button
	*
	* Adds dom elements required to give user feedback when form submit button is clicked
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function gforms_submit_button( $button, $form ) {
		
		//Init vars
		$plugin_images_folder_url 	= NULL;
		$loading_html				= NULL;
		$wait_text					= NULL;
		
		//Cache url to plugin images folder
		$plugin_images_folder_url = plugins_url('images', __FILE__);
		
		$wait_text = __( 'Uploading files please wait, may take up to 5 min.', 'gforms-youtube-upload' );
		
		//Cache html for submit loading
		$loading_html = "<div style='display:none;' class='gform-api-uploader'><img src='{$plugin_images_folder_url}/ajax-loader.gif' /><p>{$wait_text}</p></div>";
		
		//Append to submit button html
		$button = $button . $loading_html;
		
		return $button;
	}
	
	protected function plugin_error_log( $var ) {
		
		ini_set('log_errors',1);
		ini_set('error_log', $this->plugin_path . '/php_error.log');
		
		if( !is_string($var) ) {
			error_log( print_r($var, true) );
		} else {
			error_log( $var );
		}
		
	}
}