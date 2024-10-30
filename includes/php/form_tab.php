<?php
//Add settings tab
	/**
	 * Add actions
	 */
	add_action( 'wpcf7_editor_panels', 'cf7mollie_add_panel'  );
	add_action( 'wpcf7_after_save', 'cf7mollie_store_meta'  );
	add_action( 'wpcf7_after_create', 'cf7_mollie_duplicate_form_support'  );
	add_action( 'admin_notices', 'cf7mollie_admin_notice' );
	
	/**
	 * Adds a tab on contact form edit page
	 *
	 * @param array $panels an array of panels.
	 */
    function cf7mollie_add_panel( $panels ) {
        $panels['cf7molllie-panel'] = array(
            'title'     => "Mollie Settings",
            'callback'  => 'cf7_mollie_create_panel_inputs' ,
        );
        return $panels;
    }

	/**
	 * Create plugin fields
	 *
	 * @return array of plugin fields: name and type
	 */
    function cf7mollie_get_plugin_fields() {
        $fields = array(
            array(
                'name' => 'mollie_api_key',
                'type' => 'text',
            ),
			array(
				'name' => 'mollie_redirecturl',
                'type' => 'text',
			)
        );

        return $fields;
    }

	/**
	 * Validate and store meta data
	 *
	 * @param object $contact_form WPCF7_ContactForm Object - All data that is related to the form.
	 */
	function cf7mollie_store_meta( $contact_form ) {
		if ( ! isset( $_POST ) || empty( $_POST ) ) {
			return;
		} else {
			if ( ! wp_verify_nonce( $_POST['cf7_mollie_page_metaboxes_nonce'], 'cf7_mollie_page_metaboxes' ) ) {
				return;
			}

			$form_id = $_GET['post'];
			$data = $_POST['cf7-mollie'];

			$value = sanitize_text_field($data['mollie_api_key']);
    		update_post_meta( $form_id, "CF7_mollie_apikey", $value );

			$value = esc_url_raw($data['mollie_redirecturl']);
			update_post_meta( $form_id, "CF7_mollie_redirecturl", $value );
		}
	}

	/**
	 * Push all forms dropbox settings data into an array.
	 * @return array  Form dropbox settings data
	 */
	function cf7mollie_get_forms() {
		$args = array(
			'post_type' => 'wpcf7_contact_form',
			'posts_per_page' => -1,
		);
		$query = new WP_Query( $args );

		if ( $query->have_posts() ) :

			$fields = cf7mollie_get_plugin_fields();

			while ( $query->have_posts() ) : $query->the_post();

				$post_id = get_the_ID();

				foreach ( $fields as $field ) {
					$forms[ $post_id ][ $field['name'] ] = get_post_meta( $post_id, '_cf7_mollie_' . $field['name'], true );
				}

			endwhile;
			wp_reset_postdata();

		endif;

		return $forms;
	}

	/**
	 * @param object $contact_form WPCF7_ContactForm Object - All data that is related to the form.
	 */
	function cf7_mollie_duplicate_form_support( $contact_form ) {
		$contact_form_id = $contact_form->id();

		if ( ! empty( $_REQUEST['post'] ) && ! empty( $_REQUEST['_wpnonce'] ) ) {
			$post_id = intval( $_REQUEST['post'] );

			$fields = cf7mollie_get_plugin_fields();

			foreach ( $fields as $field ) {
				update_post_meta( $contact_form_id, '_cf7_mollie_' . $field['name'], get_post_meta( $post_id, '_cf7_mollie_' . $field['name'], true ) );
			}
		}
	}

	/**
	 * Create the panel inputs
	 *
	 * @param  object $post Post object.
	 */
	function cf7_mollie_create_panel_inputs( $post ) {
		wp_nonce_field( 'cf7_mollie_page_metaboxes', 'cf7_mollie_page_metaboxes_nonce' );
		?>
		<fieldset>
			<h3>
              <span>
                <?php esc_html_e( 'Form specific mollie settings', 'cf7-mollie' );?>
              </span>
            </h3>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="cf7-mollie-api-key"><?php esc_html_e( 'Api Key', 'cf7-mollie' );?></label>
						</th>
						<td>
							<input type="text" id="cf7-mollie-api-key" class="large-text" placeholder="<?php esc_html_e( 'Api Key', 'cf7-mollie' );?>" 
							name="cf7-mollie[mollie_api_key]" value="<?php echo get_post_meta( $post->id(), "CF7_mollie_apikey",true);?>">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="cf7-mollie-redirecturl"><?php esc_html_e( 'Redirect URL', 'cf7-mollie' );?></label>
						</th>
						<td>
							<input type="text" id="cf7-mollie-redirecturl" class="large-text" placeholder="<?php esc_html_e( 'Redirect URL', 'cf7-mollie' );?>" 
							name="cf7-mollie[mollie_redirecturl]" value="<?php echo get_post_meta( $post->id(), "CF7_mollie_redirecturl",true);?>">
						</td>
					</tr>
				</tbody>
			</table>
		</fieldset>
		<?php
	}