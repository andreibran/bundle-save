<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Deleta as opções globais
delete_option('bs_global_settings');

// Encontra e deleta todos os CPTs de campanha
$campaign_posts_query = new WP_Query([
    'post_type'      => 'bs_campaign',
    'posts_per_page' => -1,
    'post_status'    => 'any',
    'fields'         => 'ids'
]);

if ( $campaign_posts_query->have_posts() ) {
    foreach ($campaign_posts_query->posts as $post_id) {
        wp_delete_post( $post_id, true ); // true para forçar a exclusão permanente
    }
}