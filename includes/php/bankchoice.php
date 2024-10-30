<?php 

function cf7mollie_bankchoice_shortcode_handler ($tag){
    require_once __DIR__ . '/initialize.php';

    $wpcf7 = WPCF7_ContactForm::get_current();
    $formid = $wpcf7->id();
    
    cf7_mollie_setapikey($formid);
    $mollie = $GLOBALS['mollie'];
	$include_blank = $tag->has_option( 'include_blank' );
    try {
        $method = $mollie->methods->get(\Mollie\Api\Types\PaymentMethod::IDEAL, ["include" => "issuers"]);

        $html = '<span class="wpcf7-form-control-wrap paymentmethod">';
        $html .= '<select name="issuer" class="wpcf7-form-control wpcf7-select">';
		if ($include_blank){
			$html .= '<option value="">---</option>';
		}
        foreach ($method->issuers() as $issuer) {
            $html .= '<option value=' . htmlspecialchars($issuer->id) . '>' . htmlspecialchars($issuer->name) . '</option>';
        }

        foreach ($tag->values as $value) {
        	$html .= '<option value="">'. $value .'</option>';
        }
        $html .= '</select></span>';
    } catch (\Mollie\Api\Exceptions\ApiException $e) {
        $html = "API call failed: " . htmlspecialchars($e->getMessage());
    }

    return $html;
}

function cf7mollie_tag_generator_bankchoice( $contact_form, $args = '' ) {
    $args = wp_parse_args( $args, array() );

    $description = __( "Generate a dropdown menu to select a bank if chosen for iDeal as a paymentmethod. You can add extra options as well, these will be placed at the bottom of the list. You should use %s to only show the field when iDeal is chosen as a payment method", 'cf7-mollie-translation');

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
					        <label>
					        	<input type="checkbox" name="required" /> <?php echo esc_html( __( 'Required field', 'contact-form-7' ) ); ?>
					        </label>
				        </fieldset>
				    </td>
			    </tr>

			    <tr>
				    <th scope="row">
				    	<label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>">
				    		<?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?>
				    	</label>
				    </th>
				    <td>
				    	<input type="text" name="name" readonly="readonly" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" />
				    </td>
			    </tr>

			    <tr>
				    <th scope="row"><?php echo esc_html( __( 'Options', 'contact-form-7' ) ); ?></th>
				    <td>
				        <fieldset>
					        <legend class="screen-reader-text"><?php echo esc_html( __( 'Options', 'contact-form-7' ) ); ?></legend>
					        <textarea name="values" class="values" id="<?php echo esc_attr( $args['content'] . '-values' ); ?>"></textarea>
					        <label for="<?php echo esc_attr( $args['content'] . '-values' ); ?>">
					        	<span class="description"><?php echo esc_html( __( "One option per line.", 'contact-form-7' ) ); ?></span>
					        </label>
							<label><input type="checkbox" name="include_blank" class="option" /> <?php echo esc_html( __( 'Insert a blank item as the first option', 'contact-form-7' ) ); ?></label>
					        <br />
				        </fieldset>
				    </td>
			    </tr>

			    <tr>
				    <th scope="row">
				    	<label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'contact-form-7' ) ); ?>
				    	</label>
				    </th>
				    <td>
				    	<input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>"/>
				    </td>
				</tr>

				<tr>
				    <th scope="row">
				    	<label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'contact-form-7' ) ); ?></label>
				    </th>
				    <td>
				    	<input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" />
				    </td>
			    </tr>
			</tbody>
		</table>
	</fieldset>
</div>

<div class="insert-box">
    <input type="text" name="bankchoice" class="tag code" readonly="readonly" onfocus="this.select()" />

    <div class="submitbox">
    	<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />
    </div>

    <br class="clear" />

    <p class="description mail-tag">
    	<label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>"><?php echo sprintf( esc_html( __( "To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.", 'contact-form-7' ) ), '<strong><span class="mail-tag"></span></strong>' ); ?>
    		<input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>" />
    	</label>
    </p>
</div>
<?php
}