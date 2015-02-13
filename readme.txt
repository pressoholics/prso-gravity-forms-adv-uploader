=== Gravity Forms Advanced File Uploader ===
Contributors: ben.moody
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html
Tags: gravity forms, gravity forms file upload, gravity forms file uploader, gravity forms uploader, plupload, gravity forms videos, gravity forms youtube, youtube uploader, youtube file uploader
Requires at least: 3.0
Tested up to: 4.1
Stable tag: 1.25

Chunked Multiple file uploads, Auto upload of videos to YouTube & Brightcove, Files stored in WP Media Library, Advanced options.

== Description ==

[youtube http://www.youtube.com/watch?v=k4cKarrr4aE]

* Need more control over Gravity Forms multiple file uploads. 
* Want to store file uploads in Wordpress media library. 
* Large file support with chunked uploads, get around server upload limits
* Like a choice of upload user interfaces (jQuery UI, Queue, Custom)
* Need advanced control over plupload options
* Would like to store uploaded videos on YouTube account (also Brightcove. Vimeo coming soon!)
* Added security and validation
* Bonus Terms of Service Gravity Forms field with optional submit disable feature
* Creating posts with gavity forms? All uploads are added as post attachments and can be displayed with the [get_adv_uploads] shortcode
* Also use the wordpress gallery shortcode to display any images attached to a post

This is the Gravity Forms uploader plugin for those who need a little more than the default multi file upload of Gravity Forms v1.8. 

Note -- if you are running an older version of Gravity Forms without the built in multi file upload you can use this (tested from v1.6 upwards).

The plugin options page provides you with granular control over many Plupload parameters from file extension filters to chunked uploading and runtimes.

All files are uploaded to the Wordpress media library on successful form submission making for easy access and management.

If you chose to activate the Video Uploader add-on the plugin will detect any video files being uploaded and automatically send them to your YouTube account as private videos awaiting review (Also includes Brightcove FTP, Vimeo API is on its way!).

For the security conscious among you the plugin takes many steps to protect the server from nasty files:

* filename encryption
* prevention of file execution in tmp folder via htaccess
* validation of both file extension and mime type
* crosscheck mime types against Wordpress mime white list
* filenames changed once added to media library

Large File Support - Enable chunked file uploads to allow for large files uploads and circumvent server uploads limits.

Advanced Customization - If you are a dev and need even more control there are a number of filters and actions to hook into. Also you can make a copy of the ini scripts used to generate each UI. Place them in your theme and just wp_dequeue_script then enqueue_script with your script path and it will have access to all the localized vars.

Please Note -- When using the Video Uploader option, although actual file upload takes place asynchronously. If your server script timeouts are too short you will have problems with larger video files. That said the plugin does try to increase the timeout but it really depends on your hosting setup.

== Installation ==

NOTE: You will require PHP iconv extension installed on server for YouTube uploader to work

1. Upload `prso-gravity-forms-adv-uploader` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Settings -> Gravity Adv Uploader to set all your awesome options

== Frequently Asked Questions ==

More info over at GitHub (https://github.com/pressoholics/prso-gravity-forms-adv-uploader).

= Notification Email Support =

Extend Gravity forms advanced uploader and show a list of all local uploads as well as any external uploads (Youtube, ect) in GravityForms user notification emails.

[Learn More about notification email support][emailaddon learnmore]

[emailaddon learnmore]: http://benjaminmoody.com/downloads/gravity-forms-adv-uploads-email-tag-addon/

= Change Plupload Language =

Use 'prso_gform_pluploader_i18n_script' filter in themes functions.php to select language for Plupload:

add_filter( 'prso_gform_pluploader_i18n_script', 'plupload_i18n' );
function plupload_i18n( $i18n_filename ) {
	
	//Use fr,js file - remove .js from filename
	$i18n_filename = 'fr';
	
	return i18n_filename;
}

See plugins/prso-gravity-forms-adv-uploader/inc/js/plupload/i18n folder for language file options.

= Entries are not appearing in admin area =

Gravity forms requires that each form has at least 1 gravity forms field to show results. So if you have just the uploader in your form try adding a text field or something similar. I will look into a work around in future updates.

= Videos are not uploaded to YouTube =

The YouTube uploader requires PHP iconv extension to work. Ask your host to install it for you.

= Files are uploading but not shown in media library =

This is probably an issue with the file being larger than PHP post size allows. Try enabling chunked uploads, and be sure that the chunked upload size is not larger than your PHP post size on the server (try 1mb if you have problems).

= How can i override the uploader UI javascript =

That depends on the UI you have set in the options:

jQuery UI:

* Copy 'init_plupload_jquery_ui.js' from plugin's js directory. Dequeue 'prso-pluploader-init' then requeue 'prso-pluploader-init' pointing to your copy of the script.

Queue UI:

* Copy 'init_plupload_queue.js' from plugin's js directory. Dequeue 'prso-pluploader-init' then requeue 'prso-pluploader-init' pointing to your copy of the script.

Custom UI:

* Copy 'init_plupload_custom.js' from plugin's js directory. Dequeue 'prso-pluploader-init' then requeue 'prso-pluploader-init' pointing to your copy of the script.

Check out the Plupload docs and you can customize anything.

= The Video Uploader addon does not work with large video files =

This is due to your server script timeout settings. The plugin does attempt to set 'max_execution_time' & 'mysql.connect_timeout', but if your host has disabled these options then i'm afraid you are stuck unless you can ask them to increase these for you or you can add your own php.ini.

= File Chunking doesnt work too well in some older browsers =

This option can be hit and miss in some older browsers, that said it works in most of them. Just test it and see.

== Screenshots ==

1. Shot of jQuery UI version.
2. Shot of Queue UI version.
3. Shot of Custom UI version - you set this badboy up!
4. The options page, lost of param goodness

== Changelog ==

= 1.25 =
* Form Submit button now hidden until uploads are completed OR there are no files in the upload queue.
* Updated Plupload framework to v2.1.2, may address some mobile issues?
* Fixed bug where Redux options framework may conflict with themes also using Redux

= 1.24 =
* Bugfix with video uploader and email notifiation addon

= 1.23 =
* Added support for addon plugins

= 1.22 =
* Added support for Ajax form submissions
* Files now stay in queue after a failed validation

= 1.21 =
* Added new filter for attachment post titles 'prso_gform_pluploader_attachment_post_title'
* Added new option to not mark videos as private when uploading to youtube

= 1.20 =
* Confirmed that plugin works with Wordpress 4.0.

= 1.19 =
* Security update. Added short life nonce to youtube video uploader async requests. Wanted to lock this down some more.

= 1.18 =
* Added 'prso_gform_pluploader_i18n_script' filter to change Plupload i18n language files. See FAQ.

= 1.17 =
* Fixed bug where Wordpress media library uploads would not functions with plugin active.

= 1.16 =
* Added video uploader support for wmv, 3gp, divx, ogg, mkv, flv

= 1.15 =
* Added support for WMV and MPEG files in video uploader
* Maintain filenames "as is" if rename option is off
* Added check to append string to filename only if a file is in the tmp folder with the same filename (rare)

= 1.14 =
* Updated Redux Options Framework to address bug when saving taxonomies in posts

= 1.13 =
* Added support for creating posts with gravity forms. All uploads are added as post attachments
* New option to disable file renaming and maintain original file names (see security setting in plugin options)
* Added [get_adv_uploads] shortcode to list all post file attachments in post content

= 1.12 =
* Made PHP Mcrypt extension optional - would cause upload errors on servers without it
* Added plugin option to save video files on server after upload via video upload api

= 1.11 =
* Some file mime validation improvements and bugfixes
* Added php error log for video uploader plugin to catch potential async errors
* Added alerts to the fact that YouTube uploader requires PHP iconv extension

= 1.1 =
* Commented out instances of javascript console log.

= 1.0 =
* Inital commit to plugin repo

== Upgrade Notice ==

= 1.25 =
* Form Submit button now hidden until uploads are completed OR there are no files in the upload queue.
* Updated Plupload framework to v2.1.2, may address some mobile issues?
* Fixed bug where Redux options framework may conflict with themes also using Redux

= 1.24 =
* Bugfix with video uploader and email notifiation addon

= 1.23 =
* Added support for addon plugins, such as Email Notification Support

= 1.22 =
Added support for Ajax form submissions, Files now stay in queue after a failed validation

= 1.21 =
* Wordpress 4.0 support
* Added new filter for attachment post titles 'prso_gform_pluploader_attachment_post_title'
* Added new option to not mark videos as private when uploading to youtube

= 1.19 =
* Security update. Added short life nonce to youtube video uploader async requests. Wanted to lock this down some more.

= 1.18 =
* Added filter to change Plupload i18n language files. See FAQ.

= 1.17 =
* Fixed bug where Wordpress media library uploads would not functions with plugin active.

= 1.16 =
* Added video uploader support for wmv, 3gp, divx, ogg, mkv, flv

= 1.15 =
Filenames are maintained "as is" if file rename option is off.
Video uploader now supports WMV and MPEG files.

= 1.14 =
* Addresses bug where Redux Options Framework may have caused problems with post taxonomies

= 1.13 =
Fixes some problems with file uploads  and validation on some server setups.
Added plugin option to save video files on server after upload via video upload api
Added support for creating posts with gravity forms. All uploads are added as post attachments
New option to disable file renaming and maintain original file names (see security setting in plugin options)
Added [get_adv_uploads] shortcode to list all post file attachments in post content

= 1.1 =
Fixes problems that may occur with some browsers due to Javascript console log calls.

= 1.0 =
This is the first version of plugin.

== Hooks ==

Actions:
* 'prso_gform_pluploader_processed_uploads'
* 'wp_ajax_nopriv_prso_gforms_youtube_upload_init'
* 'wp_ajax_nopriv_prso_gforms_youtube_upload_save_data'
* 'prso_gform_youtube_uploader_pre_get_attachment_data'	-	Allow devs to hook in before getting attachment data

Filters:
* 'prso_gform_pluploader_container'
* 'prso_gform_pluploader_server_validation_args'
* 'prso_gform_pluploader_entry_attachment_links'
* 'prso_gform_pluploader_attachment_post_title'
