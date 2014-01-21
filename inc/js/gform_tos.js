(function($) {

	jQuery(document).ready(function($) {
		
		//Disable the gravity form submit button
		$(".gform_footer input[type='submit']").prop("disabled",true);
		
		//Listen for when user scrolls down the entire tos field
		//Then enable the submit button
		$(".gform_body textarea.gform_tos").scroll(function(){
			if($(this).scrollTop()+$(this).height() >= $(this)[0].scrollHeight-10){
				$(".gform_footer input[type='submit']").prop("disabled",false);
			}
		});
		
	});

})(jQuery);