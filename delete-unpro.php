<?php 
global $wpdb;
$changeformstat = GFAPI::update_entry_field( $_GET['oid'], '13', 'Deleted' );
header("Location: /unprocessed");
