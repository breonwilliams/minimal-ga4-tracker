=== Minimal GA4 Tracker ===
Contributors: breonwilliams
Tags: analytics, google analytics, ga4, tracking, gtag
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 1.1.4
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight GA4 tracking without the bloat. Just enter your Measurement ID and go.

== Description ==

Minimal GA4 Tracker is a simple, lightweight plugin for adding Google Analytics 4 tracking to your WordPress site. No bloat, no unnecessary features - just clean, efficient tracking.

**Features:**

* Simple setup - just enter your GA4 Measurement ID
* Lightweight - no external dependencies or admin bloat
* Option to disable tracking for administrators
* Developer-friendly with filter hooks for customization
* Async script loading for optimal performance
* Automatic exclusion of admin pages, feeds, previews, and more

== Installation ==

1. Upload the `minimal-ga4-tracker` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > GA4 Tracker
4. Enter your GA4 Measurement ID (e.g., G-XXXXXXXXXX)
5. Save changes

== Frequently Asked Questions ==

= Where do I find my GA4 Measurement ID? =

In your Google Analytics account, go to Admin > Data Streams > select your stream. The Measurement ID starts with "G-" followed by alphanumeric characters.

= Can I disable tracking for logged-in administrators? =

Yes, there's a checkbox in the settings to disable tracking for users with administrator privileges.

= How do I add custom events? =

You can use the standard gtag function in your theme or plugin JavaScript:

`gtag('event', 'button_click', { 'button_name': 'signup' });`

= How do I customize the gtag config? =

Use the `mga4_gtag_config` filter:

`add_filter( 'mga4_gtag_config', function( $config ) {
    $config['send_page_view'] = false;
    return $config;
});`

== Changelog ==

= 1.1.4 =
* Fix: Improve update detection reliability

= 1.1.3 =
* Fix: Output gtag in head with async attribute for Google tag detection compatibility

= 1.1.2 =
* Fix: Resolve phantom update notification after upgrading

= 1.1.1 =
* Fix: Remove version query string from gtag script URL for Google Tag detection compatibility

= 1.1.0 =
* Added GitHub-based auto-update system
* Added developer documentation (README.md, CLAUDE.md)

= 1.0.0 =
* Initial release
