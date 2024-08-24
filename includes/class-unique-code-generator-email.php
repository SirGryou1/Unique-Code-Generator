<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Empêche un accès direct
}

class Unique_Code_Generator_Email {

    public static function send_codes_to_customer($order, $codes) {
        $to = $order->get_billing_email(); // Adresse email du client
        $subject = 'Votre (vos) code(s) unique(s) pour la commande #' . $order->get_order_number(); // Objet de l'email
        $headers = array('Content-Type: text/html; charset=UTF-8'); // Définir les en-têtes de l'email pour l'envoi en HTML 

        // Url du site à partager 
        $site_url = get_site_url();

        // Url de la page Instagram
        $instagram_profile_url = 'https://www.instagram.com/votreprofil'; // Le profil de la page doit être ajouté

        // Url du logo
        $site_logo_url = 'https://www.test.alter-native-projects.com/wp-content/uploads/2024/08/images-2.png';

        // Nom du site
        $site_name = get_bloginfo('name'); // Nom du site Wordpress

        //Nombre total de codes générés 
        $number_of_codes = count($codes);

        // Contenu de l'email avec styles personnalisés
        $message = '<div style="font-family: Arial, sans-serif; line-height: 1.5;">';
        // Boîte avec ombre externe
        $message .= '<div style="max-width: 600px; margin: 0 auto; padding: 20px; border-radius: 10px; box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1); background-color: #ffffff;">';
        // <h2> dans une boîte avec couleur violette, prenant toute la largeur
        $message .= '<div style="background-color: #6a0dad; color: #ffffff; padding: 15px; border-radius: 8px; text-align: center; width: 100%; height: 100%;">';
        $message .= '<h2 style="margin: 0;">Merci, ' . esc_html($order->get_billing_first_name()) . ', pour votre achat chez ' . esc_html($site_name) . '!</h2>';
        $message .= '</div>';

        $message .= '<p style="font-size: 16px;">Nous vous remercions pour votre commande. Vous avez reçu <strong>' . esc_html($number_of_codes) . ' code(s) unique(s)</strong> pour le(s) article(s) que vous avez acheté(s) :</p>';
        $message .= '<ul style="font-size: 16px;">';

        foreach ($codes as $code) {
            $message .= '<li><strong style="color: #0056b3;">' . esc_html($code) . '</strong></li>';
        }
        $message .= '</ul>';
        $message .= '<p style="font-size: 16px;">Nous espérons que vous apprécierez vos produits. Si vous avez des questions, n\'hésitez pas à <a href="mailto:support@votre-site.com">nous contacter</a>.</p>';
        // Logo du site avec lien vers le site
        $message .= '<p><a href="' . esc_url($site_url) . '" target="_blank">';
        $message .= '<img src="' . esc_url($site_logo_url) . '" alt="' . esc_html($site_name) . '" style="display: block; margin: 0 auto; width: 150px; height: auto;">';
        $message .= '</a></p>';

        // Boutons de partage
        $message .= '<p style="font-size: 16px;">Partagez le à vos contacts :</p>';
        $message .= '<div>';
        $message .= '<a href="https://www.facebook.com/sharer/sharer.php?u=' . urlencode($site_url) . '" target="_blank" style="text-decoration: none; margin-right: 10px;">';
        $message .= '<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/51/Facebook_f_logo_%282019%29.svg/32px-Facebook_f_logo_%282019%29.svg.png" alt="Facebook" style="vertical-align: middle; width: 32px; height: 32px;">';
        $message .= '</a>';
        $message .= '<a href="https://x.com/intent/tweet?url=' . urlencode($site_url) . '&text=Vous%20aussi%20gagné%20une%20voiture!" target="_blank" style="text-decoration: none; margin-right: 10px;">';
        $message .= '<img src="https://upload.wikimedia.org/wikipedia/commons/c/ce/X_logo_2023.svg" alt="X" style="vertical-align: middle; width: 32px; height: 32px;">';
        $message .= '</a>';
        $message .= '<a href="https://www.linkedin.com/shareArticle?mini=true&url=' . urlencode($site_url) . '" target="_blank" style="text-decoration: none; margin-right: 10px;">';
        $message .= '<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/c9/Linkedin.svg/32px-Linkedin.svg.png" alt="LinkedIn" style="vertical-align: middle; width: 32px; height: 32px;">';
        $message .= '</a>';
        $message .= '<a href="' . esc_url($instagram_profile_url) . '" target="_blank" style="text-decoration: none;">';
        $message .= '<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/a5/Instagram_icon.png/32px-Instagram_icon.png" alt="Instagram" style="vertical-align: middle; width: 32px; height: 32px;">';
        $message .= '</a>';
        $message .= '</div>';
        $message .= '<p style="font-size: 16px;">Cordialement,<br>L\'équipe de ' . esc_html($site_name) . '</p>';
        $message .= '</div>';
        $message .= '</div>';

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