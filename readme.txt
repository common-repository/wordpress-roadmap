=== WordPress Roadmap ===
Contributors: gilbitron
Donate link: http://dev7studios.com
Tags: roadmap, roadmaps, development, status
Requires at least: 3.1
Tested up to: 3.1
Stable tag: 0.1

Create dynamic roadmaps in WordPress which show the status of your product development.

== Description ==

The WordPress Roadmap plugin allows you to create multiple, dynamic roadmaps that can be used to display your development 
progress of a product (think http://interstateapp.com for WordPress).

Features:

* Create multiple roadmaps
* Add an ulimited number of items per roadmap
* Simple backend UI for managing your roadmaps
* Built in theme template for displaying roadmaps
* Uses custom post types so is easily "theme-able"


== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the `wordpress-roadmap` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Roadmaps > Add New to create your first roadmap

== Frequently Asked Questions ==

= How can I style my Roadmap pages? =

You need to create a `single-roadmap.php` file in your theme folder (probably copy your single.php file to do so). Then
instead of `<?php the_content(); ?>` you would use `<?php wp_roadmap_items(); ?>`. You can also use the `get_wp_roadmap_items()` function 
if you want to manually control how to output the items.

= My Roadmap pages show a 404? =

Try re-saving your permalinks settings.

== Screenshots ==

1. The roadmap UI

== Changelog ==

= 0.1 =
* Initial release.