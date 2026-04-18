# Minimal GA4 Tracker

Lightweight Google Analytics 4 tracking for WordPress without the bloat.

## Features

- Simple setup with just your GA4 Measurement ID
- Async script loading for optimal performance
- Option to disable tracking for administrators
- Automatic exclusion of admin pages, feeds, previews, REST API, AJAX, and cron
- Developer-friendly with filter hooks for customization
- GitHub-based auto-updates

## Installation

### From GitHub Releases

1. Download the latest release ZIP from the [Releases page](https://github.com/breonwilliams/minimal-ga4-tracker/releases)
2. In WordPress admin, go to Plugins > Add New > Upload Plugin
3. Upload the ZIP file and activate

### Manual Installation

1. Clone or download this repository
2. Copy the `minimal-ga4-tracker` folder to `/wp-content/plugins/`
3. Activate through the Plugins menu

## Configuration

1. Go to **Settings > GA4 Tracker**
2. Enter your GA4 Measurement ID (e.g., `G-XXXXXXXXXX`)
3. Optionally check "Disable for Administrators" to exclude admin tracking
4. Save changes

Find your Measurement ID in Google Analytics: Admin > Data Streams > select your stream.

## Developer Hooks

### `mga4_gtag_config` Filter

Modify the gtag configuration parameters:

```php
add_filter( 'mga4_gtag_config', function( $config, $measurement_id ) {
    // Disable automatic page view tracking
    $config['send_page_view'] = false;

    // Add custom parameters
    $config['user_id'] = get_current_user_id();

    return $config;
}, 10, 2 );
```

## Custom Events

Track custom events using the standard gtag function:

```javascript
// Basic event
gtag('event', 'button_click', {
    'button_name': 'signup'
});

// E-commerce event
gtag('event', 'purchase', {
    'transaction_id': 'T12345',
    'value': 99.99,
    'currency': 'USD'
});
```

## Clearing Update Cache

If you need to force-check for updates, visit:

```
/wp-admin/?clear_mga4_update_cache=1
```

## License

GPL-2.0-or-later
