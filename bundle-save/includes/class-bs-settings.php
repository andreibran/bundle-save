<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BS_Settings {

    private $option_key = 'bs_global_settings';
    
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=bs_campaign', // Adiciona sob o menu do CPT
            __( 'Configurações Globais', 'bundle-save' ),
            __( 'Configurações', 'bundle-save' ),
            'manage_options',
            'bundle-save-global-settings',
            [ $this, 'render_page' ]
        );
    }
    
    public function register_settings() {
        register_setting( $this->option_key . '_group', $this->option_key, [ $this, 'sanitize' ] );
    }

    public function render_page() {
        $options = get_option($this->option_key, ['primary_color' => '#e92d3b']);
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Bundle & Save - Configurações Globais', 'bundle-save' ); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields( $this->option_key . '_group' ); ?>
                 <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Cor Primária', 'bundle-save' ); ?></th>
                        <td>
                            <input type="text" name="<?php echo $this->option_key; ?>[primary_color]" value="<?php echo esc_attr( $options['primary_color'] ); ?>" class="color-picker" />
                            <p class="description"><?php esc_html_e( 'Cor para elementos ativos, preços e badges.', 'bundle-save' ); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>jQuery(document).ready(function($){$('.color-picker').wpColorPicker();});</script>
        <?php
    }

    public function sanitize($input) {
        $new_input = [];
        $new_input['primary_color'] = isset($input['primary_color']) ? sanitize_hex_color($input['primary_color']) : '#e92d3b';
        return $new_input;
    }
}