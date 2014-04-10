<?php
// Early called action, to process POST and FILE data
add_action( 'template_redirect', 'zawiw_poll_process' );

function zawiw_poll_process() {
    if ( !empty( $_POST['zawiw_poll'] ) ) {
        // If the new form was used
        if ( $_POST['zawiw_poll'] == 'new' && check_admin_referer( 'zawiw_poll_new' ) ) {
            zawiw_poll_process_new();
        }
        if ( $_POST['zawiw_poll'] == 'participate' && check_admin_referer( 'zawiw_poll_participate' ) ) {
            zawiw_poll_process_participate();
        }
    }
}

function zawiw_poll_process_new() {
    // Validate POST

    global $zawiw_poll_message;
    // echo "<pre>";
    // print_r( $_POST );
    // echo "</pre>";


    // Prepare an array to store in db
    $poll_data = array();
    $current_user = wp_get_current_user();
    $poll_data['title'] = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
    $poll_data['place'] = isset( $_POST['place'] ) ? sanitize_text_field( $_POST['place'] ) : '';
    $poll_data['description'] = isset( $_POST['description'] ) ? sanitize_text_field( $_POST['description'] ) : '';
    $poll_data['owner'] = $current_user->ID;
    $poll_data['createDT'] = date( 'Y-m-d H:i:s' );

    // Form validation
    foreach ( $poll_data as $data ) {
        if ( !strlen( $data ) ) {
            $zawiw_poll_message = "Fehler der Eingabe. Bitte füllen Sie alle Felder aus.";
            return;
        }
    }

    // Generate datetime for db
    $dt_count = 0;
    for ( $i=1; $i < 6; $i++ ) {
        // Construct a datetime string
        $datestring = $_POST['dt'.$i.'y'] .'/'. $_POST['dt'.$i.'m'] .'/'. $_POST['dt'.$i.'d'] .' '. $_POST['dt'.$i.'h'] .':'. $_POST['dt'.$i.'i'];

        // echo $datestring;
        // echo "<br />";
        // Create datetime from datetimestring

        $datetime = date_create( $datestring );
        if ( !$datetime ) {
            break;
        }
        $dt_count += 1;
        $poll_data['dt'.$i] = date_format( $datetime, 'Y-m-d H:i:s' );

        // echo date_format( $datetime, 'Y-m-d H:i:s' ); // 2011-03-03 00:00:00
        // echo "<br />";
        // echo "<br />";
    }

    // echo "<pre>";
    // print_r( $poll_data );
    // echo "</pre>";
    // Test if correct datetimes exist
    if ( $dt_count < 1 ) {
        $zawiw_poll_message = "Fehler der Eingabe. Bitte geben Sie korrekte Datum/Zeitangaben an.";
        return;
    }

    // Update the db
    global $wpdb;
    $wpdb->insert( $wpdb->get_blog_prefix() . 'zawiw_poll_data', $poll_data );

    $zawiw_poll_message = "Umfrage erfolgreich hinzugefügt.";

}

function zawiw_poll_process_participate() {
    global $zawiw_poll_message;
    // echo "<pre>";
    // print_r( $_POST );
    // echo "</pre>";

    global $wpdb;
    // Query the database to get currently active poll and its appointments
    $zawiw_poll_query = 'SELECT * FROM ';
    $zawiw_poll_query .= $wpdb->get_blog_prefix() . 'zawiw_poll_data ';
    $zawiw_poll_query .= 'WHERE id='.$_GET['id'];
    $zawiw_poll_item = $wpdb->get_results( $wpdb->prepare( $zawiw_poll_query, null ), ARRAY_A );
    $zawiw_poll_item = isset( $zawiw_poll_item[0] ) ? $zawiw_poll_item[0] : '';
    if ( $zawiw_poll_item == '' ) {
        $zawiw_poll_message = 'Fehler. Umfrage konnte nicht gefunden werden. ';
        return;
    }
    // echo "<pre>";
    // print_r( $zawiw_poll_item );
    // echo "</pre>";

    // Iterate over all possible appointments
    for ( $i=1; $i < 6; $i++ ) {
        // Prepare an array to store in db
        $poll_part = array();
        $current_user = wp_get_current_user();
        // Poll ID from GET
        $poll_part['poll'] = isset( $_GET['id'] ) ? $_GET['id'] : '';
        // User ID from worpress
        $poll_part['user'] = $current_user->ID;
        // Appointent time from above query
        $poll_part['appointment'] = $zawiw_poll_item['DT'.$i];

        // Check if already participating by selectiong where id and datetime match
        $zawiw_poll_query = 'SELECT * FROM ';
        $zawiw_poll_query .= $wpdb->get_blog_prefix() . 'zawiw_poll_part ';
        $zawiw_poll_query .= 'WHERE poll='.$_GET['id'];
        $zawiw_poll_query .= ' AND appointment="'.$zawiw_poll_item['DT'.$i].'"';
        $zawiw_poll_query .= ' AND user="'.$current_user->ID.'"';
        $zawiw_poll_appointment = $wpdb->get_results( $wpdb->prepare( $zawiw_poll_query, null ), ARRAY_A );
        $zawiw_poll_appointment = isset( $zawiw_poll_appointment[0] ) ? $zawiw_poll_appointment[0] : '';

        // If user would like to participate in # appointment
        if ( isset( $_POST['part'.$i] ) ) {
            // Update participation or participate
            if ( $zawiw_poll_appointment == '' ) {
                // Insert participation
                $wpdb->insert( $wpdb->get_blog_prefix() . 'zawiw_poll_part', $poll_part );
            }else {
                // already participating
                continue;
            }

        }else {
            // Delete participation or do nothing
            if ( $zawiw_poll_appointment == '' ) {
                // already not participationg
                continue;
            }else {
                $wpdb->delete( $wpdb->get_blog_prefix() . 'zawiw_poll_part', array( 'ID' => $zawiw_poll_appointment['id'] ) );
            }
        }

    }
}