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
        if ( $_POST['zawiw_poll'] == 'delete' && check_admin_referer( 'zawiw_poll_delete' ) ) {
            zawiw_poll_process_delete($_GET['del']);
        }
    }
}

function zawiw_poll_process_new() {
    global $wpdb;
    global $zawiw_poll_transient;

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
            set_transient( $zawiw_poll_transient, "Fehler der Eingabe. Bitte füllen Sie alle Felder aus.", 5 );
            return;
        }
    }

    // Generate datetime for db
    $dt_count = 0;
    for ( $i=1; $i < 6; $i++ ) {
        // Construct a datetime string
        $datetime = strlen($_POST['datetimepicker'.$i]) ? date_create( $_POST['datetimepicker'.$i]) : null;
        if ( !$datetime ) {
            continue;
        }
        $dt_count += 1;
        $poll_data['dt'.$i] = date_format( $datetime, 'Y-m-d H:i:s' );
    }

    if ( $dt_count < 1 ) {
        set_transient( $zawiw_poll_transient, "Fehler der Eingabe. Bitte geben Sie korrekte Datum/Zeitangaben an.", 5 );
        return;
    }

    // Update the db
    $wpdb->insert( $wpdb->get_blog_prefix() . 'zawiw_poll_data', $poll_data );

    set_transient( $zawiw_poll_transient, "Umfrage erfolgreich erstellt", 5 );

    header('Location: '.get_permalink());


}

function zawiw_poll_process_participate() {
    global $wpdb;
    global $zawiw_poll_transient;

    // Query the database to get currently active poll and its appointments
    $zawiw_poll_query = 'SELECT * FROM ';
    $zawiw_poll_query .= $wpdb->get_blog_prefix() . 'zawiw_poll_data ';
    $zawiw_poll_query .= 'WHERE id = %d';
    $zawiw_poll_item = $wpdb->get_results( $wpdb->prepare( $zawiw_poll_query, $_GET['id'] ), ARRAY_A );
    $zawiw_poll_item = isset( $zawiw_poll_item[0] ) ? $zawiw_poll_item[0] : null;
    if ( $zawiw_poll_item == '' ) {
        set_transient( $zawiw_poll_transient, 'Fehler. Umfrage konnte nicht gefunden werden. ', 5 );
        return;
    }

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
        $zawiw_poll_query .= 'WHERE poll = %d';
        $zawiw_poll_query .= ' AND appointment = %s ';
        $zawiw_poll_query .= ' AND user = %d ';
        $zawiw_poll_appointment = $wpdb->get_results( $wpdb->prepare( $zawiw_poll_query, $_GET['id'], $zawiw_poll_item['DT'.$i], $current_user->ID ), ARRAY_A );
        $zawiw_poll_appointment = isset( $zawiw_poll_appointment[0] ) ? $zawiw_poll_appointment[0] : null;

        // If user would like to participate in # appointment
        if ( isset( $_POST['part'.$i] ) ) {
            // Update participation or participate
            if ( !isset($zawiw_poll_appointment) ) {
                // Insert participation
                $wpdb->insert( $wpdb->get_blog_prefix() . 'zawiw_poll_part', $poll_part );
            }else {
                // already participating
                continue;
            }

        }else {
            // Delete participation or do nothing
            if ( !isset($zawiw_poll_appointment) ) {
                // already not participationg
                continue;
            }else {
                $wpdb->delete( $wpdb->get_blog_prefix() . 'zawiw_poll_part', array( 'ID' => $zawiw_poll_appointment['id'] ) );
            }
        }
        set_transient( $zawiw_poll_transient, "Änderungen gespeichert", 5 );

    }
}
function zawiw_poll_process_delete($id){
    global $wpdb;
    global $zawiw_poll_transient;

    // Check if already participating by selectiong where id and datetime match
    $zawiw_poll_query = 'SELECT * FROM ';
    $zawiw_poll_query .= $wpdb->get_blog_prefix() . 'zawiw_poll_data ';
    $zawiw_poll_query .= 'WHERE id = %d';
    $zawiw_poll_data_delete = $wpdb->get_results( $wpdb->prepare( $zawiw_poll_query, $id ), ARRAY_A );
    $zawiw_poll_data_delete = isset( $zawiw_poll_data_delete[0] ) ? $zawiw_poll_data_delete[0] : null;

    if ( is_numeric( $id ) AND $zawiw_poll_data_delete ) {
        if($zawiw_poll_data_delete['owner'] == get_current_user_id() OR current_user_can( 'manage_options' )){
            $wpdb->delete( $wpdb->get_blog_prefix() . 'zawiw_poll_part', array( 'poll' => $id ) );
            $wpdb->delete( $wpdb->get_blog_prefix() . 'zawiw_poll_data', array( 'ID' => $id ) );
            set_transient( $zawiw_poll_transient, "Umfrage erfolgreich gelöscht", 5 );
            header('Location: '.get_permalink());
        }
        else{
            set_transient( $zawiw_poll_transient, "Sie besitzen nicht die nötigen Rechte um dies zu tun!", 5 );
        }
    }
    else{
        set_transient( $zawiw_poll_transient, "Keine gültige Umfrage gefunden!", 5 );
        return;
    }
}