<?php
/*
Plugin Name: WooCommerce / i.LEVEL Sync
Description: A plugin that allows WooCommerce product records to synchronise with records held within i.LEVEL
Version: 1.0
Author: Tyrell Digital
Author URI: https://www.tyrell.digital
*/

require_once "activate.php";
require_once "admin_page.php";
require_once "notices.php";
require_once "sync.php";
require_once "inc/woocommerce_order_sync.php";

// First, we need to check that WooCommerce is active
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	// Check the database version
	add_action('plugins_loaded', 'wisync_update_db_check');
	// Load the admin page for WooCommerce / i.LEVEL Sync
	add_action( 'admin_menu', 'wisync_admin_page' );
	// Turn the twice-daily sync on
	add_action('wisync_twice_daily', 'sync');

}
/*-------------------------------------------------------------------------------------*/
/*---- Add the Admin Page in the Dashboard Menu ---------------------------------------*/
/*-------------------------------------------------------------------------------------*/

function wisync_admin_page() {

	// Set the parameters as variables
	$page_title = __('WooCommerce / i.LEVEL Sync', 'wisync');
	$menu_title = __('WC/i.L Sync', 'wisync');
	$capability = 'manage_options';
	$menu_slug = 'wisync';
	$function = 'wisync_settings';
	$icon_url = 'dashicons-update';
	$position = 30;

	// Add the plugin options page as a top-level menu item
	add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
}

/*-------------------------------------------------------------------------------------*/
/*---- Upon activating the plugin, schedule the Sync to run twice daily ---------------*/
/*-------------------------------------------------------------------------------------*/
register_activation_hook(__FILE__, 'wisync_activation');

function wisync_activation() {
	// Check if the sync is already set in the schedule
    if ( !wp_next_scheduled( 'wisync_daily' ) ) {
    	// If not, schedule the sync to run every hour
		wp_schedule_event( strtotime('04:00:00'), 'daily', 'wisync_daily' );
    }
}

// Call the sync every hour
add_action( 'wisync_daily', 'sync' );


/*-------------------------------------------------------------------------------------*/
/*---- Upon de-activating the plugin, clear the WordPress Scheduler -------------------*/
/*-------------------------------------------------------------------------------------*/
register_deactivation_hook(__FILE__, 'wisync_deactivation');

function wisync_deactivation() {
	$timestamp = wp_next_scheduled( 'wisync_daily' );
	wp_unschedule_event( $timestamp, 'wisync_daily' );
	wp_clear_scheduled_hook('wisync_daily');
}


/*-------------------------------------------------------------------------------------*/
/*---- When a WooCommerce order is received, send order details to i.LEVEL ------------*/
/*-------------------------------------------------------------------------------------*/

// When the status of a WooCommerce order is changed at all, call the function to send the order to i.LEVEL (use this for development/testing purposes)
// add_action( 'woocommerce_order_status_changed', 'wisync_order_complete', 10, 2 );
// When the status of a WooCommerce order reaches 'processing', call the function to send the order to i.LEVEL
add_action( 'woocommerce_order_status_processing', 'wisync_order_complete', 10, 2 );


/*--------------------------------------------------------------------------------------------------*/
/*-- When i.LEVEL has received new order details, add the response to the WC notification email ----*/
/*--------------------------------------------------------------------------------------------------*/
// add_action( 'woocommerce_email_before_order_table', 'wisync_email_message', 10, 4 );
// /**
//  * Adds a single line above the table of purchases in a WooCommerce email.
//  */
// function wisync_email_message( $order, $sent_to_admin, $plain_text, $email ) {
// 	if ( $sent_to_admin ) {
// 		echo '<p>Warehouse status: ' . $order->get_status() . '</p>';
// 	}
// }
