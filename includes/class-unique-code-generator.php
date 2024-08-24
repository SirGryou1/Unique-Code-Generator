<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Empêche un accès direct
}

//Inclure le fichier contenant la classe unique_code_generator_email.php
require_once plugin_dir_path(__FILE__) . 'class-unique-code-generator-email.php';

class Unique_Code_Generator {

    public function __construct() {
        add_action('woocommerce_thankyou', array($this, 'generate_code_on_purchase'));
        add_filter('woocommerce_order_item_meta_end', array($this, 'display_code_in_order'), 10, 3);
    }

    public function generate_unique_code() {
        do {
            $unique_code = strtoupper(uniqid('CODE-'));
        } while ($this->code_exists($unique_code));

        return $unique_code;
    }

    public function code_exists($code) {
        global $wpdb;

        $query = $wpdb->prepare("
            SELECT meta_id 
            FROM {$wpdb->prefix}woocommerce_order_itemmeta 
            WHERE meta_key = '_unique_code' 
            AND meta_value = %s
        ", $code);

        return $wpdb->get_var($query) ? true : false;
    }

    public function insert_code_in_custom_table($order_id, $product_id, $code, $email_sent = 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'generated_codes';

        $wpdb->insert(
            $table_name,
            array(
                'order_id' => $order_id,
                'product_id' => $product_id,
                'code' => $code,
                'email_sent' => $email_sent
            ),
            array(
                '%d',
                '%d',
                '%s',
                '%d'
            )
        );
    }

    public function generate_code_on_purchase($order_id) {
        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);
        // Vérifiez si les codes ont déjà été générés pour cette commande
        if ($order->get_meta('_codes_generated') == 'yes') {
            return; // Arrêtez l'exécution si les codes ont déjà été générés
        }
        $additional_chances = get_option('additional_chances',10);

        $codes = [];

        foreach ($order->get_items() as $item_id => $item) {
            $product_id = $item->get_product_id();
            $quantity = $item->get_quantity();
            $additional_chances = 0;

            if (has_term('10 chances de plus', 'product_tag', $product_id)) {
                $extra_chances = $additional_chances;
            }

            for ($i = 0; $i < $quantity + $additional_chances; $i++) {
                $unique_code = $this->generate_unique_code();

                if (!empty($unique_code)) {
                    wc_add_order_item_meta($item_id, '_unique_code_' . $i, $unique_code);
                    $this->insert_code_in_custom_table($order_id, $product_id, $unique_code);
                    $codes[] = $unique_code;
                }
            }
        }

        $order->update_meta_data('_codes_generated', 'yes');
        $order->save();
    
        Unique_Code_Generator_Email::send_codes_to_customer($order, $codes);
    }

    public function display_code_in_order($item_id, $item, $order) {
        for ($i = 0; $i < 15; $i++) {
            $code = wc_get_order_item_meta($item_id, '_unique_code_' . $i, true);
            if ($code) {
                echo '<p><strong>Code Unique:</strong> ' . esc_html($code) . '</p>';
            }
        }
    }
}

new Unique_Code_Generator();