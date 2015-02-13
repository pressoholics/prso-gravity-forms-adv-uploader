<?php
/**
	!!!HOW TO SETUP!!!
	
	Replace PrsoPluginFrameworkOptionConfig with your plugin slug to create unique class name!!!
	
	*Also update the $text_domain arg for this unique plugin!!
	*Also update the $options_name arg for this unique plugin!!
	
**/


/**
	ReduxFramework Sample Config File
	For full documentation, please visit: https://github.com/ReduxFramework/ReduxFramework/wiki
**/

if ( !class_exists( "ReduxFramework" ) ) {
	return;
} 

if ( !class_exists( "PrsoGformsAdvUploaderOptions" ) ) {
	class PrsoGformsAdvUploaderOptions {

		public $args = array();
		public $sections = array();
		public $theme;
		public $ReduxFramework;
		public $text_domain 	= PRSOGFORMSADVUPLOADER__DOMAIN;
		public $options_name 	= PRSOGFORMSADVUPLOADER__OPTIONS_NAME;

		public function __construct( ) {

			// Just for demo purposes. Not needed per say.
			$this->theme = wp_get_theme();

			// Set the default arguments
			$this->setArguments();
			
			// Set a few help tabs so you can see how it's done
			$this->setHelpTabs();

			// Create the sections and fields
			$this->setSections();
			
			if ( !isset( $this->args['opt_name'] ) ) { // No errors please
				return;
			}
			
			$this->ReduxFramework = new ReduxFramework($this->sections, $this->args);

			// If Redux is running as a plugin, this will remove the demo notice and links
			add_action( 'redux/plugin/hooks', array( $this, 'remove_demo' ) );
			
			// Function to test the compiler hook and demo CSS output.
			//add_filter('redux/options/'.$this->args['opt_name'].'/compiler', array( $this, 'compiler_action' ), 10, 2); 
			// Above 10 is a priority, but 2 in necessary to include the dynamically generated CSS to be sent to the function.

			// Change the arguments after they've been declared, but before the panel is created
			//add_filter('redux/options/'.$this->args['opt_name'].'/args', array( $this, 'change_arguments' ) );
			
			// Change the default value of a field after it's been set, but before it's been used
			//add_filter('redux/options/'.$this->args['opt_name'].'/defaults', array( $this,'change_defaults' ) );

			// Dynamically add a section. Can be also used to modify sections/fields
			add_filter('redux/options/'.$this->args['opt_name'].'/sections', array( $this, 'dynamic_section' ) );

		}


		/**

			This is a test function that will let you see when the compiler hook occurs. 
			It only runs if a field	set with compiler=>true is changed.

		**/

		function compiler_action($options, $css) {
			echo "<h1>The compiler hook has run!";
			//print_r($options); //Option values
			
			// print_r($css); // Compiler selector CSS values  compiler => array( CSS SELECTORS )
			/*
			// Demo of how to use the dynamic CSS and write your own static CSS file
		    $filename = dirname(__FILE__) . '/style' . '.css';
		    global $wp_filesystem;
		    if( empty( $wp_filesystem ) ) {
		        require_once( ABSPATH .'/wp-admin/includes/file.php' );
		        WP_Filesystem();
		    }

		    if( $wp_filesystem ) {
		        $wp_filesystem->put_contents(
		            $filename,
		            $css,
		            FS_CHMOD_FILE // predefined mode settings for WP files
		        );
		    }
			*/
		}



		/**
		 
		 	Custom function for filtering the sections array. Good for child themes to override or add to the sections.
		 	Simply include this function in the child themes functions.php file.
		 
		 	NOTE: the defined constants for URLs, and directories will NOT be available at this point in a child theme,
		 	so you must use get_template_directory_uri() if you want to use any of the built in icons
		 
		 **/

		function dynamic_section($sections){
		    //$sections = array();
		    $sections[] = array(
		        'title' => __('Section via hook', $this->text_domain),
		        'desc' => __('<p class="description">This is a section created by adding a filter to the sections array. Can be used by child themes to add/remove sections from the options.</p>', $this->text_domain),
				'icon' => 'el-icon-paper-clip',
				    // Leave this as a blank section, no options just some intro text set above.
		        'fields' => array()
		    );

		    return $sections;
		}
		
		
		/**

			Filter hook for filtering the args. Good for child themes to override or add to the args array. Can also be used in other functions.

		**/
		
		function change_arguments($args){
		    //$args['dev_mode'] = true;
		    
		    return $args;
		}
			
		
		/**

			Filter hook for filtering the default value of any given field. Very useful in development mode.

		**/

		function change_defaults($defaults){
		    $defaults['str_replace'] = "Testing filter hook!";
		    
		    return $defaults;
		}


		// Remove the demo link and the notice of integrated demo from the redux-framework plugin
		function remove_demo() {
			
			// Used to hide the demo mode link from the plugin page. Only used when Redux is a plugin.
			if ( class_exists('ReduxFrameworkPlugin') ) {
				remove_filter( 'plugin_row_meta', array( ReduxFrameworkPlugin::get_instance(), 'plugin_meta_demo_mode_link'), null, 2 );
			}

			// Used to hide the activation notice informing users of the demo panel. Only used when Redux is a plugin.
			remove_action('admin_notices', array( ReduxFrameworkPlugin::get_instance(), 'admin_notices' ) );	

		}


		public function setSections() {

			/**
			 	Used within different fields. Simply examples. Search for ACTUAL DECLARATION for field examples
			 **/


			// Background Patterns Reader
			$sample_patterns_path = ReduxFramework::$_dir . '../sample/patterns/';
			$sample_patterns_url  = ReduxFramework::$_url . '../sample/patterns/';
			$sample_patterns      = array();

			if ( is_dir( $sample_patterns_path ) ) :
				
			  if ( $sample_patterns_dir = opendir( $sample_patterns_path ) ) :
			  	$sample_patterns = array();

			    while ( ( $sample_patterns_file = readdir( $sample_patterns_dir ) ) !== false ) {

			      if( stristr( $sample_patterns_file, '.png' ) !== false || stristr( $sample_patterns_file, '.jpg' ) !== false ) {
			      	$name = explode(".", $sample_patterns_file);
			      	$name = str_replace('.'.end($name), '', $sample_patterns_file);
			      	$sample_patterns[] = array( 'alt'=>$name,'img' => $sample_patterns_url . $sample_patterns_file );
			      }
			    }
			  endif;
			endif;

			ob_start();

			$ct = wp_get_theme();
			$this->theme = $ct;
			$item_name = $this->theme->get('Name'); 
			$tags = $this->theme->Tags;
			$screenshot = $this->theme->get_screenshot();
			$class = $screenshot ? 'has-screenshot' : '';

			$customize_title = sprintf( __( 'Customize &#8220;%s&#8221;',$this->text_domain ), $this->theme->display('Name') );

			?>
			<div id="current-theme" class="<?php echo esc_attr( $class ); ?>">
				<?php if ( $screenshot ) : ?>
					<?php if ( current_user_can( 'edit_theme_options' ) ) : ?>
					<a href="<?php echo wp_customize_url(); ?>" class="load-customize hide-if-no-customize" title="<?php echo esc_attr( $customize_title ); ?>">
						<img src="<?php echo esc_url( $screenshot ); ?>" alt="<?php esc_attr_e( 'Current theme preview' ); ?>" />
					</a>
					<?php endif; ?>
					<img class="hide-if-customize" src="<?php echo esc_url( $screenshot ); ?>" alt="<?php esc_attr_e( 'Current theme preview' ); ?>" />
				<?php endif; ?>

				<h4>
					<?php echo $this->theme->display('Name'); ?>
				</h4>

				<div>
					<ul class="theme-info">
						<li><?php printf( __('By %s',$this->text_domain), $this->theme->display('Author') ); ?></li>
						<li><?php printf( __('Version %s',$this->text_domain), $this->theme->display('Version') ); ?></li>
						<li><?php echo '<strong>'.__('Tags', $this->text_domain).':</strong> '; ?><?php printf( $this->theme->display('Tags') ); ?></li>
					</ul>
					<p class="theme-description"><?php echo $this->theme->display('Description'); ?></p>
					<?php if ( $this->theme->parent() ) {
						printf( ' <p class="howto">' . __( 'This <a href="%1$s">child theme</a> requires its parent theme, %2$s.' ) . '</p>',
							__( 'http://codex.wordpress.org/Child_Themes',$this->text_domain ),
							$this->theme->parent()->display( 'Name' ) );
					} ?>
					
				</div>

			</div>

			<?php
			$item_info = ob_get_contents();
			    
			ob_end_clean();

			$sampleHTML = '';
			if( file_exists( dirname(__FILE__).'/info-html.html' )) {
				/** @global WP_Filesystem_Direct $wp_filesystem  */
				global $wp_filesystem;
				if (empty($wp_filesystem)) {
					require_once(ABSPATH .'/wp-admin/includes/file.php');
					WP_Filesystem();
				}  		
				$sampleHTML = $wp_filesystem->get_contents(dirname(__FILE__).'/info-html.html');
			}

			//Plugin Help Section
			$this->sections[] = array(
				'title' => __('Addons', $this->text_domain),
				'desc' => __('', $this->text_domain),
				'icon' => 'el-icon-info-sign',
			    // 'submenu' => false, // Setting submenu to false on a given section will hide it from the WordPress sidebar menu!
				'fields' => array(
					array(
					    'id'       => 'help-raw',
					    'type'     => 'raw',
					    'title'    => __('', 'redux-framework-demo'),
					    'content'  => file_get_contents(dirname(__FILE__) . '/addons.html')
					)
				)
			);
			
			$this->sections[] = array(
				'type' => 'divide',
			);

			// ACTUAL DECLARATION OF SECTIONS
			$this->sections[] = array(
				'title' => __('Settings', $this->text_domain),
				'desc' => __('', $this->text_domain),
				'icon' => 'el-icon-cogs',
			    // 'submenu' => false, // Setting submenu to false on a given section will hide it from the WordPress sidebar menu!
				'fields' => array(
					
					//Auto Upload
					array(
						'id'			=>'auto_upload_status',
						'type' 			=> 'switch', 
						'title' 		=> __('Enable Auto Upload', $this->text_domain),
						'subtitle'		=> __('Files will start to upload as they are added', $this->text_domain),
						"default" 		=> 0,
					),
					
					//Auto Upload
					array(
						'id'			=>'duplicates_status',
						'type' 			=> 'switch', 
						'title' 		=> __('Prevent Duplicates', $this->text_domain),
						'subtitle'		=> __('Prevents duplicate files being uploaded by user', $this->text_domain),
						"default" 		=> 1,
					),
					
					//Drag drop
					array(
						'id'			=>'drag_drop_status',
						'type' 			=> 'switch', 
						'title' 		=> __('Enable Drag & Drop', $this->text_domain),
						'subtitle'		=> __('Allow users to Drag and Drop files (limited browser support)', $this->text_domain),
						"default" 		=> 0,
					),
					
					//File type filters
					array(
						'id'		=>'filter_file_type',
						'type' 		=> 'text',
						'title' 	=> __('File Type Filter', $this->text_domain),
						'subtitle' 	=> __('Add file extenstions to allow.', $this->text_domain),
						'desc' 		=> __('Can be adjusted in form field settings. Docs: http://www.plupload.com/docs/Options#filters.mime_types', $this->text_domain),
						'default' 	=> 'jpg,gif,png'
					),
					
					//Max no files
					array(
						'id'		=>'max_files',
						'type' 		=> 'slider',
						'title' 	=> __('Max No. Files', $this->text_domain),
						'desc'		=> __('Can be adjusted in form field settings.', $this->text_domain),
						"default" 	=> "5",
						"min" 		=> "1",
						"step"		=> "1",
						"max" 		=> "20",
					),
					
					//Max file size
					array(
						'id'		=>'max_file_size',
						'type' 		=> 'slider',
						'title' 	=> __('Max File Size', $this->text_domain),
						'desc'		=> __('Can be adjusted in form field settings.', $this->text_domain),
						"default" 	=> "1",
						"min" 		=> "1",
						"step"		=> "5",
						"max" 		=> "2000",
					),
					
					//User Interface
					array(
						'id'		=>'ui_select',
						'type' 		=> 'select',
						'title' 	=> __('Plupload User Interface', $this->text_domain), 
						'subtitle' 	=> __('Select UI to use for uploads.', $this->text_domain),
						'desc' 		=> __('Docs: http://www.plupload.com/examples/core', $this->text_domain),
						'options'	=> array(
							'jquery-ui' => 'JQuery UI',
							'queue'	 	=> 'Queue Widget',
							'custom' 	=> 'Custom (see dev filters)'
						),
						'default'	=> 'queue'
					),			
					
					//JQuery UI Options
					array(
						'id'			=>'list_view',
						'type' 			=> 'switch', 
						'required' 		=> array('ui_select','=','jquery-ui'),
						'title' 		=> __('Enable UI List View', $this->text_domain),
						'subtitle'		=> __('Show uploads list', $this->text_domain),
						"default" 		=> 1,
					),
					array(
						'id'			=>'thumb_view',
						'type' 			=> 'switch', 
						'required' 		=> array('ui_select','=','jquery-ui'),
						'title' 		=> __('Enable UI Thumbnail View', $this->text_domain),
						'subtitle'		=> __('Show uploads as thumbnail grid rather than list', $this->text_domain),
						"default" 		=> 0,
					),
					array(
						'id'		=>'ui_view',
						'type' 		=> 'button_set', 
						'required' 	=> array('thumb_view','=','1'),
						'title' 	=> __('Select default view', $this->text_domain),
						'subtitle'	=> __('Default view on page load', $this->text_domain),
						'options' 	=> array('list' => 'List','thumbs' => 'Thumbs'),//Must provide key => value pairs for radio options
						'default' 	=> 'list'
					),
					
					//Custom UI Options
					array(
						'id'		=>'browse_button_dom_id',
						'type' 		=> 'text',
						'required' 	=> array('ui_select','=','custom'),
						'title' 	=> __('Browse Button ID', $this->text_domain),
						'subtitle' 	=> __('DOM ID for element used as file browse button', $this->text_domain),
						'desc' 		=> __('Docs: http://www.plupload.com/docs/Options#browse_button', $this->text_domain),
						'default' 	=> 'pickfiles'
					),
					
					//Chunk Size
		        	array(
						'id'			=>'chunk_status',
						'type' 			=> 'switch', 
						'title' 		=> __('Enable Chunked File Uploads', $this->text_domain),
						'subtitle'		=> __('Can be useful for getting around server post limits ect', $this->text_domain),
						"default" 		=> 0,
					),
		        	array(
						'id'		=>'chunk_size',
						'type' 		=> 'slider', 
						'required' 	=> array('chunk_status','=','1'),
						'title' 	=> __('File Chunk Size (mb)', $this->text_domain),
						'subtitle'	=> __('Must not exceed PHP Post size limit in php.ini', $this->text_domain),
						'desc'		=> __('The size of each chunk in mb. Docs: http://www.plupload.com/docs/Options#chunk_size', $this->text_domain),
						"default" 	=> "1",
						"min" 		=> "1",
						"step"		=> "1",
						"max" 		=> "5",
					),
					
					//Runtimes
					array(
			            'id' => 'runtimes',
				        'type' => 'sortable',
			    	    'title' => __('Runtimes', $this->text_domain),
			        	'subtitle' => __('Define order of runtimes to try on init.', $this->text_domain),
						'desc' => __('Docs: http://www.plupload.com/docs/Options#runtimes', $this->text_domain),
			            'options' => array(
				            'html5' 		=> 'HTML5',
			    	        'flash' 		=> 'Flash',
			        	    'silverlight' 	=> 'Silverlight',
			        	    'html4' 		=> 'HTML4',
			    	    )
		        	),
					
				)
			);


			$this->sections[] = array(
				'type' => 'divide',
			);
			
			//Video Service APIs
			$video_service_apis = array(
					'youtube' 			=> 'YouTube',
					'brightcove_ftp'	=> 'Brightcove FTP',
			);
			$video_service_apis = apply_filters( 'prso_gform_pluploader_redux_options_video_apis', $video_service_apis );
			
			//Video Service Options
			$video_service_options = array(	
				array(
		            'id'=>'info_success',
		            'type'=>'info',
		            'style'=>'success',
		            'icon'=>'el-icon-info-sign',
		            'title'=> __( 'IMPORTANT NOTICE', $this->text_domain ),
		            'desc' => __( 'YouTube uploader requires PHP iconv extension. So if it doesn\'t work ask your host to install it', $this->text_domain)
		        ),
				
				//Video Plugin Status
				array(
					'id'			=>'video_plugin_status',
					'type' 			=> 'switch', 
					'title' 		=> __('Enable Video Uploader', $this->text_domain),
					'subtitle'		=> __('Enables the video uploader plugin to upload videos to service', $this->text_domain),
					'desc' 			=> __('When enabled plugin will intercept any videos uploaded with Adv Uploader and will move them to the video service selected below.', $this->text_domain),
					"default" 		=> 0,
				),
				
				//Save original video file on server
				array(
					'id'			=>'save_video_file_on_server',
					'type' 			=> 'switch', 
					'title' 		=> __('Save Original Video Files', $this->text_domain),
					'subtitle'		=> __('If ON, original video files will be saved in media library after api upload', $this->text_domain),
					"default" 		=> 0,
				),
				
				//Mark video as private if api allows it
				array(
					'id'			=>'video_is_private',
					'type' 			=> 'switch', 
					'title' 		=> __('Mark video as private', $this->text_domain),
					'subtitle'		=> __('If ON, video will be marked as private if service api allows.', $this->text_domain),
					"default" 		=> 1,
				),
				
				//Confirmation email
				array(
					'id'		=>'confirmation_email',
					'type' 		=> 'text',
					'title' 	=> __('Confirmation Email', $this->text_domain),
					'subtitle' 	=> __('Send alerts when videos are uploaded', $this->text_domain),
					'desc' 		=> __('Required by some APIs', $this->text_domain),
					'validate' 	=> 'email',
					'msg' 		=> 'Invalid Email Address',
					'default' 	=> ''
				),
				
				//User form submission text
				array(
					'id'		=>'user_submit_text',
					'type' 		=> 'text',
					'title' 	=> __('Form Submit Text', $this->text_domain),
					'desc' 		=> __('Text to show user while form submission is processed.', $this->text_domain),
					'default' 	=> 'Uploading files please wait'
				),
				
				//Video Service APIs
				array(
					'id'		=>'api_select',
					'type' 		=> 'select',
					'title' 	=> __('Video Service', $this->text_domain), 
					'subtitle' 	=> __('Select video service API', $this->text_domain),
					'desc' 		=> __('Service videos will be uploaded to.', $this->text_domain),
					'options'	=> $video_service_apis,
					'default'	=> 'youtube'
				),
				
				//YouTube API Options
				array(
					'required' 	=> array('api_select','=','youtube'),
					'id'		=>'youtube_api_key_text',
					'type' 		=> 'text',
					'title' 	=> __('API Dev App Key', $this->text_domain),
					'subtitle' 	=> __('Your YouTube developers app key', $this->text_domain),
					'desc' 		=> __('Docs: https://developers.google.com/youtube/registering_an_application', $this->text_domain),
					'default' 	=> 'YouTube App Key'
				),
				array(
					'required' 	=> array('api_select','=','youtube'),
					'id'		=>'youtube_username_text',
					'type' 		=> 'text',
					'title' 	=> __('YouTube Username', $this->text_domain),
					'subtitle' 	=> __('Your YouTube account username', $this->text_domain),
					'default' 	=> 'YouTube Username'
				),
				array(
					'required' 	=> array('api_select','=','youtube'),
					'id'		=>'youtube_password_text',
					'type' 		=> 'text',
					'title' 	=> __('YouTube Password', $this->text_domain),
					'subtitle' 	=> __('Your YouTube account password', $this->text_domain),
					'default' 	=> 'YouTube Password'
				),
				
				//Brightcove API Options
				array(
					'required' 	=> array('api_select','=','brightcove_ftp'),
					'id'		=>'bc_server',
					'type' 		=> 'text',
					'title' 	=> __('FTP Server', $this->text_domain),
					'subtitle' 	=> __('Brightcove FTP server Address', $this->text_domain),
					'desc' 		=> __('Docs: http://support.brightcove.com/en/video-cloud/docs/using-ftp-batch-provisioning', $this->text_domain),
					'default' 	=> 'upload.brightcove.com'
				),
				array(
					'required' 	=> array('api_select','=','brightcove_ftp'),
					'id'		=>'bc_username',
					'type' 		=> 'text',
					'title' 	=> __('FTP Username', $this->text_domain),
					'subtitle' 	=> __('Brigthcove FTP username', $this->text_domain),
					'default' 	=> 'user@domain.com'
				),
				array(
					'required' 	=> array('api_select','=','brightcove_ftp'),
					'id'		=>'bc_password',
					'type' 		=> 'text',
					'title' 	=> __('FTP Password', $this->text_domain),
					'subtitle' 	=> __('Brigthcove FTP password', $this->text_domain),
					'default' 	=> 'FTP Password'
				),
				array(
					'required' 	=> array('api_select','=','brightcove_ftp'),
					'id'		=>'bc_publisher_id',
					'type' 		=> 'text',
					'title' 	=> __('Publisher ID', $this->text_domain),
					'subtitle' 	=> __('Brightcove Publisher ID', $this->text_domain),
					'default' 	=> 'Publisher ID'
				),
				array(
					'required' 	=> array('api_select','=','brightcove_ftp'),
					'id'		=>'bc_preparer',
					'type' 		=> 'text',
					'title' 	=> __('Preparer Name', $this->text_domain),
					'subtitle' 	=> __('Unique name to identify this plugin with Brightcove', $this->text_domain),
					'default' 	=> 'E.G. MyApp'
				)
			);
			$video_service_options = apply_filters( 'prso_gform_pluploader_redux_options_video_options', $video_service_options );
			
			$this->sections[] = array(
				'title' => __('Video Uploads', $this->text_domain),
				'desc' => __('Video upload service settings', $this->text_domain),
				'icon' => 'el-icon-cogs',
			    //'submenu' => false, // Setting submenu to false on a given section will hide it from the WordPress sidebar menu!
				'fields' => $video_service_options,
			);

			$this->sections[] = array(
				'type' => 'divide',
			);
			
			$this->sections[] = array(
				'title' => __('Security Settings', $this->text_domain),
				'desc' => __('File upload security options', $this->text_domain),
				'icon' => 'el-icon-cogs',
			    //'submenu' => false, // Setting submenu to false on a given section will hide it from the WordPress sidebar menu!
				'fields' => array(	
					
					//Rename files on upload
					array(
						'id'			=>'rename_file_status',
						'type' 			=> 'switch', 
						'title' 		=> __('Rename uploaded files', $this->text_domain),
						'subtitle'		=> __('Files are given a random unique name on upload', $this->text_domain),
						'desc' 			=> __('Disabling this will make it easier for potential hackers to execute malicious files in the unlikely event they pass server file validation. <br><strong>Use at own risk</strong>', $this->text_domain),
						"default" 		=> 1,
					),
					
				)
			);
			
			$this->sections = apply_filters( 'prso_gform_pluploader_redux_options', $this->sections );
			
			$this->sections[] = array(
				'type' => 'divide',
			);
			
			if(file_exists(trailingslashit(dirname(__FILE__)) . 'README.html')) {
			    $tabs['docs'] = array(
					'icon' => 'el-icon-book',
					    'title' => __('Documentation', $this->text_domain),
			        'content' => nl2br(file_get_contents(trailingslashit(dirname(__FILE__)) . 'README.html'))
			    );
			}

		}	

		public function setHelpTabs() {

		}


		/**
			
			All the possible arguments for Redux.
			For full documentation on arguments, please refer to: https://github.com/ReduxFramework/ReduxFramework/wiki/Arguments

		 **/
		public function setArguments() {
			
			$theme = wp_get_theme(); // For use with some settings. Not necessary.

			$this->args = array(
	            
	            // TYPICAL -> Change these values as you need/desire
				'opt_name'          	=> $this->options_name, // This is where your data is stored in the database and also becomes your global variable name.
				'display_name'			=> __( 'Gravity Forms Advanced Uploader', $this->text_domain ), // Name that appears at the top of your panel
				'display_version'		=> PRSOGFORMSADVUPLOADER__VERSION, // Version that appears at the top of your panel
				'menu_type'          	=> 'menu', //Specify if the admin menu should appear or not. Options: menu or submenu (Under appearance only)
				'allow_sub_menu'     	=> true, // Show the sections below the admin menu item or not
				'menu_title'			=> __( 'Gravity Adv Uploader', $this->text_domain ),
	            'page'		 	 		=> __( 'Gravity Adv Uploader', $this->text_domain ),
	            'google_api_key'   	 	=> '', // Must be defined to add google fonts to the typography module
	            'global_variable'    	=> '', // Set a different name for your global variable other than the opt_name
	            'dev_mode'           	=> false, // Show the time the page took to load, etc
	            'customizer'         	=> true, // Enable basic customizer support

	            // OPTIONAL -> Give you extra features
	            'page_priority'      	=> null, // Order where the menu appears in the admin area. If there is any conflict, something will not show. Warning.
	            'page_type'   			=> 'submenu', // set to “menu” for a top level menu, or “submenu” to add below an existing item
	            'page_parent'        	=> 'options-general.php', // For a full list of options, visit: http://codex.wordpress.org/Function_Reference/add_submenu_page#Parameters
	            'page_permissions'   	=> 'manage_options', // Permissions needed to access the options panel.
	            'menu_icon'          	=> '', // Specify a custom URL to an icon
	            'last_tab'           	=> '', // Force your panel to always open to a specific tab (by id)
	            'page_icon'          	=> 'icon-themes', // Icon displayed in the admin panel next to your menu_title
	            'page_slug'          	=> $this->options_name.'_options', // Page slug used to denote the panel
	            'save_defaults'      	=> true, // On load save the defaults to DB before user clicks save or not
	            'default_show'       	=> false, // If true, shows the default value next to each field that is not the default value.
	            'default_mark'       	=> '', // What to print by the field's title if the value shown is default. Suggested: *


	            // CAREFUL -> These options are for advanced use only
	            'transient_time' 	 	=> 60 * MINUTE_IN_SECONDS,
	            'output'            	=> true, // Global shut-off for dynamic CSS output by the framework. Will also disable google fonts output
	            'output_tag'            	=> true, // Allows dynamic CSS to be generated for customizer and google fonts, but stops the dynamic CSS from going to the head
	            //'domain'             	=> 'redux-framework', // Translation domain key. Don't change this unless you want to retranslate all of Redux.
	            //'footer_credit'      	=> '', // Disable the footer credit of Redux. Please leave if you can help it.
	            

	            // FUTURE -> Not in use yet, but reserved or partially implemented. Use at your own risk.
	            'database'           	=> '', // possible: options, theme_mods, theme_mods_expanded, transient. Not fully functional, warning!
	            
	        
	            'show_import_export' 	=> true, // REMOVE
	            'system_info'        	=> false, // REMOVE
	            
	            'help_tabs'          	=> array(),
	            'help_sidebar'       	=> '', // __( '', $this->args['domain'] );            
				);


			// SOCIAL ICONS -> Setup custom links in the footer for quick links in your panel footer icons.		
			$this->args['share_icons'][] = array(
			    'url' => 'https://github.com/ReduxFramework/ReduxFramework',
			    'title' => 'Visit us on GitHub', 
			    'icon' => 'el-icon-github'
			    // 'img' => '', // You can use icon OR img. IMG needs to be a full URL.
			);		
			$this->args['share_icons'][] = array(
			    'url' => 'https://www.facebook.com/pages/Redux-Framework/243141545850368',
			    'title' => 'Like us on Facebook', 
			    'icon' => 'el-icon-facebook'
			);
			$this->args['share_icons'][] = array(
			    'url' => 'http://twitter.com/reduxframework',
			    'title' => 'Follow us on Twitter', 
			    'icon' => 'el-icon-twitter'
			);
			$this->args['share_icons'][] = array(
			    'url' => 'http://www.linkedin.com/company/redux-framework',
			    'title' => 'Find us on LinkedIn', 
			    'icon' => 'el-icon-linkedin'
			);

			
	 
			// Panel Intro text -> before the form
			if (!isset($this->args['global_variable']) || $this->args['global_variable'] !== false ) {
				if (!empty($this->args['global_variable'])) {
					$v = $this->args['global_variable'];
				} else {
					$v = str_replace("-", "_", $this->args['opt_name']);
				}
				$this->args['intro_text'] = sprintf( __('<p>To access any of your saved options from within your code you can use your global variable: <strong>$%1$s</strong></p>', $this->text_domain ), $v );
			} else {
				$this->args['intro_text'] = __('<p>This text is displayed above the options panel. It isn\'t required, but more info is always better! The intro_text field accepts all HTML.</p>', $this->text_domain);
			}

			// Add content after the form.
			$this->args['footer_text'] = __('', $this->text_domain);

		}
	}
	new PrsoGformsAdvUploaderOptions();

}


/** 

	Custom function for the callback referenced above

 */
if ( !function_exists( 'redux_my_custom_field' ) ):
	function redux_my_custom_field($field, $value) {
	    print_r($field);
	    print_r($value);
	}
endif;

/**
 
	Custom function for the callback validation referenced above

**/
if ( !function_exists( 'redux_validate_callback_function' ) ):
	function redux_validate_callback_function($field, $value, $existing_value) {
	    $error = false;
	    $value =  'just testing';
	    /*
	    do your validation
	    
	    if(something) {
	        $value = $value;
	    } elseif(something else) {
	        $error = true;
	        $value = $existing_value;
	        $field['msg'] = 'your custom error message';
	    }
	    */
	    
	    $return['value'] = $value;
	    if($error == true) {
	        $return['error'] = $field;
	    }
	    return $return;
	}
endif;
