<?php

/*
Plugin Name: WP No External Links dashboard statistics
Plugin URI: https://alkoweb.ru/
Description: Add
Version: 3.6.0
Author: Petrozavodsky
Author URI: https://alkoweb.ru/
*/



function wp_no_external_links_dashboard_statistics_init()
{
    require_once ("includes/WidgetSearch.php");
	WidgetSearch::run(__FILE__);
    require_once ("includes/WidgetChart.php");
	new WidgetChart(__FILE__);
}

add_action('plugins_loaded', 'wp_no_external_links_dashboard_statistics_init' , 40);