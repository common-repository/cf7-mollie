<?php
/*
Plugin Name: Contact Form 7 - Mollie extension
Description: Include Mollie payments in your CF7 form
Version: 5.0.0
Author: Ewald Harmsen
Text Domain: cf7-mollie-translation
Domain Path: /includes/lang
Tested up to: 6.2
*/

require __DIR__ . '/includes/php/form_tab.php';
require __DIR__ . '/includes/php/amount.php';
require __DIR__ . '/includes/php/bankchoice.php';
require __DIR__ . '/includes/php/paymentchoice.php';
require __DIR__ . '/includes/php/admin_menu.php';
require __DIR__ . '/includes/php/paymentresult.php';
require __DIR__ . '/includes/php/database.php';
require __DIR__ . '/includes/php/table_shortcode.php';
require __DIR__ . '/includes/php/paymenthandler.php';

/**
 * Function init plugin
**/
function cf7mollie_init(){
	//Add shortcode
    add_action( 'wpcf7_init', 'cf7mollie_add_shortcode_payment' );
	//Translate plugin
	load_plugin_textdomain( 'cf7-mollie-translation', FALSE, basename( dirname( __FILE__ ) ) . '/includes/lang/' );
}
add_action( 'plugins_loaded', 'cf7mollie_init' , 20 );

//Run on plugin activation
register_activation_hook( __FILE__, 'cf7_mollie_install' );

//Run on plugin deactivation
register_deactivation_hook( __FILE__, 'cf7_mollie_remove' );

/**
 * Function add payment field in wpcf7
**/
function cf7mollie_add_shortcode_payment() {
    wpcf7_add_form_tag('amount',
        'cf7mollie_amount_shortcode_handler', true );
    wpcf7_add_form_tag('amount*',
        'cf7mollie_amount_shortcode_handler', true );
    wpcf7_add_form_tag('bankchoice',
        'cf7mollie_bankchoice_shortcode_handler', true );
	wpcf7_add_form_tag('paymentchoice',
        'cf7mollie_paymentchoice_shortcode_handler', true );
	wpcf7_add_form_tag('paymentchoice*',
        'cf7mollie_paymentchoice_shortcode_handler', true );
}

/* Tag generator */
add_action( 'wpcf7_admin_init','cf7mollie_add_tag_generator_paymentfields', 30 );
function cf7mollie_add_tag_generator_paymentfields() {
    $tag_generator = WPCF7_TagGenerator::get_instance();
    $tag_generator->add( 'amount', __( 'payment amount', 'cf7-mollie-translation' ),'cf7mollie_tag_generator_amount' );
    $tag_generator->add( 'paymentmethod', __( 'payment options', 'cf7-mollie-translation' ),'cf7mollie_tag_generator_payment' );
    $tag_generator->add( 'bankchoice', __( 'iDeal bankchoice', 'cf7-mollie-translation' ),'cf7mollie_tag_generator_bankchoice' );
}

//Add custom css file
add_action( 'wp_enqueue_scripts', 'cf7mollie_enqueue_frontend');
add_action( 'admin_enqueue_scripts', 'cf7mollie_enqueue_backend' );
/**
 * Enqueue theme styles and scripts - front-end
 */
function cf7mollie_enqueue_frontend() {
	wp_enqueue_script( 'cf7-mollie-script', (plugins_url()) . '/cf7-mollie/includes/js/mollie_api.js', array(), null, true );
	//make the ajax.php handler available in a object for js.
	wp_localize_script( 'cf7-mollie-script', 'cf7_mollie_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	wp_register_style( 'cf7-mollie-style', (plugins_url()) . '/cf7-mollie/includes/css/cf7-mollie-css.css', false, '1.0.0', 'all');
	wp_enqueue_style( 'cf7-mollie-style' );
}

/**
 * Enqueue theme styles and scripts - back-end
 */
function cf7mollie_enqueue_backend() {
	wp_register_style( 'cf7-mollie-style', (plugins_url()) . '/cf7-mollie/includes/css/cf7-mollie-admin.css', false, '1.0.0', 'all');
	wp_enqueue_style( 'cf7-mollie-style' );
}

/**
 * Verify Contact Form 7 dependencies.
 */
function cf7mollie_admin_notice() {
    if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
        $wpcf7_path = WP_PLUGIN_DIR . '/contact-form-7/wp-contact-form-7.php';
        $wpcf7_data = get_plugin_data( $wpcf7_path, false, false );

        $version = $wpcf7_data['Version'];

        // If Contact Form 7 version is < 4.2.0.
        if ( $version < 4.2 ) {
            ?>

            <div class="error notice">
                <p>
                    <?php esc_html_e( "Error: Please update Contact Form 7.", 'cf7-mollie' );?>
                </p>
            </div>

            <?php
        }
    } else {
        // If Contact Form 7 isn't installed and activated, throw an error.
        $wpcf7_path = WP_PLUGIN_DIR . '/contact-form-7/wp-contact-form-7.php';
        $wpcf7_data = get_plugin_data( $wpcf7_path, false, false );
        ?>

        <div class="error notice">
            <p>
                <?php esc_html_e( 'Error: Please install and activate Contact Form 7.', 'cf7-mollie' );?>
            </p>
        </div>

        <?php
    }
}

function cf7mollie_update_db_check() {
    global $cf7_mollie_db_version;
    if ( get_site_option( 'cf7_mollie_db_version' ) != $cf7_mollie_db_version) {
        cf7_mollie_install();
    }
}
add_action('plugins_loaded', 'cf7mollie_update_db_check');