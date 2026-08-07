/* ============================================
   MARKETPLACE — MAIN JAVASCRIPT
   ============================================ */

document.addEventListener('DOMContentLoaded', function () {
    initMobileMenu();
    initFavorites();
    initMessageSeller();  
    initUnreadBadge();
    initDropdowns();
    initLazyImages();
    initNavbarScroll();
});

/* ============================================
   MOBILE MENU
   ============================================ */

function initMobileMenu() {
    const menuBtn = document.querySelector('.mobile-menu-btn');
    const menu    = document.querySelector('.mobile-menu');
    const overlay = document.querySelector('.mobile-menu-overlay');
    const closeBtn = document.querySelector('.mobile-close');

    if (!menuBtn || !menu) return;

    function openMenu() {
        menu.classList.add('active');
        if (overlay) overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeMenu() {
        menu.classList.remove('active');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    menuBtn.addEventListener('click', openMenu);
    if (closeBtn)  closeBtn.addEventListener('click', closeMenu);
    if (overlay)   overlay.addEventListener('click', closeMenu);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && menu.classList.contains('active')) closeMenu();
    });
}

/* ============================================
   MESSAGE SELLER
   ============================================ */


function _chatClickHandler(e) {
    e.preventDefault();
    const productId = this.dataset.productId;
    if (productId) startChat(productId, this);
}

function startChat(productId, btn) {
    if (!productId) return;

    const originalHtml = btn ? btn.innerHTML : '';

    if (btn) {
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Starting chat...';
        btn.style.pointerEvents = 'none';
        btn.disabled = true;
    }

    fetch('api/conversations.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + encodeURIComponent(productId)
    })
    .then(r => {
        if (!r.ok) throw new Error('Server error ' + r.status);
        return r.json();
    })
    .then(data => {
        if (data.success && data.conversation_id) {
            window.location.href = 'messages.php?chat=' + encodeURIComponent(data.conversation_id);
        } else {
            showToast(data.error || 'Could not start chat. Please try again.', 'error');
            if (btn) { btn.innerHTML = originalHtml; btn.style.pointerEvents = ''; btn.disabled = false; }
        }
    })
    .catch(err => {
        console.error('Chat error:', err);
        showToast('Network error. Please check your connection.', 'error');
        if (btn) { btn.innerHTML = originalHtml; btn.style.pointerEvents = ''; btn.disabled = false; }
    });
}

window.startChat = startChat;

/* ============================================
   FAVORITES / WISHLIST
   ============================================ */

function initFavorites() {
    document.querySelectorAll('.favorite-btn, .fav-overlay-btn, .btn-fav').forEach(btn => {
        if (btn.dataset.favInit) return;
        btn.dataset.favInit = 'true';
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const productId = this.dataset.productId;
            if (productId) toggleFavorite(productId, this);
        });
    });
}

function toggleFavorite(productId, btn) {
    if (!productId) return;

    const wasActive = btn.classList.contains('active');

    _setFavState(productId, !wasActive);

    fetch('api/favorites.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + encodeURIComponent(productId)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            _setFavState(productId, data.action === 'added');
        } else {
            _setFavState(productId, wasActive);
        }
    })
    .catch(() => {
        _setFavState(productId, wasActive); 
    });
}

function _setFavState(productId, active) {
    document.querySelectorAll(`[data-product-id="${productId}"]`).forEach(b => {
        if (!b.classList.contains('favorite-btn') &&
            !b.classList.contains('fav-overlay-btn') &&
            !b.classList.contains('btn-fav')) return;

        b.classList.toggle('active', active);
        const icon = b.querySelector('i');
        if (icon) icon.className = active ? 'fas fa-heart' : 'far fa-heart';
        const label = b.querySelector('span');
        if (label) label.textContent = active ? 'Saved' : 'Save';
    });
}

window.toggleFavorite = toggleFavorite;

/* ============================================
   UNREAD MESSAGE BADGE (Navbar)
   ============================================ */

function initUnreadBadge() {
    const badge = document.getElementById('navMsgBadge');
    if (!badge) return;
    updateUnreadCount();
    setInterval(updateUnreadCount, 10000);
}

function updateUnreadCount() {
    const badge = document.getElementById('navMsgBadge');
    if (!badge) return;

    fetch('api/unread_count.php')
        .then(r => r.json())
        .then(data => {
            const count = parseInt(data.count) || 0;
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'flex';
                badge.classList.add('has-unread');
            } else {
                badge.style.display = 'none';
                badge.classList.remove('has-unread');
            }
        })
        .catch(() => {});
}

/* ============================================
   DROPDOWNS
   ============================================ */

function initDropdowns() {
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.user-dropdown') && !e.target.closest('.mobile-menu-btn')) {
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('active'));
        }
    });

    document.querySelectorAll('.mobile-links a').forEach(link => {
        link.addEventListener('click', () => {
            document.querySelector('.mobile-menu')?.classList.remove('active');
            document.querySelector('.mobile-menu-overlay')?.classList.remove('active');
            document.body.style.overflow = '';
        });
    });
}

/* ============================================
   NAVBAR SCROLL SHADOW
   ============================================ */

function initNavbarScroll() {
    const navbar = document.querySelector('.navbar');
    if (!navbar) return;
    window.addEventListener('scroll', function () {
        navbar.style.boxShadow = window.pageYOffset > 50
            ? '0 4px 20px rgba(0,0,0,0.3)'
            : 'none';
    }, { passive: true });
}

/* ============================================
   LAZY IMAGES
   ============================================ */

function initLazyImages() {
    if (!('IntersectionObserver' in window)) return;
    const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                if (img.dataset.src) { img.src = img.dataset.src; img.removeAttribute('data-src'); }
                obs.unobserve(img);
            }
        });
    }, { rootMargin: '50px' });
    document.querySelectorAll('img[data-src]').forEach(img => observer.observe(img));
}

/* ============================================
   TOAST NOTIFICATIONS
   ============================================ */

function showToast(message, type = 'info') {
    document.querySelectorAll('.toast-notification').forEach(t => t.remove());

    const icons = { success: 'fa-check-circle', error: 'fa-exclamation-circle', info: 'fa-info-circle' };
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.style.cssText = `
        position: fixed; bottom: 24px; right: 24px; z-index: 9999;
        display: flex; align-items: center; gap: 10px;
        background: var(--bg-card); border: 1px solid var(--border-color);
        border-radius: var(--radius-md); padding: 14px 18px;
        box-shadow: var(--shadow-lg); font-size: 0.9rem; font-weight: 500;
        transform: translateX(120%); opacity: 0;
        transition: transform 0.3s ease, opacity 0.3s ease;
        max-width: 320px;
    `;
    if (type === 'error') toast.style.borderColor = 'rgba(255,107,107,0.4)';
    if (type === 'success') toast.style.borderColor = 'rgba(0,212,170,0.4)';

    const color = type === 'error' ? '#ff6b6b' : type === 'success' ? 'var(--accent)' : 'var(--text-secondary)';
    toast.innerHTML = `<i class="fas ${icons[type] || icons.info}" style="color:${color}"></i><span>${escapeHtml(message)}</span>`;

    document.body.appendChild(toast);
    requestAnimationFrame(() => { toast.style.transform = 'translateX(0)'; toast.style.opacity = '1'; });
    setTimeout(() => {
        toast.style.transform = 'translateX(120%)';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

window.showToast = showToast;

/* ============================================
   UTILITIES
   ============================================ */

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function (...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

window.debounce  = debounce;
window.throttle  = throttle;
window.escapeHtml = escapeHtml;

window.startChat = startChat;