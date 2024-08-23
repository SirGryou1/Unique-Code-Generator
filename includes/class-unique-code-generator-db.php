<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Empêche un accès direct
}

class Unique_Code_Generator_DB {

    private $db_version = '1.3';

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'create_or_update_table'));
        add_action('init', array($this, 'check_db_version'));
    }

    public function check_db_version() {
        $installed_version = get_option('unique_code_generator_db_version');
        if ($installed_version !== $this->db_version) {
            $this->create_or_update_table();
        }
    }

    public function create_or_update_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'generated_codes';

        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            product_id bigint(20) NOT NULL,
            code varchar(255) NOT NULL,
            email_sent tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('unique_code_generator_db_version', $this->db_version);
    }
}

new Unique_Code_Generator_DB();