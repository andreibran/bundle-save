<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registra o Custom Post Type para as Campanhas de Oferta.
 */
class BS_CPT {

    public function __construct() {
        add_action( 'init', [ $this, 'register_post_type' ] );
    }

    public function register_post_type() {
        $labels = [
            'name'                  => _x( 'Campanhas', 'Post Type General Name', 'bundle-save' ),
            'singular_name'         => _x( 'Campanha', 'Post Type Singular Name', 'bundle-save' ),
            'menu_name'             => __( 'Campanhas Bundle', 'bundle-save' ),
            'name_admin_bar'        => __( 'Campanha', 'bundle-save' ),
            'add_new_item'          => __( 'Adicionar Nova Campanha', 'bundle-save' ),
            'add_new'               => __( 'Adicionar Nova', 'bundle-save' ),
            'new_item'              => __( 'Nova Campanha', 'bundle-save' ),
            'edit_item'             => __( 'Editar Campanha', 'bundle-save' ),
            'update_item'           => __( 'Atualizar Campanha', 'bundle-save' ),
            'view_item'             => __( 'Ver Campanha', 'bundle-save' ),
            'all_items'             => __( 'Todas as Campanhas', 'bundle-save' ),
            'search_items'          => __( 'Procurar Campanha', 'bundle-save' ),
            'not_found'             => __( 'Nenhuma campanha encontrada', 'bundle-save' ),
            'not_found_in_trash'    => __( 'Nenhuma campanha encontrada na lixeira', 'bundle-save' ),
        ];

        $args = [
            'label'                 => __( 'Campanha', 'bundle-save' ),
            'description'           => __( 'Campanhas para ofertas Bundle & Save', 'bundle-save' ),
            'labels'                => $labels,
            'supports'              => [ 'title' ],
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 57,
            'menu_icon'             => 'dashicons-tag',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
        ];

        register_post_type( 'bs_campaign', $args );
    }
}