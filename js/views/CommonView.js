// ==========================================================================
// VAST STUDIO — MVC ARCHITECTURE: VIEW LAYER (Common & UI Shell)
// File: js/views/CommonView.js
// Handles shared UI elements: Toasts, Modals, Theme, and Icon rendering
// ==========================================================================

(function(window) {
    'use strict';

    class CommonView {
        static formatVND(num) {
            return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(num || 0);
        }

        static renderIcons(container = document) {
            container.querySelectorAll('[data-icon]').forEach(el => {
                const iconName = el.getAttribute('data-icon');
                const size = el.getAttribute('data-size') || 18;
                if (window.getIcon) {
                    el.innerHTML = window.getIcon(iconName, parseInt(size));
                }
            });
        }

        static showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = 'toast';

            let icon = 'checkCircle';
            let iconColor = '#10b981';
            if (type === 'danger') { icon = 'xCircle'; iconColor = '#ef4444'; }
            if (type === 'warning') { icon = 'alertTriangle'; iconColor = '#f59e0b'; }
            if (type === 'info') { icon = 'bell'; iconColor = '#3b82f6'; }

            toast.innerHTML = `
                <span style="color: ${iconColor}; display: flex; align-items: center;">${window.getIcon(icon, 18)}</span>
                <span>${message}</span>
            `;

            container.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(10px)';
                toast.style.transition = 'all 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3200);
        }

        static openModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.add('active');
                CommonView.renderIcons(modal);
            }
        }

        static closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.remove('active');
            }
        }

        static applyTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            const iconSpan = document.getElementById('theme-icon');
            if (iconSpan) {
                iconSpan.setAttribute('data-icon', theme === 'dark' ? 'sun' : 'moon');
                CommonView.renderIcons(iconSpan.parentElement);
            }
        }

        static updateSidebarBadges(productCount, pendingOrderCount) {
            const prdEl = document.getElementById('sidebar-products-count');
            if (prdEl) prdEl.textContent = productCount;

            const ordEl = document.getElementById('sidebar-pending-orders');
            if (ordEl) ordEl.textContent = pendingOrderCount;
        }

        static setPageTitle(title) {
            const el = document.getElementById('topbar-title');
            if (el) el.textContent = title;
        }
    }

    window.CommonView = CommonView;
})(window);

