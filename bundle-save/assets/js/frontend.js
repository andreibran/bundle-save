jQuery(function ($) {
    'use strict';

    const $offerBox = $('#bs-offer-box');
    if (!$offerBox.length) {
        return;
    }

    const $cards = $offerBox.find('.bs-offer-card');
    const $qtyInput = $('form.cart .quantity input.qty');
    const $hiddenInput = $offerBox.find('input[name="bs_selected_tier"]');

    // Flag para evitar o loop infinito
    let isBeingUpdated = false;

    // Função para atualizar a UI com base na OFERTA selecionada
    function updateStateFromCard($card) {
        if (isBeingUpdated || !$card || !$card.length) return;
        isBeingUpdated = true;

        const qty = $card.data('qty');
        const tierIndex = $card.data('tier-index');

        // Atualiza a UI
        $cards.removeClass('is-active');
        $card.addClass('is-active');
        $card.find('input[type="radio"]').prop('checked', true);

        // Atualiza os dados (campos do formulário)
        $hiddenInput.val(tierIndex);
        $qtyInput.val(qty); // Apenas define o valor, sem disparar evento 'change'

        isBeingUpdated = false;
    }

    // Função para atualizar a UI com base na QUANTIDADE digitada
    function updateStateFromQty() {
        if (isBeingUpdated) return;
        isBeingUpdated = true;
        
        const currentQty = parseInt($qtyInput.val(), 10) || 0;
        let bestMatch = null;

        // Encontra a melhor oferta para a quantidade atual
        $cards.each(function() {
            const cardQty = $(this).data('qty');
            if (currentQty >= cardQty) {
                if (!bestMatch || cardQty > $(bestMatch).data('qty')) {
                    bestMatch = this;
                }
            }
        });

        const $bestMatchCard = $(bestMatch);

        if (bestMatch) {
            $cards.removeClass('is-active');
            $bestMatchCard.addClass('is-active');
            $bestMatchCard.find('input[type="radio"]').prop('checked', true);
            $hiddenInput.val($bestMatchCard.data('tier-index'));
        } else if ($cards.length) {
            // Se a quantidade for menor que a menor oferta (ex: 0), desativa todos
            $cards.removeClass('is-active');
            $cards.find('input[type="radio"]').prop('checked', false);
        }
        
        isBeingUpdated = false;
    }

    // --- Eventos ---

    // 1. Clique em um card de oferta
    $cards.on('click', function () {
        updateStateFromCard($(this));
    });

    // 2. Alteração no campo de quantidade
    $qtyInput.on('change input', function () {
        updateStateFromQty();
    });

    // --- Estado Inicial ---
    // Ao carregar a página, seleciona a oferta que corresponde à quantidade inicial
    if ($cards.length > 0) {
        const initialQty = parseInt($qtyInput.val(), 10);
        if (initialQty > 0) {
            updateStateFromQty();
        } else {
            // Se a quantidade inicial for 0 ou inválida, seleciona a primeira oferta
             updateStateFromCard($cards.first());
        }
    }
});