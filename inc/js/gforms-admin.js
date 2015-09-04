jQuery(document).ready(function(){
	
	jQuery('#prso-adv-uploader-youtube-auth').click(
		function( event ) {
			
			event.preventDefault();
			
			var auth_url = jQuery(this).data( 'prso-youtube-auth' ),
			    w = 600,
				h = 500,
				left = (screen.width / 2) - (w / 2),
				top = (screen.height / 2) - (h / 2);
				
			return window.open(auth_url, 'prsoyoutubeauthcode', 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=no, copyhistory=no, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);
			
		}
	);
	
	jQuery('#redux-header').hide();
	
	jQuery('.redux-sidebar').append('<a target="_blank" href="http://shareasale.com/r.cfm?b=768137&amp;u=1029469&amp;m=41388&amp;urllink=&amp;afftrack="><img src="http://static.shareasale.com/image/41388/Fastsite_200x200-v1.jpg" border="0" alt="WP Engine Hosting" /></a>');
	
});