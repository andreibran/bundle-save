/**
 * Bundle & Save – front-end logic
 * v1.6  (preserva NOTE configurado no back-end)
 */
(function ($) {

  /* util – 167000 → "167.000" */
  const fmt = n => n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');

  /* util – "R$ 129.000,00" → 129000 */
  const parsePrice = s => {
    let t = s.replace(/[^\d.,]/g, '').trim();
    if (!t) return 0;
    t = t.replace(/\./g, '').replace(',', '.');
    return parseFloat(t);
  };

  /* detecta preço base (promo > regular) */
  function getBasePrice() {
    const $box = $('.price').first();
    if (!$box.length) return { p: 0, c: '$' };

    const $promo = $box.find('ins .woocommerce-Price-amount').first();
    const $cands = $promo.length ? $promo : $box.find('.woocommerce-Price-amount');

    let cur = '$', vals = [];

    $cands.each(function () {
      const raw = $(this).text();
      const m   = raw.match(/^(\D+)/);
      if (m && m[1].trim()) cur = m[1].trim();
      const num = parsePrice(raw);
      if (num) vals.push(num);
    });

    return { p: Math.min(...vals), c: cur };
  }

  $(function () {

    const { p: basePrice, c: C } = getBasePrice();
    if (!basePrice) { console.warn('[Bundle] base price not found'); return; }

    /* pacotes (q = qty, d = discount) */
    const bundles = [
      { q: 1, d: 0.00 },
      { q: 2, d: 0.20 },
      { q: 3, d: 0.30 }
    ];

    const $cards = $('#bundle-save .bundle-card');

    /* atualiza apenas qty + preços; deixa títulos, labels e note intactos */
    $cards.each(function (i) {
      const cfg = bundles[i]; if (!cfg) return;

      const unit = Math.round(basePrice * (1 - cfg.d));
      const tot  = unit * cfg.q;

      $(this)
        .attr('data-qty', cfg.q)
        .find('input').val(cfg.q).end()
        /* NOTE fica como veio do PHP */
        .find('.total').text(`${C}${fmt(tot)}`).attr('data-price', tot);
    });

    /* ---------- interação ---------- */
    const $qty = $('form.cart input.qty');
    const $btn = $('form.cart button[type="submit"]');

    function select($c){
      $cards.removeClass('is-active');
      $c.addClass('is-active').find('input').prop('checked',true);

      const tot = parseInt($c.find('.total').data('price'),10);
      $qty.val( $c.data('qty') ).trigger('change');
      if(!isNaN(tot)) $btn.text(`Añadir al carrito - ${C}${fmt(tot)}`);
    }

    $cards.on('click', function(){ select($(this)); });
    select($cards.first());   // estado inicial

    /* cor primária via opções */
    if(window.BundleSaveOpt && BundleSaveOpt.primary){
      document.documentElement.style.setProperty('--bundle-primary', BundleSaveOpt.primary);
    }
  });

})(jQuery);