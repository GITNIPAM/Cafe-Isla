/* ==========================================================================
   Café Isla — Storefront & Operational Client Interactions
   ========================================================================== */

(function () {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  // 1. Dismissible Announcement Strip
  const announceClose = document.getElementById('closeAnnouncement');
  const announceBar = document.getElementById('announcementStrip');
  if (announceClose && announceBar) {
    announceClose.addEventListener('click', () => {
      announceBar.style.display = 'none';
    });
  }

  // 2. Responsive Mobile Navigation Toggle
  const mobileToggle = document.getElementById('mobileMenuToggle');
  const primaryNav = document.getElementById('primaryNav');
  if (mobileToggle && primaryNav) {
    mobileToggle.addEventListener('click', () => {
      const open = primaryNav.classList.toggle('is-open');
      mobileToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  // 3. Interactive 4-Island Bar Switcher (Home Page)
  const islandTabs = document.querySelectorAll('.island-tab');
  const islandPanels = document.querySelectorAll('.island-panel');
  islandTabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      const target = tab.dataset.islandTarget;
      islandTabs.forEach((t) => {
        t.classList.toggle('is-active', t === tab);
        t.setAttribute('aria-selected', t === tab ? 'true' : 'false');
      });
      islandPanels.forEach((p) => {
        p.classList.toggle('is-active', p.dataset.islandPanel === target);
      });
    });
  });

  // 4. Merch Horizontal Carousel Scroll Progress
  const merchTrack = document.getElementById('merchCarouselTrack');
  const merchProgress = document.getElementById('merchCarouselProgress');
  if (merchTrack && merchProgress) {
    const syncProgress = () => {
      const maxScroll = merchTrack.scrollWidth - merchTrack.clientWidth;
      if (maxScroll <= 0) {
        merchProgress.style.width = '100%';
        return;
      }
      const ratio = 0.28 + 0.72 * (merchTrack.scrollLeft / maxScroll);
      merchProgress.style.width = `${Math.min(100, Math.round(ratio * 100))}%`;
    };
    merchTrack.addEventListener('scroll', syncProgress, { passive: true });
    syncProgress();
  }

  // 5. Slide-Over Drawers (Shopping Bag + Live Email Inbox)
  const bagDrawer = document.getElementById('bagDrawer');
  const inboxDrawer = document.getElementById('inboxDrawer');
  let activeDrawer;
  let drawerOpener;
  let inertBackground = [];

  function openDrawer(drawer, opener) {
    if (!drawer || activeDrawer === drawer) return;
    closeDrawer();
    activeDrawer = drawer;
    drawerOpener = opener;
    drawer.classList.add('is-open');
    drawer.setAttribute('aria-hidden', 'false');
    drawer.querySelector('[data-close-drawer]').focus();
    inertBackground = Array.from(document.body.children)
      .filter((element) => element !== drawer && !element.inert);
    inertBackground.forEach((element) => { element.inert = true; });
  }

  function closeDrawer() {
    if (!activeDrawer) return;
    inertBackground.forEach((element) => { element.inert = false; });
    drawerOpener?.focus();
    activeDrawer.classList.remove('is-open');
    activeDrawer.setAttribute('aria-hidden', 'true');
    activeDrawer = null;
  }

  document.querySelectorAll('[data-open-bag]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      refreshBagDrawer();
      openDrawer(bagDrawer, btn);
    });
  });

  document.querySelectorAll('[data-open-inbox]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      refreshInboxDrawer();
      openDrawer(inboxDrawer, btn);
    });
  });

  document.querySelectorAll('[data-close-drawer]').forEach((btn) => {
    btn.addEventListener('click', closeDrawer);
  });

  [bagDrawer, inboxDrawer].forEach((backdrop) => {
    if (!backdrop) return;
    backdrop.addEventListener('click', (e) => {
      if (e.target === backdrop) closeDrawer();
    });
  });

  document.addEventListener('keydown', (e) => {
    if (!activeDrawer) return;
    if (e.key === 'Escape') {
      e.preventDefault();
      closeDrawer();
    }
    if (e.key !== 'Tab') return;

    const focusable = Array.from(activeDrawer.querySelectorAll('a[href], button:not(:disabled), input:not(:disabled), select:not(:disabled), textarea:not(:disabled)'))
      .filter((element) => element.getClientRects().length > 0);
    const first = focusable[0];
    const last = focusable.at(-1);
    if (!focusable.includes(document.activeElement)
      || (e.shiftKey ? document.activeElement === first : document.activeElement === last)) {
      e.preventDefault();
      (e.shiftKey ? last : first)?.focus();
    }
  });

  // 6. Persistent Bag API + DOM Sync
  function formatPeso(val) {
    return '₱' + Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function escapeHtml(str) {
    return String(str || '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;');
  }

  function renderBagSummary(summary) {
    document.querySelectorAll('[data-bag-count]').forEach((el) => {
      el.textContent = summary.count || 0;
    });

    const bodyEl = document.getElementById('bagDrawerBody');
    const footerEl = document.getElementById('bagDrawerFooter');
    if (!bodyEl || !footerEl) return;

    const focusedControl = bagDrawer.contains(document.activeElement) ? document.activeElement : null;
    const focusedItemId = focusedControl?.dataset.bagQty ?? focusedControl?.dataset.bagRemove;
    const restoreFocus = () => {
      if (!focusedControl || focusedControl.isConnected) return;

      const matchingItemControl = Array.from(bodyEl.querySelectorAll('[data-bag-qty]')).find((control) =>
        control.dataset.bagQty === focusedItemId && control.getAttribute('aria-label') === focusedControl.getAttribute('aria-label'));
      const matchingLink = Array.from(footerEl.querySelectorAll('a')).find((link) =>
        link.getAttribute('href') === focusedControl.getAttribute('href'));
      (matchingItemControl ?? matchingLink ?? bagDrawer.querySelector('[data-close-drawer]')).focus();
    };

    if (!summary.items || summary.items.length === 0) {
      bodyEl.innerHTML = '<div style="padding:2rem 0;text-align:center;"><p style="font-family:var(--font-serif);font-style:italic;font-size:1.25rem;margin:0 0 0.75rem 0;">Your bag is currently resting.</p><p style="font-family:var(--font-mono);font-size:0.82rem;color:var(--taupe-muted);">Explore our four Island Series pours or coastal Isla Merch to start an order.</p></div>';
      footerEl.innerHTML = '<a href="/menu" class="tag-btn tag-btn--canopy" style="width:100%;">Explore Menu &amp; Merch</a>';
      restoreFocus();
      return;
    }

    bodyEl.innerHTML = summary.items.map((item) => `
      <div class="bag-item">
        <img src="${escapeHtml(item.image_url)}" alt="${escapeHtml(item.product_name)}">
        <div>
          <div style="display:flex;justify-content:space-between;gap:0.5rem;align-items:baseline;">
            <strong style="font-family:var(--font-sans);font-size:0.8rem;text-transform:uppercase;letter-spacing:0.04em;">${escapeHtml(item.product_name)}</strong>
            <span class="tabular" style="font-family:var(--font-mono);font-size:0.82rem;font-weight:700;">${formatPeso(item.line_total)}</span>
          </div>
          <div style="font-family:var(--font-mono);font-size:0.75rem;color:var(--taupe-muted);margin:0.15rem 0 0.45rem 0;">
            ${escapeHtml(item.island_name)} &middot; ${escapeHtml(item.variant_label)}
            ${item.custom_notes ? `<br>Note: ${escapeHtml(item.custom_notes)}` : ''}
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <div class="qty-stepper">
              <button type="button" data-bag-qty="${item.id}" data-next="${item.quantity - 1}" aria-label="Decrease quantity">-</button>
              <span class="tabular">${item.quantity}</span>
              <button type="button" data-bag-qty="${item.id}" data-next="${item.quantity + 1}" aria-label="Increase quantity">+</button>
            </div>
            <button type="button" data-bag-remove="${item.id}" style="background:none;border:0;font-family:var(--font-mono);font-size:0.74rem;text-decoration:underline;cursor:pointer;color:var(--taupe-muted);">Remove</button>
          </div>
        </div>
      </div>
    `).join('');

    const freeMsg = summary.free_shipping_gap > 0
      ? `Add ${formatPeso(summary.free_shipping_gap)} more for free island delivery.`
      : 'Complimentary Café Isla delivery unlocked.';

    footerEl.innerHTML = `
      <div style="font-family:var(--font-mono);font-size:0.76rem;color:var(--canopy-deep);margin-bottom:0.75rem;">${freeMsg}</div>
      <div style="display:flex;justify-content:space-between;font-family:var(--font-mono);font-size:0.84rem;margin-bottom:0.25rem;">
        <span>Subtotal</span>
        <span class="tabular">${formatPeso(summary.subtotal)}</span>
      </div>
      ${summary.discount_amount > 0 ? `<div style="display:flex;justify-content:space-between;font-family:var(--font-mono);font-size:0.84rem;color:var(--clay-coral);margin-bottom:0.25rem;"><span>Promo (${escapeHtml(summary.coupon_code)})</span><span class="tabular">-${formatPeso(summary.discount_amount)}</span></div>` : ''}
      <div style="display:flex;justify-content:space-between;font-family:var(--font-mono);font-size:0.84rem;margin-bottom:0.5rem;">
        <span>Estimated Delivery</span>
        <span class="tabular">${summary.delivery_fee === 0 ? 'FREE' : formatPeso(summary.delivery_fee)}</span>
      </div>
      <div style="display:flex;justify-content:space-between;font-family:var(--font-mono);font-size:0.96rem;font-weight:700;padding-top:0.5rem;border-top:1px solid var(--espresso-ink);margin-bottom:0.85rem;">
        <span>Total</span>
        <span class="tabular">${formatPeso(summary.grand_total)}</span>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1.25fr;gap:0.5rem;">
        <a href="/cart" class="tag-btn tag-btn--outline tag-btn--sm">Full Bag</a>
        <a href="/checkout" class="tag-btn tag-btn--canopy tag-btn--sm">Checkout</a>
      </div>
    `;

    bindBagItemControls();
    restoreFocus();
  }

  async function refreshBagDrawer() {
    try {
      const res = await fetch('/cart', { headers: { 'Accept': 'application/json' } });
      const data = await res.json();
      if (data.ok && data.summary) {
        renderBagSummary(data.summary);
      }
    } catch (err) {
      console.error(err);
    }
  }

  function bindBagItemControls() {
    document.querySelectorAll('[data-bag-qty]').forEach((btn) => {
      btn.addEventListener('click', async () => {
        const id = btn.dataset.bagQty;
        const qty = Number(btn.dataset.next);
        const res = await fetch(`/cart/items/${id}`, {
          method: 'PATCH',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
          },
          body: JSON.stringify({ quantity: qty }),
        });
        const data = await res.json();
        if (data.ok && data.summary) {
          renderBagSummary(data.summary);
        } else if (data.message) {
          alert(data.message);
        }
      });
    });

    document.querySelectorAll('[data-bag-remove]').forEach((btn) => {
      btn.addEventListener('click', async () => {
        const id = btn.dataset.bagRemove;
        const res = await fetch(`/cart/items/${id}`, {
          method: 'DELETE',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
          },
        });
        const data = await res.json();
        if (data.ok && data.summary) {
          renderBagSummary(data.summary);
        }
      });
    });
  }

  document.querySelectorAll('[data-quick-pour]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const variantId = btn.dataset.quickPour;
      const originalText = btn.innerHTML;
      btn.disabled = true;
      try {
        const res = await fetch('/cart/items', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
          },
          body: JSON.stringify({ variant_id: Number(variantId), quantity: 1 }),
        });
        const data = await res.json();
        if (data.ok && data.summary) {
          renderBagSummary(data.summary);
          openDrawer(bagDrawer, btn);
        } else {
          alert(data.message || 'Could not add item to bag.');
        }
      } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
      }
    });
  });

  // 7. Live Email Inbox Drawer
  async function refreshInboxDrawer() {
    const bodyEl = document.getElementById('inboxDrawerBody');
    if (!bodyEl) return;
    try {
      const res = await fetch(inboxDrawer.dataset.inboxUrl, { headers: { 'Accept': 'application/json' } });
      const data = await res.json();
      if (!data.emails || data.emails.length === 0) {
        bodyEl.innerHTML = '<p style="font-family:var(--font-mono);font-size:0.84rem;color:var(--taupe-muted);">No transactional notices recorded yet. Complete an order or shipping update to inspect rendered confirmation emails here.</p>';
        return;
      }
      bodyEl.innerHTML = data.emails.map((mail) => `
        <article class="inbox-card">
          <div class="inbox-card__meta">
            <span>${escapeHtml(mail.event_type.replace('_', ' ').toUpperCase())}</span>
            <span>${escapeHtml(mail.sent_at_human)}</span>
          </div>
          <h4 class="inbox-card__subject">${escapeHtml(mail.subject)}</h4>
          <div style="font-family:var(--font-mono);font-size:0.74rem;color:var(--taupe-muted);margin-bottom:0.5rem;">
            To: ${escapeHtml(mail.recipient_email)} &middot; Status: ${escapeHtml(mail.smtp_status)}
          </div>
          <div class="inbox-card__preview">${mail.body_html}</div>
        </article>
      `).join('');
    } catch (err) {
      console.error(err);
    }
  }

  // 8. Demo Role Autofill Buttons on /auth
  document.querySelectorAll('[data-demo-email]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const emailInput = document.getElementById('loginEmailInput');
      const passInput = document.getElementById('loginPasswordInput');
      if (emailInput && passInput) {
        emailInput.value = btn.dataset.demoEmail;
        passInput.value = btn.dataset.demoPass || 'Isla2026!';
        emailInput.focus();
      }
    });
  });

  // 9. Presentation Diagrams Tab Switcher (/diagrams)
  const diagTabs = document.querySelectorAll('[data-diagram-tab]');
  const diagPanels = document.querySelectorAll('[data-diagram-panel]');
  diagTabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      const target = tab.dataset.diagramTab;
      diagTabs.forEach((t) => t.classList.toggle('is-active', t === tab));
      diagPanels.forEach((p) => {
        p.style.display = p.dataset.diagramPanel === target ? 'block' : 'none';
      });
    });
  });

  // 10. Password Show / Hide Toggle
  document.querySelectorAll('[data-password-toggle]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const wrap = btn.closest('.password-field-wrap');
      const input = wrap?.querySelector('input');
      if (!input) return;

      const showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';
      btn.setAttribute('aria-pressed', showing ? 'false' : 'true');
      btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');

      const labelEl = btn.querySelector('[data-password-toggle-label]');
      if (labelEl) labelEl.textContent = showing ? 'Show' : 'Hide';

      const showIcon = btn.querySelector('.password-toggle-btn__icon-show');
      const hideIcon = btn.querySelector('.password-toggle-btn__icon-hide');
      if (showIcon) showIcon.style.display = showing ? 'inline' : 'none';
      if (hideIcon) hideIcon.style.display = showing ? 'none' : 'inline';
    });
  });

  // 11. Checkout Payment Method Ref Toggle (GCash vs COD)
  const gcashRefGroup = document.getElementById('gcashRefGroup');
  if (gcashRefGroup) {
    const syncPaymentRef = () => {
      const selected = document.querySelector('input[name="payment_method"]:checked')?.value;
      gcashRefGroup.style.display = selected === 'cod' ? 'none' : '';
    };
    document.querySelectorAll('input[name="payment_method"]').forEach((radio) => {
      radio.addEventListener('change', syncPaymentRef);
    });
    syncPaymentRef();
  }

  // 12. Brand Kit SVG Logo Download & Hex Copy (/diagrams)
  document.querySelectorAll('[data-download-seal]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const wrap = document.getElementById(btn.dataset.downloadSeal);
      const svg = wrap?.querySelector('svg');
      if (!svg) return;
      const serializer = new XMLSerializer();
      const source = '<?xml version="1.0" encoding="UTF-8"?>\n' + serializer.serializeToString(svg);
      const blob = new Blob([source], { type: 'image/svg+xml;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = btn.dataset.filename || 'cafe-isla-seal.svg';
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(url);
    });
  });

  document.querySelectorAll('[data-copy-hex]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const hex = btn.dataset.copyHex;
      try {
        await navigator.clipboard.writeText(hex);
        const orig = btn.innerHTML;
        btn.innerHTML = `<strong>Copied</strong><br>${hex}`;
        setTimeout(() => { btn.innerHTML = orig; }, 1100);
      } catch (e) {
        console.error(e);
      }
    });
  });

  bindBagItemControls();
})();
