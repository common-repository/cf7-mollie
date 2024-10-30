<?php
require __DIR__ . '/webhook.php';
/**
 * Function add payment amount field in wpcf7
 * @version 1.0
**/

function cf7mollie_amount_shortcode_handler ($tag){
    if (isset($_GET['paymenttype'])) {
        cf7_mollie_payment_handler();
    }elseif (isset($_POST['id'])) {
        cf7_mollie_payment_status();
    }else{
        if ( empty( $tag->name ) ) {
            return '';
        }

        $validation_error = wpcf7_get_validation_error( $tag->name );

        $class = wpcf7_form_controls_class( $tag->type, 'wpcf7-text' );

        if ( in_array( $tag->basetype, array( 'email', 'url', 'tel' ) ) ) {
            $class .= ' wpcf7-validates-as-' . $tag->basetype;
        }

        if ( $validation_error ) {
            $class .= ' wpcf7-not-valid';
        }

        require_once __DIR__ . '/initialize.php';

        $wpcf7 = WPCF7_ContactForm::get_current();
        $formid = $wpcf7->id();
        cf7_mollie_setapikey($formid);

        $atts = array();
        $atts['size'] = $tag->get_size_option( '10' );
        $atts['maxlength'] = $tag->get_maxlength_option();
        $atts['minlength'] = $tag->get_minlength_option();
        if ( $atts['maxlength'] && $atts['minlength']
        && $atts['maxlength'] < $atts['minlength'] ) {
            unset( $atts['maxlength'], $atts['minlength'] );
        }
        $atts['class'] = $tag->get_class_option( $class );
        $atts['id'] = $tag->get_id_option();
        $atts['tabindex'] = $tag->get_option( 'tabindex', 'signed_int', true );
        $atts['autocomplete'] = $tag->get_option( 'autocomplete',
            '[-0-9a-zA-Z]+', true );
        if ( $tag->has_option( 'readonly' ) ) {
            $atts['readonly'] = 'readonly';
        }
        if ( $tag->is_required() ) {
            $atts['aria-required'] = 'true';
        }
        $atts['aria-invalid'] = $validation_error ? 'true' : 'false';

        $value = (string) reset( $tag->values );

        if ( $tag->has_option( 'placeholder' ) || $tag->has_option( 'watermark' ) ) {
            $atts['placeholder'] = $value;
            $value = '';
        }
        $value = $tag->get_default_option( $value );
        $value = wpcf7_get_hangover( $tag->name, $value );

        $scval = do_shortcode('['.$value.']');
        if( $scval != '['.$value.']' ){
            $value = esc_attr( $scval );
        }
        $atts['value'] = $value;

        if ( wpcf7_support_html5() ) {
            $atts['type'] = $tag->basetype;
        } else {
            $atts['type'] = 'text';
        }

        $atts['name'] = $tag->name;

        $atts = wpcf7_format_atts( $atts );

        $html = sprintf(
            '<span class="wpcf7-form-control-wrap %1$s"><input %2$s />%3$s</span>',
            sanitize_html_class( $tag->name ), $atts, $validation_error );
        return $html;
    }
}

/* Validation filter */

add_filter( 'wpcf7_validate_amount', 'cf7mollie_amount_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_amount*', 'cf7mollie_amount_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_range', 'wpcf7_number_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_range*', 'wpcf7_number_validation_filter', 10, 2 );

function cf7mollie_amount_validation_filter( $result, $tag ) {
	$name = $tag->name;

	$value = isset( $_POST[$name] )
		? trim( strtr( (string) $_POST[$name], "\n", " " ) )
		: '';

	$value = str_replace(",",".",$value);

	$min = $tag->get_option( 'min', 'signed_int', true );
	$max = $tag->get_option( 'max', 'signed_int', true );

	if ( $tag->is_required() && '' == $value ) {
		$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
	} elseif ( '' != $value && ! is_numeric( $value ) ) {
		$result->invalidate( $tag, wpcf7_get_message( 'invalid_number' ) );
	} elseif ( '' != $value && '' != $min && (float) $value < (float) $min ) {
		$result->invalidate( $tag, wpcf7_get_message( 'number_too_small' ) );
	} elseif ( '' != $value && '' != $max && (float) $max < (float) $value ) {
		$result->invalidate( $tag, wpcf7_get_message( 'number_too_large' ) );
	}

	return $result;
}


/* Messages */

add_filter( 'wpcf7_messages', 'cf7mollie_amount_messages' );
function cf7mollie_amount_messages( $messages ) {
	return array_merge( $messages, array(
		'invalid_number2' => array(
			'description' => __( "Number format that the sender entered is invalid", 'contact-form-7' ),
			'default' => __( "The number format is invalid.2", 'contact-form-7' )
			//'default' => $_POST[$name]
		),

		'number_too_small2' => array(
			'description' => __( "Number is smaller than minimum lim2it", 'contact-form-7' ),
			'default' => __( "The number is smaller than the minimu2m allowed.", 'contact-form-7' )
		),

		'number_too_large2' => array(
			'description' => __( "Number is larger than maximum l2mit", 'contact-form-7' ),
			'default' => __( "The number is larger than the maxim2um allowed.", 'contact-form-7' )
		),
	) );
}


function cf7mollie_tag_generator_amount( $contact_form, $args = '' ) {
    $args = wp_parse_args( $args, array() );
    $type = 'amount';

    $description = __( "Generate a form-tag for a field where users can fill in their amount.", 'cf7-mollie-translation' );
    $desc_link = wpcf7_link( __( 'https://wordpress.org/plugins/cf7-conditional-fields/', 'cf7-mollie'), __( 'conditional fields', 'cf7-mollie-translation' ) );

?>
<div class="control-box">
<fieldset>
<legend><?php echo sprintf( esc_html( $description ), $desc_link ); ?></legend>

<table class="form-table">
<tbody>
    <tr>
    <th scope="row"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></th>
    <td>
        <fieldset>
        <legend class="screen-reader-text"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></legend>
        <select name="tagtype">
            <option value="amount" selected="selected"><?php echo esc_html( __( 'Spinbox', 'contact-form-7' ) ); ?></option>
            <option value="range"><?php echo esc_html( __( 'Slider', 'contact-form-7' ) ); ?></option>
        </select>
        <br />
        <label><input type="checkbox" name="required" checked="checked"/> <?php echo esc_html( __( 'Required field', 'contact-form-7' ) ); ?></label>
        </fieldset>
    </td>
    </tr>

    <tr>
    <th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?></label></th>
    <td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
    </tr>

    <tr>
    <th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-values' ); ?>"><?php echo esc_html( __( 'Default value', 'contact-form-7' ) ); ?></label></th>
    <td><input type="text" name="values" class="oneline" id="<?php echo esc_attr( $args['content'] . '-values' ); ?>" /><br />
    <label><input type="checkbox" name="placeholder" class="option" /> <?php echo esc_html( __( 'Use this text as the placeholder of the field', 'contact-form-7' ) ); ?></label></td>
    </tr>

    <tr>
    <th scope="row"><?php echo esc_html( __( 'Range', 'contact-form-7' ) ); ?></th>
    <td>
        <fieldset>
        <legend class="screen-reader-text"><?php echo esc_html( __( 'Range', 'contact-form-7' ) ); ?></legend>
        <label>
        <?php echo esc_html( __( 'Min', 'contact-form-7' ) ); ?>
        <input type="number" name="min" class="numeric option" value="0"/>
        </label>
        &ndash;
        <label>
        <?php echo esc_html( __( 'Max', 'contact-form-7' ) ); ?>
        <input type="number" name="max" class="numeric option" />
        </label>
        </fieldset>
    </td>
    </tr>

    <tr>
    <th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'contact-form-7' ) ); ?></label></th>
    <td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" /></td>
    </tr>

    <tr>
    <th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'contact-form-7' ) ); ?></label></th>
    <td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>"  /></td>
    </tr>
</tbody>
</table>
</fieldset>
</div>

<div class="insert-box">
    <input type="text" name="<?php echo $type; ?>" class="tag code" readonly="readonly" onfocus="this.select()" />

    <div class="submitbox">
    <input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />
    </div>

    <br class="clear" />

    <p class="description mail-tag"><label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>"><?php echo sprintf( esc_html( __( "To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.", 'contact-form-7' ) ), '<strong><span class="mail-tag"></span></strong>' ); ?><input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>" /></label></p>
</div>
<?php
}