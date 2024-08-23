<?php
/*
  Plugin Name: Unique Code Generator
  Plugin URI: T SOON
  Description: Génère des codes uniques pour chaque article écheté et les envoie par mail au client
  Small indication : Cette version permet d'avoir dans un widget wordpress une notification pour sur les 10 dernieres commandes de si le mail avec le code c'est bien envoyé ou non
  Author: ALTER NATIVE PROJECTS SARL
  Version: 2.2
  Requires PHP: 5.2
  Tested up to: 6.6
  Author URI: https://www.alter-native-projects.com
*/
if ( ! defined( 'ABSPATH' ) ) {
    exit; //Empecher un acces direct
}

class Unique_Code_Generator {

    private $db_version = '1.2';
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'create_table'));
        add_action('init', array($this, 'check_db_version'));  //verification de la version de la db
        add_action('woocommerce_thankyou', array($this, 'generate_code_on_purchase'));
        add_filter('woocommerce_order_item_meta_end', array($this, 'display_code_in_order'), 10, 3);

        // Fonctions de notification dans l'admin
        add_action('wp_dashboard_setup', array($this,'add_dashboard_widget'));
    }

    public function check_db_version() {
        $installed_version = get_option('unique_code_generator_db_version');

        if ($installed_version !== $this->db_version) {
            $this->create_or_update_table();
        }
    }

    // Fonction pour créer et mettre à jour la table personnalisée
    public function create_or_update_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'generated_codes';

        $charset_collate = $wpdb->get_charset_collate();
        
        // Structure actuelle de la table
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            product_id bigint(20) NOT NULL,
            code varchar(255) NOT NULL,
            email_sent tinyint(1) NOT NULL DEFAULT 0, 
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Mettre à jour la version de la base de données 
        update_option('unique_code_generator_db_version', $this->db_version);
    }

    // Fonction pour ajouter le widget au tableau de bord
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'unique_code_email_tracking_widget', // ID du widget
            'Suivi des Emails Contenant les Codes Uniques', // Titre du widget
            array($this, 'display_dashboard_widget') // Fonction de rappel pour afficher le contenu du widget
        );
    }
    // Fonction pour afficher le contenu du widget
    public function display_dashboard_widget() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'generated_codes';

        // Récupérer les 10 dernières commandes avec leurs emails
        $results = $wpdb->get_results("
            SELECT g.order_id, g.code, o.post_date, o.post_status, pm.meta_value AS email_status
            FROM $table_name AS g
            JOIN {$wpdb->prefix}posts AS o ON g.order_id = o.ID
            LEFT JOIN {$wpdb->prefix}postmeta AS pm ON o.ID = pm.post_id AND pm.meta_key = '_email_sent'
            ORDER BY o.post_date DESC
            LIMIT 10
        ");

        if ($results) {
            echo '<ul>';
            foreach ($results as $order) {
                echo '<li>';
                echo '<strong>Commande #:</strong> ' . esc_html($order->order_id) . '<br>';
                echo '<strong>Date:</strong> ' . esc_html($order->post_date) . '<br>';
                echo '<strong>Statut:</strong> ' . esc_html($order->post_status) . '<br>';
                echo '<strong> Code Unique:</strong>' . esc_html($order->code) . '<br>';
                echo '<strong>Email:</strong> ' . (esc_html($order->email_status) ? 'Envoyé' : 'Non envoyé');
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>Aucune donnée disponible pour le moment.</p>';
        }
    }    

    // Fonction pour générer un code unique
    public function generate_unique_code() {
        do {
            $unique_code = strtoupper(uniqid('CODE-'));
        } while ($this->code_exists($unique_code));

        return $unique_code;
    }

    // Fonction pour vérifier si un code existe déjà dans les métadonnées des commandes
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

    // Fonction pour insérer le code dans la table personnalisée
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

    // Action pour générer le code lors de l'achat
    public function generate_code_on_purchase($order_id) {
        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);
        $codes = [];

        foreach ($order->get_items() as $item_id => $item) {
            $product_id = $item->get_product_id();
            $quantity = $item->get_quantity();
            $additional_chances = 0;

            //vérifier si le produit a l'étiquette "10 chances de plus"
            if (has_term('10 chances de plus', 'product_tag', $product_id)) {
                $additional_chances = 10;
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

        $this->send_codes_to_customer($order, $codes);
    }

    // Fonction pour envoyer les codes au client par email
    public function send_codes_to_customer($order, $codes) {
        $to = $order->get_billing_email(); // Adresse email du client
        $subject = 'Votre (vos) code(s) unique(s) pour la commande #' . $order->get_order_number(); //Object de l'email
        $headers = array('Content-Type: text/html; charset=UTF-8'); // définir les en têtes de l'email pour l'envoie en HTML 

        //Url du site à partager 
        $site_url = get_site_url();

        //url de la page instagram
        $instagram_profile_url = 'https://www.instagram.com./votreprofil'; //LE PROFIL DE LA PAGE DOIS ETRE AJOUTER !! 

        //Url du logo
        $site_logo_url = 'https://www.test.alter-native-projects.com/wp-content/uploads/2024/08/images-2.png';

        // Nom du site
        $site_name = get_bloginfo('name'); // Nom du site Wordpress

        // Contenu de l'email avec styles personnalisés
        $message = '<div style="font-family: Arial, sans-serif; line-height: 1.5;">';

        // Boîte avec ombre externe
        $message .= '<div style="max-width: 600px; margin: 0 auto; padding: 20px; border-radius: 10px; box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1); background-color: #ffffff;">';

        // <h2> dans une boîte avec couleur violette, prenant toute la largeur
        $message .= '<div style="background-color: #6a0dad; color: #ffffff; padding: 15px; border-radius: 8px; text-align: center; width: 100%; height: 100%;">';
        $message .= '<h2 style="margin: 0;">Merci, ' . esc_html($order->get_billing_first_name()) . ', pour votre achat chez ' . esc_html($site_name) . '!</h2>';
        $message .= '</div>';

        
        $message .= '<p style="font-size: 16px;">Nous vous remercions pour votre commande. Voici votre (vos) code(s) unique(s) pour le(s) article(s) que vous avez acheté(s) :</p>';
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
    $message .= '<p style="font-size: 16px;">Partagez le à vos contact :</p>'; //voir pour le texte
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
    $message .= '<p style="font-size: 16px;">Cordialement,<br>L\'équipe de DEFINIR NOM DE LA BOUTIQUE</p>';
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

    // Filtre pour afficher le code dans les détails de la commande
    public function display_code_in_order($item_id, $item, $order) {
        for($i = 0; $i < 15; $i++){
            $code = wc_get_order_item_meta($item_id, '_unique_code_' . $i, t:rue);
            if ($code) {
                echo'<p><strong>Code Unique:</strong>' . esc_html($code) .'</p>';
            }
        }
    }
}

// Initialiser le plugin
new Unique_Code_Generator();