<?php

// Action on plugin activation
register_activation_hook( dirname( __FILE__ ).'/zawiw-poll.php', 'zawiw_poll_activation' );

// Called on plugin activation to prepare database
function zawiw_poll_activation() {
    // Get access to global database access class
    global $wpdb;
    // Check to see if WordPress installation is a network
    if ( is_multisite() ) {
        // If it is, cycle through all blogs, switch to them
        // and call function to create plugin table
        if ( !empty( $_GET['networkwide'] ) ) {
            // Save main blog
            $start_blog = $wpdb->blogid;
            $blog_list = $wpdb->get_col( 'SELECT blog_id FROM ' . $wpdb->blogs );
            foreach ( $blog_list as $blog ) {
                switch_to_blog( $blog );
                // Send blog table prefix to creation function
                zawiw_poll_create_db( $wpdb->get_blog_prefix() );
            }
            // Return to main blog
            switch_to_blog( $start_blog );
            return;
        }
    }
    // Create table on main blog in network mode or single blog
    zawiw_poll_create_db( $wpdb->get_blog_prefix() );
}

//Creates the actual database
function zawiw_poll_create_db( $prefix ) {
    // Prepare SQL query to create database table
    // using function parameter
    $creation_query = 'CREATE TABLE ' . $prefix . "zawiw_poll_data (
      id int(20) NOT NULL AUTO_INCREMENT,
      createDT datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      title tinytext NOT NULL,
      owner int(20) NOT NULL,
      place tinytext NOT NULL,
      description TEXT NOT NULL,
      DT1 datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      DT2 datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      DT3 datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      DT4 datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      DT5 datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      UNIQUE KEY id (id)
      );";
    // Updates database if nescessary
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $creation_query );
    // Prepare SQL query to create participants table
    // using function parameter
    $creation_query = 'CREATE TABLE ' . $prefix . "zawiw_poll_part (
      id int(20) NOT NULL AUTO_INCREMENT,
      poll int(20) NOT NULL,
      user int(20) NOT NULL,
      appointment datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      UNIQUE KEY id (id)
      );";
    // Updates database if nescessary
    dbDelta( $creation_query );
}

// Register function to be called when new blogs are added to a network site
add_action( 'wpmu_new_blog', 'zawiw_poll_new_network_site' );
function zawiw_poll_new_network_site( $blog_id ) {
    global $wpdb;
    // Check if this plugin is active when new blog is created
    // Include plugin functions if it is
    if ( !function_exists( 'is_plugin_active_for_network' ) )
        require_once ABSPATH . '/wp-admin/includes/plugin.php';
    // Select current blog, create new table and switch back
    if ( is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
        $start_blog = $wpdb->blogid;
        switch_to_blog( $blog_id );
        // Send blog table prefix to table creation function
        zawiw_poll_create_db( $wpdb->get_blog_prefix() );
        switch_to_blog( $start_blog );
    }
}

?>