<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Empêche un accès direct
}

class Unique_Code_Generator_Email {

    public static function send_codes_to_customer($order, $codes) {
        $to = $order->get_billing_email(); // Adresse email du client
        $subject = get_option('email_subject', 'Votre (vos) code(s) unique(s) pour la commande #' . $order->get_order_number()); // Objet de l'email
        $headers = array('Content-Type: text/html; charset=UTF-8'); // Définir les en-têtes de l'email pour l'envoi en HTML

        // Récupérer les options personnalisées pour le contenu de l'email
        $email_content = get_option('email_content', '<p>Nous vous remercions pour votre commande. Voici votre (vos) code(s) unique(s) pour le(s) article(s) que vous avez acheté(s) :</p>');
        
        // Remplacer les placeholders par les valeurs dynamiques
        $site_name = get_bloginfo('name');
        $site_url = get_site_url();
        $site_logo_url = 'https://www.test.alter-native-projects.com/wp-content/uploads/2024/08/images-2.png';
        $instagram_profile_url = 'https://www.instagram.com/votreprofil';

        $code_count = count($codes);

        // Générer la liste des codes
        $code_list = '';
        foreach ($codes as $code) {
            $code_list .= '<li><strong style="color: #0056b3;">' . esc_html($code) . '</strong></li>';
        }

        // Remplacer les placeholders dans le contenu de l'email
        $message = str_replace(
            array('{{billing_first_name}}', '{{site_name}}', '{{site_url}}', '{{site_logo_url}}', '{{instagram_profile_url}}', '{{code_list}}', '{{code_count}}'),
            array(esc_html($order->get_billing_first_name()), esc_html($site_name), esc_url($site_url), esc_url($site_logo_url), esc_url($instagram_profile_url), $code_list, $code_count),
            $email_content
        );

        // Envoyer l'email au client
        $sent = wp_mail($to, $subject, $message, $headers);
        if ($sent) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'generated_codes';
            $wpdb->update(
                $table_name,
                array('email_sent' => 1),
                array('order_id' => $order->get_id())
            );
        }   
    }
}
