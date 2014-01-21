(function($) {

	jQuery(document).ready(function($) {
		
		//Init vars
		var LocalizedPluginVars = WpPrsoPluploadPluginVars;
		
		//console.log(LocalizedPluginVars);
		
		//Loop the plupload field init vars from wordpress and init plupload for each one
		jQuery.each( LocalizedPluginVars, function( key, PrsoPluploadVars ){
			
			//Init Plupload
			jQuery("#" + PrsoPluploadVars.element).plupload({
				// General settings
				runtimes : PrsoPluploadVars.runtimes,
				url : PrsoPluploadVars.wp_ajax_url,
				max_file_size : PrsoPluploadVars.max_file_size,
				max_file_count: PrsoPluploadVars.max_files, // user can add no more then x files at a time
				chunk_size: PrsoPluploadVars.chunking,
				unique_names : true,
				multiple_queues : true,
				multipart_params : { 
					'action': 'prso-plupload-submit', 
					'currentFormID': PrsoPluploadVars.params.form_id,
					'currentFieldID': PrsoPluploadVars.params.field_id,
					'nonce': PrsoPluploadVars.params.nonce,
				},
		
				// Specify what files to browse for
				filters : [
					{
						title : "files", 
						extensions : PrsoPluploadVars.filters.files
					}
				],
		
				// Flash settings
				flash_swf_url : PrsoPluploadVars.flash_url,
		
				// Silverlight settings
				silverlight_xap_url : PrsoPluploadVars.silverlight_url,
				
				//Post init events
				init : {
					Error: function(up, response) {
						
						
					},
					FileUploaded: function(up, file, response) {
						
						//Called when a file finishes uploading
						
						var obj = jQuery.parseJSON(response.response);
						
						//Detect error
						if( obj.result === 'error' ) {
							
							//Alert user of error
							up.trigger('Error', {
								code : obj.error.code,
								message : obj.error.message,
								details : 'upload for' + file.name + ' failed.',
								file : file
							});
							
							
						} else if( obj.result === 'success' ) {
							
							var inputField = '<input id="gform-plupload-'+ obj.file_uid +'" type="hidden" name="plupload['+ PrsoPluploadVars.params.field_id +'][]" value="'+ obj.success.file_id +'"/>';
							
							jQuery('#gform_' + PrsoPluploadVars.params.form_id).append(inputField);
							
						} else {
							
							//General error
							up.trigger('Error', {
								code : 300,
								message : 'Server Error. File might be too large.',
								details : 'upload for' + file.name + ' failed.',
								file : file
							});
							
						}
						
					},
					FilesAdded: function(up, files) {
					
						//Remove files if max limit reached
		                plupload.each(files, function(file) {
		                	
		                	//File added result
		                	var file_added_result = true;
		                	
		                    if (up.files.length > PrsoPluploadVars.max_files) {
		                        up.removeFile(file);
		                        
		                        file_added_result = false;
		                    }
		                    
		                    //Prevent duplicate files
		                    var upa = jQuery('#' + PrsoPluploadVars.element).plupload('getUploader');
		                    var i = 0;
		                    while (i <= upa.files.length) {
		                        ultimo = upa.files.length;
		                        if (ultimo > 1) {
		                            if (i > 0) {
		                                ultimo2 = ultimo - 1;
		                                ii = i-1;
		                                if (ultimo2 != ii) {
		                                    if (up.files[ultimo - 1].name == upa.files[i-1].name) {
		                                        up.removeFile(file);
		                                        
		                                        file_added_result = false;
		                                    }
		                                }
		                            }
		                        }
		                        i++;
		                    }
		                    
		                    //If file added then check if auto upload isset
		                    if( file_added_result === true ) {
		                    	if( PrsoPluploadVars.auto_upload === true ) {
		                    		up.start();
		                    	}
		                    }
		                    
		                });
		                
		            },
					FilesRemoved: function(up, files) {
						
						//Remove hidden gforms input for this file
						jQuery("#gform-plupload-" + files[0].id).remove();
					
					}
				}
				
			});
			
		});
		
	});

})(jQuery);