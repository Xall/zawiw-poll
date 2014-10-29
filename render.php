<?php
// Defines the zawiw-poll shortcode
add_shortcode( 'zawiw_poll', 'zawiw_poll_shortcode' );

// Stylesheets and Scripts
add_action( 'wp_enqueue_scripts', 'zawiw_poll_queue_stylesheet' );
add_action( 'wp_enqueue_scripts', 'zawiw_poll_queue_script' );

function zawiw_poll_shortcode() {

    global $zawiw_poll_transient;

    // Is user logged in?
    if ( !is_user_logged_in() ) {
        echo "<div id='zawiw-poll-message'>Sie müssen angemeldet sein, um diese Funktion zu nutzen</div>";
        return;
    }
    // Prints the message if it isn't empty
    if ( get_transient( $zawiw_poll_transient ) ) {
        echo "<div id='zawiw-poll-message'>".get_transient( $zawiw_poll_transient )."</div>";
    }

    // GET SWITCH

    // NEW POLL CASE
    if ( isset( $_GET['new'] ) ){
        zawiw_poll_new();
    }

    // EXISTING POLL CASE
    elseif ( isset( $_GET['id'] ) ){
        zawiw_poll_get();
    }

    // DELETE POLL CASE
    elseif ( isset( $_GET['del'] ) ){
        zawiw_poll_del($_GET['del']);
    }

    // STANDARD CASE
    else{
        zawiw_poll_std();
    }

}
function zawiw_poll_new(){
?>
    <div id="zawiw_poll_new">
        <form action="" method="post" enctype="multipart/form-data">
            <!-- Form protection -->
            <?php wp_nonce_field( 'zawiw_poll_new' ); ?>
            <label for="title">Titel</label>
            <input type="text" name="title" id="title" value="<?php echo $_POST['title'] ?>"><br>
            <label for="place">Ort</label>
            <input type="text" name="place" id="place" value="<?php echo $_POST['place'] ?>"><br>
            <label for="description">Beschreibung</label>
            <input type="text" name="description" id="description" value="<?php echo $_POST['description'] ?>"><br>
            <?php for ( $d = 1; $d <= 5 ; $d++ ): ?>
                <label for="datetimepicker<?php echo $d?>">Termin <?php echo $d?></label>
                <input name="datetimepicker<?php echo $d?>" class="datetimepicker<?php echo $d?>" type="text" value="<?php echo $_POST['datetimepicker'.$d] ?>">
            <?php endfor ?>
            <input type="submit" name="submit" value="Abschicken">

            <input type="hidden" name="zawiw_poll" value="new" />
        </form>
    </div>
<?php
}

function zawiw_poll_get(){
    global $wpdb;
    // Select all polls where id is _GET[id]
    $zawiw_poll_query = 'SELECT * FROM ';
    $zawiw_poll_query .= $wpdb->get_blog_prefix() . 'zawiw_poll_data ';
    $zawiw_poll_query .= 'WHERE id = %d';
    $zawiw_poll_item = $wpdb->get_results( $wpdb->prepare( $zawiw_poll_query, $_GET['id'] ), ARRAY_A );

    if ( !is_numeric( $_GET['id'] ) ) {
        $zawiw_poll_item = null;
    }
    else{
        global $wpdb;
        // Select all polls where id is _GET[id]
        $zawiw_poll_query = 'SELECT * FROM ';
        $zawiw_poll_query .= $wpdb->get_blog_prefix() . 'zawiw_poll_data ';
        $zawiw_poll_query .= 'WHERE id=%d';
        $zawiw_poll_item = $wpdb->get_results( $wpdb->prepare( $zawiw_poll_query, $_GET['id'] ), ARRAY_A );

        // Returns first item if available, otherwise return zero
        $zawiw_poll_item = isset( $zawiw_poll_item[0] ) ? $zawiw_poll_item[0] : 0;

    }

    // Error case
    if ( !$zawiw_poll_item ) {
        echo "<div id='zawiw-poll-message'>Umfrage konnte nicht gefunden werden.</div>";
        return;
    }

?>
    <div id="zawiw_poll_id">
        <form action="" method="post" enctype="multipart/form-data">
        <div class="meta">
            <h2 class="title"><?php echo $zawiw_poll_item['title'] ?></h2>
            <div class="owner"><i class="fa fa-user"></i>
                <?php echo get_userdata( $zawiw_poll_item['owner'] ) ? get_userdata( $zawiw_poll_item['owner'] )->display_name : "Unbekannt" ?>
            </div>
            <div class="place"><i class="fa fa-home"></i>
                <?php echo $zawiw_poll_item['place']; ?>
            </div>
            <div class="description"><i class="fa fa-file-text-o"></i>
                <?php echo $zawiw_poll_item['description']; ?>
            </div>
        </div>
        <?php for ($i = 1; $i<6; $i++): ?>

        <?php if ($zawiw_poll_item['DT'.$i] != "0000-00-00 00:00:00"): ?>
<?php
                // Get all users participation on current appointment
                $current_user = wp_get_current_user();
                $zawiw_poll_query = 'SELECT * FROM ';
                $zawiw_poll_query .= $wpdb->get_blog_prefix() . 'zawiw_poll_part ';
                $zawiw_poll_query .= 'WHERE poll = %d';
                $zawiw_poll_query .= ' AND appointment = %s ORDER BY user ASC';

                $zawiw_poll_participants = $wpdb->get_results( $wpdb->prepare( $zawiw_poll_query, $_GET['id'], $zawiw_poll_item['DT'.$i] ), ARRAY_A );

                // Count partcipants to highlight the one with the most participants
                $zawiw_poll_participants_count = count($zawiw_poll_participants);
?>

            <div class="appointment" count="<?php echo $zawiw_poll_participants_count?>">
                <div class="dtDesc">Termin <?php echo $i; ?></div>
                <div class="dtApp"><i class="fa fa-calendar"></i>
                    <?php echo date_format( date_create($zawiw_poll_item['DT'.$i]), 'm.d.Y H:i') ?>
                </div>
                <div class="participants"><i class="fa fa-check"></i>Bisherige Teilnehmer:

<?php

                // No participant
                if (!count($zawiw_poll_participants)) {
                    echo "Niemand";
                }

                // Remeber if I participate
                $i_participate = 0;
                foreach ($zawiw_poll_participants as $participant) {
                    echo "<span class='participant'>";
                    echo get_userdata( $participant['user'] ) ? get_userdata( $participant['user'] )->display_name : "Unbekannt";
                    echo "</span>";
                    $i_participate = get_userdata( $participant['user'] ) == wp_get_current_user() ? 1 : $i_participate;
                }
?>
                </div>
                <!-- Form protection -->
                <?php wp_nonce_field( 'zawiw_poll_participate' ); ?>
                <label for="part<?php echo $i ?>">Teilnehmen? </label>
                <input class="checkbox" type="checkbox" id="part<?php echo $i ?>" name="part<?php echo $i ?>" <?php echo $i_participate ? "checked" : "" ?> value="1" />
                <input type="hidden" name="zawiw_poll" value="participate" />
            </div>
        <?php endif ?>
        <?php endfor ?>
        <input type="submit" value="Speichern">
        </form>
    </div>
<?php
}

function zawiw_poll_del($id){
?>
    <p>Wollen Sie Umfrage wirklich unwiderruflich löschen?</p>
    <form action="" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field( 'zawiw_poll_delete' ); ?>
        <input type="hidden" name="zawiw_poll" value="delete" />
        <input type="submit" value="Löschen">
        <input type="button" onClick="history.go(-1);return true;" value="Zurück">

    </form>

<?php
}

function zawiw_poll_std(){
?>
    <h1>Neue Umfrage</h1>
    <a href="?new">Klicken Sie hier um eine neue Umfrage zu starten.</a>
    <h1 id="polls">Bisherige Umfragen finden Sie hier</h1>
<?php
        // Render the headlines of all polls
        global $wpdb;
        $zawiw_poll_query = 'SELECT * FROM ';
        $zawiw_poll_query .= $wpdb->get_blog_prefix() . 'zawiw_poll_data ';
        $zawiw_poll_query .= 'ORDER by createDT DESC';
        $zawiw_poll_items = $wpdb->get_results( $zawiw_poll_query, ARRAY_A );
        foreach ( $zawiw_poll_items as $item ) {
            $dt = date_create($item['createDT']);
            // Echo the poll with creation date and link
            echo date_format( $dt, 'm.d.Y ' )."<a href=?id=".$item['id'].">".$item['title']."</a>";
            // Buttons for owner and admin
            if ($item['owner'] == get_current_user_id() OR current_user_can( 'manage_options' )) {
                echo "<a class='zawiw_poll_btn' href=?del=".$item['id']."><i class='fa fa-trash'></i></a>";
            }
            echo "<br />";


        }
        if (!sizeof($zawiw_poll_items)) {
            echo "<div id='zawiw-poll-message'>Bisher keine Umfragen vorhanden</div>";
        }
}

function zawiw_poll_queue_stylesheet() {
    wp_enqueue_style( 'zawiw_poll_style', plugins_url( 'style.css', __FILE__ ) );
    wp_enqueue_style( 'font_awesome4.2', '//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css' );
    wp_enqueue_style( 'datetimepickercss', plugins_url( 'datetimepicker/jquery.datetimepicker.css', __FILE__ ) );
}

function zawiw_poll_queue_script() {
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'zawiw_poll_script', plugins_url( 'helper.js', __FILE__ ) );
    wp_enqueue_script( 'datetimepickerjs', plugins_url( 'datetimepicker/jquery.datetimepicker.js', __FILE__ ) );

}

?>

<!-- this should go after your </body> -->
<!-- <link rel="stylesheet" type="text/css" href="datetimepicker/jquery.datetimepicker.css"/ >
<script src="datetimepicker/jquery.js"></script>
<script src="datetimepicker/jquery.datetimepicker.js"></script> -->