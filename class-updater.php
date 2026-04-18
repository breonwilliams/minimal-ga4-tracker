<?php
/**
 * GitHub-based auto-updater.
 *
 * @package Minimal_GA4_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class MGA4_Updater {

    private $github_user = 'breonwilliams';
    private $github_repo = 'minimal-ga4-tracker';
    private $plugin_slug;
    private $plugin_basename;
    private $current_version;
    private $transient_key = 'mga4_github_release';

    public function __construct() {
        $this->current_version = MGA4_VERSION;
        $this->plugin_basename = plugin_basename( MGA4_PLUGIN_FILE );
        $this->plugin_slug     = dirname( $this->plugin_basename );
    }

    /**
     * Get the actual installed version by reading the plugin file header.
     * This ensures we compare against the version on disk, not the in-memory version.
     *
     * @return string The installed plugin version.
     */
    private function get_installed_version() {
        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugin_file = WP_PLUGIN_DIR . '/' . $this->plugin_basename;
        if ( file_exists( $plugin_file ) ) {
            $plugin_data = get_plugin_data( $plugin_file, false, false );
            return $plugin_data['Version'] ?? $this->current_version;
        }
        return $this->current_version;
    }

    public function init() {
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
        add_filter( 'plugins_api', array( $this, 'get_plugin_info' ), 10, 3 );
        add_filter( 'upgrader_post_install', array( $this, 'post_install' ), 10, 3 );
        add_action( 'admin_init', array( $this, 'maybe_clear_cache' ) );
    }

    /**
     * Clear update cache when requested via URL parameter.
     * Usage: /wp-admin/?clear_mga4_update_cache=1
     */
    public function maybe_clear_cache() {
        if ( isset( $_GET['clear_mga4_update_cache'] ) && current_user_can( 'manage_options' ) ) {
            delete_transient( $this->transient_key );
            delete_site_transient( 'update_plugins' );
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>Minimal GA4 Tracker update cache cleared. Refresh the Plugins page to check for updates.</p></div>';
            });
        }
    }

    private function get_latest_release() {
        $cached = get_transient( $this->transient_key );
        if ( false !== $cached ) {
            return $cached;
        }

        $url      = sprintf( 'https://api.github.com/repos/%s/%s/releases/latest', $this->github_user, $this->github_repo );
        $response = wp_remote_get( $url, array(
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
            ),
        ) );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return false;
        }

        $release = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $release['tag_name'] ) ) {
            return false;
        }

        set_transient( $this->transient_key, $release, 12 * HOUR_IN_SECONDS );

        return $release;
    }

    private function get_remote_version( $release ) {
        return ltrim( $release['tag_name'], 'v' );
    }

    private function get_download_url( $release ) {
        // Prefer an attached ZIP asset.
        if ( ! empty( $release['assets'] ) ) {
            foreach ( $release['assets'] as $asset ) {
                if ( 'application/zip' === $asset['content_type'] || str_ends_with( $asset['name'], '.zip' ) ) {
                    return $asset['browser_download_url'];
                }
            }
        }

        // Fallback to GitHub auto-generated zipball.
        return $release['zipball_url'] ?? '';
    }

    public function check_update( $transient ) {
        if ( ! is_object( $transient ) ) {
            $transient = new stdClass();
        }

        $release = $this->get_latest_release();
        if ( ! $release ) {
            return $transient;
        }

        $remote_version = $this->get_remote_version( $release );
        $download_url   = $this->get_download_url( $release );

        $installed_version = $this->get_installed_version();

        if ( version_compare( $remote_version, $installed_version, '>' ) && $download_url ) {
            $transient->response[ $this->plugin_basename ] = (object) array(
                'slug'        => $this->plugin_slug,
                'plugin'      => $this->plugin_basename,
                'new_version' => $remote_version,
                'url'         => $release['html_url'] ?? '',
                'package'     => $download_url,
            );
            // Remove from no_update if present.
            unset( $transient->no_update[ $this->plugin_basename ] );
        } else {
            // No update available - must add to no_update to clear stale notifications.
            unset( $transient->response[ $this->plugin_basename ] );
            $transient->no_update[ $this->plugin_basename ] = (object) array(
                'slug'        => $this->plugin_slug,
                'plugin'      => $this->plugin_basename,
                'new_version' => $installed_version,
                'url'         => $release['html_url'] ?? '',
                'package'     => '',
            );
        }

        return $transient;
    }

    public function get_plugin_info( $result, $action, $args ) {
        if ( 'plugin_information' !== $action || ( $args->slug ?? '' ) !== $this->plugin_slug ) {
            return $result;
        }

        $release = $this->get_latest_release();
        if ( ! $release ) {
            return $result;
        }

        $remote_version = $this->get_remote_version( $release );

        return (object) array(
            'name'          => 'Minimal GA4 Tracker',
            'slug'          => $this->plugin_slug,
            'version'       => $remote_version,
            'author'        => '<a href="https://breonwilliams.com">Breon Williams</a>',
            'homepage'      => 'https://github.com/' . $this->github_user . '/' . $this->github_repo,
            'download_link' => $this->get_download_url( $release ),
            'sections'      => array(
                'description' => 'Lightweight GA4 tracking without the bloat.',
                'changelog'   => nl2br( esc_html( $release['body'] ?? '' ) ),
            ),
            'requires'      => '6.0',
            'tested'        => '6.9',
            'requires_php'  => '7.4',
        );
    }

    public function post_install( $response, $hook_extra, $result ) {
        if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_basename ) {
            return $response;
        }

        global $wp_filesystem;

        $plugin_dir   = WP_PLUGIN_DIR . '/' . $this->plugin_slug;
        $installed_to = $result['destination'];

        if ( $installed_to !== $plugin_dir ) {
            $wp_filesystem->move( $installed_to, $plugin_dir );
            $result['destination'] = $plugin_dir;
        }

        activate_plugin( $this->plugin_basename );

        // Clear cached data to force fresh update check.
        delete_transient( $this->transient_key );
        delete_site_transient( 'update_plugins' );

        return $response;
    }
}
