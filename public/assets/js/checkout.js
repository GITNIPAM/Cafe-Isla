(() => {
  const form = document.querySelector('[data-checkout-quote]');
  if (!form) return;

  const savedAddress = form.elements.saved_address_id;
  const zone = form.elements.delivery_zone;
  const city = form.elements.shipping_city;
  const province = form.elements.shipping_province;
  const newAddress = form.querySelector('[data-new-address]');
  const status = form.querySelector('[data-quote-status]');
  const subtotal = form.querySelector('[data-quote-subtotal]');
  const fee = form.querySelector('[data-quote-fee]');
  const total = form.querySelector('[data-quote-total]');
  const discountRow = form.querySelector('[data-quote-discount-row]');
  const discount = form.querySelector('[data-quote-discount]');
  const lines = Array.from(form.querySelectorAll('[data-quote-item]'));
  const placeOrder = form.querySelector('[data-place-order]');
  const quotedTotal = form.querySelector('[data-quoted-total]');
  const currency = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
  let timer;
  let pendingRequest;

  function updateAddressFields() {
    const useSavedAddress = Boolean(savedAddress?.value);
    newAddress.hidden = useSavedAddress;
    newAddress.disabled = useSavedAddress;
  }

  function showUnconfirmed(message) {
    fee.textContent = '—';
    total.textContent = '—';
    status.textContent = message;
    quotedTotal.value = '';
    placeOrder.disabled = true;
  }

  function scheduleQuote() {
    clearTimeout(timer);
    pendingRequest?.abort();
    showUnconfirmed('Checking delivery…');
    timer = setTimeout(refreshQuote, 200);
  }

  async function refreshQuote() {
    const controller = new AbortController();
    pendingRequest = controller;

    try {
      const response = await fetch(form.dataset.checkoutQuote, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': form.elements._token.value,
        },
        body: JSON.stringify({
          saved_address_id: savedAddress?.value || null,
          shipping_city: newAddress.disabled ? null : city.value,
          shipping_province: newAddress.disabled ? null : province.value,
          delivery_zone: zone.value,
        }),
        signal: controller.signal,
      });
      if (!response.ok) throw new Error('Quote unavailable');
      const quote = await response.json();
      if (controller.signal.aborted) return;

      if (Object.keys(quote.items).length !== lines.length
        || lines.some((line) => !Object.hasOwn(quote.items, line.dataset.quoteItem))
        || (quote.coupon_code || '') !== form.dataset.checkoutCoupon) {
        showUnconfirmed('Your bag changed. Reload checkout to review the latest items and total.');
        return;
      }

      subtotal.textContent = currency.format(quote.subtotal);
      if (discountRow) {
        discountRow.hidden = quote.discount_amount <= 0;
        discount.textContent = `−${currency.format(quote.discount_amount)}`;
      }
      for (const line of lines) {
        const item = quote.items[line.dataset.quoteItem];
        const quantity = form.querySelector(`[data-quote-quantity="${line.dataset.quoteItem}"]`);
        quantity.textContent = item.quantity;
        line.textContent = currency.format(item.line_total);
      }

      if (quote.delivery_error) {
        showUnconfirmed(quote.delivery_error);
        return;
      }

      fee.textContent = quote.delivery_fee === 0 ? 'FREE' : currency.format(quote.delivery_fee);
      total.textContent = currency.format(quote.grand_total);
      status.textContent = `Delivery available. Amount due ${total.textContent}.`;
      quotedTotal.value = quote.grand_total;
      placeOrder.disabled = false;
    } catch (error) {
      if (!controller.signal.aborted) {
        showUnconfirmed('We couldn’t confirm your total. Try changing the delivery method or reloading checkout.');
      }
    }
  }

  savedAddress?.addEventListener('change', () => {
    updateAddressFields();
    const preferredZone = savedAddress.selectedOptions[0]?.dataset.deliveryZone;
    if (preferredZone && Array.from(zone.options).some((option) => option.value === preferredZone && !option.disabled)) {
      zone.value = preferredZone;
    }
    scheduleQuote();
  });
  zone.addEventListener('change', scheduleQuote);
  city.addEventListener('input', scheduleQuote);
  province.addEventListener('input', scheduleQuote);
  form.addEventListener('submit', (event) => {
    if (placeOrder.disabled) event.preventDefault();
  });

  updateAddressFields();
  scheduleQuote();
})();
