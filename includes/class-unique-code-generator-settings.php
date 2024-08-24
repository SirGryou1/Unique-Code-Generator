<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Empêche un accès direct
}

class Unique_Code_Generator_Settings {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_settings_page() {
        add_options_page(
            'Unique Code Generator Settings',
            'Code Generator',
            'manage_options',
            'unique-code-generator',
            array($this, 'settings_page_content')
        );
    }

    public function register_settings() {
        register_setting('unique_code_generator_settings', 'code_limit'); //definir le nombre de jour à afficher 
        register_setting('unique_code_genrator_settings','additional_chances'); //définir le nombre de code generer 
    }

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
                        <th scope="row">Nombre de jour à afficher (10 par défault)</th>
                        <td>
                            <input type="number" name="code_limit" value="<?php echo esc_attr(get_option('code_limit', 10)); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Chances supplémentaires (10 par default)</th>
                        <td>
                            <input type="number" name="additional_chances" value="<?php echo esc_attr(get_option('additional_chances', 10)); ?>" />
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

new Unique_Code_Generator_Settings();