(function($) {
  
  $(document).ready(function(){
	  
	  //Pluing's js object storing any vars from php
	  //console.log( prso_gforms_api_upload_vars );
	  
	  //Cache current form's id
	  var formId = prso_gforms_api_upload_vars.gform_id;
	  
	  //Intercept form submit
	  $("form#" + formId).submit(function(e){
		  
		  //Hide submit button
		  $("form#" + formId + " input[type=submit]").hide();
		  
		  //Show ajax loading image
		  $(".gform-api-uploader").show();
		  
		  //Return true to allow submit to continue
		  return true;		  
	  });
	  
  });
  
})(jQuery);