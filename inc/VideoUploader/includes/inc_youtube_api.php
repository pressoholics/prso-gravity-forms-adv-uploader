<?php
/**
 *	PrsoAdvYoutubeApi
 *	
 *	Extends PrsoAdvVideoUploader Class which in turn calls $this->init_api()
 *	to kick off the upload process
 *
 *	Zend YouTube Api Class - This class makes use of the Zend youtube class to upload files
 *	
 * https://developers.google.com/youtube/v3/code_samples/php#resumable_uploads
 */
class PrsoAdvYoutubeApi extends PrsoAdvVideoUploader {
	
	protected $youtube_uploads_url = 'http://uploads.gdata.youtube.com/feeds/api/users/default/uploads';
	
	/**
	* init_api
	* 
	* Called by Video Uploader class to start the YouTube upload process
	* Passes an array of validated video files as the param
	*
	* @param	$validated_attachments - Array of validated video files to be uploaded
	* @access 	public
	* @author	Ben Moody
	*/
	public function init_api( $validated_attachments ) {
		
		//Init the youtube api
		$this->init_youtube_api();
		
		//Process video uploads
		return $this->init_youtube_uploads( $validated_attachments );
		
	}
	
	/**
	* get_video_url
	* 
	* Helper to get full url to videos uploaded to youtube.
	*
	* @param	$video_id - YouTube video ID
	* @access 	private
	* @author	Ben Moody
	*/
	public function get_video_url( $video_id = NULL ) {
		
		//Init vars
		$video_entry = NULL;
		
		if( isset($video_id) ) {
			
			//Init the youtube api
			$this->init_youtube_api();
			
			//Get data on video
			$video_entry = $this->data['YouTubeClass']->getVideoEntry( $video_id );
			
			return $video_entry->getVideoWatchPageUrl();
		}
		
	}
	
	/**
	* init_youtube_api
	* 
	* Initiates YouTube API
	*
	* @access 	private
	* @author	Ben Moody
	*/
	private function init_youtube_api() {
		
		//Init vars
		$returned_video_data = array();
		$plugin_options		= array();
		
		$this->data['is_google_oauth2'] = FALSE;
		
		//First try and get the plugin options
		if( isset($this->plugin_options_slug) ) {
			$plugin_options = get_option( $this->plugin_options_slug );
		}
		
		//Try and get oauth token
		$oauth_token = get_option( 'prso_gforms_adv_youtube_token' );
		
		//Detect which version of the api we are using via options
		
		//Confirm required options are set
		if( $plugin_options !== FALSE && isset($oauth_token) && !empty($oauth_token) ) {
			
			$this->plugin_error_log( 'Using new api' );
			
			//Cache path to google api php client library
			$this->data['google_api_library_path'] = $this->plugin_includes . '/Google';
			
			// Call set_include_path() as needed to point to your client library.
			require_once $this->data['google_api_library_path'] . DIRECTORY_SEPARATOR . 'autoload.php';
			require_once $this->data['google_api_library_path'] . DIRECTORY_SEPARATOR . 'Client.php';
			require_once $this->data['google_api_library_path'] . DIRECTORY_SEPARATOR . 'Service' . DIRECTORY_SEPARATOR . 'YouTube.php';
			
			
			$this->data['is_google_oauth2'] = TRUE;
			
			$this->init_youtube_oauth2_api( $oauth_token );
			
		
		} elseif( $plugin_options !== FALSE && isset($plugin_options['youtube_api_key_text'], $plugin_options['youtube_username_text'], $plugin_options['youtube_password_text']) ) {
			
			$this->plugin_error_log( 'Using old api' );
			
			//Cache path to zend library
			$this->data['zend_library_path'] = $this->plugin_includes . '/Zend';
			
			set_include_path($this->plugin_includes . PATH_SEPARATOR . get_include_path());
			
			//Setup YouTube account details
			$this->data['http_client_args'] = array(
				'username'	=>	esc_attr( $plugin_options['youtube_username_text'] ),
				'password'	=>	esc_attr( $plugin_options['youtube_password_text'] )
			);
			
			//Cache developer key
			$this->data['developer_key'] = esc_attr( $plugin_options['youtube_api_key_text'] );
			
			//Initialize zend youtube api object
			if( !isset($this->data['YouTubeClass']) ) {
				$this->init_youtube_api_obj();
			}
			
		} else {
			
			$this->plugin_error_log( 'YouTube API:: Missing api key, username, password, or all of them.' );
			
		}
		
	}
	
	/**
	* init_youtube_uploads
	* 
	* Starts the entire upload process
	*
	* @param	$validated_attachments 	- Array of validated video uploads
	* @return	$returned_video_data	- Updated array of video uploads /w youtube video id's added after upload
	* @access 	private
	* @author	Ben Moody
	*/
	private function init_youtube_uploads( $validated_attachments ) {
		
		//Upload videos to youtube and cache the resulting array of video id/data
		$returned_video_data = $this->youtube_api_process_videos( $validated_attachments );
		
		//Now cache the returned video id for each video uploaded
		$returned_video_data = $this->youtube_api_cache_video_id( $returned_video_data );
		
		return $returned_video_data;
		
	}
	
	private function init_youtube_oauth2_api( $access_token ) {
		
		$OAUTH2_CLIENT_ID 		= PrsoGformsAdvUploaderInit::$google_oauth_client_id;
		$OAUTH2_CLIENT_SECRET 	= PrsoGformsAdvUploaderInit::$google_oauth_client_secret;
		
		$client = new Google_Client();
		$client->setClientId($OAUTH2_CLIENT_ID);
		$client->setClientSecret($OAUTH2_CLIENT_SECRET);
		$client->setScopes('https://www.googleapis.com/auth/youtube');
		$client->setRedirectUri( PrsoGformsAdvUploaderInit::$google_oauth_redirect );
		
		// Define an object that will be used to make all API requests.
		$youtube = new Google_Service_YouTube($client);
		
		//Refresh access token
		$AccessData = json_decode( $access_token );	
		if( isset($AccessData->refresh_token) ) {
			$client->refreshToken( $AccessData->refresh_token );
		}
		
		$client->setAccessToken( $access_token );
		
		if( $client->getAccessToken() ) {
		
			$this->data['YouTubeClass']['client'] 	= $client;
			$this->data['YouTubeClass']['youtube'] 	= $youtube;
			
		} else {
			
			$this->plugin_error_log( 'YouTube API:: Access token not valid, need to refresh' );
			
		}
		
	}
	
	/**
	* init_youtube_api_obj
	* 
	* Iniates the Zend YouTube API library
	* 
	* The Init class is stored in $this->data['YouTubeClass']
	*
	* @access 	private
	* @author	Ben Moody
	*/
	private function init_youtube_api_obj() {
		
		//Init vars
		$zend_loader_path 	= $this->data['zend_library_path'] . '/Loader.php';
		$zend_youtube_class = 'Zend_Gdata_YouTube';
		$client_login_class = 'Zend_Gdata_ClientLogin';
		$authenticationUrl	= 'https://www.google.com/accounts/ClientLogin';
		$httpClient			= NULL;
		
		$app_args = array(
			'applicationId'	=>	'Gforms YouTube Uploader WP Plugin',
			'clientId'		=>	NULL
		);
		
		extract($app_args);
		
		extract($this->data['http_client_args']);
		
		//Require zend loader class
		if( file_exists($zend_loader_path) ) {
			
			require_once( $zend_loader_path );
			
			//Load core youtube api class
			Zend_Loader::loadClass( $zend_youtube_class );
			
			//Load youtube ClientLogin class
			Zend_Loader::loadClass( $client_login_class );
			
			$httpClient = Zend_Gdata_ClientLogin::getHttpClient(
				$username,
				$password,
				$service		=	'youtube',
				$client 		=	NULL,
				$source			=	'Gravity Forms YouTube Uploader Wordpress Plugin',
				$loginToken		=	NULL,
				$loginCaptcha	=	NULL,
				$authenticationUrl
			);
			
			//Cache instance of authenticated class
			$this->data['YouTubeClass'] = new Zend_Gdata_YouTube( $httpClient, $applicationId, $clientId, $this->data['developer_key'] );
			
		} else {
			$this->plugin_error_log( 'YouTube API:: Problem loading YouTube Zend Library.' );
		}
		
	}
	
	/**
	* youtube_api_process_videos
	* 
	* Loops array of video attachments at call method to upload them to youtube.
	* 
	* @param	Array		$validated_attachments - Array of all video attachments
	* @access 	private
	* @author	Ben Moody
	*/
	private function youtube_api_process_videos( $validated_attachments ) {
		
		//Loop each attachment video and try to upload each to youtube
		foreach( $validated_attachments as $field_id => $attachments ) {
			
			foreach( $attachments as $key => $attachment_data ) {
				
				//Detect which api we are using
				if( TRUE === $this->data['is_google_oauth2'] ) {
					
					$validated_attachments[$field_id][$key]['video_data'] = $this->youtube_oauth2_upload_video( $attachment_data );
					
				} else {
					$validated_attachments[$field_id][$key]['video_data'] = $this->youtube_api_upload_video( $attachment_data );
				}
				
			}
			
		}
		
		return $validated_attachments;
	}
	
	private function youtube_oauth2_upload_video( $attachment_data ) {
		
		//Init vars
		$file_type			= NULL;
		$path_info			= NULL;
		$myVideoEntry 		= NULL;
		$uploadUrl			= NULL;
		$filesource			= NULL;
		$newEntry			= NULL;
		$output				= NULL;
		
		//Cache plugin options
 		$plugin_options = get_option( PRSOGFORMSADVUPLOADER__OPTIONS_NAME );
		
		//cache client object from youtube api
		$client 	= $this->data['YouTubeClass']['client'];
		$youtube	= $this->data['YouTubeClass']['youtube'];
		
		//Check for required data
		if( isset($attachment_data['file_path'], $attachment_data['mime_type'], $attachment_data['title'], $attachment_data['description']) ) {
		
			try{
			
			    // REPLACE this value with the path to the file you are uploading.
			    $videoPath = $attachment_data['file_path'];
			
			    // Create a snippet with title, description, tags and category ID
			    // Create an asset resource and set its snippet metadata and type.
			    // This example sets the video's title, description, keyword tags, and
			    // video category.
			    $snippet = new Google_Service_YouTube_VideoSnippet();
			    $snippet->setTitle( $attachment_data['title'] );
			    $snippet->setDescription( $attachment_data['description'] );
			    //$snippet->setTags(array("tag1", "tag2"));
			
			    // Numeric video category. See
			    // https://developers.google.com/youtube/v3/docs/videoCategories/list 
			    //$snippet->setCategoryId("22");
			
			    // Set the video's status to "public". Valid statuses are "public",
			    // "private" and "unlisted".
			    $status = new Google_Service_YouTube_VideoStatus();

				if( $plugin_options['video_is_private'] ){
					$status->privacyStatus = "private";
				} else {
					$status->privacyStatus = "public";
				}
			
			    // Associate the snippet and status objects with a new video resource.
			    $video = new Google_Service_YouTube_Video();
			    $video->setSnippet($snippet);
			    $video->setStatus($status);
			
			    // Specify the size of each chunk of data, in bytes. Set a higher value for
			    // reliable connection as fewer chunks lead to faster uploads. Set a lower
			    // value for better recovery on less reliable connections.
			    $chunkSizeBytes = 1 * 1024 * 1024;
			
			    // Setting the defer flag to true tells the client to return a request which can be called
			    // with ->execute(); instead of making the API call immediately.
			    $client->setDefer(true);
			
			    // Create a request for the API's videos.insert method to create and upload the video.
			    $insertRequest = $youtube->videos->insert("status,snippet", $video);
			
			    // Create a MediaFileUpload object for resumable uploads.
			    $media = new Google_Http_MediaFileUpload(
			        $client,
			        $insertRequest,
			        'video/*',
			        null,
			        true,
			        $chunkSizeBytes
			    );
			    $media->setFileSize(filesize($videoPath));
			
			
			    // Read the media file and upload it chunk by chunk.
			    $status = false;
			    $handle = fopen($videoPath, "rb");
			    while (!$status && !feof($handle)) {
			      $chunk = fread($handle, $chunkSizeBytes);
			      $status = $media->nextChunk($chunk);
			    }
			
			    fclose($handle);
			
			    // If you want to make other calls after the file upload, set setDefer back to false
			    $client->setDefer(false);
				
			    return $status;
			
			  } catch (Google_Service_Exception $e) {
			  	
			  	$this->plugin_error_log( 
			  		sprintf('<p>A service error occurred: <code>%s</code></p>',
			        htmlspecialchars($e->getMessage())) 
			    );
			  	
			  } catch (Google_Exception $e) {
			  	
			  	$this->plugin_error_log( 
			  		sprintf('<p>A service error occurred: <code>%s</code></p>',
			        htmlspecialchars($e->getMessage())) 
			    );
			  	
			  }
		
		}
		
	}
	
	/**
	* youtube_api_upload_video
	* 
	* Uploads a video attachment to YouTube via API. Harnesses Zend YouTube api class
	* 
	* @param	Array		$attachment_data - Video file upload data
	* @access 	private
	* @author	Ben Moody
	*/
	private function youtube_api_upload_video( $attachment_data ) {
		
		//Init vars
		$file_type			= NULL;
		$path_info			= NULL;
		$myVideoEntry 		= NULL;
		$uploadUrl			= NULL;
		$filesource			= NULL;
		$newEntry			= NULL;
		$output				= NULL;
		
		//Cache plugin options
 		$plugin_options = get_option( PRSOGFORMSADVUPLOADER__OPTIONS_NAME );
		
		//Check for required data
		if( isset($attachment_data['file_path'], $attachment_data['mime_type'], $attachment_data['title'], $attachment_data['description']) ) {
			
			// upload URI for the currently authenticated user
			$uploadUrl = $this->youtube_uploads_url;
			
			// create a new VideoEntry object
			$myVideoEntry = new Zend_Gdata_YouTube_VideoEntry();
			
			//Get file path
			$file_path = $attachment_data['file_path'];
			
			//Get file type
			$file_type 	= $attachment_data['mime_type'];
			
			//Get file slug - filename plus ext
			$path_info	= pathinfo( $file_path );
			
			// create a new Zend_Gdata_App_MediaFileSource object
			$filesource = $this->data['YouTubeClass']->newMediaFileSource( $file_path );
			$filesource->setContentType( $file_type );
			
			// set slug header
			$filesource->setSlug( $path_info['basename'] );
			
			// add the filesource to the video entry
			$myVideoEntry->setMediaSource( $filesource );
			
			$myVideoEntry->setVideoTitle( $attachment_data['title'] );
			$myVideoEntry->setVideoDescription( $attachment_data['description'] );
			
			// The category must be a valid YouTube category!
			$myVideoEntry->setVideoCategory('Autos');
			
			//Set video upload as private
			if( $plugin_options['video_is_private'] ){
				$myVideoEntry->setVideoPrivate();
			}
			
			// try to upload the video, catching a Zend_Gdata_App_HttpException, 
			// if available, or just a regular Zend_Gdata_App_Exception otherwise
			try {
			
			  $output = $this->data['YouTubeClass']->insertEntry($myVideoEntry, $uploadUrl, 'Zend_Gdata_YouTube_VideoEntry');
			  
			  
			} catch (Zend_Gdata_App_HttpException $httpException) {
			
			  	$output = $httpException->getRawResponseBody();
			  	
			  	$this->plugin_error_log( $output );
			  	
			} catch (Zend_Gdata_App_Exception $e) {
			    
			    $output = $e->getMessage();
			    
			    $this->plugin_error_log( $output );
			    
			}
			
		}
		
		return $output;
	}
	
	/**
	* youtube_api_cache_video_id
	* 
	* Loops validated file attachments array and tries to find the YouTube
	* Video ID for each attachment that was uploaded to youtube.
	*
	* It then caches the youtube video id for those attachements into the attachement
	* data array to be stored in wordpress for later use.
	* 
	* @param	Array		$validated_attachments - Array of uploaded file attachments
	* @access 	private
	* @author	Ben Moody
	*/
	private function youtube_api_cache_video_id( $validated_attachments ) {
		
		//Init vars
		$YoutubeObj = NULL;
		
		//Loop each attachment video and try to cache the video id from youtube
		foreach( $validated_attachments as $field_id => $attachments ) {
			
			foreach( $attachments as $key => $attachment_data ) {
				
				//Detect api we are using
				if( TRUE === $this->data['is_google_oauth2'] ) {
					
					$YoutubeObj = $attachment_data['video_data'];
					
					if( isset($YoutubeObj->id) ) {
						
						$video_id = esc_attr( $YoutubeObj->id );
						
						//Get video id from youtube returned object
						$validated_attachments[$field_id][$key]['video_id'] 	= $video_id;
						$validated_attachments[$field_id][$key]['video_url'] 	= esc_url( "https://www.youtube.com/watch?v={$video_id}" );
						
						//Unset youtube object from array
						unset( $validated_attachments[$field_id][$key]['video_data'] );
						
					}
					
				} else {
					
					if( isset($attachment_data['video_data']) && is_object($attachment_data['video_data']) ) {
					
						$YoutubeObj = $attachment_data['video_data'];
						
						if( method_exists($YoutubeObj, 'getVideoId') ) {
							
							//Get video id from youtube returned object
							$validated_attachments[$field_id][$key]['video_id'] = $YoutubeObj->getVideoId();
							$validated_attachments[$field_id][$key]['video_url'] = $YoutubeObj->getVideoWatchPageUrl();
							
							//Unset youtube object from array
							unset( $validated_attachments[$field_id][$key]['video_data'] );
							
						}
						
					}
					
				}
				
			}
			
		}
		
		return $validated_attachments;
	}
	
	/**
	* printVideoEntry
	* 
	* DEV ONLY: Echos out data from video entry object
	* 
	* @param	Obj		$videoEntry
	* @access 	private
	* @author	Ben Moody
	*/
	private function printVideoEntry($videoEntry) {
	  // the videoEntry object contains many helper functions
	  // that access the underlying mediaGroup object
	  echo 'Video: ' . $videoEntry->getVideoTitle() . "\n";
	  echo 'Video ID: ' . $videoEntry->getVideoId() . "\n";
	  echo 'Updated: ' . $videoEntry->getUpdated() . "\n";
	  echo 'Description: ' . $videoEntry->getVideoDescription() . "\n";
	  echo 'Category: ' . $videoEntry->getVideoCategory() . "\n";
	  echo 'Tags: ' . implode(", ", $videoEntry->getVideoTags()) . "\n";
	  echo 'Watch page: ' . $videoEntry->getVideoWatchPageUrl() . "\n";
	  echo 'Flash Player Url: ' . $videoEntry->getFlashPlayerUrl() . "\n";
	  echo 'Duration: ' . $videoEntry->getVideoDuration() . "\n";
	  echo 'View count: ' . $videoEntry->getVideoViewCount() . "\n";
	  echo 'Rating: ' . $videoEntry->getVideoRatingInfo() . "\n";
	  echo 'Geo Location: ' . $videoEntry->getVideoGeoLocation() . "\n";
	  echo 'Recorded on: ' . $videoEntry->getVideoRecorded() . "\n";
	  
	  // see the paragraph above this function for more information on the 
	  // 'mediaGroup' object. in the following code, we use the mediaGroup
	  // object directly to retrieve its 'Mobile RSTP link' child
	  foreach ($videoEntry->mediaGroup->content as $content) {
	    if ($content->type === "video/3gpp") {
	      echo 'Mobile RTSP link: ' . $content->url . "\n";
	    }
	  }
	  
	  echo "Thumbnails:\n";
	  $videoThumbnails = $videoEntry->getVideoThumbnails();
	
	  foreach($videoThumbnails as $videoThumbnail) {
	    echo $videoThumbnail['time'] . ' - ' . $videoThumbnail['url'];
	    echo ' height=' . $videoThumbnail['height'];
	    echo ' width=' . $videoThumbnail['width'] . "\n";
	  }
	}
	
}