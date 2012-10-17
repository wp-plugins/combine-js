=== Combine JS ===
Contributors: timmcdaniels
Donate link: http://WeAreConvoy.com
Tags: Javascript, minify
Requires at least: 3.0.1
Tested up to: 3.4.2
Stable tag: 0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WordPress plugin that combines, minifies, and compresses JS files.

== Description ==

WordPress plugin that combines, minifies, and compresses JS files. The JS files that this plugin combines and minifies must be enqueued by using wp_enqueue_script. The plugin combines and minifies JS and writes the output into files in the uploads directory. Also see the companion plugin, [Combine CSS](http://wordpress.org/extend/plugins/combine-css/).

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

= 0.2 =
* Fix mime type
* Change class file name

= 0.1 =
* First release!

== Upgrade Notice ==

= 0.1 =
First release!
