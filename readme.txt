=== Gravity Forms Advanced Uploader ===
Contributors: ben.moody
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html
Tags: gravity forms, gravity forms file upload, gravity forms file uploader, gravity forms uploader, plupload, gravity forms videos, gravity forms youtube, youtube uploader, youtube file uploader
Requires at least: 3.0
Tested up to: 3.8
Stable tag: 1.0

Chunked Multiple file uploads, Auto upload of videos to YouTube & Brightcove, Files stored in WP Media Library, Advanced options.

== Description ==

* Large file support with chunked uploads, get around server upload limits
* Need more control over Gravity Forms multiple file uploads. 
* Want to store file uploads in Wordpress media library. 
* Like a choice of upload user interfaces (jQuery UI, Queue, Custom)
* Need advanced control over plupload options
* Would like to store uploaded videos on YouTube account (also Brightcove. Vimeo coming soon!)
* Added security and validation

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

This section describes how to install the plugin and get it working.

e.g.

1. Upload `prso-gravity-forms-adv-uploader` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Settings -> Gravity Adv Uploader to set all your awesome options

== Frequently Asked Questions ==

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

= 1.0 =
* Inital commit to plugin repo

== Upgrade Notice ==

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
