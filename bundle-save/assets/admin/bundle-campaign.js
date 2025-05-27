// Bundle Campaign Admin JS
jQuery(function($) {
    'use strict';

    // Initialize color picker
    $('.bundle-color-picker').wpColorPicker({
        change: function(event, ui) {
            updatePreview();
        }
    });

    // Product search
    $('.bundle-product-search').select2({
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    term: params.term,
                    action: 'bundle_search_products',
                    nonce: bundleCampaign.nonce
                };
            },
            processResults: function(data) {
                return {
                    results: data
                };
            },
            cache: true
        },
        minimumInputLength: 2,
        placeholder: bundleCampaign.i18n.searchPlaceholder,
        allowClear: true,
        multiple: true
    });

    // Target type switcher
    $('input[name="bundle_target_type"]').on('change', function() {
        const type = $(this).val();
        $('.target-selector > div').hide();
        $(`.${type}-selector`).show();
        $('.target-selector').attr('data-type', type);
        updatePreview();
    });

    // Offers table
    $('.add-offer').on('click', function() {
        const $table = $('.bundle-offers-table tbody');
        const index = $table.children().length;
        
        if (index >= 3) return;

        const $row = $(`
            <tr class="bundle-offer-row">
                <td>
                    <input type="number" 
                           name="bundle_offers[${index}][qty]" 
                           value="1"
                           min="1" 
                           max="99"
                           class="small-text"
                           required>
                </td>
                <td>
                    <select name="bundle_offers[${index}][discount_type]">
                        <option value="percentage">${bundleCampaign.i18n.percentageDiscount}</option>
                        <option value="fixed">${bundleCampaign.i18n.fixedDiscount}</option>
                    </select>
                </td>
                <td>
                    <input type="number" 
                           name="bundle_offers[${index}][discount_value]" 
                           value="0"
                           step="0.01"
                           min="0"
                           class="small-text"
                           required>
                </td>
                <td>
                    <input type="text" 
                           name="bundle_offers[${index}][badge]" 
                           class="regular-text">
                </td>
                <td>
                    <input type="text" 
                           name="bundle_offers[${index}][note]" 
                           class="regular-text">
                </td>
                <td>
                    <button type="button" class="button remove-offer">
                        ${bundleCampaign.i18n.removeOffer}
                    </button>
                </td>
            </tr>
        `);

        $table.append($row);
        updatePreview();
    });

    $(document).on('click', '.remove-offer', function() {
        $(this).closest('tr').remove();
        updatePreview();
    });

    // Live preview updates
    let previewTimeout;
    function updatePreview() {
        clearTimeout(previewTimeout);
        previewTimeout = setTimeout(function() {
            const $frame = $('#bundle-preview-frame');
            if ($frame.length) {
                $frame.attr('src', $frame.attr('src'));
            }
        }, 500);
    }

    // Update preview on any form change
    $('.bundle-campaign-offers input, .bundle-campaign-offers select, .bundle-campaign-style input, .bundle-campaign-style select').on('change input', function() {
        updatePreview();
    });

    // Keyboard navigation for offers table
    $('.bundle-offers-table').on('keydown', 'input, select', function(e) {
        if (e.key === 'Tab') {
            const $inputs = $('.bundle-offers-table').find('input, select');
            const index = $inputs.index(this);
            
            if (e.shiftKey) {
                if (index === 0) {
                    e.preventDefault();
                    $inputs.last().focus();
                }
            } else {
                if (index === $inputs.length - 1) {
                    e.preventDefault();
                    $inputs.first().focus();
                }
            }
        }
    });
});