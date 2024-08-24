<?php
/*
  Plugin Name: Unique Code Generator
  Plugin URI: T SOON
  Description: Génère des codes uniques pour chaque article acheté et les envoie par email au client. Affiche une notification dans un widget WordPress sur les dernières commandes et l'état des emails avec un graphique de statistiques.
  Author: ALTER NATIVE PROJECTS SARL
  Version: 3.1.8
  Requires PHP: 5.2
  Tested up to: 6.6
  Author URI: https://www.alter-native-projects.com
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Empêche un accès direct
}

// Inclure les fichiers nécessaires
require_once plugin_dir_path(__FILE__) . 'includes/class-unique-code-generator.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-unique-code-generator-db.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-unique-code-generator-widget.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-unique-code-generator-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-unique-code-generator-email.php';

// Ajouter des liens personnalisés pour votre plugin
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'ucg_add_action_links');

function ucg_add_action_links($links) {
    $settings_link = '<a href="options-general.php?page=unique-code-generator">Réglages</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Initialiser le plugin
new Unique_Code_Generator();