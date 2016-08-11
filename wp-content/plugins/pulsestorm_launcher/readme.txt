=== Plugin Name ===
Contributors: alanstorm
Donate link: http://alanstorm.com
Tags: admin, ajax, posts, plugin, navigation
Requires at least: 4.5.3
Tested up to: 4.5.3
Stable tag: 4.5.3
License: MIT
License URI: https://opensource.org/licenses/MIT

The Pulse Storm Launcher provides an admin launcher application, providing navigation-less access to all your admin pages and posts, including support for products and orders in popular Wordpress e-commerce packages like WooCommerce and WP eCommerce.

== Description ==

Still not sure what we're about?  Watch our [4 minutes introductory screencast](https://vimeo.com/178378720) where all is revealed. 

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload the plugin files to the `wp-content/plugins/pulsestorm_launcher` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings->Plugin Name screen to configure the plugin's keyboard shortcut
4. Use the plugin by clicking the "Pulse Storm Launcher" link in the admin bar, or invoking the keyboard  shortcut (Ctrl-M by default)


== Frequently Asked Questions ==

= Why would I want this? =

If you, your writers, or your support staff are tired of clicking around the Wordpress admin looking for things. 

== Screenshots ==

1. The Pulse Storm Launcher, searching for WooCommerce Ninjas
2. The Pulse Storm Launcher, having a boring settings screen

== Changelog ==

= 1.0 =
* Initial Release
* I bet we didn't get something right and we'll need a 1.0.1

== Upgrade Notice ==

= 1.0 =
This version is the first version. Users will want to "upgrade" to this version (i.e. install the plugin) if they're ready for a faster Wordpress admin experience. 

== Arbitrary section ==

End users programmers can use the following filter hooks to add their own menus to the immediate and ajax launcher results. 

    add_filter('pulsestorm_launcher_ajax_menus', function($links){
        return $links;
    });

    add_filter('pulsestorm_launcher_menus', function($links){
        return $links;
    });    

Programmers should use the immediate results (`pulsestorm_launcher_menus`) sparingly, as they're loaded into memory on every page load. See `pulsestorm_launcher.php` for more usage information, as we honor the filter hooks internally. 