<?php
/**
 * Minimal GA4 Tracker Uninstall
 *
 * Removes plugin data when the plugin is deleted.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'mga4_settings' );
