<?php
/*
  Plugin Name: Unique Code Generator
  Plugin URI: T SOON
  Description: Génère des codes uniques pour chaque article acheté et les envoie par email au client. Affiche une notification dans un widget WordPress sur les dernières commandes et l'état des emails avec un graphique de statistiques.
  Author: ALTER NATIVE PROJECTS SARL
<<<<<<< HEAD
  Version: 3.1.0
  Requires PHP: 8.1
  Tested up to: 6.6.1
=======
  Version: 3.0.1
  Requires PHP: 5.2
  Tested up to: 6.6
>>>>>>> 6e9c82273f0764857750497bf052ff18340c3226
  Author URI: https://www.alter-native-projects.com
*/
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Empêche un accès direct
}
<<<<<<< HEAD
// Inclure les fichiers nécessaires
require_once plugin_dir_path(__FILE__) . 'includes/class-unique-code-generator.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-unique-code-generator-db.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-unique-code-generator-widget.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-unique-code-generator-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-unique-code-generator-email.php';

// Initialiser le plugin
new Unique_Code_Generator();
=======

class Unique_Code_Generator {

    private $db_version = '1.3';

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'create_table'));
        add_action('init', array($this, 'check_db_version'));  // Vérification de la version de la DB
        add_action('woocommerce_thankyou', array($this, 'generate_code_on_purchase'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('woocommerce_order_item_meta_end', array($this, 'display_code_in_order'), 10, 3);
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

        // Mettre à jour la version de la base de données 
        update_option('unique_code_generator_db_version', $this->db_version);
    }

    // Ajouter le widget de tableau de bord
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'unique_code_dashboard_widget',
            'Unique Code Generator',
            array($this, 'display_dashboard_widget')
        );
    }

    // Fonction pour afficher le contenu du widget de suivi des emails et des statistiques
    public function display_dashboard_widget() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'generated_codes';

        // Récupérer la limite des paramètres, avec 10 comme valeur par défaut
        $limit = get_option('code_limit', 10);

        // Récupérer le nombre total de codes générés
        $total_codes = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

        // Récupérer les statistiques par jour pour les 30 derniers jours
        $date_format = '%Y-%m-%d';
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT DATE_FORMAT(FROM_UNIXTIME(UNIX_TIMESTAMP(post_date)), %s) as day, COUNT(*) as count
            FROM $table_name
            JOIN {$wpdb->prefix}posts AS o ON $table_name.order_id = o.ID
            WHERE o.post_date >= NOW() - INTERVAL 30 DAY
            GROUP BY day
            ORDER BY day
        ", $date_format));

        // Préparer les données pour le graphique
        $dates = array();
        $counts = array();
        foreach ($results as $row) {
            $dates[] = $row->day;
            $counts[] = intval($row->count);
        }

        // Message de bienvenue avec la date d'aujourd'hui
        $current_user = wp_get_current_user();
        $username = $current_user->user_firstname ? $current_user->user_firstname : $current_user->user_login;
        $current_date = date_i18n('d F Y'); // Date au format jour mois année

        // Afficher le nom du plugin et les statistiques
        echo '<h2>Unique Code Generator</h2>';
        echo '<p>Bonjour ' . esc_html($username) . ', voici les informations d\'aujourd\'hui ' . esc_html($current_date) . '.</p>';
        echo '<p><strong>Nombre total de codes générés:</strong> ' . esc_html($total_codes) . '</p>';

        // Graphique des codes générés par jour
        echo '<div style="margin-bottom: 20px;">';
        echo '<canvas id="codesChart" width="400" height="200"></canvas>';
        echo '</div>';

        // Afficher les résultats dans un tableau
        if ($results) {
            echo '<table style="width: 100%; border-collapse: collapse;">';
            echo '<thead>';
            echo '<tr>';
            echo '<th style="border: 1px solid #ddd; padding: 8px;">Date</th>';
            echo '<th style="border: 1px solid #ddd; padding: 8px;">Nombre de Codes Générés</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
        
            foreach ($results as $row) {
                echo '<tr>';
                echo '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($row->day) . '</td>';
                echo '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html(intval($row->count)) . '</td>';
                echo '</tr>';
            }
        
            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<p>Aucune donnée disponible pour le moment.</p>';
        }

        // Inclure Chart.js depuis un CDN
        echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                var ctx = document.getElementById("codesChart").getContext("2d");
                var codesChart = new Chart(ctx, {
                    type: "line",
                    data: {
                        labels: ' . json_encode($dates) . ',
                        datasets: [{
                            label: "Nombre de Codes par Jour",
                            data: ' . json_encode($counts) . ',
                            borderColor: "rgba(75, 192, 192, 1)",
                            backgroundColor: "rgba(75, 192, 192, 0.2)",
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            x: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: "Date"
                                }
                            },
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: "Nombre de Codes"
                                }
                            }
                        }
                    }
                });
            });
        </script>';
    }

    // Ajouter la page de réglages
    public function add_settings_page() {
        add_options_page(
            'Unique Code Generator Settings',
            'Code Generator',
            'manage_options',
            'unique-code-generator',
            array($this, 'settings_page_content')
        );
    }

    // Enregistrer les réglages
    public function register_settings() {
        register_setting('unique_code_generator_settings', 'code_limit');
    }

    // Contenu de la page de réglages
    public function settings_page_content() {
        ?>
        <div class="wrap">
            <h1>Unique Code Generator Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('unique_code_generator_settings');
                do_settings_sections('unique_code_generator_settings');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Nombre de commandes à afficher</th>
                        <td>
                            <input type="number" name="code_limit" value="<?php echo esc_attr(get_option('code_limit', 10)); ?>" />
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
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
        $items = $order->get_items();

        foreach ($items as $item) {
            $product_id = $item->get_product_id();
            $unique_code = $this->generate_unique_code();
            $this->insert_code_in_custom_table($order_id, $product_id, $unique_code);
        }
    }

    // Fonction pour afficher le code unique dans les détails de la commande
    public function display_code_in_order($item_id, $item, $order) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'generated_codes';
        $code = $wpdb->get_var($wpdb->prepare("
            SELECT code
            FROM $table_name
            WHERE order_id = %d AND product_id = %d
        ", $order->get_id(), $item->get_product_id()));

        if ($code) {
            echo '<p><strong>Code Unique:</strong> ' . esc_html($code) . '</p>';
        }
    }
}

new Unique_Code_Generator();
>>>>>>> 6e9c82273f0764857750497bf052ff18340c3226
