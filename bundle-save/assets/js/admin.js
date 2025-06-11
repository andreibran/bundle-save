jQuery(function($) {
    'use strict';

    // Função para reindexar os nomes dos inputs (importante para salvar corretamente)
    function reindexTiers() {
        $('#bs_tiers_list .bs-tier-row').each(function(index) {
            $(this).find('input, select').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    var newName = name.replace(/\[\d+\]/, '[' + index + ']');
                    $(this).attr('name', newName);
                }
            });
        });
    }

    // Adicionar novo nível
    $('#add_bs_tier').on('click', function(e) {
        e.preventDefault();
        var template = $('#bs-tier-template').html();
        var newIndex = $('#bs_tiers_list .bs-tier-row').length;
        var newRow = template.replace(/__INDEX__/g, newIndex);
        
        $('#bs_tiers_list').append('<tr class="bs-tier-row">' + newRow + '</tr>');
    });

    // Remover um nível
    $('#bs_tiers_list').on('click', '.bs-remove-tier', function(e) {
        e.preventDefault();
        if (confirm(bs_admin_params.i18n.remove_tier_confirm)) {
            $(this).closest('.bs-tier-row').remove();
            reindexTiers();
        }
    });

    // Tornar os níveis ordenáveis (arrastar e soltar)
    $('#bs_tiers_list').sortable({
        handle: '.sort-handle',
        placeholder: 'bs-tier-placeholder',
        forcePlaceholderSize: true,
        update: function() {
            reindexTiers();
        }
    });

    // Lógica para mostrar/ocultar campos de condição
    var $applyToSelect = $('#bs_apply_to');
    function toggleConditionalFields() {
        var val = $applyToSelect.val();
        $('#bs_products_setting').toggle(val === 'products');
        $('#bs_categories_setting').toggle(val === 'categories');
    }
    $applyToSelect.on('change', toggleConditionalFields).trigger('change');

    // Inicializar Select2
    function initSelect2() {
        if (!$.fn.select2) return;
        $('.wc-product-search').select2({
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 250,
                data: params => ({
                    term: params.term,
                    action: 'woocommerce_json_search_products_and_variations',
                    security: '<?php echo wp_create_nonce("search-products"); ?>'
                }),
                processResults: data => ({
                    results: $.map(data, (text, id) => ({ text: text, id: id }))
                })
            }
        });
        $('.wc-category-search').select2({
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 250,
                data: params => ({
                    term: params.term,
                    action: 'woocommerce_json_search_categories',
                    security: '<?php echo wp_create_nonce("search-categories"); ?>'
                }),
                processResults: data => ({
                    results: $.map(data, (text, id) => ({ text: text, id: id }))
                })
            }
        });
    }
    initSelect2();
});