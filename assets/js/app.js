// NomNom App JS

document.addEventListener('DOMContentLoaded', () => {

  // ── Dropdown Menus ──────────────────────────────
  function setupDropdown(btnId, dropId) {
    const btn = document.getElementById(btnId);
    const drop = document.getElementById(dropId);
    if (!btn || !drop) return;
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      drop.classList.toggle('open');
    });
    document.addEventListener('click', () => drop.classList.remove('open'));
  }
  setupDropdown('notifBtn', 'notifDropdown');
  setupDropdown('userMenuBtn', 'userDropdown');

  // ── Auto-dismiss alerts ─────────────────────────
  document.querySelectorAll('.alert').forEach(a => {
    setTimeout(() => a.style.opacity = '0', 4000);
    setTimeout(() => a.remove(), 4500);
    a.style.transition = 'opacity 0.5s';
  });

  // ── AJAX Add to Cart ────────────────────────────
  document.querySelectorAll('.btn-add-cart').forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      const productId = this.dataset.id;
      const notes = this.dataset.notes || '';
      fetch(`${BASE_URL}/controllers/CartController.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add&product_id=${productId}&qty=1&notes=${encodeURIComponent(notes)}`
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          showToast('✅ Ditambahkan ke keranjang!');
          updateCartBadge(data.cart_count);
        } else {
          showToast('❌ ' + (data.message || 'Gagal menambah ke keranjang'), 'error');
        }
      })
      .catch(() => showToast('❌ Terjadi kesalahan', 'error'));
    });
  });

  // ── Cart Qty Controls (AJAX) ────────────────────
  document.querySelectorAll('.qty-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const cartId = this.dataset.cart;
      const action = this.dataset.action; // 'inc' or 'dec'
      const qtyEl = document.querySelector(`.qty-value[data-cart="${cartId}"]`);
      fetch(`${BASE_URL}/controllers/CartController.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=${action}&cart_id=${cartId}`
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          if (data.qty === 0) {
            document.querySelector(`.cart-item[data-cart="${cartId}"]`)?.remove();
            updateCartTotal(data.total);
          } else {
            if (qtyEl) qtyEl.textContent = data.qty;
            updateCartTotal(data.total);
          }
          updateCartBadge(data.cart_count);
        }
      });
    });
  });

  // ── Star Rating ─────────────────────────────────
  document.querySelectorAll('.star-rating').forEach(container => {
    const stars = container.querySelectorAll('.star');
    const input = container.querySelector('input[type="hidden"]');
    stars.forEach((star, i) => {
      star.addEventListener('click', () => {
        stars.forEach((s, j) => s.classList.toggle('active', j <= i));
        if (input) input.value = i + 1;
      });
      star.addEventListener('mouseenter', () => {
        stars.forEach((s, j) => s.classList.toggle('active', j <= i));
      });
    });
    container.addEventListener('mouseleave', () => {
      const val = input ? parseInt(input.value) : 0;
      stars.forEach((s, j) => s.classList.toggle('active', j < val));
    });
  });

  // ── Upload Preview ──────────────────────────────
  document.querySelectorAll('.upload-area input[type="file"]').forEach(input => {
    input.addEventListener('change', function () {
      const file = this.files[0];
      if (!file) return;
      const preview = this.closest('.upload-area').querySelector('.upload-preview');
      if (preview && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = e => {
          preview.innerHTML = `<img src="${e.target.result}" style="max-height:200px;border-radius:8px;margin-top:10px">`;
        };
        reader.readAsDataURL(file);
      }
    });
  });

  // ── Modal ───────────────────────────────────────
  document.querySelectorAll('[data-modal]').forEach(btn => {
    btn.addEventListener('click', () => {
      const modal = document.getElementById(btn.dataset.modal);
      if (modal) modal.classList.add('open');
    });
  });
  document.querySelectorAll('.modal-close, .modal-overlay').forEach(el => {
    el.addEventListener('click', function (e) {
      if (e.target === this) {
        this.closest('.modal-overlay')?.classList.remove('open');
      }
    });
  });
  document.querySelectorAll('.modal').forEach(m => {
    m.addEventListener('click', e => e.stopPropagation());
  });

  // ── Category Filter ─────────────────────────────
  document.querySelectorAll('.category-chip').forEach(chip => {
    chip.addEventListener('click', function () {
      document.querySelectorAll('.category-chip').forEach(c => c.classList.remove('active'));
      this.classList.add('active');
      const cat = this.dataset.category;
      filterByCategory(cat);
    });
  });

  // ── Order Status Auto Refresh ───────────────────
  const trackingPage = document.getElementById('order-tracking-live');
  if (trackingPage) {
    const orderId = trackingPage.dataset.order;
    setInterval(() => {
      fetch(`${BASE_URL}/controllers/OrderController.php?action=status&id=${orderId}`)
        .then(r => r.json())
        .then(data => {
          const statusEl = document.getElementById('order-status-badge');
          if (statusEl && data.status) {
            statusEl.className = `status-badge status-${data.status}`;
            statusEl.textContent = statusLabel(data.status);
          }
        });
    }, 10000); // poll every 10 seconds
  }

  // ── Voucher Apply ───────────────────────────────
  const voucherBtn = document.getElementById('applyVoucher');
  if (voucherBtn) {
    voucherBtn.addEventListener('click', () => {
      const code = document.getElementById('voucherCode').value.trim();
      const total = parseFloat(document.getElementById('checkoutTotal').dataset.total);
      if (!code) return;
      fetch(`${BASE_URL}/controllers/VoucherController.php?action=check&code=${encodeURIComponent(code)}&total=${total}`)
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            document.getElementById('voucherResult').innerHTML = `
              <div class="voucher-applied">
                🎟️ Voucher <strong>${code}</strong> berhasil! Hemat ${data.discount_display}
              </div>
            `;
            document.getElementById('discountAmount').textContent = data.discount_display;
            document.getElementById('finalTotal').textContent = data.final_display;
            document.getElementById('voucherIdInput').value = data.voucher_id;
          } else {
            document.getElementById('voucherResult').innerHTML = `<div class="alert alert-error">${data.message}</div>`;
          }
        });
    });
  }

  // ── Real-time Notification Polling ─────────────────
  const notifBtn = document.getElementById('notifBtn');
  if (notifBtn && BASE_URL) {
    let lastNotifId = 0;
    
    // Initial fetch to get max ID so we don't display all old messages as fresh notifications
    fetch(`${BASE_URL}/controllers/NotificationController.php?action=poll&last_id=0`)
      .then(r => r.json())
      .then(data => {
        if (data.success && data.new_notifications.length > 0) {
          lastNotifId = Math.max(...data.new_notifications.map(n => parseInt(n.id)));
        }
        startNotifPolling();
      })
      .catch(() => startNotifPolling());

    function startNotifPolling() {
      setInterval(() => {
        fetch(`${BASE_URL}/controllers/NotificationController.php?action=poll&last_id=${lastNotifId}`)
          .then(r => r.json())
          .then(data => {
            if (data.success) {
              updateNotifBadge(data.unread);
              if (data.new_notifications.length > 0) {
                playNotifSound();
                data.new_notifications.forEach(n => {
                  lastNotifId = Math.max(lastNotifId, parseInt(n.id));
                  showToast(`🔔 <strong>${n.title}</strong><br>${n.message}`);
                  
                  const listEl = document.querySelector('.notif-list');
                  if (listEl) {
                    const emptyEl = listEl.querySelector('.empty-state');
                    if (emptyEl) emptyEl.remove();

                    const item = document.createElement('a');
                    item.href = `${BASE_URL}/controllers/NotificationController.php?action=read&id=${n.id}`;
                    item.className = 'notif-item unread';
                    const icon = n.type === 'order' ? '📦' : (n.type === 'payment' ? '💳' : (n.type === 'promo' ? '🎟️' : '🔔'));
                    item.innerHTML = `
                      <div class="notif-icon type-${n.type}">${icon}</div>
                      <div>
                        <div class="notif-title">${n.title}</div>
                        <div class="notif-time">Baru saja</div>
                      </div>
                    `;
                    listEl.insertBefore(item, listEl.firstChild);
                  }
                });
              }
            }
          })
          .catch(() => {});
      }, 3000);
    }
  }

  function updateNotifBadge(count) {
    let badge = document.querySelector('#notifBtn .badge');
    if (count > 0) {
      if (!badge) {
        badge = document.createElement('span');
        badge.className = 'badge';
        document.getElementById('notifBtn').appendChild(badge);
      }
      badge.textContent = count;
    } else if (badge) {
      badge.remove();
    }
  }

  function playNotifSound() {
    try {
      const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      const playBeep = (time, freq, duration) => {
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.frequency.setValueAtTime(freq, time);
        gain.gain.setValueAtTime(0.1, time);
        gain.gain.exponentialRampToValueAtTime(0.01, time + duration);
        osc.start(time);
        osc.stop(time + duration);
      };
      const now = audioCtx.currentTime;
      playBeep(now, 523.25, 0.15); // C5
      playBeep(now + 0.18, 659.25, 0.2); // E5
    } catch (e) {
      console.warn("AudioContext not allowed or not supported:", e);
    }
  }

});

// ── Helpers ─────────────────────────────────────
const BASE_URL = document.querySelector('meta[name="base-url"]')?.content || '';

function showToast(msg, type = 'success') {
  const t = document.createElement('div');
  t.className = `toast toast-${type}`;
  t.innerHTML = msg;
  t.style.cssText = `
    position:fixed; bottom:24px; right:24px; z-index:99999;
    background:${type === 'success' ? '#1a1a2e' : '#ef4444'};
    color:white; padding:14px 20px; border-radius:12px;
    font-size:14px; font-weight:600; box-shadow:0 8px 30px rgba(0,0,0,0.3);
    animation: slideIn 0.3s ease; max-width:320px;
  `;
  document.body.appendChild(t);
  setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity 0.3s'; }, 3000);
  setTimeout(() => t.remove(), 3400);
}

function updateCartBadge(count) {
  document.querySelectorAll('.btn-icon .badge').forEach(b => {
    if (b.closest('a[href*="cart"]')) b.textContent = count;
  });
}

function updateCartTotal(total) {
  const el = document.getElementById('cart-total');
  if (el) el.textContent = 'Rp ' + Number(total).toLocaleString('id-ID');
}

function filterByCategory(cat) {
  document.querySelectorAll('.product-card[data-category]').forEach(card => {
    card.closest('.product-card-wrap').style.display =
      (cat === 'all' || card.dataset.category === cat) ? '' : 'none';
  });
}

function statusLabel(status) {
  const labels = {
    pending: '⏳ Menunggu',
    confirmed: '✅ Dikonfirmasi',
    preparing: '👨‍🍳 Dimasak',
    on_delivery: '🛵 Dikirim',
    delivered: '✅ Selesai',
    cancelled: '❌ Dibatalkan'
  };
  return labels[status] || status;
}

// CSS animation
const style = document.createElement('style');
style.textContent = `@keyframes slideIn { from { transform: translateY(20px); opacity: 0; } to { transform: none; opacity: 1; } }`;
document.head.appendChild(style);
