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

The Pulse Storm Launcher provides an admin launcher application, providing navigation-less access to all your admin pages and posts, including support for products and orders in popular Wordpress e-commerce packages like WooCommerce and WP eCommerce.

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

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets 
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png` 
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

= 1.0 =
* Initial Release
* I bet we didn't get something right and we'll need a 1.0.1

== Upgrade Notice ==

= 1.0 =
This version is the first version. Users will want to "upgrade" to this version (i.e. install the plugin) if they're ready for a faster Wordpress admin experience. 

== Arbitrary section ==

This plugin provider two filter hooks

    add_filter('pulsestorm_launcher_ajax_menus', function($links){
        return $links;
    });

    add_filter('pulsestorm_launcher_menus', function($links){
        return $links;
    });    
