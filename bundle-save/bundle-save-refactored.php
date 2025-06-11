<?php
/**
 * Plugin Name:       Bundle & Save (Refactored)
 * Plugin URI:        https://example.com/
 * Description:       Cria ofertas dinâmicas por quantidade e as insere automaticamente nas páginas de produto do WooCommerce.
 * Version:           2.0.0
 * Author:            Seu Nome
 * Author URI:        https://example.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bundle-save
 * Domain Path:       /languages
 * WC requires at least: 4.0
 * WC tested up to:   8.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define Constantes do Plugin
define( 'BS_PLUGIN_VERSION', '2.0.0' );
define( 'BS_PLUGIN_FILE', __FILE__ );
define( 'BS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Carrega a classe principal do plugin.
 */
function bs_run_bundle_save_plugin() {
    require_once BS_PLUGIN_DIR . 'includes/class-bs-main.php';
    new BS_Main();
}

// Garante que o plugin seja carregado após todos os plugins, especialmente o WooCommerce.
add_action( 'plugins_loaded', 'bs_run_bundle_save_plugin' );

/**
 * Registra o hook de ativação para definir as opções padrão.
 */
require_once BS_PLUGIN_DIR . 'includes/class-bs-activator.php';
register_activation_hook( BS_PLUGIN_FILE, ['BS_Activator', 'activate'] );