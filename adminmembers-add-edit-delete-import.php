<?php

// Register function to be called when administration pages init takes place
add_action( 'admin_init', 'wpamms_adminmembers_init' );

// Register functions to be called when members are saved
function wpamms_adminmembers_init() {
	add_action('admin_post_save_wpamms_member',
		'wpamms_process_member');

	add_action('admin_post_delete_amms_member',
		'wpamms_delete_member');

	add_action('admin_post_import_wpamms_members',
		'wpamms_import_members');
}

// Function to be called when new members are created or existing members
// are saved
function wpamms_process_member() {
    $options = get_option( 'wpamms_options' );

	// Check if user has proper security level
	if ( !current_user_can( 'manage_options' ) )
		wp_die( 'Not allowed' );

	// Check if nonce field is present for security
	check_admin_referer( 'wpamms_members_add_edit' );

	global $wpdb;

	// Place all user submitted values in an array
	$member_data = array();
	$member_data['id'] = ( isset( $_POST['id'] ) ? sanitize_text_field($_POST['id']) : '' );
	$member_data['name'] = ( isset( $_POST['name'] ) ? sanitize_text_field($_POST['name']) : '' );
	$member_data['memberid'] = ( isset( $_POST['memberid'] ) ? sanitize_text_field($_POST['memberid']) : '' );
	$member_data['institution'] = ( isset( $_POST['institution'] ) ? sanitize_text_field($_POST['institution']) : '' );
	$member_data['department'] = ( isset( $_POST['department'] ) ? sanitize_text_field($_POST['department']) : '' );
	$member_data['address'] = ( isset( $_POST['address'] ) ? sanitize_text_field($_POST['address']) : '' );
	$member_data['city'] = ( isset( $_POST['city'] ) ? sanitize_text_field($_POST['city']) : '' );
	$member_data['province'] = ( isset( $_POST['province'] ) ? sanitize_text_field($_POST['province']) : '' );
	$member_data['postalcode'] = ( isset( $_POST['postalcode'] ) ? sanitize_text_field($_POST['postalcode']) : '' );
	$member_data['emailaddress'] = ( isset( $_POST['emailaddress'] ) ? sanitize_email($_POST['emailaddress']) : '' );
        if(is_email($member_data['emailaddress']) == false)
            $member_data['emailaddress'] = '';
        else
            $member_data['emailaddress'] = is_email($member_data['emailaddress']);
	$member_data['phonenumber'] = ( isset( $_POST['phonenumber'] ) ? sanitize_text_field($_POST['phonenumber']) : '' );
	$member_data['gender'] = ( isset( $_POST['gender'] ) ? sanitize_text_field($_POST['gender']) : '' );
	$member_data['researchfocus'] = ( isset( $_POST['researchfocus'] ) ? sanitize_text_field($_POST['researchfocus']) : '' );
	$member_data['photo'] = ( isset( $_POST['photo'] ) ? sanitize_text_field($_POST['photo']) : '' );
	$member_data['paymentreceipt'] = ( isset( $_POST['paymentreceipt'] ) ? sanitize_text_field($_POST['paymentreceipt']) : '' );
	$member_data['membersince'] = ( isset( $_POST['membersince'] ) ? sanitize_text_field($_POST['membersince']) : '' );
	$member_data['expirationdate'] = ( isset( $_POST['expirationdate'] ) ? sanitize_text_field($_POST['expirationdate']) : '' );
	$member_data['active'] = ( isset( $_POST['active'] ) ? sanitize_text_field($_POST['active']) : '' );
	$member_data['notification1'] = '0';
	$member_data['notification2'] = '0';
	$member_data['notification3'] = '0';
            
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }

        if( file_exists($_FILES['filePaymentReceiptToUpload']['tmp_name']) && is_uploaded_file($_FILES['filePaymentReceiptToUpload']['tmp_name'])) {

            $uploadedfile = $_FILES['filePaymentReceiptToUpload'];
            $uploadedfile['name'] = date("Y-m-d-H-i-s") . '_' . $uploadedfile['name'];
            $upload_overrides = array( 'test_form' => false );
            $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );
            if ( $movefile && !isset( $movefile['error'] ) ) {
                //echo "File is valid, and was successfully uploaded.\n";
                //var_dump( $movefile);
                $member_data['paymentreceipt'] = $movefile['url'];
            } else {
                wp_die("Upload PROOF OF PAYMENT Failed! " );
            }
            
        }

        if( file_exists($_FILES['filePhotoToUpload']['tmp_name']) && is_uploaded_file($_FILES['filePhotoToUpload']['tmp_name'])) {

            $uploadedfile = $_FILES['filePhotoToUpload'];
            $uploadedfile['name'] = date("Y-m-d-H-i-s") . '_' . $uploadedfile['name'];
            $upload_overrides = array( 'test_form' => false );
            $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );
            if ( $movefile && !isset( $movefile['error'] ) ) {
                //echo "File is valid, and was successfully uploaded.\n";
                //var_dump( $movefile);
                $member_data['photo'] = $movefile['url'];
            } else {
                wp_die("Upload PHOTO Failed! " );
            }
            
        }
        
        
	// Call the wpdb insert or update method based on value
	// of hidden member_id field
	if ( isset( $_POST['id'] ) && $_POST['id'] == 'new' ) {
                $member_data['id'] = ''; 
		$wpdb->insert($wpdb->get_blog_prefix() . 'amms_members', $member_data );
        } elseif ( isset( $_POST['id'] ) && is_numeric( $_POST['id'] ) ) {
		$wpdb->update( $wpdb->get_blog_prefix() . 'amms_members', $member_data, array( 'id' => intval($_POST['id']) ) );
        }
        
        if ($options['send_email_notification']) {
            $multiple_recipients = array(
                sanitize_email($member_data['emailaddress']),
                sanitize_email($options['membership_admin_email'])
            );
            $subj = "[Notification] ".esc_html($options['short_tittle'])." Membership (id=".esc_html($member_data['memberid']).') has been updated';

            $body = "Your Membership Registration Data :\r\n\r\n";
            $body .= "name = " . esc_html($member_data['name']) . "\r\n\r\n";
            $body .= "member id = " . esc_html($member_data['memberid']) . "\r\n\r\n";
            $body .= "institution = " . esc_html($member_data['institution'])  . "\r\n";
            $body .= "department = " . esc_html($member_data['department'])  . "\r\n";
            $body .= "address = " . esc_html($member_data['address'])  . "\r\n";
            $body .= "city = " . esc_html($member_data['city'])   . "\r\n";
            $body .= "province = " . esc_html($member_data['province'])   . "\r\n";
            $body .= "postalcode = " . esc_html($member_data['postalcode'])   . "\r\n";
            $body .= "phonenumber = " . esc_html($member_data['phonenumber'])   . "\r\n";
            $body .= "gender = " . esc_html($member_data['gender'])   . "\r\n";
            $body .= "researchfocus = " . esc_html($member_data['researchfocus'])   . "\r\n";
            $body .= "registration date = " . esc_html($member_data['membersince'])   . "\r\n";
            $body .= "expiration date = " . esc_html($member_data['expirationdate'])   . "\r\n";

            if($member_data['active'] == 1)
                $body .= "membership status = ACTIVE \r\n\r\n ";
            else 
                $body .= "membership status = EXPIRED \r\n\r\n ";

            $body .= "In Case Any Incorrect Data, Please Report to Membership Help Desk at = ". sanitize_email($options['membership_admin_email']) . "\r\n\r\n";
            $body .= "Your Membership Card Is Available To Be Downloaded at Our Membership Website \r\n\r\n";
            wp_mail( $multiple_recipients, $subj, $body );     
        }
        
	// Redirect the page to the admin form
	wp_redirect( add_query_arg( 'page', 'wpamms-members', admin_url( 'admin.php' ) ) );
	exit;
}

// Function to be called when deleting members
function wpamms_delete_member() {
	// Check that user has proper security level
	if ( !current_user_can( 'manage_options' ) )
		wp_die( 'Not allowed' );

	// Check if nonce field is present
	check_admin_referer( 'amms_member_deletion' );

	if ( !empty( $_POST['members_id'] ) ) {
		// Retrieve array of members IDs to be deleted
		$members_to_delete = $_POST['members_id'];

		global $wpdb;

		foreach ( $members_to_delete as $member_to_delete ) {
			$query = 'DELETE from ' . $wpdb->get_blog_prefix() . 'amms_members ';
			$query .= 'WHERE id = ' . intval( sanitize_text_field($member_to_delete) );
			$wpdb->query( $query );
		}
	}

	// Redirect the page to the admin page
	wp_redirect( add_query_arg( 'page', 'wpamms-members', admin_url( 'admin.php' ) ) );
	exit;
}

// Function to be called when importing members
function wpamms_import_members() {
	// Check that user has proper security level
	if ( !current_user_can( 'manage_options' ) )
		wp_die( 'Not allowed' );

	// Check if nonce field is present
	check_admin_referer( 'wpamms_import' );

	// Check if file has been upladed
	if( array_key_exists( 'importmembersfile', $_FILES ) ) {
		// If file exists, open it in read mode
		$handle = fopen( $_FILES['importmembersfile']['tmp_name'], 'r' );

		// If file is successfully open, extract a row of csv data
		// based on comma separator, and store in $data array
		if ( $handle ) {
			while ( ( $data = fgetcsv( $handle, 5000, ',' ) ) !== FALSE ) {
				$row += 1;

				// If row count is accurate and row is not header row
				// Create array and insert in database
				if ( count( $data ) == 17 && $row != 1 ) {
					$new_member = array( 
                                                        'id' => '',
                                                        'name' => sanitize_text_field($data[0]),
                                                        'memberid' => sanitize_text_field($data[1]),
                                                        'institution' => sanitize_text_field($data[2]),
                                                        'department' => sanitize_text_field($data[3]),
                                                        'address' => sanitize_text_field($data[4]),
                                                        'city' => sanitize_text_field($data[5]),
                                                        'province' => sanitize_text_field($data[6]),
                                                        'postalcode' => sanitize_text_field($data[7]),
                                                        'emailaddress' => sanitize_email($data[8]),
                                                        'phonenumber' => sanitize_text_field($data[9]),
                                                        'gender' => sanitize_text_field($data[10]),
                                                        'researchfocus' => sanitize_text_field($data[11]),
                                                        'photo' => sanitize_text_field($data[12]),
                                                        'paymentreceipt' => sanitize_text_field($data[13]),
                                                        'membersince' => sanitize_text_field($data[14]),
                                                        'expirationdate' => sanitize_text_field($data[15]),
                                                        'active' => sanitize_text_field($data[16]),
                                                        'notification1' => '0',
                                                        'notification2' => '0',
                                                        'notification3' => '0' );

					global $wpdb;

					$wpdb->insert( $wpdb->get_blog_prefix() . "amms_members", $new_member );
				}
			}
		}
	}

	// Redirect the page to the admin page
	wp_redirect( add_query_arg( 'page', 'wpamms-members', admin_url( 'admin.php' ) ) );
	exit;
}