// ==========================================================================
// VAST STUDIO — MVC ARCHITECTURE: MAIN APPLICATION CONTROLLER
// File: js/controllers/AppController.js
// Central router, state coordinator, theme & modal event dispatcher
// ==========================================================================

(function(window) {
    'use strict';

    class AppController {
        static currentTab = 'dashboard';
        static currentTheme = localStorage.getItem('vast_theme_v1') || 'dark';

        static init() {
            // Apply initial theme
            window.CommonView.applyTheme(AppController.currentTheme);
            window.CommonView.renderIcons();
            AppController.initDate();
            AppController.initSettingsValues();

            // Initialize Sub-Controllers
            window.ProductController.init();
            window.OrderController.init();
            window.DashboardController.init();

            // Setup Global Listeners
            AppController.setupEventListeners();

            // Initial Render
            AppController.updateCounters();
            AppController.navigateTo(AppController.currentTab);
        }

        static setupEventListeners() {
            // Close modal when clicking outside dialog
            document.querySelectorAll('.modal-overlay').forEach(overlay => {
                overlay.addEventListener('click', (e) => {
                    if (e.target === overlay) {
                        overlay.classList.remove('active');
                    }
                });
            });

            // Close mobile drawer when clicking backdrop
            document.addEventListener('click', (e) => {
                const sidebar = document.getElementById('sidebar');
                const toggleBtn = document.getElementById('mobile-toggle-btn');
                if (window.innerWidth <= 768 && sidebar?.classList.contains('open')) {
                    if (!sidebar.contains(e.target) && !toggleBtn?.contains(e.target)) {
                        sidebar.classList.remove('open');
                    }
                }
            });

            // Customers filter
            document.getElementById('customer-search-input')?.addEventListener('input', () => AppController.renderCustomers());
            document.getElementById('customer-tier-filter')?.addEventListener('change', () => AppController.renderCustomers());

            // Settings Forms
            document.getElementById('settings-form')?.addEventListener('submit', (e) => AppController.handleSaveSettings(e));
            document.getElementById('payment-settings-form')?.addEventListener('submit', (e) => AppController.handleSavePaymentSettings(e));
            document.getElementById('collection-form')?.addEventListener('submit', (e) => AppController.handleCollectionSubmit(e));
            document.getElementById('coupon-form')?.addEventListener('submit', (e) => AppController.handleCouponSubmit(e));
        }

        static initDate() {
            const dateEl = document.getElementById('topbar-date');
            if (!dateEl) return;
            const now = new Date();
            const days = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
            const dayName = days[now.getDay()];
            const d = String(now.getDate()).padStart(2, '0');
            const m = String(now.getMonth() + 1).padStart(2, '0');
            const y = now.getFullYear();
            dateEl.textContent = `Hôm nay: ${dayName}, ${d}/${m}/${y}`;
        }

        static navigateTo(tabId) {
            AppController.currentTab = tabId;

            // Update Sidebar Active state
            document.querySelectorAll('.sidebar .nav-item').forEach(item => {
                item.classList.toggle('active', item.getAttribute('data-tab') === tabId);
            });

            // Show active pane
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('active');
            });
            document.getElementById(`tab-${tabId}`)?.classList.add('active');

            // Page Titles
            const titles = {
                dashboard: 'Tổng quan kinh doanh',
                products: 'Quản lý Sản phẩm',
                orders: 'Quản lý Đơn hàng',
                collections: 'Bộ sưu tập thời trang',
                customers: 'Danh sách Khách hàng',
                coupons: 'Mã Giảm Giá & Khuyến Mãi',
                settings: 'Cài đặt Cửa hàng'
            };
            window.CommonView.setPageTitle(titles[tabId] || 'VAST STUDIO Admin');

            // Dispatch render to appropriate Controller
            if (tabId === 'dashboard') window.DashboardController.render();
            if (tabId === 'products') window.ProductController.render();
            if (tabId === 'orders') window.OrderController.render();
            if (tabId === 'collections') AppController.renderCollections();
            if (tabId === 'customers') AppController.renderCustomers();
            if (tabId === 'coupons') AppController.renderCoupons();
            if (tabId === 'settings') AppController.initSettingsValues();

            if (window.innerWidth <= 768) {
                document.getElementById('sidebar')?.classList.remove('open');
            }

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        static toggleTheme() {
            AppController.currentTheme = AppController.currentTheme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('vast_theme_v1', AppController.currentTheme);
            window.CommonView.applyTheme(AppController.currentTheme);
            window.CommonView.showToast(`Đã chuyển sang giao diện ${AppController.currentTheme === 'dark' ? 'Tối (Dark Mode)' : 'Sáng (Light Mode)'}`, 'info');
        }

        static toggleSidebar() {
            document.getElementById('sidebar')?.classList.toggle('open');
        }

        static updateCounters() {
            const products = window.StoreModel.getProducts();
            const orders = window.StoreModel.getOrders();
            const pendingOrders = orders.filter(o => o.orderStatus === 'pending' || o.orderStatus === 'processing');
            window.CommonView.updateSidebarBadges(products.length, pendingOrders.length);
        }

        // --- Collections ---
        static renderCollections() {
            const container = document.getElementById('collections-grid-container');
            if (!container) return;
            const collections = window.StoreModel.getCollections();

            container.innerHTML = collections.map(col => `
                <div class="collection-card">
                    <img class="collection-banner" src="${col.banner || window.createFashionPlaceholder(col.name, '#09090b')}" alt="${col.name}">
                    <div class="collection-body">
                        <div>
                            <h3 class="collection-name">${col.name}</h3>
                            <p class="collection-desc">${col.description || 'Bộ sưu tập thời trang giới hạn từ VAST STUDIO.'}</p>
                        </div>
                        <div class="collection-meta">
                            <span>${col.productsCount || 0} sản phẩm</span>
                            <span class="badge badge-success">Active Drop</span>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        static handleCollectionSubmit(e) {
            e.preventDefault();
            const name = document.getElementById('col-name').value.trim();
            const description = document.getElementById('col-description').value.trim();
            const banner = document.getElementById('col-banner').value.trim() || window.createFashionPlaceholder(name, '#09090b');

            window.StoreModel.saveCollection({ name, description, banner });
            window.CommonView.closeModal('collection-modal');
            AppController.renderCollections();
            window.CommonView.showToast(`Đã tạo bộ sưu tập "${name}" thành công!`);
        }

        // --- Customers ---
        static renderCustomers() {
            const tbody = document.getElementById('customers-table-body');
            if (!tbody) return;

            const customers = window.StoreModel.getCustomers();
            const searchVal = (document.getElementById('customer-search-input')?.value || '').toLowerCase().trim();
            const tierVal = document.getElementById('customer-tier-filter')?.value || 'all';

            let filtered = customers.filter(c => {
                const matchSearch = c.name.toLowerCase().includes(searchVal) ||
                                    c.email.toLowerCase().includes(searchVal) ||
                                    c.phone.includes(searchVal);
                const matchTier = tierVal === 'all' || c.tier === tierVal;
                return matchSearch && matchTier;
            });

            tbody.innerHTML = filtered.map(c => {
                let tierBadge = `<span class="badge">${c.tier}</span>`;
                if (c.tier === 'VIP Black Card') tierBadge = `<span class="badge" style="background: #09090b; color: #fbbf24; border: 1px solid #d97706;">★ VIP Black</span>`;
                if (c.tier === 'Gold Member') tierBadge = `<span class="badge badge-warning">Gold</span>`;
                if (c.tier === 'Silver Member') tierBadge = `<span class="badge badge-info">Silver</span>`;

                return `
                    <tr>
                        <td><span style="font-family: monospace; font-weight: 700;">${c.id}</span></td>
                        <td><div style="font-weight: 600; color: var(--text-primary);">${c.name}</div></td>
                        <td>
                            <div style="font-size: 0.8rem;">${c.phone}</div>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">${c.email}</div>
                        </td>
                        <td>${c.city}</td>
                        <td>${tierBadge}</td>
                        <td><strong>${c.ordersCount}</strong></td>
                        <td><span class="price-display">${window.CommonView.formatVND(c.totalSpent)}</span></td>
                        <td><span style="font-size: 0.75rem; color: var(--text-muted);">${c.lastOrder}</span></td>
                    </tr>
                `;
            }).join('');
        }

        // --- Coupons ---
        static renderCoupons() {
            const tbody = document.getElementById('coupons-table-body');
            if (!tbody) return;

            const coupons = window.StoreModel.getCoupons();
            tbody.innerHTML = coupons.map(cp => {
                const discountText = cp.type === 'percent' ? `${cp.value}%` : window.CommonView.formatVND(cp.value);
                return `
                    <tr>
                        <td>
                            <span style="font-family: monospace; font-weight: 800; font-size: 0.95rem; color: #3b82f6; background: rgba(59, 130, 246, 0.1); padding: 4px 8px; border-radius: 4px;">
                                ${cp.code}
                            </span>
                        </td>
                        <td><span style="font-weight: 500;">${cp.description}</span></td>
                        <td><strong style="color: #10b981; font-size: 0.92rem;">Giảm ${discountText}</strong></td>
                        <td>${window.CommonView.formatVND(cp.minOrder)}</td>
                        <td><strong>${cp.usageCount}</strong> / ${cp.usageLimit}</td>
                        <td><span style="font-size: 0.78rem; color: var(--text-muted);">${cp.expiryDate}</span></td>
                        <td><span class="badge badge-success">Đang kích hoạt</span></td>
                        <td style="text-align: right;">
                            <button class="btn-icon-sm delete-btn" title="Xóa voucher" onclick="window.AppController.confirmDeleteCoupon('${cp.id}', '${cp.code}')">
                                <span data-icon="trash"></span>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');

            window.CommonView.renderIcons(tbody);
        }

        static handleCouponSubmit(e) {
            e.preventDefault();
            const code = document.getElementById('cpn-code').value.trim().toUpperCase();
            const description = document.getElementById('cpn-description').value.trim();
            const type = document.getElementById('cpn-type').value;
            const value = parseInt(document.getElementById('cpn-value').value) || 0;
            const minOrder = parseInt(document.getElementById('cpn-minOrder').value) || 0;
            const usageLimit = parseInt(document.getElementById('cpn-usageLimit').value) || 100;
            const expiryDate = document.getElementById('cpn-expiryDate').value;

            window.StoreModel.saveCoupon({ code, description, type, value, minOrder, usageLimit, expiryDate, status: 'active' });
            window.CommonView.closeModal('coupon-modal');
            AppController.renderCoupons();
            window.CommonView.showToast(`Đã tạo mã khuyến mãi "${code}" thành công!`);
        }

        static confirmDeleteCoupon(id, code) {
            if (confirm(`Bạn có muốn xóa mã giảm giá "${code}"?`)) {
                window.StoreModel.deleteCoupon(id);
                AppController.renderCoupons();
                window.CommonView.showToast(`Đã xóa voucher "${code}".`, 'info');
            }
        }

        // --- Settings ---
        static initSettingsValues() {
            const settings = window.StoreModel.getSettings();
            if (!settings) return;

            if (document.getElementById('setting-storeName')) document.getElementById('setting-storeName').value = settings.storeName || 'VAST STUDIO';
            if (document.getElementById('setting-tagline')) document.getElementById('setting-tagline').value = settings.tagline || '';
            if (document.getElementById('setting-hotline')) document.getElementById('setting-hotline').value = settings.hotline || '';
            if (document.getElementById('setting-email')) document.getElementById('setting-email').value = settings.email || '';
            if (document.getElementById('setting-addressHanoi')) document.getElementById('setting-addressHanoi').value = settings.addressHanoi || '';
            if (document.getElementById('setting-addressHcm')) document.getElementById('setting-addressHcm').value = settings.addressHcm || '';

            if (document.getElementById('setting-freeShippingThreshold')) document.getElementById('setting-freeShippingThreshold').value = settings.freeShippingThreshold || 500000;
            if (document.getElementById('setting-defaultShippingFee')) document.getElementById('setting-defaultShippingFee').value = settings.defaultShippingFee || 30000;
            if (document.getElementById('setting-bankName')) document.getElementById('setting-bankName').value = settings.bankName || '';
            if (document.getElementById('setting-bankAccount')) document.getElementById('setting-bankAccount').value = settings.bankAccount || '';
            if (document.getElementById('setting-bankOwner')) document.getElementById('setting-bankOwner').value = settings.bankOwner || '';
        }

        static handleSaveSettings(e) {
            e.preventDefault();
            const storeName = document.getElementById('setting-storeName').value.trim();
            const tagline = document.getElementById('setting-tagline').value.trim();
            const hotline = document.getElementById('setting-hotline').value.trim();
            const email = document.getElementById('setting-email').value.trim();
            const addressHanoi = document.getElementById('setting-addressHanoi').value.trim();
            const addressHcm = document.getElementById('setting-addressHcm').value.trim();

            window.StoreModel.saveSettings({ storeName, tagline, hotline, email, addressHanoi, addressHcm });
            window.CommonView.showToast('Đã lưu thông tin thương hiệu VAST STUDIO!');
        }

        static handleSavePaymentSettings(e) {
            e.preventDefault();
            const freeShippingThreshold = parseInt(document.getElementById('setting-freeShippingThreshold').value) || 0;
            const defaultShippingFee = parseInt(document.getElementById('setting-defaultShippingFee').value) || 0;
            const bankName = document.getElementById('setting-bankName').value.trim();
            const bankAccount = document.getElementById('setting-bankAccount').value.trim();
            const bankOwner = document.getElementById('setting-bankOwner').value.trim();

            window.StoreModel.saveSettings({ freeShippingThreshold, defaultShippingFee, bankName, bankAccount, bankOwner });
            window.CommonView.showToast('Đã cập nhật cấu hình thanh toán & vận chuyển!');
        }

        static confirmResetData() {
            if (confirm('Khôi phục toàn bộ dữ liệu mẫu (Sản phẩm, Đơn hàng, Voucher) của VAST STUDIO?')) {
                window.StoreModel.resetAll();
                AppController.updateCounters();
                AppController.navigateTo('dashboard');
                window.CommonView.showToast('Đã khôi phục dữ liệu ban đầu của VAST STUDIO!', 'info');
            }
        }

        static handleGlobalSearch(e) {
            const val = e.target.value.toLowerCase().trim();
            if (!val || e.key !== 'Enter') return;

            const products = window.StoreModel.getProducts();
            const orders = window.StoreModel.getOrders();

            const foundPrd = products.find(p => p.name.toLowerCase().includes(val) || p.sku.toLowerCase().includes(val));
            const foundOrder = orders.find(o => o.id.toLowerCase().includes(val) || o.customerName.toLowerCase().includes(val));

            if (foundPrd) {
                AppController.navigateTo('products');
                const searchInput = document.getElementById('product-search-input');
                if (searchInput) {
                    searchInput.value = val;
                    window.ProductController.render();
                }
                window.CommonView.showToast(`Đã tìm thấy sản phẩm: ${foundPrd.name}`);
            } else if (foundOrder) {
                AppController.navigateTo('orders');
                const orderInput = document.getElementById('order-search-input');
                if (orderInput) {
                    orderInput.value = val;
                    window.OrderController.render();
                }
                window.CommonView.showToast(`Đã tìm thấy đơn hàng: ${foundOrder.id}`);
            } else {
                window.CommonView.showToast(`Không tìm thấy kết quả khớp với "${val}"`, 'warning');
            }
        }
    }

    window.AppController = AppController;
})(window);

