=== Combine JS ===
Contributors: timmcdaniels
Donate link: http://WeAreConvoy.com
Tags: Javascript, minify
Requires at least: 3.0.1
Tested up to: 3.4.2
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WordPress plugin that combines, minifies, and compresses JS files.

== Description ==

WordPress plugin that combines, minifies, and compresses JS files. The JS files that this plugin combines and minifies must be enqueued by using wp_enqueue_script. The plugin combines and minifies JS and writes the output into files in the uploads directory. This plugin uses jsmin.php - PHP implementation of Douglas Crockford's JSMin. This plugin combines all local JS files into a single file and includes the file in the footer of the theme (requires wp_footer being used in your theme files). Also see the companion plugin, [Combine CSS](http://wordpress.org/extend/plugins/combine-css/).

Features include:

* option to change the JS domain if a CDN is used
* option to change how often JS files get refreshed
* option to exclude certain JS files from combining
* option to turn on/off gzip compression
* option to turn on debugging

== Installation ==

1. Upload `combine-js` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= JS is not working properly after activating the plugin. What can I do? =

You can debug the plugin by activating the debug option on the settings page and reviewing the server's error log for details. You can try excluding certain JS files from getting combined to see if that fixes the issue. The plugin will not minify any javascript files that have '-min' in the file name, so if you can narrow the issue to a single file, try renaming it with -min and see if that fixes the issue.

== Screenshots ==
1. This is a screenshot of the Combine JS settings page.

== Changelog ==

= 1.1 =
* Have js.php use the conditional tmp path.

= 1.0 =
* Fix system tmp directory path.

= 0.9 =
* Use system tmp directory if plugin dir is not writable.

= 0.8 =
* Additional fix to tmp directory creation; change mkdir to wp_mkdir_p.

= 0.7 =
* Fixed issue with auto creation of tmp directory.

= 0.6 =
* Fixed notices and warnings in error log.
* Simplified functionality by including one single JS file in the footer of the site.

= 0.5 =
* Fixed notices and warnings in error log (thanks to pha3z).
* Added glob function to remove cached files when settings saved.

= 0.4 =
* Fixed php warning: Warning:  Missing argument 2 for CombineJS::compress()

= 0.3 =
* Note: Version 0.3 uses a file (js.php) within the plugin directory to serve combined JavaScript files. Also, it requires a tmp directory to be created within the plugin directory; the plugin will create the directory automatically if it has the permission to do so. View the settings page after updating to version 0.3, and it will let you know what commands need to be run, if any.
* Created standalone script with token argument to serve combined CSS file.
* Created tmp directory that allows WP settings to be stored in a file.
* Added newline after minified content that appears to fix some reported JavaScript errors.

= 0.2 =
* Fix mime type
* Change class file name

= 0.1 =
* First release!

== Upgrade Notice ==

= 0.6 =
This version now includes the combined JS file in the footer of the site. 0.5 and before would allow 2 combined files to exist for header and footer JS files, but it now requires the JS to be in the footer. You can use the ignore files feature to exempt some JS files from this constraint.

= 0.1 =
First release!
