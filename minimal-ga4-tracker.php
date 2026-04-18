<?php
/**
 * Plugin Name: Minimal GA4 Tracker
 * Description: Lightweight GA4 tracking without the bloat.
 * Version: 1.1.4
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Breon Williams
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: minimal-ga4-tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MGA4_VERSION', '1.1.4' );
define( 'MGA4_PLUGIN_FILE', __FILE__ );
define( 'MGA4_OPTION_KEY', 'mga4_settings' );

/**
 * Main plugin class.
 */
final class MGA4_Tracker {

	/**
	 * Singleton instance.
	 *
	 * @var MGA4_Tracker|null
	 */
	private static $instance = null;

	/**
	 * Plugin settings.
	 *
	 * @var array
	 */
	private $settings = array();

	/**
	 * Get singleton instance.
	 *
	 * @return MGA4_Tracker
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->settings = $this->get_settings();

		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_head', array( $this, 'output_tracking_script' ), 1 );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_settings_link' ) );
	}

	/**
	 * Get plugin settings with defaults.
	 *
	 * @return array
	 */
	private function get_settings() {
		$defaults = array(
			'measurement_id'     => '',
			'disable_for_admins' => false,
		);

		$settings = get_option( MGA4_OPTION_KEY, array() );

		return wp_parse_args( $settings, $defaults );
	}

	/**
	 * Add settings page to admin menu.
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'GA4 Tracker', 'minimal-ga4-tracker' ),
			__( 'GA4 Tracker', 'minimal-ga4-tracker' ),
			'manage_options',
			'mga4-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings using Settings API.
	 */
	public function register_settings() {
		register_setting(
			'mga4_settings_group',
			MGA4_OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		add_settings_section(
			'mga4_main_section',
			__( 'Tracking Configuration', 'minimal-ga4-tracker' ),
			array( $this, 'render_section_description' ),
			'mga4-settings'
		);

		add_settings_field(
			'measurement_id',
			__( 'GA4 Measurement ID', 'minimal-ga4-tracker' ),
			array( $this, 'render_measurement_id_field' ),
			'mga4-settings',
			'mga4_main_section'
		);

		add_settings_field(
			'disable_for_admins',
			__( 'Disable for Administrators', 'minimal-ga4-tracker' ),
			array( $this, 'render_disable_admins_field' ),
			'mga4-settings',
			'mga4_main_section'
		);
	}

	/**
	 * Sanitize settings input.
	 *
	 * @param array $input Raw input from settings form.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		// Sanitize measurement ID.
		$measurement_id = isset( $input['measurement_id'] ) ? sanitize_text_field( $input['measurement_id'] ) : '';
		$measurement_id = strtoupper( trim( $measurement_id ) );

		if ( ! empty( $measurement_id ) && ! preg_match( '/^G-[A-Z0-9]+$/', $measurement_id ) ) {
			add_settings_error(
				MGA4_OPTION_KEY,
				'invalid_measurement_id',
				__( 'Invalid GA4 Measurement ID format. It should start with "G-" followed by alphanumeric characters (e.g., G-XXXXXXXXXX).', 'minimal-ga4-tracker' ),
				'error'
			);
			$sanitized['measurement_id'] = '';
		} else {
			$sanitized['measurement_id'] = $measurement_id;
		}

		// Sanitize disable for admins checkbox.
		$sanitized['disable_for_admins'] = ! empty( $input['disable_for_admins'] );

		return $sanitized;
	}

	/**
	 * Render section description.
	 */
	public function render_section_description() {
		echo '<p>' . esc_html__( 'Enter your GA4 Measurement ID to enable tracking on your site.', 'minimal-ga4-tracker' ) . '</p>';
	}

	/**
	 * Render measurement ID field.
	 */
	public function render_measurement_id_field() {
		$value = $this->settings['measurement_id'];
		?>
		<input
			type="text"
			id="mga4_measurement_id"
			name="<?php echo esc_attr( MGA4_OPTION_KEY ); ?>[measurement_id]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			placeholder="G-XXXXXXXXXX"
		/>
		<p class="description">
			<?php esc_html_e( 'Enter your GA4 Measurement ID (e.g., G-XXXXXXXXXX). Find this in your Google Analytics account under Admin > Data Streams.', 'minimal-ga4-tracker' ); ?>
		</p>
		<?php
	}

	/**
	 * Render disable for admins checkbox.
	 */
	public function render_disable_admins_field() {
		$checked = $this->settings['disable_for_admins'];
		?>
		<label for="mga4_disable_for_admins">
			<input
				type="checkbox"
				id="mga4_disable_for_admins"
				name="<?php echo esc_attr( MGA4_OPTION_KEY ); ?>[disable_for_admins]"
				value="1"
				<?php checked( $checked, true ); ?>
			/>
			<?php esc_html_e( 'Do not track administrators when they are logged in.', 'minimal-ga4-tracker' ); ?>
		</label>
		<?php
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'mga4_settings_group' );
				do_settings_sections( 'mga4-settings' );
				submit_button();
				?>
			</form>

			<hr />

			<h2><?php esc_html_e( 'Custom Events', 'minimal-ga4-tracker' ); ?></h2>
			<p><?php esc_html_e( 'You can add custom GA4 events via JavaScript:', 'minimal-ga4-tracker' ); ?></p>
			<pre style="background: #f0f0f0; padding: 15px; border-radius: 4px;">gtag('event', 'button_click', { 'button_name': 'signup' });</pre>

			<p><?php esc_html_e( 'Or use the mga4_gtag_config filter in your theme or plugin:', 'minimal-ga4-tracker' ); ?></p>
			<pre style="background: #f0f0f0; padding: 15px; border-radius: 4px;">add_filter( 'mga4_gtag_config', function( $config ) {
    $config['send_page_view'] = false;
    return $config;
});</pre>
		</div>
		<?php
	}

	/**
	 * Add settings link to plugins page.
	 *
	 * @param array $links Existing plugin action links.
	 * @return array Modified links.
	 */
	public function add_settings_link( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=mga4-settings' ) ),
			__( 'Settings', 'minimal-ga4-tracker' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Check if tracking should be disabled for current request.
	 *
	 * @return bool True if tracking should be skipped.
	 */
	private function should_skip_tracking() {
		// Skip in admin area.
		if ( is_admin() ) {
			return true;
		}

		// Skip REST API requests.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		// Skip AJAX requests.
		if ( wp_doing_ajax() ) {
			return true;
		}

		// Skip cron jobs.
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return true;
		}

		// Skip feeds.
		if ( is_feed() ) {
			return true;
		}

		// Skip previews.
		if ( is_preview() ) {
			return true;
		}

		// Skip robots.txt.
		if ( is_robots() ) {
			return true;
		}

		// Skip trackbacks.
		if ( is_trackback() ) {
			return true;
		}

		// Skip login page.
		if ( isset( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'] ) {
			return true;
		}

		// Skip for admins if option is enabled.
		if ( $this->settings['disable_for_admins'] && current_user_can( 'manage_options' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Output GA4 tracking script directly in head.
	 */
	public function output_tracking_script() {
		$measurement_id = $this->settings['measurement_id'];
		if ( empty( $measurement_id ) ) {
			return;
		}

		if ( $this->should_skip_tracking() ) {
			return;
		}

		// Build gtag config with filter support.
		$gtag_config = apply_filters( 'mga4_gtag_config', array(), $measurement_id );

		if ( ! empty( $gtag_config ) ) {
			$config_json   = wp_json_encode( $gtag_config );
			$config_string = 'gtag("config", "' . esc_js( $measurement_id ) . '", ' . $config_json . ');';
		} else {
			$config_string = 'gtag("config", "' . esc_js( $measurement_id ) . '");';
		}

		// Output exactly as Google specifies.
		?>
<!-- Google tag (gtag.js) - Minimal GA4 Tracker -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $measurement_id ); ?>"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
<?php echo $config_string . "\n"; ?>
</script>
		<?php
	}
}

// Initialize the plugin.
MGA4_Tracker::get_instance();

// Initialize GitHub updater.
require_once plugin_dir_path( __FILE__ ) . 'class-updater.php';
$mga4_updater = new MGA4_Updater();
$mga4_updater->init();
