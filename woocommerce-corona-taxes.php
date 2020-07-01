<?php
/**
 * Plugin Name: WooCommerce Corona Taxes
 * Description: This plugin uses the Woo action scheduler to adjust german tax rates automatically on 01.07.20 and 01.01.21.
 * Plugin URI: https://vendidero.de/
 * Version: 1.0.1
 * Author: vendidero
 * Author URI: https://vendidero.de
 * WC requires at least: 3.0.0
 * WC tested up to: 4.2.0
 *
 * @author vendidero
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'init', 'wc_corona_schedule_event' );
add_action( 'wc_corona_adjust_taxes_start', 'wc_corona_adjust_taxes_start_callback' );
add_action( 'wc_corona_adjust_taxes_end', 'wc_corona_adjust_taxes_end_callback' );

/**
 * Queue Woo events
 */
function wc_corona_schedule_event() {
	$queue = WC()->queue();

	$end_date     = defined( 'WC_CORONA_END_DATE' ) ? WC_CORONA_END_DATE : '2021-01-01';
	$end_date_obj = new DateTime( $end_date, new DateTimeZone( 'UTC' ) );
	$now          = new DateTime( 'now', new DateTimeZone( 'UTC' ) );

	if ( $now < $end_date_obj ) {
		if ( ! $queue->get_next( 'wc_corona_adjust_taxes_end', array(), 'woocommerce-corona-taxes' ) ) {
			$queue->schedule_single( intval( $end_date_obj->getTimestamp() ), 'wc_corona_adjust_taxes_end', array(), 'woocommerce-corona-taxes' );
		}
    }
}

function wc_corona_adjust_taxes_start_callback() {

}

function wc_corona_adjust_taxes_end_callback() {
	if ( class_exists( 'WC_GZD_Install' ) ) {
		WC_GZD_Install::create_tax_rates( 19, 7 );
		WC_GZD_Install::create_virtual_tax_rates( array( 'DE' => 19 ) );

		wc_get_logger()->log( 'info', 'Corona tax rates updated to 19% and 7% successfully' );
	}
}

function wc_corona_maybe_show_notice() {
	if ( ! class_exists( 'WooCommerce_Germanized' ) || version_compare( get_option( 'woocommerce_gzd_version' ), '3.1.9', '<' ) ) {
		?>
		<div id="message" class="error">
			<p>
				<?php printf( 'The Corona Tax plugin needs %s installed in version %s or greater', '<a href="https://wordpress.org/plugins/woocommerce-germanized/">Germanized</a>', '3.1.9' ); ?>
			</p>
		</div>
		<?php
	}
}

function wc_corona_clear_schedule() {
    if ( $queue = WC()->queue() ) {
        $queue->cancel_all( 'wc_corona_adjust_taxes_end', array(), 'woocommerce-corona-taxes' );
    }
}

add_action( 'admin_notices', 'wc_corona_maybe_show_notice', 20 );
register_activation_hook( __FILE__, 'wc_corona_clear_schedule' );