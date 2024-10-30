<?php
function cf7mollie_paymentchoice_shortcode_handler( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}
	
	//Load Mollie
	require_once __DIR__ . '/initialize.php';
    $wpcf7 = WPCF7_ContactForm::get_current();
    $formid = $wpcf7->id();
    cf7_mollie_setapikey($formid);
    $mollie = $GLOBALS['mollie'];

	$validation_error = wpcf7_get_validation_error( $tag->name );

	$class = wpcf7_form_controls_class( $tag->type );

	if ( $validation_error ) {
		$class .= ' wpcf7-not-valid';
	}

	$label_first = $tag->has_option( 'label_first' );
	$use_label_element = $tag->has_option( 'use_label_element' );
	$free_text = $tag->has_option( 'free_text' );
	$multiple = false;

	$atts = array();

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();

	$tabindex = $tag->get_option( 'tabindex', 'signed_int', true );

	if ( false !== $tabindex ) {
		$tabindex = (int) $tabindex;
	}

	$html = '';
	$count = 0;

	if ( $data = (array) $tag->get_data_option() ) {
		if ( $free_text ) {
			$tag->values = array_merge(
				array_slice( $tag->values, 0, -1 ),
				array_values( $data ),
				array_slice( $tag->values, -1 ) );
			$tag->labels = array_merge(
				array_slice( $tag->labels, 0, -1 ),
				array_values( $data ),
				array_slice( $tag->labels, -1 ) );
		} else {
			$tag->values = array_merge( $tag->values, array_values( $data ) );
			$tag->labels = array_merge( $tag->labels, array_values( $data ) );
		}
	}
	
	//See if payment is a first, recurring or an oneoff 
	$options = $tag->options;
	$default_choice = 1;
	foreach($options as $item) {
		if (strpos($item, 'paymenttype:') !== false) {
			$paymenttype = str_replace('paymenttype:',"",$item);
		}else if(strpos($item, 'default:') !== false){
			$default_choice = str_replace('default:',"",$item);
		}
	}
	
	try{
		$methods = $mollie->methods->allActive(['sequenceType' => $paymenttype]);
		$methodsize = count($methods);
	} catch (\Mollie\Api\Exceptions\ApiException $e) {
		return "Paymentchoice: API call failed: " . htmlspecialchars($e->getMessage());
    }

	$values = $tag->values;
	$labels = $tag->labels;
	$icons = array();
	
	for($i=0;$i<$methodsize;$i++){
        $method=$methods[$i];
		array_unshift($values,htmlspecialchars($method->id));
		array_unshift($labels,htmlspecialchars($method->description));
		array_unshift($icons, '<img src="' . htmlspecialchars($method->image->size1x) . '" srcset="' . htmlspecialchars($method->image->size2x) . ' 2x"> ') ;
	}

	foreach ( $values as $key => $value ) {
		//Check the default option
		$checked = false;
		if ($key == $default_choice-1){
			$checked = true;
		}

		if ( isset( $labels[$key] ) ) {
			$label = $labels[$key];
		} else {
			$label = $value;
		}
		
		if ( isset( $icons[$key] ) ) {
			$icon = $icons[$key];
			$placeholder = $paymenttype;
		} else {
			$icon = "";
			$placeholder = "Not a mollie payment";
		}

		$item_atts = array(
			'type' => "radio",
			'name' => $tag->name . ( $multiple ? '[]' : '' ),
			'value' => $value,
			'checked' => $checked ? 'checked' : '',
			'tabindex' => false !== $tabindex ? $tabindex : '',
			'placeholder' => $placeholder,
		);

		$item_atts = wpcf7_format_atts( $item_atts );

		if ( $label_first ) { // put label first, input last
			$item = sprintf(
				'<span class="wpcf7-list-item-label">%1$s</span>
				%3$s
				<input %2$s />',
				esc_html( $label ), $item_atts , $icon );
		} else {
			$item = sprintf(
				'%3$s <input %2$s /><span class="wpcf7-list-item-label">%1$s</span>',
				esc_html( $label ), $item_atts,  $icon  );
		}

		if ( $use_label_element ) {
			$item = '<label>' . $item . '</label>';
		}

		if ( false !== $tabindex
		and 0 < $tabindex ) {
			$tabindex += 1;
		}

		$class = 'wpcf7-list-item';
		$count += 1;

		if ( 1 == $count ) {
			$class .= ' first';
		}

		if ( count( $values ) == $count ) { // last round
			$class .= ' last';

			if ( $free_text ) {
				$free_text_name = sprintf(
					'_wpcf7_%1$s_free_text_%2$s', $tag->basetype, $tag->name );

				$free_text_atts = array(
					'name' => $free_text_name,
					'class' => 'wpcf7-free-text',
					'tabindex' => false !== $tabindex ? $tabindex : '',
				);

				if ( wpcf7_is_posted()
				and isset( $_POST[$free_text_name] ) ) {
					$free_text_atts['value'] = wp_unslash(
						$_POST[$free_text_name] );
				}

				$free_text_atts = wpcf7_format_atts( $free_text_atts );

				$item .= sprintf( ' <input type="text" %s />', $free_text_atts );

				$class .= ' has-free-text';
			}
		}

		$item = '<span class="' . esc_attr( $class ) . '">' . $item . '</span>';
		$html .= $item;
	}

	$atts = wpcf7_format_atts( $atts );

	$html = sprintf(
		'<span class="wpcf7-form-control-wrap %1$s"><span %2$s>%3$s</span>%4$s</span>',
		sanitize_html_class( $tag->name ), $atts, $html, $validation_error );

	return $html;
}

/* add_filter( 'wpcf7_validate_paymentchoice','wpcf7_checkbox_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_paymentchoice*','wpcf7_checkbox_validation_filter', 10, 2 );
function cf7mollie_checkbox_validation_filter( $result, $tag ) {
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
} */

function cf7mollie_tag_generator_payment( $contact_form, $args = '' ) {
	$args = wp_parse_args( $args, array() );
	$type = 'radio';

	$description = __( "Generate radio buttons to select a paymentmethod. You can add extra options as well, these will be placed at the bottom of the list. To set up a recurring payment the client has to do an initial payment. The form needs to have an 'name' and a 'email' field. Optionally you add a field with a namme containing the word 'frequency' containing numbers. That will be the frequency in months. Optionally you can add a date field with 'chargedate' in its name that will be the startdate. If you only have one recurring payment option, you do not have to add the 'Payment option field - recurring' just the 'payment option - first is sufficient.'");
	$desc_link = wpcf7_link( __( 'https://wordpress.org/plugins/cf7-conditional-fields/', 'cf7-mollie'), __( 'conditional fields', 'cf7-mollie-translation' ) );
	
	?>
	<div class="control-box">
		<fieldset>
			<legend><?php echo sprintf( esc_html( $description ), $desc_link ); ?></legend>

			<table class="form-table">
				<tbody>
					<tr>
					<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?></label></th>
					<td><input type="text" name="name" readonly="readonly" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
					</tr>

					<tr>
					<th scope="row"><?php echo esc_html( __( 'Options', 'contact-form-7' ) ); ?></th>
					<td>
						<fieldset>
						<legend class="screen-reader-text"><?php echo esc_html( __( 'Options', 'contact-form-7' ) ); ?></legend>
						<textarea name="values" class="values" id="<?php echo esc_attr( $args['content'] . '-values' ); ?>"></textarea>
						<label for="<?php echo esc_attr( $args['content'] . '-values' ); ?>"><span class="description"><?php echo esc_html( __( "One option per line.", 'contact-form-7' ) ); ?></span></label><br />
						<label><input type="checkbox" name="label_first" class="option" /> <?php echo esc_html( __( 'Put a label first, a checkbox last', 'contact-form-7' ) ); ?></label><br />
						<label><input type="checkbox" name="use_label_element" class="option" /> <?php echo esc_html( __( 'Wrap each item with label element', 'contact-form-7' ) ); ?></label>
						</fieldset>
					</td>
					</tr>
					
					<tr>
					<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'Payment type', 'contact-form-7' ) ); ?></label></th>
					<td><input type="radio" name="paymenttype" value="oneoff" checked class="paymentvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-paymenttype' ); ?>" />Default
					<input type="radio" name="paymenttype" value="recurring" class="paymentvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-paymenttype' ); ?>" />Recurring
					<input type="radio" name="paymenttype" value="first" class="paymentvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-paymenttype' ); ?>" />Initial payment</td>
					</tr>

					<tr>
					<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'contact-form-7' ) ); ?></label></th>
					<td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" /></td>
					</tr>

					<tr>
					<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'contact-form-7' ) ); ?></label></th>
					<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" /></td>
					</tr>
				</tbody>
			</table>
		</fieldset>
	</div>

	<div class="insert-box">
		<input type="text" name="paymentchoice" class="tag code" readonly="readonly" onfocus="this.select()" />

		<div class="submitbox">
		<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />
		</div>

		<br class="clear" />

		<p class="description mail-tag"><label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>"><?php echo sprintf( esc_html( __( "To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.", 'contact-form-7' ) ), '<strong><span class="mail-tag"></span></strong>' ); ?><input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>" /></label></p>
	</div>
	<?php
}
