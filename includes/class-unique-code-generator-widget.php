<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Empêche un accès direct
}

class Unique_Code_Generator_Widget {

    public function __construct() {
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
    }

    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'unique_code_dashboard_widget',
            'Unique Code Generator',
            array($this, 'display_dashboard_widget')
        );
    }

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
}

new Unique_Code_Generator_Widget();