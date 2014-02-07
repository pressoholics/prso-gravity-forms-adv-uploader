<?php
/**
 *	Class - qqFileUploader
 *	
 * 	Handles file upload requestd from Plupload.
 *	
 *	1. Creates plupload tmp folder in uploads if not created
 *	2. Adds index.php and htaccess to tmp folder to try and prevent
 *	   excution of files
 *  3. Validates file extension and mime type against wordpress allowed mimes
 *  4. Handles single or chunked upload of files
 *  5. Also runs a tmp folder cleanup after 1 week or every 1000 requests
 *
 */
class qqFileUploader {

    public $allowedExtensions 	= array();
    public $sizeLimit 			= null;
    public $inputName 			= 'file';
    public $enable_chunked		= FALSE;
    public $chunksFolder 		= 'chunks';
    public $cleanupTargetDir	= TRUE;
    public $maxFileAge			= NULL;
    public $allowed_mimes		= array();

    public $chunksCleanupProbability = 0.001; // Once in 1000 requests on avg
    public $chunksExpireIn = 604800; // One week

    protected $uploadName;
    
    private $uuid = NULL; //Upload ID

    function __construct(){
    
        $this->sizeLimit = $this->toBytes(ini_get('upload_max_filesize'));
        
    }

    /**
     * Get the original filename
     */
    public function getName(){
        if (isset($_REQUEST['name']))
            return esc_attr( $_REQUEST['name'] );

        if (isset($_FILES[$this->inputName]))
            return esc_attr( $_FILES[$this->inputName]['name'] );
    }

    /**
     * Get the name of the uploaded file
     */
    public function getUploadName(){
        return $this->uploadName;
    }
    
    /**
     * Process the upload.
     * @param string $uploadDirectory Target directory.
     * @param string $name Overwrites the name of the file.
     */
    public function handleUpload($uploadDirectory, $name = null){
	    
	    //Init vars
	    $file_info = array();
	    $chunked_input_data_stream = NULL;
	    
	    //Cache upload id for current file
	    $this->uuid = current( explode('.', $this->getName()) );
	    
        if (is_writable($this->chunksFolder) &&
            1 == mt_rand(1, 1/$this->chunksCleanupProbability)){

            // Run garbage collection
            $this->cleanupChunks();
        }

        // Check that the max upload size specified in class configuration does not
        // exceed size allowed by server config
        if( $this->enable_chunked === FALSE ){
	       
	       if ($this->toBytes(ini_get('post_max_size')) < $this->sizeLimit ||
	            $this->toBytes(ini_get('upload_max_filesize')) < $this->sizeLimit){
	            $size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';
	            return array(
	            	'result' 	=> 'error',
	            	'file_uid'	=> $this->uuid,
	            	'error' 	=> array( 
	            		'code' => 100,
	            		'message' => __( "Server Error. Max file size too high, try activate chunking.", "prso-gforms-plupload") 
	            	)
	            );
	        }
	        
        }
        
        
        //First check to see if requested uploads dir exists, if not make it
        if( !file_exists($uploadDirectory) ) {
	        mkdir($uploadDirectory);
	        chmod($uploadDirectory, 0744);
	        
	        //Add index.php to folder to stop direct access via browser
	        $index_content = '<?php //Nothing to see here';
	        $index_file = fopen( $uploadDirectory . '/index.php', 'w' );
	        if( $index_file !== FALSE ) {
		        fwrite($index_file, $index_content);
		        fclose($index_file);
	        }
	        
	        //Add .htaccess file to folder to prevent the server from running scripts
	        $htaccess_content = NULL;
	        ob_start();
	        ?>
	        ForceType application/octet-stream
			<FilesMatch "(?i)\.jpe?g$">
			    ForceType image/jpeg
			</FilesMatch>
			<FilesMatch "(?i)\.gif$">
			    ForceType image/gif
			</FilesMatch>
			<FilesMatch "(?i)\.png$">
			    ForceType image/png
			</FilesMatch>
	        <?php
	        $htaccess_content = ob_get_contents();
	        ob_end_clean();
	        
	        $htaccess_file = fopen( $uploadDirectory . '/.htaccess', 'w' );
	        if( $htaccess_file !== FALSE ) {
		        fwrite($htaccess_file, $htaccess_content);
		        fclose($htaccess_file);
	        }
        }
        
        if (!is_writable($uploadDirectory)){
            return array(
	            	'result' 	=> 'error',
	            	'file_uid'	=> $this->uuid,
	            	'error' 	=> array( 
	            		'code' => 100,
	            		'message' => __( "Server error. Uploads directory isn't writable or executable.", "prso-gforms-plupload") 
	            	)
	            );
        }

        if(!isset($_SERVER['CONTENT_TYPE'])) {
        
            return array(
            	'result' 	=> 'error',
            	'file_uid'	=> $this->uuid,
            	'error' 	=> array( 
            		'code' => 100,
            		'message' => "No files were uploaded." 
            	)
            );
            
        } else if (strpos(strtolower($_SERVER['CONTENT_TYPE']), 'multipart/') !== 0){
            return array(
            	'result' 	=> 'error',
            	'file_uid'	=> $this->uuid,
            	'error' 	=> array( 
            		'code' => 100,
            		'message' => __( "Server error. Not a multipart request. Please set forceMultipart to default value (true).", "prso-gforms-plupload") 
            	)
            );
        }

        // Get size and name
        $file = array_map( 'esc_attr', $_FILES[$this->inputName] );
        $size = $file['size'];

        if ($name === null){
            $name = $this->getName();
        }

        // Validate name

        if ($name === null || $name === ''){
            return array(
            	'result' 	=> 'error',
            	'file_uid'	=> $this->uuid,
            	'error' 	=> array( 
            		'code' => 100,
            		'message' => __( "File name is empty.", "prso-gforms-plupload") 
            	)
            );
        }

        // Validate file size

        if ($size == 0){
            return array(
            	'result' 	=> 'error',
            	'file_uid'	=> $this->uuid,
            	'error' 	=> array( 
            		'code' => 100,
            		'message' => __( "File is empty.", "prso-gforms-plupload") 
            	)
            );
        }

        if ($size > $this->sizeLimit){
            return array(
            	'result' 	=> 'error',
            	'file_uid'	=> $this->uuid,
            	'error' 	=> array( 
            		'code' => 100,
            		'message' => sprintf( __("File is too large. Max %s M", "prso-gforms-plupload"), $this->sizeLimit )
            	)
            );
        }
        
        // Remove old temp files	
		if ($this->cleanupTargetDir && is_dir($uploadDirectory) && ($dir = opendir($uploadDirectory))) {
			$this->maxFileAge = 5 * 3600; // Temp file age in seconds (5 hrs)
			while (($file = readdir($dir)) !== false) {
				$tmpfilePath = $uploadDirectory . DIRECTORY_SEPARATOR . $file;
		
				// Remove temp file if it is older than the max age and is not the current file
				if ((filemtime($tmpfilePath) < time() - $this->maxFileAge) && ($tmpfilePath != "{$name}.part")) {
					@unlink($tmpfilePath);
				}
			}
		
			closedir($dir);
		}
        
		//Check for chunked uploads
        $totalParts = isset($_REQUEST['chunks']) ? (int)$_REQUEST['chunks'] : 1;
		
		//Handle chunked uploads
        if ($totalParts > 1){

            $chunksFolder = $this->chunksFolder;
            
            //First check to see if requested uploads dir exists, if not make it
	        if( !file_exists($chunksFolder) ) {
		        mkdir($chunksFolder);
		        chmod($chunksFolder, 0744);
	        }
            
            $partIndex = (int)$_REQUEST['chunk'];

            if (!is_writable($chunksFolder)){
                return array(
	            	'result' 	=> 'error',
	            	'file_uid'	=> $this->uuid,
	            	'error' 	=> array( 
	            		'code' => 100,
	            		'message' => __( "Server error. Chunks directory isn't writable or executable.", "prso-gforms-plupload")
	            	)
	            );
            }

            $targetFolder = $this->chunksFolder.DIRECTORY_SEPARATOR.$this->uuid;

            if (!file_exists($targetFolder)){
                mkdir($targetFolder);
            }
            
            //Cache a unique tmp file path in chunks dir to buffer the file chunks
            $tmp_chunk_file_path = $targetFolder."/{$name}.part";
            
            //Open the temp file
            $out = @fopen($tmp_chunk_file_path, $partIndex == 0 ? "wb" : "ab");
            
            //If tmp file has been opened successfully start to write the stream to it
        	if ($out) {
        	
				// Read binary input stream and append it to temp file
				$chunked_input_data_stream = esc_attr( $_FILES[$this->inputName]['tmp_name'] );
				$in = @fopen($chunked_input_data_stream, "rb");
				
				//If stream file has been opened then start to write the tmp file to the desintaion file
				if ($in) {
					
					//Note we are reading in small sections of 4096 bytes
					while ($buff = fread($in, 4096))
						fwrite($out, $buff);
					
				} else {
					return array(
		            	'result' 	=> 'error',
		            	'file_uid'	=> $this->uuid,
		            	'error' 	=> array( 
		            		'code' => 100,
		            		'message' => __( "Failed to open input stream", "prso-gforms-plupload")
		            	)
		            );
				}
				
				@fclose($in);
				@fclose($out);
				
			} else {
				return array(
		            	'result' 	=> 'error',
		            	'file_uid'	=> $this->uuid,
		            	'error' 	=> array( 
		            		'code' => 100,
		            		'message' => __( "Failed to open chunk destination file", "prso-gforms-plupload")
		            	)
		            );
			}
            
            //So we have buffered the last chunk of the stream lets move the file into the main dir
            if( $totalParts-1 == $partIndex ) {
	            
	            $file_info = $this->getUniqueTargetPath($uploadDirectory, $name);
	            
	            if( isset($file_info['file_path']) ) {
		            
		            $target = esc_attr( $file_info['file_path'] );
		            
		            if( $this->move_file($tmp_chunk_file_path, $target) ) {
		            	
		            	//Validate whole tmp file
			            if( ( $validate_result = $this->validateUploadedFile($name, $target) ) !== TRUE ) {
					        
					        //Delete files
					        @unlink($tmp_chunk_file_path);
					        @unlink($target);
					        
					        //Return result to user
					        return $validate_result;
					        
				        }
		            	
		            	//Remove the chunk tmp folder for this file
		            	rmdir($targetFolder);
		            	
		            	//Return that all is ok
			            return array(
			            	'result' 	=> 'success',
			            	'file_uid'	=> $this->uuid,
			            	'success' 	=> array( 
			            		"file_id" => $file_info['file_name']
			            	)
			            );
			            
		            } else {
			            return array(
			            	'result' 	=> 'error',
			            	'file_uid'	=> $this->uuid,
			            	'error' 	=> array( 
			            		'code' => 100,
			            		'message' => __( "Failed to move final buffer file", "prso-gforms-plupload")
			            	)
			            );
		            }
		            
	            } else {
		            return array(
			            	'result' 	=> 'error',
			            	'file_uid'	=> $this->uuid,
			            	'error' 	=> array( 
			            		'code' => 100,
			            		'message' => __( "Error generating final file path", "prso-gforms-plupload")
			            	)
			            );
	            }
	            
            }
            
            return array("success" => true);

        } else {
	        
	        //Validate file for NON-Chunked uploads
	        if( ( $validate_result = $this->validateUploadedFile($name) ) !== TRUE ) {
		        //Delete files
				@unlink($_FILES[$this->inputName]['tmp_name']);
		        
		        return $validate_result;
	        }
	        
	        $file_info = $this->getUniqueTargetPath($uploadDirectory, $name);
	        $this->uuid = current( explode('.', $name) );
	        
	        if( isset($file_info['file_name'], $file_info['file_path'], $_FILES[$this->inputName]['tmp_name']) ) {
		        
		        $target = $file_info['file_path'];

	            if ($target){
	                $this->uploadName = basename($target);

	                if (move_uploaded_file($_FILES[$this->inputName]['tmp_name'], $target)){
	                    return array(
			            	'result' 	=> 'success',
			            	'file_uid'	=> $this->uuid,
			            	'success' 	=> array( 
			            		"file_id" => $file_info['file_name']
			            	)
			            );
	                }
	            }
		        
	        }
	        
            return array(
            	'result' 	=> 'error',
            	'file_uid'	=> $this->uuid,
            	'error' 	=> array( 
            		'code' => 100,
            		'message' => __( "The upload was cancelled, or server error encountered", "prso-gforms-plupload")
            	)
            );
        }
    }
	
	/**
	* validateUploadedFile
	* 
	* Validates both a files extension and then the mime type
	* Mime type is compared to the wordpress allowed mime types array
	* 
	* Note that the method prefers to use finfo to check the mime type but falls
	* back to mime_content_type() and then no mime validation if neither function is available
	* 
	* @param	string	$name
	* @param	string	$file_path - defaults to $_FILES[$this->inputName]['tmp_name']
	* @return	mixed	array/bool
	* @access 	protected
	* @author	Ben Moody
	*/
	protected function validateUploadedFile( $name = NULL, $file_path = NULL ) {
		
		//Init vars
		$mime_type = NULL;
		
		if( !isset($file_path) ) {
			$file_path = $_FILES[$this->inputName]['tmp_name'];
		}
		
		// Validate file extension
        $pathinfo = pathinfo($name);
        $ext = isset($pathinfo['extension']) ? $pathinfo['extension'] : '';

        if($this->allowedExtensions && !in_array(strtolower($ext), array_map("strtolower", $this->allowedExtensions))){
            $these = implode(', ', $this->allowedExtensions);
            
            return array(
            	'result' 	=> 'error',
            	'file_uid'	=> $this->uuid,
            	'error' 	=> array( 
            		'code' => 100,
            		'message' => sprintf( __("File has an invalid extension, it should be one of %s.", "prso-gforms-plupload"), $these )
            	)
            );
            
        }
       
		//VALIDATE MIME TYPE - comapre to wordpress allowed mime types array
		
		//First check which php tools we have
		if( function_exists('finfo_open') ) {
		
			$finfo 		= finfo_open(FILEINFO_MIME_TYPE);
			$mime_type	= finfo_file($finfo, $file_path);
			finfo_close($finfo);
	        
		} elseif( function_exists('mime_content_type') ) {
		
			$mime_type	= mime_content_type( $file_path );
			
		}
		
		//Stop nasty mime types
        if( !empty($mime_type) && !in_array($mime_type, array_values($this->allowed_mimes)) ) {
	        
	        return array(
            	'result' 	=> 'error',
            	'file_uid'	=> $this->uuid,
            	'error' 	=> array( 
            		'code' => 100,
            		'message' => sprintf( __("File Type Error: %s.", "prso-gforms-plupload"), $mime_type )
            	)
            );
	        
        }
		
		return TRUE;
	}
	
    /**
     * Returns a path to use with this upload. Check that the name does not exist,
     * and appends a suffix otherwise.
     * @param string $uploadDirectory Target directory
     * @param string $filename The name of the file to use.
     */
    protected function getUniqueTargetPath($uploadDirectory, $filename)
    {
        // Allow only one process at the time to get a unique file name, otherwise
        // if multiple people would upload a file with the same name at the same time
        // only the latest would be saved.

        if (function_exists('sem_acquire')){
            $lock = sem_get(ftok(__FILE__, 'u'));
            sem_acquire($lock);
        }

        $pathinfo = pathinfo($filename);
        $base = $pathinfo['filename'];
        $ext = isset($pathinfo['extension']) ? $pathinfo['extension'] : '';
        $ext = $ext == '' ? $ext : '.' . $ext;

        $unique = $base;
        $suffix = 0;
        
        //Create a new random name for file - security reasons
        if( isset($filename) ) {
	        $unique = md5( $filename );
        }
        
        // Get unique file name for the file, by appending random suffix.
        
        while (file_exists($uploadDirectory . DIRECTORY_SEPARATOR . $unique . $ext)){
            $suffix += rand(1, 999);
            $unique = $unique.'-'.$suffix;
        }
        
        $result['file_name'] = $unique . $ext;
        
        $result['file_path'] =  $uploadDirectory . DIRECTORY_SEPARATOR . $unique . $ext;

        // Create an empty target file
        if (!touch($result['file_path'])){
            // Failed
            $result = false;
        }

        if (function_exists('sem_acquire')){
            sem_release($lock);
        }

        return $result;
    }
    
    /**
     * Deletes all file parts in the chunks folder for files uploaded
     * more than chunksExpireIn seconds ago
     */
    protected function cleanupChunks(){
        foreach (scandir($this->chunksFolder) as $item){
            if ($item == "." || $item == "..")
                continue;

            $path = $this->chunksFolder.DIRECTORY_SEPARATOR.$item;

            if (!is_dir($path))
                continue;

            if (time() - filemtime($path) > $this->chunksExpireIn){
                $this->removeDir($path);
            }
        }
    }

    /**
     * Removes a directory and all files contained inside
     * @param string $dir
     */
    protected function removeDir($dir){
        foreach (scandir($dir) as $item){
            if ($item == "." || $item == "..")
                continue;

            unlink($dir.DIRECTORY_SEPARATOR.$item);
        }
        rmdir($dir);
    }

    /**
     * Converts a given size with units to bytes.
     * @param string $str
     */
    protected function toBytes($str){
        $val = trim($str);
        $last = strtolower($str[strlen($str)-1]);
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;
        }
        return $val;
    }
    
    /**
	* move_file
	* 
	* Helper to move a file from one path to another
	* Paths are full paths to a file including filename and ext
	* 
	* @access 	private
	* @author	Ben Moody
	*/
	private function move_file( $current_path = NULL, $destination_path = NULL ) {
		
		//Init vars
		$result = FALSE;
		
		if( isset($current_path) && file_exists($current_path) ) {
			
			//First check if destination dir exists if not make it
			if( !file_exists(dirname($destination_path)) ) {		
		        mkdir( dirname($destination_path) );
	        }
			
			if( file_exists(dirname($destination_path)) ) {
			        
		        //Move file into dir
		        if( copy($current_path, $destination_path) ) {
			        unlink($current_path);
			        
			        if( file_exists($destination_path) ) {
				        $result = TRUE;
			        }
			        
		        }
		        
	        }
			
		}
		
		return $result;
	}
    
}