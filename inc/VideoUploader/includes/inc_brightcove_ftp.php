<?php
/**
 * General App Functions
 *
 * Contents:
 * 	** PRSO PLUGIN FRAMEWORK METHODS **
 *		__construct()		- Magic method construct
 *		admin_init()		- Helps to consolidate all plugin wide calls to Wordpress action hooks that must be added during 'admin_init'
 *		enqueue_scripts()	- Call all plugin wp_enqueue_script or wp_enqueue_style here
 *		add_actions()		- Add any calls to Wordpress add_action() here
 *		add_filters()		- Add any calls to Wordpress add_filter() here
 *
 *	** METHODS SPECIFIC TO THIS PLUGIN **
 *		
 *
 */
class PrsoAdvBrightCoveFtp extends PrsoAdvVideoUploader {
	
	private $ftp_server 		= NULL;
	private $username			= NULL;
	private	$password			= NULL;
	private $publisher_id		= NULL;
	private	$preparer			= NULL;
	private	$manifext_filename	= 'myManifest';
	private $ftp_conn_id		= '';
	private $callback_url		= '';
	
	public function init_api( $validated_attachments ) {
		
		//Init api and Process video uploads
		return $this->init_brightcove_ftp( $validated_attachments );;
		
	}
	
	public function get_video_url( $video_id = NULL ) {
	
		//Init vars
		$bright_cove_url = 'http://www.brightcove.com';
		
		return $bright_cove_url;
		
	}
	
	
	private function init_brightcove_ftp( $validated_attachments ) {
		
		//Init vars
		$manifest_path 		= NULL;
		$ftp_conn_id		= NULL;
		$upload_results		= array();
		$upload_manifest	= FALSE;
		$plugin_options		= array();
		
		//First try and get the plugin options
		if( isset($this->plugin_options_slug) ) {
			$plugin_options = get_option( $this->plugin_options_slug );
		}
		
		$this->plugin_error_log( $plugin_options );
		
		if( $plugin_options !== FALSE && isset($plugin_options['bc_server'], $plugin_options['bc_username'], $plugin_options['bc_password'], $plugin_options['bc_publisher_id'], $plugin_options['bc_preparer']) ) {
			
			//Cache options values
			$this->ftp_server		=	esc_attr( $plugin_options['bc_server'] );
			$this->username			=	esc_attr( $plugin_options['bc_username'] );
			$this->password			=	esc_attr( $plugin_options['bc_password'] );
			$this->publisher_id		=	esc_attr( $plugin_options['bc_publisher_id'] );
			$this->preparer			=	esc_attr( $plugin_options['bc_preparer'] );
			
			//Init process
			if( !empty($validated_attachments) && is_array($validated_attachments) ) {
			
				//Write xml manifest file for all validate video files
				$manifest_path = $this->generate_manifest_xml( $validated_attachments );
				
				//Init ftp connection
				if( $manifest_path !== FALSE && file_exists($manifest_path) ) {
					
					$ftp_conn_id = $this->init_ftp_connection();
					
					//Error check connection
					if( $ftp_conn_id !== FALSE ) {
						
						//cache connection id
						$this->ftp_conn_id = $ftp_conn_id;
						
						//Upload video files
						$upload_results = $this->upload_video_files( $validated_attachments );
						
						//Upload manifest xml file
						if( !empty($upload_results) && is_array($upload_results) ) {
							$upload_manifest = $this->upload_manifest_file( $manifest_path );
						}
						
						//Final error check, make sure manifest uploaded ok
						if( $upload_manifest === FALSE ) {
							
							//Empty upload results array to ensure a redundant copy of
							//video files remain in wp media library as backup
							$upload_results = array();
							
						}
						
					}
					
				}
				
			}
			
		} else {
			$this->plugin_error_log( 'Brightcove FTP:: Missing options for this api.' );
		}
		
		
		//Return the array of succefully uploaded files to be updated in gforms
		//meta table and deleted from wp media library
		return $upload_results;
	}
	
	/**
	* generate_manifest_xml
	* 
	* Loops over all validate video files and creates
	* the mainfest xml file
	*
	* Video Array Format:
	*	Array
		(
		    [1] => Array //Each gravity forms upload field
		        (
		            [0] => Array //Each video file uploaded via this field
		                (
		                    [wp_attachement_id] => 906
		                    [mime_type] => video/quicktime
		                    [file_path] => /Users/ben/Documents/Development/vhost/www.pressoholics.dev/wp-content/uploads/2013/03/cdeecad1844aca9e7b95fd66bc556df6.mov
		                    [title] => Uploaded File: Form 6, Entry 4, File 0
		                    [description] => This video was uploaded from your website, here are the details: Form 6, Entry 4, File 0
		                )
		
		        )
		
		)
	* 
	* @access 	private
	* @author	Ben Moody
	*/
	private function generate_manifest_xml( $validated_attachments ) {
		
		//Init vars
		$result					= FALSE;
		$save_path				= NULL;
		$wp_wpload_dir_info		= array();
		$doc 					= NULL;
		$type					= 'VIDEO_FULL';
		$encode_to				= 'MP4';
		$encode_multiple		= 'true';
		$conf_email				= NULL;
		
		//Cache local save path for manifest
		$wp_wpload_dir_info = wp_upload_dir();
		
		if( isset($wp_wpload_dir_info['basedir'], $this->manifext_filename) ) {
			$save_path = $wp_wpload_dir_info['basedir'] . '/' . $this->manifext_filename . '.xml';
		}
		
		//Cache confirmation email address
		$conf_email = get_option( 'prso_gforms_youtube_main_options', NULL );
		if( isset($conf_email['confirmation_email']) ) {
			$conf_email = $conf_email['confirmation_email'];
		}
		
		$this->plugin_error_log( 'Email: ' . $conf_email );
		
		//Start xml write process
		if( !empty($save_path) ) {
			
			$doc = new DomDocument( '1.0', 'UTF-8' );
			
			//Create publisher-upload-manifest element
			$pub_manifest 		= $doc->createElement( 'publisher-upload-manifest' );
			$pub_manifest_node	= $doc->appendChild( $pub_manifest );
			
			//Add publisher-upload-manifest attributes
			$pub_manifest_node->setAttribute( 'publisher-id', $this->publisher_id );
			$pub_manifest_node->setAttribute( 'preparer', $this->preparer );
			$pub_manifest_node->setAttribute( 'report-success', 'true' );
			
			//Create notify element
			$notify 		= $doc->createElement( 'notify' );
			$notify_node	= $pub_manifest->appendChild( $notify );
			
			//Add callback attributes
			$notify_node->setAttribute( 'email', $conf_email );
			
			
			//Create callback element
			//$callback 		= $doc->createElement( 'callback' );
			//$callback_node	= $pub_manifest->appendChild( $callback );
			
			//Add callback attributes
			//$callback_node->setAttribute( 'entity-url', $this->callback_url );
			
			
			
			//Create video file elements
			foreach( $validated_attachments as $field_id => $upload_fields ) {
				foreach( $upload_fields as $key => $upload_data ) {
					
					//Init vars
					$file_path_info	= NULL;
					$basename		= NULL;
					$filename 		= NULL;
					$ref_id			= NULL;
					
					if( isset($upload_data['file_path'], $upload_data['wp_attachement_id']) ) {
						
						//Get file path info
						$file_path_info = pathinfo( $upload_data['file_path'] );
						
						//Create and add Asset element for this file
						if( isset($file_path_info['basename'], $file_path_info['filename']) ) {
						
							$basename = $file_path_info['basename'];
							
							$filename = $file_path_info['filename'];
							
							//Cache ref_id - format:: podders_{$wp_attachement_id}
							$ref_id = 'podders_' . rand(0, 1000) . '_' . $upload_data['wp_attachement_id'];
							
							//Add Asset element to manifest for this file
							$file_asset 		= $doc->createElement( 'asset' );
							$file_asset_node	= $pub_manifest->appendChild( $file_asset );
							
							//Add Asset attributes
							$file_asset_node->setAttribute( 'filename', $basename );	
							$file_asset_node->setAttribute( 'refid', $ref_id );
							$file_asset_node->setAttribute( 'type', $type );
							$file_asset_node->setAttribute( 'encode-to', $encode_to );
							$file_asset_node->setAttribute( 'encode-multiple', $encode_multiple );
													
						}
						
						//Create and add Title element for this file
						if( isset($ref_id, $upload_data['title'], $upload_data['description']) ) {
							
							//Add Title element to manifest for this file
							$file_title			= $doc->createElement( 'title' );
							$file_title_node	= $pub_manifest->appendChild( $file_title );
							
							//Add Title attributes
							$file_title_node->setAttribute( 'name', $upload_data['title'] );
							$file_title_node->setAttribute( 'refid', $ref_id );
							$file_title_node->setAttribute( 'video-full-refid', $ref_id );
							
							//Create description element
							$short_desc = $doc->createElement( 'short-description', $upload_data['description'] );
							
							//Append description to Title element
							$file_title->appendChild( $short_desc );
							
						}
						
					}
					
				}
			}
			
			
			if( ($result = $doc->save( $save_path )) !== FALSE ) {
				//Return full path to manifest xml file
				$result = $save_path;	
			} else {
				$this->plugin_error_log( 'Brightcove FTP:: Error saving Manifest xml file.' );
			}
			
		}
		
		return $result;
	}
	
	private function init_ftp_connection() {
		
		//Init vars
		$conn_id 		= FALSE;
		$login_result	= FALSE;
		
		if( isset($this->ftp_server, $this->username, $this->password) ) {
			
			//Connect to FTP server (port 21)
			$conn_id = ftp_connect( $this->ftp_server, 21 );
			
			//Check for error
			if( $conn_id !== FALSE ) {
				
				//Send access params
				$login_result = ftp_login( $conn_id, $this->username, $this->password );
				
				//Detect result - allow return of conn_id if succesfull
				if( $login_result === FALSE ) {
					$conn_id = FALSE;
					$this->plugin_error_log( 'Brightcove FTP:: Error in ftp_login.' );
				}
				
			} else {
				$this->plugin_error_log( 'Brightcove FTP:: Error in ftp_connect.' );
			}
			
		} else {
			$this->plugin_error_log( 'Brightcove FTP:: Missing FTP credentials.' );
		}
		
		return $conn_id;
	}
	
	private function upload_video_files( $validated_attachments ) {
		
		//Init vars
		$result = FALSE;
		
		//Loop validate video files array and upload each video found
		foreach( $validated_attachments as $field_id => $upload_fields ) {
			foreach( $upload_fields as $key => $upload_data ) {
				
				//Reset result just to be sure
				$result = FALSE;
				
				//Try and upload file
				if( isset($upload_data['file_path']) ) {
				
					$result = $this->upload_file_ftp( $upload_data['file_path'] );
					
					//Error check, if there was an error remove video data from array
					//This will ensure the file remains in wordpress as a backup
					if( $result === FALSE ) {
						unset( $validated_attachments[$field_id][$key] );
						$this->plugin_error_log( 'Upload Failed:: ' . $upload_data['file_path'] );
					} else {
						$this->plugin_error_log( 'File Uploaded:: ' . $upload_data['file_path'] );
					}
					
				}
				
			}
		}
		
		//Return array of files successfully uploaded
		//These will be removed from the wordpress media library and server
		return $validated_attachments;
	}
	
	private function upload_manifest_file( $manifest_path ) {
		
		//Init vars
		$result = FALSE;
			
		//Upload manifest file
		$result = $this->upload_file_ftp( $manifest_path );
		
		return $result;
	}
	
	private function upload_file_ftp( $local_file_path ) {
		
		//Init vars
		$remote_file	= NULL;
		$result 		= FALSE;
		
		//Check file exsits
		if( file_exists($local_file_path) && isset($this->ftp_conn_id) ) {
			
			//First try and get remote_file value, this is the filename of upload
			$remote_file = pathinfo( $local_file_path );
			
			if( isset($remote_file['basename']) ) {
				$remote_file = $remote_file['basename'];
			} else {
				$remote_file = NULL;
			}
			
			if( !empty($remote_file) ) {
				
				//Turn on ftp passive mode
				//ftp_pasv( $this->ftp_conn_id, TRUE );
				
				//Perform file upload
				$result = ftp_put( $this->ftp_conn_id, $remote_file, $local_file_path, FTP_ASCII );
				
			} else {
				$this->plugin_error_log( 'Brightcove FTP:: Error getting file basename.' );
			}
			
		} else {
			$this->plugin_error_log( 'Brightcove FTP:: Can\'t find file to upload.' );
		}
		
		return $result;
	}
	
}