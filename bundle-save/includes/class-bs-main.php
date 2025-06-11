<?php
if ( ! defined( 'ABSPATH' ) ) exit;

final class BS_Main {

    public function __construct() {
        if ( ! $this->is_woocommerce_active() ) {
            add_action( 'admin_notices', [ $this, 'notice_woocommerce_inactive' ] );
            return;
        }

        $this->load_dependencies();
        $this->init_classes();
    }

    private function is_woocommerce_active() {
        return class_exists( 'WooCommerce' );
    }

    public function notice_woocommerce_inactive() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e( 'O plugin "Bundle & Save" requer que o WooCommerce esteja instalado e ativo.', 'bundle-save' ); ?></p>
        </div>
        <?php
    }

    private function load_dependencies() {
        require_once BS_PLUGIN_DIR . 'includes/class-bs-cpt.php';
        require_once BS_PLUGIN_DIR . 'includes/class-bs-admin.php';
        require_once BS_PLUGIN_DIR . 'includes/class-bs-settings.php';
        require_once BS_PLUGIN_DIR . 'includes/class-bs-frontend.php';
        require_once BS_PLUGIN_DIR . 'includes/class-bs-cart.php';
    }

    private function init_classes() {
        new BS_CPT();
        new BS_Admin();
        new BS_Settings();
        new BS_Frontend();
        new BS_Cart();
    }
}