// ==========================================================================
// VAST STUDIO — STOREFRONT CONTROLLER & VIEW LOGIC
// File: js/storefront.js
// Modern Streetwear E-Commerce Client Architecture (Complete Suite)
// ==========================================================================

(function(window) {
    'use strict';

    // Global Storefront State
    const Storefront = {
        state: {
            products: [],
            collections: [],
            coupons: [],
            settings: {},
            appliedCoupon: null,
            filter: {
                category: 'all',
                collection: 'all',
                minPrice: 0,
                maxPrice: 2000000,
                size: 'all',
                color: 'all',
                inStockOnly: false,
                search: '',
                sort: 'popular',
                view: 'grid-4'
            },
            quickViewProduct: null,
            selectedSize: null,
            selectedColor: null,
            quickViewQty: 1,
            // PDP State
            pdpProduct: null,
            pdpSelectedSize: null,
            pdpSelectedColor: null,
            pdpQty: 1
        },

        // --- INIT STOREFRONT ---
        init() {
            this.loadData();
            this.initTheme();
            this.bindEvents();
            this.updateHeaderBadges();

            // Detect current page
            const path = window.location.pathname.toLowerCase();
            if (path.endsWith('products.php') || path.endsWith('shop.php')) {
                this.initShopPage();
            } else if (path.endsWith('product-detail.php')) {
                this.initProductDetailPage();
            } else if (path.endsWith('order-tracking.php')) {
                this.initOrderTrackingPage();
            } else if (path.endsWith('checkout.php')) {
                this.initCheckoutPage();
            } else if (path.endsWith('auth.php') || path.endsWith('login.php')) {
                this.initAuthPage();
            } else if (path.endsWith('about.php') || path.endsWith('contact.php')) {
                // Info pages: general bindings
            } else {
                this.initHomePage();
            }
        },

        loadData() {
            if (window.StoreModel) {
                if (typeof window.__VAST_SESSION_USER__ !== 'undefined') {
                    window.StoreModel.setCurrentUser(window.__VAST_SESSION_USER__);
                }
                if (window.__VAST_LIVE_CATALOG__) {
                    window.StoreModel.syncLiveCatalog(window.__VAST_LIVE_CATALOG__);
                }
                this.state.products = window.StoreModel.getProducts();
                this.state.collections = window.StoreModel.getCollections();
                this.state.coupons = window.StoreModel.getCoupons();
                this.state.settings = window.StoreModel.getSettings();
            }
        },

        initTheme() {
            const savedTheme = localStorage.getItem('vast_theme_v1') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
            this.updateThemeIcon(savedTheme);
        },

        toggleTheme() {
            const current = document.documentElement.getAttribute('data-theme') || 'dark';
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('vast_theme_v1', next);
            this.updateThemeIcon(next);
            this.showToast(`Đã chuyển sang giao diện ${next === 'dark' ? 'Tối' : 'Sáng'}`, 'info');
        },

        updateThemeIcon(theme) {
            const iconWrap = document.getElementById('theme-toggle-icon');
            if (iconWrap && window.ICONS) {
                iconWrap.innerHTML = theme === 'dark' ? window.ICONS.sun : window.ICONS.moon;
            }
        },

        bindEvents() {
            // Cart update events
            window.addEventListener('vast:cart-updated', () => {
                this.updateHeaderBadges();
                this.renderCartDrawer();
            });

            // Wishlist update events
            window.addEventListener('vast:wishlist-updated', () => {
                this.updateHeaderBadges();
                this.updateWishlistButtonsState();
                this.renderWishlistDrawer();
            });

            // Auth update events
            window.addEventListener('vast:auth-updated', () => {
                this.updateAuthHeader();
            });

            // ESC key to close any active modal
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.closeAllModals();
                }
            });

            // Back to Top button listener
            window.addEventListener('scroll', () => {
                const btn = document.getElementById('btn-back-to-top');
                if (btn) {
                    if (window.scrollY > 300) {
                        btn.classList.add('show');
                    } else {
                        btn.classList.remove('show');
                    }
                }
            });
        },

        scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        updateHeaderBadges() {
            const cartBadge = document.getElementById('header-cart-badge');
            const wishlistBadge = document.getElementById('header-wishlist-badge');

            if (window.StoreModel) {
                const cartCount = window.StoreModel.getCartCount();
                if (cartBadge) {
                    cartBadge.textContent = cartCount;
                    cartBadge.style.display = cartCount > 0 ? 'flex' : 'none';
                }

                const wishlist = window.StoreModel.getWishlist();
                if (wishlistBadge) {
                    wishlistBadge.textContent = wishlist.length;
                    wishlistBadge.style.display = wishlist.length > 0 ? 'flex' : 'none';
                }

                this.updateAuthHeader();
            }
        },

        updateAuthHeader() {
            const authBtn = document.getElementById('header-auth-btn');
            if (!authBtn || !window.StoreModel) return;

            const user = window.StoreModel.getCurrentUser();
            if (user) {
                const initial = (user.name || 'U').trim().charAt(0).toUpperCase();
                authBtn.innerHTML = `<span class="header-user-avatar" title="${user.name} (${user.tier || 'Thành viên'})">${initial}</span>`;
            } else {
                authBtn.innerHTML = window.ICONS?.user || `<span data-icon="user"></span>`;
            }
        },

        // --- HELPER: FORMAT CURRENCY ---
        formatMoney(num) {
            return (num || 0).toLocaleString('vi-VN') + ' ₫';
        },

        // --- HELPER: TOAST NOTIFICATION ---
        showToast(message, type = 'success') {
            let container = document.getElementById('store-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'store-toast-container';
                container.style.cssText = `
                    position: fixed;
                    bottom: 24px;
                    right: 24px;
                    z-index: 9999;
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                    pointer-events: none;
                `;
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            const bg = type === 'success' ? '#10b981' : (type === 'error' ? '#ef4444' : '#18181b');
            toast.style.cssText = `
                background: ${bg};
                color: #ffffff;
                padding: 12px 20px;
                border-radius: 8px;
                font-size: 0.88rem;
                font-weight: 600;
                box-shadow: 0 10px 25px rgba(0,0,0,0.3);
                display: flex;
                align-items: center;
                gap: 8px;
                opacity: 0;
                transform: translateY(12px);
                transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
                pointer-events: auto;
            `;

            let iconSvg = window.ICONS ? window.ICONS.checkCircle : '✓';
            if (type === 'error') iconSvg = window.ICONS ? window.ICONS.alertTriangle : '⚠';
            if (type === 'info') iconSvg = window.ICONS ? window.ICONS.shoppingBag : 'ℹ';

            toast.innerHTML = `<span style="display:flex;align-items:center;">${iconSvg}</span><span>${message}</span>`;
            container.appendChild(toast);

            requestAnimationFrame(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateY(0)';
            });

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(12px)';
                setTimeout(() => toast.remove(), 300);
            }, 3200);
        },

        // --- RENDER SINGLE PRODUCT CARD ---
        renderProductCard(product) {
            const isWish = window.StoreModel ? window.StoreModel.isInWishlist(product.id) : false;
            const hasSale = product.originalPrice && product.originalPrice > product.price;
            const discountPercent = hasSale ? Math.round((1 - product.price / product.originalPrice) * 100) : 0;
            const isLowStock = product.stock <= 5;
            const isHot = (product.salesCount || 0) >= 80;

            // Color dots
            const colorsHtml = (product.colors || ['#000']).map(c => {
                let colorHex = '#18181b';
                const lower = c.toLowerCase();
                if (lower.includes('trắng') || lower.includes('kem')) colorHex = '#f4f4f5';
                if (lower.includes('xám') || lower.includes('bạc')) colorHex = '#71717a';
                if (lower.includes('xanh') || lower.includes('indigo') || lower.includes('pastel')) colorHex = '#38bdf8';
                if (lower.includes('rêu') || lower.includes('sage')) colorHex = '#84cc16';
                if (lower.includes('nâu') || lower.includes('mocha') || lower.includes('be')) colorHex = '#b45309';

                return `<span class="color-dot" style="background-color: ${colorHex};" title="${c}"></span>`;
            }).join('');

            return `
                <div class="product-card" data-product-id="${product.id}">
                    <div class="card-media">
                        <a href="product-detail.php?id=${product.id}" style="display:block; width:100%; height:100%;">
                            <img src="${product.image}" alt="${product.name}" 
                                 onerror="this.onerror=null; this.src='${product.fallbackImage || ''}'" loading="lazy">
                        </a>
                        
                        <div class="card-badges">
                            ${hasSale ? `<span class="badge-sale">-${discountPercent}%</span>` : ''}
                            ${isHot ? `<span class="badge-tag-hot">HOT</span>` : ''}
                            ${isLowStock ? `<span class="badge-low-stock">Chỉ còn ${product.stock}</span>` : ''}
                        </div>

                        <button class="card-btn-wishlist ${isWish ? 'active' : ''}" 
                                onclick="Storefront.toggleWishlist('${product.id}', this)" 
                                title="${isWish ? 'Xóa khỏi yêu thích' : 'Thêm vào yêu thích'}">
                            ${isWish ? (window.ICONS?.heartFilled || '♥') : (window.ICONS?.heart || '♡')}
                        </button>

                        <div class="card-action-bar">
                            <button class="btn-card-quickview" onclick="Storefront.openQuickView('${product.id}')">
                                ${window.ICONS?.eye || ''} Xem nhanh
                            </button>
                            <button class="btn-card-addcart" onclick="Storefront.quickAddToCart('${product.id}')">
                                ${window.ICONS?.shoppingBag || ''} Thêm giỏ
                            </button>
                        </div>
                    </div>

                    <div class="card-content">
                        <span class="card-category">${product.category || 'Thời trang'}</span>
                        <h4 class="card-title" title="${product.name}">
                            <a href="product-detail.php?id=${product.id}" style="color:inherit; text-decoration:none;">
                                ${product.name}
                            </a>
                        </h4>

                        <div class="card-price-row">
                            <span class="card-price-current">${this.formatMoney(product.price)}</span>
                            ${hasSale ? `<span class="card-price-old">${this.formatMoney(product.originalPrice)}</span>` : ''}
                        </div>

                        <div class="card-meta-row">
                            <div class="card-colors-row" title="Màu sắc sẵn có">
                                ${colorsHtml}
                            </div>
                            <span class="card-stock-hint">Đã bán ${product.salesCount || 0}</span>
                        </div>
                    </div>
                </div>
            `;
        },

        // ======================================================================
        // TRANG CHỦ (HOME PAGE) LOGIC
        // ======================================================================
        initHomePage() {
            this.renderFeaturedCategories();
            this.renderFeaturedCollections();
            this.renderBestSellers();
            this.renderNewArrivals('all');
        },

        renderFeaturedCategories() {
            const container = document.getElementById('home-categories-grid');
            if (!container) return;

            const categoryMap = [
                { name: 'Áo thun', icon: 'products', desc: 'Heavyweight Boxy Tee' },
                { name: 'Áo sơ mi', icon: 'products', desc: 'Oversized Oxford' },
                { name: 'Hoodie & Sweater', icon: 'products', desc: 'Acid Wash & Knit' },
                { name: 'Quần', icon: 'package', desc: 'Cargo & Raw Denim' },
                { name: 'Áo khoác', icon: 'package', desc: 'Varsity Bomber' },
                { name: 'Phụ kiện', icon: 'tag', desc: 'Beanie & Caps' }
            ];

            const products = this.state.products;

            container.innerHTML = categoryMap.map(cat => {
                const count = products.filter(p => p.category === cat.name).length;
                return `
                    <a href="products.php?category=${encodeURIComponent(cat.name)}" class="category-card">
                        <div class="category-icon-box">
                            ${window.ICONS ? window.ICONS[cat.icon] || window.ICONS.products : '👕'}
                        </div>
                        <h4 class="category-name">${cat.name}</h4>
                        <span class="category-count">${count} sản phẩm</span>
                    </a>
                `;
            }).join('');
        },

        renderFeaturedCollections() {
            const container = document.getElementById('home-collections-grid');
            if (!container) return;

            const collections = this.state.collections || [];
            container.innerHTML = collections.slice(0, 4).map(col => {
                return `
                    <a href="products.php?collection=${encodeURIComponent(col.name)}" 
                       class="collection-card" 
                       style="background-image: url('${col.banner}')">
                        <div class="collection-card-inner">
                            <span class="col-badge">Bộ Sưu Tập</span>
                            <h3 class="col-title">${col.name}</h3>
                            <p class="col-desc">${col.description}</p>
                            <span class="col-action-btn">
                                Khám phá ngay ${window.ICONS?.arrowRight || '→'}
                            </span>
                        </div>
                    </a>
                `;
            }).join('');
        },

        renderBestSellers() {
            const container = document.getElementById('home-bestsellers-grid');
            if (!container) return;

            const sorted = [...this.state.products].sort((a, b) => (b.salesCount || 0) - (a.salesCount || 0));
            const top4 = sorted.slice(0, 4);

            container.innerHTML = top4.map(p => this.renderProductCard(p)).join('');
        },

        renderNewArrivals(category = 'all') {
            const container = document.getElementById('home-new-arrivals-grid');
            if (!container) return;

            let filtered = [...this.state.products].reverse();
            if (category !== 'all') {
                filtered = filtered.filter(p => p.category === category);
            }

            container.innerHTML = filtered.slice(0, 8).map(p => this.renderProductCard(p)).join('');

            document.querySelectorAll('.tab-pill-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.category === category);
            });
        },

        // ======================================================================
        // TRANG DANH SÁCH SẢN PHẨM (SHOP / PRODUCTS PAGE) LOGIC
        // ======================================================================
        initShopPage() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('category')) this.state.filter.category = params.get('category');
            if (params.get('collection')) this.state.filter.collection = params.get('collection');
            if (params.get('search')) this.state.filter.search = params.get('search');
            if (params.get('sort')) this.state.filter.sort = params.get('sort');

            this.renderCategorySidebar();
            this.renderCollectionSidebar();
            this.syncFilterInputsFromState();
            this.applyFiltersAndRender();
        },

        renderCategorySidebar() {
            const container = document.getElementById('shop-category-filters');
            if (!container) return;

            const categories = ['all', 'Áo thun', 'Áo sơ mi', 'Hoodie & Sweater', 'Quần', 'Áo khoác', 'Phụ kiện'];
            const allProducts = this.state.products;

            container.innerHTML = categories.map(cat => {
                const count = cat === 'all' 
                    ? allProducts.length 
                    : allProducts.filter(p => p.category === cat).length;

                const isActive = this.state.filter.category === cat;
                const label = cat === 'all' ? 'Tất cả sản phẩm' : cat;

                return `
                    <li>
                        <button class="filter-item-btn ${isActive ? 'active' : ''}" 
                                onclick="Storefront.setCategoryFilter('${cat}')">
                            <span>${label}</span>
                            <span class="filter-count-badge">${count}</span>
                        </button>
                    </li>
                `;
            }).join('');
        },

        renderCollectionSidebar() {
            const container = document.getElementById('shop-collection-filters');
            if (!container) return;

            const collections = ['all', ...(this.state.collections || []).map(c => c.name)];
            const allProducts = this.state.products;

            container.innerHTML = collections.map(col => {
                const count = col === 'all' 
                    ? allProducts.length 
                    : allProducts.filter(p => p.collection === col).length;

                const isActive = this.state.filter.collection === col;
                const label = col === 'all' ? 'Tất cả bộ sưu tập' : col;

                return `
                    <li>
                        <button class="filter-item-btn ${isActive ? 'active' : ''}" 
                                onclick="Storefront.setCollectionFilter('${col}')">
                            <span>${label}</span>
                            <span class="filter-count-badge">${count}</span>
                        </button>
                    </li>
                `;
            }).join('');
        },

        syncFilterInputsFromState() {
            const slider = document.getElementById('price-range-slider');
            const label = document.getElementById('price-range-value');
            if (slider && label) {
                slider.value = this.state.filter.maxPrice;
                label.textContent = this.formatMoney(this.state.filter.maxPrice);
            }

            const sortSelect = document.getElementById('shop-sort-select');
            if (sortSelect) {
                sortSelect.value = this.state.filter.sort;
            }

            const searchInput = document.getElementById('shop-search-input');
            if (searchInput) {
                searchInput.value = this.state.filter.search;
            }
        },

        setCategoryFilter(cat) {
            this.state.filter.category = cat;
            this.renderCategorySidebar();
            this.applyFiltersAndRender();
        },

        setCollectionFilter(col) {
            this.state.filter.collection = col;
            this.renderCollectionSidebar();
            this.applyFiltersAndRender();
        },

        setSizeFilter(size) {
            this.state.filter.size = (this.state.filter.size === size) ? 'all' : size;
            
            document.querySelectorAll('.size-pill-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.size === this.state.filter.size);
            });
            this.applyFiltersAndRender();
        },

        setColorFilter(color) {
            this.state.filter.color = (this.state.filter.color === color) ? 'all' : color;

            document.querySelectorAll('.color-filter-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.color === this.state.filter.color);
            });
            this.applyFiltersAndRender();
        },

        handlePriceSliderChange(val) {
            this.state.filter.maxPrice = Number(val);
            const label = document.getElementById('price-range-value');
            if (label) label.textContent = this.formatMoney(this.state.filter.maxPrice);
            this.applyFiltersAndRender();
        },

        handleSortChange(val) {
            this.state.filter.sort = val;
            this.applyFiltersAndRender();
        },

        handleSearchInput(val) {
            this.state.filter.search = (val || '').trim();
            this.applyFiltersAndRender();
        },

        toggleInStockFilter(checked) {
            this.state.filter.inStockOnly = checked;
            this.applyFiltersAndRender();
        },

        resetFilters() {
            this.state.filter = {
                category: 'all',
                collection: 'all',
                minPrice: 0,
                maxPrice: 2000000,
                size: 'all',
                color: 'all',
                inStockOnly: false,
                search: '',
                sort: 'popular',
                view: this.state.filter.view
            };

            this.renderCategorySidebar();
            this.renderCollectionSidebar();
            this.syncFilterInputsFromState();

            document.querySelectorAll('.size-pill-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.color-filter-btn').forEach(b => b.classList.remove('active'));
            const stockCheckbox = document.getElementById('filter-in-stock-checkbox');
            if (stockCheckbox) stockCheckbox.checked = false;

            this.applyFiltersAndRender();
            this.showToast('Đã xóa tất cả bộ lọc', 'info');
        },

        applyFiltersAndRender() {
            const container = document.getElementById('shop-products-grid');
            if (!container) return;

            let result = [...this.state.products];

            if (this.state.filter.category !== 'all') {
                result = result.filter(p => p.category === this.state.filter.category);
            }

            if (this.state.filter.collection !== 'all') {
                result = result.filter(p => p.collection === this.state.filter.collection);
            }

            result = result.filter(p => p.price <= this.state.filter.maxPrice);

            if (this.state.filter.size !== 'all') {
                result = result.filter(p => (p.sizes || []).includes(this.state.filter.size));
            }

            if (this.state.filter.color !== 'all') {
                result = result.filter(p => (p.colors || []).some(c => c.toLowerCase().includes(this.state.filter.color.toLowerCase())));
            }

            if (this.state.filter.inStockOnly) {
                result = result.filter(p => p.stock > 0);
            }

            if (this.state.filter.search) {
                const q = this.state.filter.search.toLowerCase();
                result = result.filter(p => 
                    p.name.toLowerCase().includes(q) ||
                    (p.category && p.category.toLowerCase().includes(q)) ||
                    (p.collection && p.collection.toLowerCase().includes(q)) ||
                    (p.description && p.description.toLowerCase().includes(q))
                );
            }

            switch (this.state.filter.sort) {
                case 'newest':
                    result.sort((a, b) => new Date(b.createdAt || 0) - new Date(a.createdAt || 0));
                    break;
                case 'price-asc':
                    result.sort((a, b) => a.price - b.price);
                    break;
                case 'price-desc':
                    result.sort((a, b) => b.price - a.price);
                    break;
                case 'discount':
                    result.sort((a, b) => {
                        const discA = a.originalPrice ? (a.originalPrice - a.price) : 0;
                        const discB = b.originalPrice ? (b.originalPrice - b.price) : 0;
                        return discB - discA;
                    });
                    break;
                case 'popular':
                default:
                    result.sort((a, b) => (b.salesCount || 0) - (a.salesCount || 0));
                    break;
            }

            const countLabel = document.getElementById('shop-product-count');
            if (countLabel) {
                countLabel.innerHTML = `Đang hiển thị <strong>${result.length}</strong> sản phẩm`;
            }

            this.renderActiveFilterChips();

            if (result.length === 0) {
                container.innerHTML = `
                    <div class="empty-shop-state">
                        <div class="empty-shop-icon">${window.ICONS?.package || '📦'}</div>
                        <h3 style="margin:0; font-family: var(--store-font-heading);">Không tìm thấy sản phẩm</h3>
                        <p style="color:var(--text-muted); margin:0;">Không có sản phẩm nào phù hợp với các tiêu chí bạn đã chọn.</p>
                        <button class="btn-hero-primary" onclick="Storefront.resetFilters()" style="padding:10px 20px; font-size:0.88rem;">
                            ${window.ICONS?.refresh || ''} Xóa bộ lọc và thử lại
                        </button>
                    </div>
                `;
            } else {
                container.innerHTML = result.map(p => this.renderProductCard(p)).join('');
            }
        },

        renderActiveFilterChips() {
            const container = document.getElementById('shop-active-chips');
            if (!container) return;

            const chips = [];

            if (this.state.filter.category !== 'all') {
                chips.push(`
                    <span class="filter-chip">
                        Danh mục: ${this.state.filter.category}
                        <button class="chip-remove-btn" onclick="Storefront.setCategoryFilter('all')">×</button>
                    </span>
                `);
            }

            if (this.state.filter.collection !== 'all') {
                chips.push(`
                    <span class="filter-chip">
                        BST: ${this.state.filter.collection}
                        <button class="chip-remove-btn" onclick="Storefront.setCollectionFilter('all')">×</button>
                    </span>
                `);
            }

            if (this.state.filter.maxPrice < 2000000) {
                chips.push(`
                    <span class="filter-chip">
                        Giá ≤ ${this.formatMoney(this.state.filter.maxPrice)}
                        <button class="chip-remove-btn" onclick="Storefront.handlePriceSliderChange(2000000)">×</button>
                    </span>
                `);
            }

            if (this.state.filter.size !== 'all') {
                chips.push(`
                    <span class="filter-chip">
                        Size: ${this.state.filter.size}
                        <button class="chip-remove-btn" onclick="Storefront.setSizeFilter('${this.state.filter.size}')">×</button>
                    </span>
                `);
            }

            if (this.state.filter.color !== 'all') {
                chips.push(`
                    <span class="filter-chip">
                        Màu: ${this.state.filter.color}
                        <button class="chip-remove-btn" onclick="Storefront.setColorFilter('${this.state.filter.color}')">×</button>
                    </span>
                `);
            }

            if (this.state.filter.search) {
                chips.push(`
                    <span class="filter-chip">
                        Từ khóa: "${this.state.filter.search}"
                        <button class="chip-remove-btn" onclick="Storefront.handleSearchInput('')">×</button>
                    </span>
                `);
            }

            if (chips.length > 0) {
                chips.push(`
                    <button class="chip-remove-btn" onclick="Storefront.resetFilters()" 
                            style="font-size:0.8rem; font-weight:700; color:var(--store-red); text-decoration:underline;">
                        Xóa tất cả
                    </button>
                `);
            }

            container.innerHTML = chips.join('');
        },

        setViewMode(mode) {
            this.state.filter.view = mode;
            const grid = document.getElementById('shop-products-grid');
            if (grid) {
                grid.className = `products-grid ${mode === 'grid-3' ? 'grid-3' : (mode === 'list' ? 'list-view' : '')}`;
            }

            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.view === mode);
            });
        },

        toggleMobileFilterSidebar() {
            const sidebar = document.getElementById('shop-filter-sidebar');
            if (sidebar) {
                sidebar.classList.toggle('mobile-open');
            }
        },

        // ======================================================================
        // TRANG CHI TIẾT SẢN PHẨM (PRODUCT DETAIL PAGE - PDP)
        // ======================================================================
        initProductDetailPage() {
            const params = new URLSearchParams(window.location.search);
            const productId = params.get('id') || (this.state.products[0] ? this.state.products[0].id : 'PRD-001');
            if (!productId || !window.StoreModel) return;

            const product = window.StoreModel.getProductById(productId) || this.state.products[0];
            if (!product) return;

            this.state.pdpProduct = product;
            this.state.pdpSelectedSize = product.sizes ? product.sizes[0] : 'M';
            this.state.pdpSelectedColor = product.colors ? product.colors[0] : 'Tiêu chuẩn';
            this.state.pdpQty = 1;

            this.renderPDPDetails(product);
            this.renderPDPReviews(product.id);
            this.renderPDPRelated(product);
        },

        renderPDPDetails(product) {
            // Update Title & Meta
            document.title = `${product.name} — VAST STUDIO`;

            // Breadcrumbs
            const bcName = document.getElementById('pdp-breadcrumb-name');
            if (bcName) bcName.textContent = product.name;

            // Gallery
            const mainImg = document.getElementById('pdp-main-img');
            if (mainImg) {
                mainImg.src = product.image;
                mainImg.onerror = () => { mainImg.src = product.fallbackImage || ''; };
            }

            const thumbsContainer = document.getElementById('pdp-thumbs-container');
            if (thumbsContainer) {
                const galleryImages = [
                    product.image,
                    'https://images.unsplash.com/photo-1509631179647-0177331693ae?w=600&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=600&auto=format&fit=crop&q=80'
                ];
                thumbsContainer.innerHTML = galleryImages.map((img, i) => `
                    <div class="pdp-thumb-item ${i === 0 ? 'active' : ''}" onclick="Storefront.changePDPImage('${img}', this)">
                        <img src="${img}" alt="${product.name} Thumbnail">
                    </div>
                `).join('');
            }

            // Info Details
            const catTag = document.getElementById('pdp-category-tag');
            if (catTag) catTag.textContent = `${product.category} • ${product.collection}`;

            const titleEl = document.getElementById('pdp-title');
            if (titleEl) titleEl.textContent = product.name;

            const skuEl = document.getElementById('pdp-sku');
            if (skuEl) skuEl.textContent = `SKU: ${product.sku}`;

            const stockEl = document.getElementById('pdp-stock-status');
            if (stockEl) {
                stockEl.innerHTML = product.stock > 0 
                    ? `<span style="color:var(--store-emerald); font-weight:700;">● Còn hàng (${product.stock} sản phẩm sẵn có)</span>` 
                    : `<span style="color:var(--store-red); font-weight:700;">● Tạm hết hàng</span>`;
            }

            // Price
            const hasSale = product.originalPrice && product.originalPrice > product.price;
            const priceEl = document.getElementById('pdp-price');
            const oldPriceEl = document.getElementById('pdp-old-price');
            const savingsEl = document.getElementById('pdp-savings');

            if (priceEl) priceEl.textContent = this.formatMoney(product.price);
            if (oldPriceEl) {
                oldPriceEl.textContent = hasSale ? this.formatMoney(product.originalPrice) : '';
                oldPriceEl.style.display = hasSale ? 'inline' : 'none';
            }
            if (savingsEl) {
                if (hasSale) {
                    const diff = product.originalPrice - product.price;
                    savingsEl.textContent = `Tiết kiệm ${this.formatMoney(diff)}`;
                    savingsEl.style.display = 'inline-block';
                } else {
                    savingsEl.style.display = 'none';
                }
            }

            // Description
            const descEl = document.getElementById('pdp-desc');
            if (descEl) descEl.textContent = product.description;

            // Colors
            const colorContainer = document.getElementById('pdp-colors-container');
            const colorLabel = document.getElementById('pdp-selected-color-label');
            if (colorLabel) colorLabel.textContent = this.state.pdpSelectedColor;
            if (colorContainer) {
                colorContainer.innerHTML = (product.colors || []).map((c, i) => `
                    <button class="quickview-pill-btn ${i === 0 ? 'active' : ''}" 
                            onclick="Storefront.selectPDPColor('${c}', this)">
                        ${c}
                    </button>
                `).join('');
            }

            // Sizes
            const sizeContainer = document.getElementById('pdp-sizes-container');
            const sizeLabel = document.getElementById('pdp-selected-size-label');
            if (sizeLabel) sizeLabel.textContent = this.state.pdpSelectedSize;
            if (sizeContainer) {
                sizeContainer.innerHTML = (product.sizes || []).map((s, i) => `
                    <button class="quickview-pill-btn ${i === 0 ? 'active' : ''}" 
                            onclick="Storefront.selectPDPSize('${s}', this)">
                        ${s}
                    </button>
                `).join('');
            }

            // Wishlist PDP Button
            const wishBtn = document.getElementById('pdp-btn-wishlist');
            if (wishBtn && window.StoreModel) {
                const isWish = window.StoreModel.isInWishlist(product.id);
                wishBtn.classList.toggle('active', isWish);
                wishBtn.innerHTML = isWish ? (window.ICONS?.heartFilled || '♥') : (window.ICONS?.heart || '♡');
            }
        },

        changePDPImage(src, thumbEl) {
            const mainImg = document.getElementById('pdp-main-img');
            if (mainImg) mainImg.src = src;

            document.querySelectorAll('.pdp-thumb-item').forEach(t => t.classList.remove('active'));
            if (thumbEl) thumbEl.classList.add('active');
        },

        selectPDPSize(size, btn) {
            if (btn && btn.disabled) return;
            this.state.pdpSelectedSize = size;
            const label = document.getElementById('pdp-selected-size-label');
            if (label) label.textContent = size;

            btn.parentElement.querySelectorAll('.quickview-pill-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            this.syncPDPVariantStock();
        },

        selectPDPColor(color, btn) {
            if (btn && btn.disabled) return;
            this.state.pdpSelectedColor = color;
            const label = document.getElementById('pdp-selected-color-label');
            if (label) label.textContent = color;

            btn.parentElement.querySelectorAll('.quickview-pill-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            this.syncPDPVariantStock();
        },

        syncPDPVariantStock() {
            const product = this.state.pdpProduct;
            if (!product || !window.StoreModel) return;
            const stock = window.StoreModel.getVariantStock(product, this.state.pdpSelectedSize, this.state.pdpSelectedColor);
            const stockEl = document.getElementById('pdp-stock-status');
            if (stockEl) {
                stockEl.innerHTML = stock > 0
                    ? `<span style="color:var(--store-emerald); font-weight:700;">● Còn ${stock} sản phẩm (Size ${this.state.pdpSelectedSize} • ${this.state.pdpSelectedColor})</span>`
                    : `<span style="color:var(--store-red); font-weight:700;">● Tạm hết hàng phân loại này</span>`;
            }
            if (stock > 0 && this.state.pdpQty > stock) {
                this.state.pdpQty = stock;
                const display = document.getElementById('pdp-qty-display');
                if (display) display.textContent = this.state.pdpQty;
            }
        },

        adjustPDPQty(delta) {
            const product = this.state.pdpProduct;
            const maxStock = product && window.StoreModel
                ? window.StoreModel.getVariantStock(product, this.state.pdpSelectedSize, this.state.pdpSelectedColor)
                : 99;
            const next = Math.max(1, this.state.pdpQty + delta);
            if (maxStock > 0 && next > maxStock) {
                this.showToast(`Số lượng tối đa cho phân loại này là ${maxStock}`, 'error');
                this.state.pdpQty = maxStock;
            } else {
                this.state.pdpQty = next;
            }
            const display = document.getElementById('pdp-qty-display');
            if (display) display.textContent = this.state.pdpQty;
        },

        submitPDPAddToCart() {
            const product = this.state.pdpProduct;
            if (!product || !window.StoreModel) return;
            const maxStock = window.StoreModel.getVariantStock(product, this.state.pdpSelectedSize, this.state.pdpSelectedColor);
            if (maxStock <= 0) {
                this.showToast('Phân loại bạn chọn đã hết hàng!', 'error');
                return;
            }
            if (this.state.pdpQty > maxStock) {
                this.showToast(`Chỉ còn ${maxStock} sản phẩm cho phân loại này!`, 'error');
                return;
            }

            window.StoreModel.addToCart(
                product,
                this.state.pdpSelectedSize,
                this.state.pdpSelectedColor,
                this.state.pdpQty
            );

            this.showToast(`Đã thêm ${this.state.pdpQty}x "${product.name}" vào giỏ hàng`, 'success');
            this.openCart();
        },

        submitPDPBuyNow() {
            const product = this.state.pdpProduct;
            if (!product || !window.StoreModel) return;
            const maxStock = window.StoreModel.getVariantStock(product, this.state.pdpSelectedSize, this.state.pdpSelectedColor);
            if (maxStock <= 0 || this.state.pdpQty > maxStock) {
                this.showToast(maxStock <= 0 ? 'Phân loại bạn chọn đã hết hàng!' : `Chỉ còn ${maxStock} sản phẩm cho phân loại này!`, 'error');
                return;
            }

            window.StoreModel.addToCart(
                product,
                this.state.pdpSelectedSize,
                this.state.pdpSelectedColor,
                this.state.pdpQty
            );

            this.openCheckoutModal();
        },

        switchPDPTab(tabId, btn) {
            document.querySelectorAll('.pdp-tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.pdp-tab-pane').forEach(p => p.style.display = 'none');

            btn.classList.add('active');
            const targetPane = document.getElementById(`pdp-pane-${tabId}`);
            if (targetPane) targetPane.style.display = 'block';
        },

        renderPDPReviews(productId) {
            if (!window.StoreModel) return;
            const reviews = window.StoreModel.getProductReviews(productId);
            const container = document.getElementById('pdp-reviews-list');
            const avgScoreEl = document.getElementById('pdp-avg-rating');
            const countEl = document.getElementById('pdp-review-count');

            if (reviews.length > 0 && avgScoreEl) {
                const totalRating = reviews.reduce((sum, r) => sum + r.rating, 0);
                const avg = (totalRating / reviews.length).toFixed(1);
                avgScoreEl.textContent = avg;
                if (countEl) countEl.textContent = `${reviews.length} nhận xét chân thực`;
            }

            if (container) {
                container.innerHTML = reviews.map(r => `
                    <div class="review-entry-card">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                            <div>
                                <strong style="font-size:0.95rem; color:var(--text-primary);">${r.author}</strong>
                                <span style="font-size:0.75rem; color:var(--text-muted); margin-left:6px;">(${r.city})</span>
                                <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">
                                    Phân loại: <strong>${r.variant}</strong> • Ngày: ${r.date}
                                </div>
                            </div>
                            <div style="color:#f59e0b; font-size:0.85rem;">
                                ${'★'.repeat(r.rating)}${'☆'.repeat(5 - r.rating)}
                            </div>
                        </div>
                        <p style="margin:0; font-size:0.9rem; color:var(--text-secondary); line-height:1.5;">
                            "${r.content}"
                        </p>
                    </div>
                `).join('');
            }
        },

        handleReviewSubmit(e) {
            e.preventDefault();
            const product = this.state.pdpProduct;
            if (!product || !window.StoreModel) return;

            const name = document.getElementById('review-input-name')?.value.trim();
            const city = document.getElementById('review-input-city')?.value.trim();
            const rating = document.getElementById('review-input-rating')?.value || 5;
            const content = document.getElementById('review-input-content')?.value.trim();

            if (!name || !content) {
                this.showToast('Vui lòng nhập tên và nội dung đánh giá!', 'error');
                return;
            }

            window.StoreModel.addReview(product.id, {
                author: name,
                city: city || 'Việt Nam',
                rating: rating,
                variant: `Size ${this.state.pdpSelectedSize} • ${this.state.pdpSelectedColor}`,
                content: content
            });

            this.showToast('Cảm ơn bạn đã gửi đánh giá cho sản phẩm!', 'success');
            document.getElementById('pdp-review-form')?.reset();
            this.renderPDPReviews(product.id);
        },

        renderPDPRelated(product) {
            const container = document.getElementById('pdp-related-grid');
            if (!container) return;

            // Find related by same category or collection, excluding current
            let related = this.state.products.filter(p => 
                p.id !== product.id && (p.category === product.category || p.collection === product.collection)
            );

            if (related.length === 0) {
                related = this.state.products.filter(p => p.id !== product.id);
            }

            container.innerHTML = related.slice(0, 4).map(p => this.renderProductCard(p)).join('');
        },

        // ======================================================================
        // TRANG TRA CỨU ĐƠN HÀNG (ORDER TRACKING PAGE)
        // ======================================================================
        initOrderTrackingPage() {
            const params = new URLSearchParams(window.location.search);
            const orderId = params.get('order');
            if (orderId) {
                const input = document.getElementById('tracking-query-input');
                if (input) input.value = orderId;
                this.searchOrder(orderId);
            }
        },

        handleTrackingSearch(e) {
            if (e) e.preventDefault();
            const input = document.getElementById('tracking-query-input');
            const query = input ? input.value.trim() : '';
            if (!query) {
                this.showToast('Vui lòng nhập mã đơn hoặc số điện thoại!', 'error');
                return;
            }
            this.searchOrder(query);
        },

        quickTrackDemo(code) {
            const input = document.getElementById('tracking-query-input');
            if (input) input.value = code;
            this.searchOrder(code);
        },

        async searchOrder(query) {
            const container = document.getElementById('tracking-result-container');
            if (!container) return;

            const q = query.trim();
            container.innerHTML = `<div class="empty-shop-state" style="margin-top:20px;"><p style="color:var(--text-muted);margin:0;">Đang tra cứu đơn hàng...</p></div>`;

            let order = null;
            try {
                const res = await fetch(`api.php?action=order_track&q=${encodeURIComponent(q)}`);
                const payload = await res.json();
                if (payload && payload.ok && payload.order) {
                    order = payload.order;
                }
            } catch (err) {
                if (window.StoreModel) {
                    const allOrders = window.StoreModel.getOrders();
                    const up = q.toUpperCase();
                    order = allOrders.find(o => o.id.toUpperCase() === up);
                }
            }

            if (!order) {
                container.innerHTML = `
                    <div class="empty-shop-state" style="margin-top:20px;">
                        <div class="empty-shop-icon">${window.ICONS?.alertTriangle || '⚠'}</div>
                        <h3 style="margin:0; font-family:var(--store-font-heading);">Không tìm thấy đơn hàng</h3>
                        <p style="color:var(--text-muted); margin:0;">
                            Không có đơn hàng nào khớp với "<strong>${query}</strong>". Vui lòng kiểm tra lại mã đơn hoặc số điện thoại đặt hàng.
                        </p>
                    </div>
                `;
                return;
            }

            this.renderTrackingDetails(order, container);
        },

        renderTrackingDetails(order, container) {
            const statusStyleMap = {
                pending: ['Chờ xác nhận', 'badge-warning', 1],
                confirmed: ['Đã xác nhận', 'badge-info', 2],
                processing: ['Đang xử lý', 'badge-purple', 2],
                shipping: ['Đang giao hàng', 'badge-info', 3],
                delivered: ['Giao hàng thành công', 'badge-success', 4],
                awaiting_confirmation: ['Chờ xác nhận nhận hàng', 'badge-warning', 4],
                completed: ['Hoàn tất', 'badge-success', 5],
                cancel_requested: ['Yêu cầu hủy', 'badge-danger', 1],
                cancelled: ['Đã hủy', 'badge-danger', 0],
                return_requested: ['Yêu cầu đổi trả', 'badge-warning', 4],
                return_awaiting_shipment: ['Chờ gửi hàng đổi trả', 'badge-purple', 4],
                return_received: ['Đã nhận hàng đổi trả', 'badge-info', 4],
                return_completed: ['Đổi trả hoàn tất', 'badge-success', 5]
            };
            const stInfo = statusStyleMap[order.orderStatus] || [order.statusLabel || order.orderStatus, 'badge-warning', 1];
            const statusBadge = `<span class="badge ${stInfo[1]}">${stInfo[0]}</span>`;
            const stepIndex = stInfo[2];
            const historyHtml = Array.isArray(order.history) && order.history.length > 0
                ? `<div style="padding:18px 32px; border-top:1px solid var(--border-subtle);">
                        <h4 style="font-family:var(--store-font-heading); margin:0 0 12px; font-size:0.98rem;">Lịch sử xử lý đơn hàng</h4>
                        <div style="display:grid; gap:8px;">
                            ${order.history.map(h => `
                                <div style="display:flex; justify-content:space-between; gap:12px; font-size:0.82rem; padding:8px 12px; background:var(--bg-subtle); border-radius:var(--radius-sm);">
                                    <div>
                                        <strong style="color:var(--text-primary);">${h.statusLabel || h.status}</strong>
                                        <span style="color:var(--text-muted);"> • ${h.actor || 'Hệ thống'}</span>
                                        ${h.note ? `<div style="color:var(--text-secondary); margin-top:2px;">${h.note}</div>` : ''}
                                    </div>
                                    <span style="color:var(--text-muted); white-space:nowrap;">${h.createdAt || ''}</span>
                                </div>
                            `).join('')}
                        </div>
                   </div>`
                : '';

            container.innerHTML = `
                <div class="tracking-result-box">
                    <div class="tracking-header-row">
                        <div>
                            <span style="font-size:0.78rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Chi tiết đơn hàng</span>
                            <h2 style="font-family:var(--store-font-heading); margin:4px 0 0; font-size:1.6rem; color:var(--text-primary);">
                                ${order.id}
                            </h2>
                            <span style="font-size:0.82rem; color:var(--text-muted);">Ngày đặt: ${order.createdAt}</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                            ${statusBadge}
                            <a href="auth.php?detail_order=${encodeURIComponent(order.id)}" class="btn-reset-filters" style="width:auto; padding:6px 14px; text-decoration:none;">
                                Thao tác trong Tài khoản
                            </a>
                        </div>
                    </div>

                    ${order.orderStatus !== 'cancelled' ? `
                        <div class="tracking-timeline-box">
                            <div class="timeline-steps-track">
                                <div class="timeline-step-item ${stepIndex >= 1 ? (stepIndex > 1 ? 'completed' : 'active') : ''}">
                                    <div class="step-circle">${stepIndex > 1 ? '✓' : '1'}</div>
                                    <span class="step-label">Đặt hàng</span>
                                    <span class="step-time">Chờ xác nhận</span>
                                </div>
                                <div class="timeline-step-item ${stepIndex >= 2 ? (stepIndex > 2 ? 'completed' : 'active') : ''}">
                                    <div class="step-circle">${stepIndex > 2 ? '✓' : '2'}</div>
                                    <span class="step-label">Xác nhận &amp; Xử lý</span>
                                    <span class="step-time">Kho VAST</span>
                                </div>
                                <div class="timeline-step-item ${stepIndex >= 3 ? (stepIndex > 3 ? 'completed' : 'active') : ''}">
                                    <div class="step-circle">${stepIndex > 3 ? '✓' : '3'}</div>
                                    <span class="step-label">Đang giao</span>
                                    <span class="step-time">Đơn vị vận chuyển</span>
                                </div>
                                <div class="timeline-step-item ${stepIndex >= 4 ? (stepIndex > 4 ? 'completed' : 'active') : ''}">
                                    <div class="step-circle">${stepIndex > 4 ? '✓' : '4'}</div>
                                    <span class="step-label">Giao thành công</span>
                                    <span class="step-time">${stepIndex >= 5 ? 'Khách đã xác nhận' : 'Chờ khách xác nhận'}</span>
                                </div>
                            </div>
                        </div>
                    ` : `
                        <div style="padding:20px 32px; background:rgba(239,68,68,0.1); color:var(--store-red); font-size:0.9rem; font-weight:600;">
                            Đơn hàng này đã bị hủy.${order.cancelReason ? ' Lý do: ' + order.cancelReason : ''}
                        </div>
                    `}

                    ${historyHtml}

                    <div class="tracking-body-grid">
                        <div>
                            <h4 style="font-family:var(--store-font-heading); margin:0 0 16px; font-size:1.05rem;">Sản phẩm trong kiện (${(order.items || []).length})</h4>
                            <div style="display:flex; flex-direction:column; gap:14px;">
                                ${(order.items || []).map(item => `
                                    <div style="display:flex; gap:12px; align-items:center; border-bottom:1px solid var(--border-subtle); padding-bottom:12px;">
                                        <img src="${item.image}" alt="${item.name}" style="width:60px; height:75px; object-fit:cover; border-radius:4px; background:var(--bg-subtle);">
                                        <div style="flex:1;">
                                            <h5 style="margin:0 0 4px; font-size:0.9rem; color:var(--text-primary);">${item.name}</h5>
                                            <div style="font-size:0.78rem; color:var(--text-muted);">
                                                ${item.sku ? `SKU: <strong>${item.sku}</strong> • ` : ''}Size: <strong>${item.size || '—'}</strong> • Màu: <strong>${item.color || '—'}</strong> • SL: <strong>×${item.quantity}</strong>
                                            </div>
                                            <div style="font-family:var(--store-font-heading); font-weight:800; font-size:0.92rem; margin-top:2px;">
                                                ${this.formatMoney(item.price * item.quantity)}
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>

                        <div style="background:var(--bg-subtle); border-radius:var(--radius-md); padding:20px; display:flex; flex-direction:column; gap:16px;">
                            <div>
                                <span style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Người nhận hàng</span>
                                <h4 style="margin:4px 0 2px; font-size:1rem; color:var(--text-primary);">${order.customerName}</h4>
                                <div style="font-size:0.85rem; color:var(--text-secondary);">${order.customerPhone}</div>
                                <div style="font-size:0.85rem; color:var(--text-secondary); margin-top:4px;">${order.shippingAddress}</div>
                                ${order.note ? `<div style="font-size:0.8rem; font-style:italic; color:var(--text-muted); margin-top:6px;">"Ghi chú: ${order.note}"</div>` : ''}
                            </div>

                            <div style="border-top:1px dashed var(--border-color); padding-top:12px; display:flex; flex-direction:column; gap:6px; font-size:0.85rem;">
                                <div style="display:flex; justify-content:space-between;">
                                    <span style="color:var(--text-muted);">Tạm tính:</span>
                                    <span>${this.formatMoney(order.subtotal)}</span>
                                </div>
                                ${order.discount > 0 ? `
                                    <div style="display:flex; justify-content:space-between; color:var(--store-emerald);">
                                        <span>Giảm giá (${order.couponCode || 'Voucher'}):</span>
                                        <span>-${this.formatMoney(order.discount)}</span>
                                    </div>
                                ` : ''}
                                <div style="display:flex; justify-content:space-between;">
                                    <span style="color:var(--text-muted);">Phí vận chuyển:</span>
                                    <span>${order.shippingFee === 0 ? 'Miễn phí' : this.formatMoney(order.shippingFee)}</span>
                                </div>
                                <div style="display:flex; justify-content:space-between; border-top:1px dashed var(--border-color); padding-top:8px; font-weight:800; font-size:1.15rem; font-family:var(--store-font-heading); color:var(--text-primary);">
                                    <span>Tổng thanh toán:</span>
                                    <span style="color:var(--store-gold);">${this.formatMoney(order.total)}</span>
                                </div>
                                <div style="font-size:0.78rem; color:var(--text-muted); margin-top:4px;">
                                    Hình thức: <strong>${order.paymentMethod}</strong> (${order.paymentStatus})
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        },

        handleCancelOrder(orderId) {
            if (!confirm(`Bạn có chắc chắn muốn hủy đơn hàng ${orderId} không?`)) return;
            if (!window.StoreModel) return;

            const res = window.StoreModel.cancelOrder(orderId);
            if (res.success) {
                this.showToast(`Đã hủy thành công đơn hàng ${orderId}`, 'info');
                this.searchOrder(orderId);
            } else {
                this.showToast(res.message || 'Không thể hủy đơn hàng', 'error');
            }
        },

        // ======================================================================
        // TRANG THANH TOÁN (CHECKOUT PAGE LOGIC)
        // ======================================================================
        initCheckoutPage() {
            const container = document.getElementById('checkout-main-content');
            if (!container) return;
            if (!window.StoreModel) return;

            // Default checkout state
            if (!this.state.checkoutShipping) this.state.checkoutShipping = 'standard';
            if (!this.state.checkoutPayment) this.state.checkoutPayment = 'QR';

            const cart = window.StoreModel.getCart();

            // 1. If cart is empty
            if (!cart || cart.length === 0) {
                this.renderCheckoutEmpty(container);
                return;
            }

            // 2. Load saved customer info or logged in user
            let savedCustomer = {};
            const loggedInUser = window.StoreModel.getCurrentUser();
            if (loggedInUser) {
                savedCustomer = {
                    name: loggedInUser.name,
                    phone: loggedInUser.phone,
                    email: loggedInUser.email,
                    city: loggedInUser.city || 'Hà Nội'
                };
            } else {
                try {
                    savedCustomer = JSON.parse(localStorage.getItem('vast_customer_info') || '{}');
                } catch (err) {}
            }

            // 3. Render 2-column checkout layout
            container.innerHTML = `
                <form id="checkout-main-form" class="checkout-main-grid" onsubmit="Storefront.submitCheckoutPage(event)">
                    <!-- LEFT COLUMN: SHIPPING & PAYMENT -->
                    <div class="checkout-left-col">
                        <!-- SECTION 1: CUSTOMER INFO -->
                        <div class="checkout-section-card">
                            <div class="checkout-section-header">
                                <h2 class="checkout-section-title">
                                    <span class="checkout-section-title-num">1</span>
                                    Thông Tin Giao Hàng
                                </h2>
                                <span class="checkout-section-badge">Giao Tận Nơi</span>
                            </div>

                            <div class="checkout-form-grid">
                                <div class="form-group-full">
                                    <label class="checkout-label">Họ và tên người nhận <span class="required">*</span></label>
                                    <input type="text" id="checkout-fullname" class="checkout-input" 
                                           placeholder="Ví dụ: Nguyễn Văn A" 
                                           value="${savedCustomer.name || ''}" required>
                                </div>

                                <div>
                                    <label class="checkout-label">Số điện thoại <span class="required">*</span></label>
                                    <input type="tel" id="checkout-phone" class="checkout-input" 
                                           placeholder="Ví dụ: 0912 345 678" 
                                           value="${savedCustomer.phone || ''}" required>
                                </div>

                                <div>
                                    <label class="checkout-label">Email (nhận hóa đơn & tra cứu)</label>
                                    <input type="email" id="checkout-email" class="checkout-input" 
                                           placeholder="email@example.com" 
                                           value="${savedCustomer.email || ''}">
                                </div>

                                <div class="form-group-3col">
                                    <div>
                                        <label class="checkout-label">Tỉnh / Thành phố <span class="required">*</span></label>
                                        <select id="checkout-city" class="checkout-select" required onchange="Storefront.handleCityChange(this.value)">
                                            <option value="">-- Chọn Tỉnh/TP --</option>
                                            <option value="Hà Nội" ${savedCustomer.city === 'Hà Nội' ? 'selected' : ''}>Hà Nội</option>
                                            <option value="TP. Hồ Chí Minh" ${savedCustomer.city === 'TP. Hồ Chí Minh' ? 'selected' : ''}>TP. Hồ Chí Minh</option>
                                            <option value="Đà Nẵng" ${savedCustomer.city === 'Đà Nẵng' ? 'selected' : ''}>Đà Nẵng</option>
                                            <option value="Hải Phòng" ${savedCustomer.city === 'Hải Phòng' ? 'selected' : ''}>Hải Phòng</option>
                                            <option value="Cần Thơ" ${savedCustomer.city === 'Cần Thơ' ? 'selected' : ''}>Cần Thơ</option>
                                            <option value="Bình Dương" ${savedCustomer.city === 'Bình Dương' ? 'selected' : ''}>Bình Dương</option>
                                            <option value="Đồng Nai" ${savedCustomer.city === 'Đồng Nai' ? 'selected' : ''}>Đồng Nai</option>
                                            <option value="Quảng Ninh" ${savedCustomer.city === 'Quảng Ninh' ? 'selected' : ''}>Quảng Ninh</option>
                                            <option value="Bắc Ninh" ${savedCustomer.city === 'Bắc Ninh' ? 'selected' : ''}>Bắc Ninh</option>
                                            <option value="Khánh Hòa" ${savedCustomer.city === 'Khánh Hòa' ? 'selected' : ''}>Khánh Hòa</option>
                                            <option value="Lâm Đồng" ${savedCustomer.city === 'Lâm Đồng' ? 'selected' : ''}>Lâm Đồng</option>
                                            <option value="Thừa Thiên Huế" ${savedCustomer.city === 'Thừa Thiên Huế' ? 'selected' : ''}>Thừa Thiên Huế</option>
                                            <option value="Nghệ An" ${savedCustomer.city === 'Nghệ An' ? 'selected' : ''}>Nghệ An</option>
                                            <option value="Thanh Hóa" ${savedCustomer.city === 'Thanh Hóa' ? 'selected' : ''}>Thanh Hóa</option>
                                            <option value="Khác">Tỉnh thành khác</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="checkout-label">Quận / Huyện <span class="required">*</span></label>
                                        <input type="text" id="checkout-district" class="checkout-input" 
                                               placeholder="Quận / Huyện" 
                                               value="${savedCustomer.district || ''}" required>
                                    </div>
                                    <div>
                                        <label class="checkout-label">Phường / Xã <span class="required">*</span></label>
                                        <input type="text" id="checkout-ward" class="checkout-input" 
                                               placeholder="Phường / Xã" 
                                               value="${savedCustomer.ward || ''}" required>
                                    </div>
                                </div>

                                <div class="form-group-full">
                                    <label class="checkout-label">Địa chỉ cụ thể (Số nhà, tên ngõ/đường, tòa nhà) <span class="required">*</span></label>
                                    <input type="text" id="checkout-address-detail" class="checkout-input" 
                                           placeholder="Số nhà, tên ngõ, tên đường..." 
                                           value="${savedCustomer.addressDetail || ''}" required>
                                </div>

                                <div class="form-group-full">
                                    <label class="checkout-label">Ghi chú giao hàng (tùy chọn)</label>
                                    <textarea id="checkout-notes" class="checkout-textarea" 
                                              placeholder="Ví dụ: Giao giờ hành chính, gọi trước 15 phút, gửi lễ tân...">${savedCustomer.note || ''}</textarea>
                                </div>

                                <div class="form-group-full" style="display:flex; align-items:center; gap:8px; margin-top:4px;">
                                    <input type="checkbox" id="checkout-save-info" style="accent-color:var(--store-gold); width:16px; height:16px; cursor:pointer;" checked>
                                    <label for="checkout-save-info" style="font-size:0.82rem; color:var(--text-secondary); cursor:pointer;">
                                        Lưu thông tin giao hàng này cho lần mua sau
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 2: SHIPPING METHODS -->
                        <div class="checkout-section-card">
                            <div class="checkout-section-header">
                                <h2 class="checkout-section-title">
                                    <span class="checkout-section-title-num">2</span>
                                    Phương Thức Vận Chuyển
                                </h2>
                                <span class="checkout-section-badge">Toàn Quốc</span>
                            </div>

                            <div class="method-choice-list" id="shipping-methods-container">
                                <!-- Populated dynamically by updateCheckoutSummary -->
                            </div>
                        </div>

                        <!-- SECTION 3: PAYMENT METHODS -->
                        <div class="checkout-section-card">
                            <div class="checkout-section-header">
                                <h2 class="checkout-section-title">
                                    <span class="checkout-section-title-num">3</span>
                                    Phương Thức Thanh Toán
                                </h2>
                                <span class="checkout-section-badge">Bảo Mật SSL</span>
                            </div>

                            <div class="method-choice-list">
                                <!-- VietQR -->
                                <label class="method-choice-card ${this.state.checkoutPayment === 'QR' ? 'active' : ''}" 
                                       onclick="Storefront.changeCheckoutPayment('QR')">
                                    <input type="radio" name="checkoutPayment" value="QR" class="method-radio-input" 
                                           ${this.state.checkoutPayment === 'QR' ? 'checked' : ''}>
                                    <div class="method-choice-content">
                                        <div class="method-choice-title">
                                            <span>Chuyển khoản QR Ngân hàng (VietQR)</span>
                                            <span class="method-badge-rec">Khuyên Dùng</span>
                                        </div>
                                        <p class="method-choice-desc">
                                            Quét mã VietQR tự động qua app bất kỳ ngân hàng nào (Vietcombank, MB, Techcombank, ACB...). Hệ thống tự xác nhận đơn tức thì.
                                        </p>
                                        <div class="payment-details-panel">
                                            <strong>Thông tin thụ hưởng chính thức VAST STUDIO:</strong><br>
                                            • Ngân hàng: <strong>Vietcombank</strong> (Ngoại thương VN)<br>
                                            • Số tài khoản: <strong style="font-family:monospace; color:var(--store-gold); font-size:0.95rem;">9988 7766 5500</strong><br>
                                            • Chủ tài khoản: <strong>CONG TY TNHH VAST STUDIO VIETNAM</strong><br>
                                            <em>(Mã QR chuyển tiền tự động kèm nội dung chính xác sẽ hiển thị ngay sau khi bấm Xác Nhận Đặt Hàng)</em>
                                        </div>
                                    </div>
                                </label>

                                <!-- COD -->
                                <label class="method-choice-card ${this.state.checkoutPayment === 'COD' ? 'active' : ''}" 
                                       onclick="Storefront.changeCheckoutPayment('COD')">
                                    <input type="radio" name="checkoutPayment" value="COD" class="method-radio-input" 
                                           ${this.state.checkoutPayment === 'COD' ? 'checked' : ''}>
                                    <div class="method-choice-content">
                                        <div class="method-choice-title">
                                            <span>COD — Thanh toán khi nhận hàng</span>
                                        </div>
                                        <p class="method-choice-desc">
                                            Thanh toán tiền mặt cho shipper khi nhận hàng. Quý khách được quyền đồng kiểm sản phẩm trước khi thanh toán.
                                        </p>
                                    </div>
                                </label>

                                <!-- MOMO -->
                                <label class="method-choice-card ${this.state.checkoutPayment === 'MOMO' ? 'active' : ''}" 
                                       onclick="Storefront.changeCheckoutPayment('MOMO')">
                                    <input type="radio" name="checkoutPayment" value="MOMO" class="method-radio-input" 
                                           ${this.state.checkoutPayment === 'MOMO' ? 'checked' : ''}>
                                    <div class="method-choice-content">
                                        <div class="method-choice-title">
                                            <span>Ví điện tử MoMo</span>
                                        </div>
                                        <p class="method-choice-desc">
                                            Quét mã MoMo QR hoặc thanh toán nhanh qua ứng dụng MoMo trên điện thoại.
                                        </p>
                                    </div>
                                </label>

                                <!-- CREDIT CARD -->
                                <label class="method-choice-card ${this.state.checkoutPayment === 'CARD' ? 'active' : ''}" 
                                       onclick="Storefront.changeCheckoutPayment('CARD')">
                                    <input type="radio" name="checkoutPayment" value="CARD" class="method-radio-input" 
                                           ${this.state.checkoutPayment === 'CARD' ? 'checked' : ''}>
                                    <div class="method-choice-content">
                                        <div class="method-choice-title">
                                            <span>Thẻ Quốc tế (Visa / Master / JCB)</span>
                                        </div>
                                        <p class="method-choice-desc">
                                            Cổng thanh toán thẻ quốc tế mã hóa chuẩn PCI-DSS 256-bit an toàn tuyệt đối.
                                        </p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- SECTION 4: TRUST & BENEFITS -->
                        <div class="checkout-section-card" style="padding:18px 24px;">
                            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:16px;">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div style="color:var(--store-emerald);">${window.ICONS?.shieldCheck || '🛡️'}</div>
                                    <div style="font-size:0.8rem;">
                                        <strong>100% Chính hãng</strong><br>
                                        <span style="color:var(--text-muted);">Form dáng Boxy độc quyền</span>
                                    </div>
                                </div>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div style="color:var(--store-gold);">${window.ICONS?.rotateCcw || '🔄'}</div>
                                    <div style="font-size:0.8rem;">
                                        <strong>Đổi trả 30 ngày</strong><br>
                                        <span style="color:var(--text-muted);">Đổi size tận nơi miễn phí</span>
                                    </div>
                                </div>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div style="color:var(--status-info-text);">${window.ICONS?.truck || '🚚'}</div>
                                    <div style="font-size:0.8rem;">
                                        <strong>Đồng kiểm khi nhận</strong><br>
                                        <span style="color:var(--text-muted);">Ưng ý mới thanh toán</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: ORDER SUMMARY (STICKY) -->
                    <div class="checkout-right-col checkout-summary-wrap">
                        <div class="checkout-summary-card">
                            <div class="checkout-summary-header">
                                <h3 class="checkout-summary-title">Đơn Hàng Của Bạn</h3>
                                <span class="checkout-section-badge" id="checkout-cart-count-badge">0 sản phẩm</span>
                            </div>

                            <!-- Cart Items List -->
                            <div class="summary-items-list" id="checkout-items-list">
                                <!-- Populated dynamically -->
                            </div>

                            <!-- Coupon Box -->
                            <div class="checkout-coupon-card">
                                <div class="coupon-input-group">
                                    <input type="text" id="checkout-coupon-input" class="coupon-input" 
                                           placeholder="Mã giảm giá (Coupon)..." 
                                           value="${this.state.appliedCoupon ? this.state.appliedCoupon.code : ''}">
                                    <button type="button" class="coupon-apply-btn" onclick="Storefront.applyCheckoutCoupon()">
                                        Áp Dụng
                                    </button>
                                </div>

                                <div id="checkout-active-coupon-badge">
                                    <!-- Rendered if coupon applied -->
                                </div>

                                <div class="coupon-suggestions">
                                    <span style="color:var(--text-muted);">Gợi ý mã:</span>
                                    <button type="button" class="coupon-pill-btn" onclick="Storefront.applySuggestedCoupon('VASTWELCOME')">
                                        VASTWELCOME (-100K)
                                    </button>
                                    <button type="button" class="coupon-pill-btn" onclick="Storefront.applySuggestedCoupon('VAST50')">
                                        VAST50 (-50K)
                                    </button>
                                </div>
                            </div>

                            <!-- Price Breakdown -->
                            <div class="checkout-totals-block">
                                <div class="checkout-totals-row">
                                    <span>Tạm tính hàng:</span>
                                    <strong id="checkout-calc-subtotal" style="color:var(--text-primary);">0 ₫</strong>
                                </div>
                                <div class="checkout-totals-row">
                                    <span>Phí vận chuyển:</span>
                                    <strong id="checkout-calc-shipping" style="color:var(--text-primary);">0 ₫</strong>
                                </div>
                                <div class="checkout-totals-row discount-row" id="checkout-calc-discount-row" style="display:none;">
                                    <span>Giảm giá voucher:</span>
                                    <strong id="checkout-calc-discount">-0 ₫</strong>
                                </div>
                                <div class="checkout-totals-row grand-total">
                                    <div>
                                        <span style="display:block;">Tổng thanh toán:</span>
                                        <small style="font-size:0.75rem; color:var(--text-muted); font-weight:normal;">(Đã bao gồm thuế & phí)</small>
                                    </div>
                                    <strong class="checkout-grand-price" id="checkout-calc-total">0 ₫</strong>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn-submit-order" id="btn-submit-checkout">
                                <span>Xác Nhận Đặt Hàng</span>
                                <span data-icon="arrowRight"></span>
                            </button>

                            <div class="checkout-security-notice">
                                <span data-icon="shieldCheck"></span>
                                <span>Thông tin đặt hàng được bảo mật và mã hóa an toàn 100%</span>
                            </div>
                        </div>
                    </div>
                </form>
            `;

            this.updateCheckoutSummary();

            // Refresh icons
            if (window.ICONS) {
                container.querySelectorAll('[data-icon]').forEach(el => {
                    const iconName = el.getAttribute('data-icon');
                    if (window.ICONS[iconName]) el.innerHTML = window.ICONS[iconName];
                });
            }
        },

        renderCheckoutEmpty(container) {
            container.innerHTML = `
                <div class="checkout-empty-box">
                    <div class="empty-cart-icon-wrap">
                        <span data-icon="shoppingBag" style="font-size:36px;"></span>
                    </div>
                    <h2 style="font-family:var(--store-font-heading); margin:0 0 10px; font-size:1.6rem;">Giỏ Hàng Của Bạn Đang Trống</h2>
                    <p style="color:var(--text-secondary); margin:0 0 28px; font-size:0.95rem; max-width:440px; margin-inline:auto;">
                        Bạn chưa chọn sản phẩm nào vào giỏ hàng. Hãy khám phá bộ sưu tập streetwear mới nhất của VAST STUDIO để chọn những món đồ ưng ý nhé!
                    </p>
                    <a href="products.php" class="btn-hero-primary" style="display:inline-flex; align-items:center; gap:8px; padding:14px 28px; text-decoration:none;">
                        <span>Khám Phá Sản Phẩm Ngay</span>
                        <span data-icon="arrowRight"></span>
                    </a>
                </div>
            `;
            if (window.ICONS) {
                container.querySelectorAll('[data-icon]').forEach(el => {
                    const iconName = el.getAttribute('data-icon');
                    if (window.ICONS[iconName]) el.innerHTML = window.ICONS[iconName];
                });
            }
        },

        updateCheckoutSummary() {
            if (!window.StoreModel) return;
            const cart = window.StoreModel.getCart();
            const subtotal = window.StoreModel.getCartSubtotal();
            const freeShipThreshold = 500000;

            // 1. Calculate shipping fee
            let shippingFee = 0;
            if (this.state.checkoutShipping === 'express') {
                shippingFee = 50000;
            } else {
                // standard
                shippingFee = (subtotal >= freeShipThreshold || subtotal === 0) ? 0 : 30000;
            }

            // 2. Calculate coupon discount
            let discount = 0;
            if (this.state.appliedCoupon) {
                const val = window.StoreModel.validateCoupon(this.state.appliedCoupon.code, subtotal);
                if (val.valid) {
                    discount = val.discount;
                } else {
                    this.state.appliedCoupon = null;
                }
            }

            const grandTotal = Math.max(0, subtotal - discount + shippingFee);

            // 3. Update Shipping cards in DOM
            const shippingContainer = document.getElementById('shipping-methods-container');
            if (shippingContainer) {
                const isFree = subtotal >= freeShipThreshold;
                shippingContainer.innerHTML = `
                    <label class="method-choice-card ${this.state.checkoutShipping === 'standard' ? 'active' : ''}" 
                           onclick="Storefront.changeCheckoutShipping('standard')">
                        <input type="radio" name="checkoutShipping" value="standard" class="method-radio-input" 
                               ${this.state.checkoutShipping === 'standard' ? 'checked' : ''}>
                        <div class="method-choice-content">
                            <div class="method-choice-title">
                                <span>Giao hàng Tiêu Chuẩn (2 - 3 ngày)</span>
                                <div>
                                    ${isFree ? `<span class="method-badge-free">FREESHIP</span>` : `<span style="font-weight:700;">30.000 ₫</span>`}
                                </div>
                            </div>
                            <p class="method-choice-desc">
                                ${isFree ? 'Đơn hàng từ 500.000₫ đủ điều kiện Miễn phí vận chuyển toàn quốc.' : 'Miễn phí cho đơn từ 500.000₫ (mua thêm để được freeship).'}
                            </p>
                        </div>
                    </label>

                    <label class="method-choice-card ${this.state.checkoutShipping === 'express' ? 'active' : ''}" 
                           onclick="Storefront.changeCheckoutShipping('express')">
                        <input type="radio" name="checkoutShipping" value="express" class="method-radio-input" 
                               ${this.state.checkoutShipping === 'express' ? 'checked' : ''}>
                        <div class="method-choice-content">
                            <div class="method-choice-title">
                                <span>Giao hàng Hỏa Tốc Express (2 - 4 giờ)</span>
                                <span style="font-weight:700;">50.000 ₫</span>
                            </div>
                            <p class="method-choice-desc">
                                Áp dụng nội thành Hà Nội & TP.HCM. Shipper giao ngay trong khung giờ bạn chọn.
                            </p>
                        </div>
                    </label>
                `;
            }

            // 4. Update Items list in DOM
            const itemsListEl = document.getElementById('checkout-items-list');
            const countBadge = document.getElementById('checkout-cart-count-badge');
            if (countBadge) {
                const totalQty = window.StoreModel.getCartCount();
                countBadge.textContent = `${totalQty} sản phẩm`;
            }

            if (itemsListEl) {
                itemsListEl.innerHTML = cart.map((item, idx) => `
                    <div class="summary-item-row">
                        <img src="${item.image}" alt="${item.name}" class="summary-item-thumb">
                        <div class="summary-item-info">
                            <div class="summary-item-name" title="${item.name}">${item.name}</div>
                            <div class="summary-item-meta">Size: <strong>${item.size}</strong> • Màu: <strong>${item.color}</strong></div>
                            <div class="summary-item-controls">
                                <div class="summary-qty-box">
                                    <button type="button" class="summary-qty-btn" onclick="Storefront.updateCheckoutQty(${idx}, ${item.quantity - 1})">-</button>
                                    <span class="summary-qty-val">${item.quantity}</span>
                                    <button type="button" class="summary-qty-btn" onclick="Storefront.updateCheckoutQty(${idx}, ${item.quantity + 1})">+</button>
                                </div>
                                <button type="button" class="summary-item-delete" onclick="Storefront.removeCheckoutItem(${idx})" title="Xóa món này">
                                    ✕
                                </button>
                            </div>
                        </div>
                        <div class="summary-item-price">
                            ${this.formatMoney(item.price * item.quantity)}
                        </div>
                    </div>
                `).join('');
            }

            // 5. Update Active Coupon Badge
            const couponBadgeContainer = document.getElementById('checkout-active-coupon-badge');
            if (couponBadgeContainer) {
                if (this.state.appliedCoupon && discount > 0) {
                    couponBadgeContainer.innerHTML = `
                        <div class="coupon-active-badge">
                            <span>Đang áp dụng mã <strong>${this.state.appliedCoupon.code}</strong> (-${this.formatMoney(discount)})</span>
                            <button type="button" class="coupon-remove-btn" onclick="Storefront.removeCheckoutCoupon()">Gỡ bỏ</button>
                        </div>
                    `;
                } else {
                    couponBadgeContainer.innerHTML = '';
                }
            }

            // 6. Update Price Breakdown
            const subtotalEl = document.getElementById('checkout-calc-subtotal');
            const shippingEl = document.getElementById('checkout-calc-shipping');
            const discountRowEl = document.getElementById('checkout-calc-discount-row');
            const discountEl = document.getElementById('checkout-calc-discount');
            const totalEl = document.getElementById('checkout-calc-total');

            if (subtotalEl) subtotalEl.textContent = this.formatMoney(subtotal);
            if (shippingEl) shippingEl.textContent = shippingFee === 0 ? 'Miễn phí' : this.formatMoney(shippingFee);
            if (discountRowEl) {
                discountRowEl.style.display = discount > 0 ? 'flex' : 'none';
                if (discountEl) discountEl.textContent = `-${this.formatMoney(discount)}`;
            }
            if (totalEl) totalEl.textContent = this.formatMoney(grandTotal);
        },

        changeCheckoutShipping(method) {
            this.state.checkoutShipping = method;
            this.updateCheckoutSummary();
        },

        changeCheckoutPayment(method) {
            this.state.checkoutPayment = method;
            document.querySelectorAll('input[name="checkoutPayment"]').forEach(radio => {
                const card = radio.closest('.method-choice-card');
                if (radio.value === method) {
                    radio.checked = true;
                    if (card) card.classList.add('active');
                } else {
                    radio.checked = false;
                    if (card) card.classList.remove('active');
                }
            });
        },

        updateCheckoutQty(idx, newQty) {
            if (!window.StoreModel) return;
            window.StoreModel.updateCartQuantity(idx, newQty);
            const cart = window.StoreModel.getCart();
            if (cart.length === 0) {
                this.initCheckoutPage();
            } else {
                this.updateCheckoutSummary();
            }
        },

        removeCheckoutItem(idx) {
            if (!window.StoreModel) return;
            window.StoreModel.removeFromCart(idx);
            this.showToast('Đã xóa sản phẩm khỏi đơn hàng', 'info');
            const cart = window.StoreModel.getCart();
            if (cart.length === 0) {
                this.initCheckoutPage();
            } else {
                this.updateCheckoutSummary();
            }
        },

        applyCheckoutCoupon() {
            const input = document.getElementById('checkout-coupon-input');
            if (!input || !window.StoreModel) return;
            const code = input.value.trim();
            this.applyCouponByCode(code);
        },

        applySuggestedCoupon(code) {
            const input = document.getElementById('checkout-coupon-input');
            if (input) input.value = code;
            this.applyCouponByCode(code);
        },

        async applyCouponByCode(code) {
            if (!code) {
                this.showToast('Vui lòng nhập mã giảm giá', 'error');
                return;
            }
            const subtotal = window.StoreModel.getCartSubtotal();
            try {
                const response = await fetch(`api.php?action=coupon_validate&code=${encodeURIComponent(code)}&subtotal=${encodeURIComponent(subtotal)}`);
                const res = await response.json();
                if (!res.valid) {
                    this.showToast(res.message || 'Mã giảm giá không hợp lệ', 'error');
                    return;
                }
                this.state.appliedCoupon = res.coupon;
                this.showToast(`Áp dụng mã ${res.coupon.code} thành công (-${this.formatMoney(res.discount)})!`, 'success');
                this.updateCheckoutSummary();
            } catch (err) {
                const res = window.StoreModel.validateCoupon(code, subtotal);
                if (!res.valid) {
                    this.showToast(res.message, 'error');
                    return;
                }
                this.state.appliedCoupon = res.coupon;
                this.showToast(`Áp dụng mã ${res.coupon.code} thành công (-${this.formatMoney(res.discount)})!`, 'success');
                this.updateCheckoutSummary();
            }
        },

        removeCheckoutCoupon() {
            this.state.appliedCoupon = null;
            const input = document.getElementById('checkout-coupon-input');
            if (input) input.value = '';
            this.showToast('Đã gỡ mã giảm giá', 'info');
            this.updateCheckoutSummary();
        },

        handleCityChange(city) {
            // Optional hook for city specific adjustments
        },

        async submitCheckoutPage(e) {
            if (e) e.preventDefault();
            if (!window.StoreModel) return;

            const name = document.getElementById('checkout-fullname')?.value.trim();
            const phone = document.getElementById('checkout-phone')?.value.trim();
            const email = document.getElementById('checkout-email')?.value.trim();
            const city = document.getElementById('checkout-city')?.value.trim();
            const district = document.getElementById('checkout-district')?.value.trim();
            const ward = document.getElementById('checkout-ward')?.value.trim();
            const addressDetail = document.getElementById('checkout-address-detail')?.value.trim();
            const note = document.getElementById('checkout-notes')?.value.trim();
            const saveInfo = document.getElementById('checkout-save-info')?.checked;

            if (!name || !phone || !city || !district || !ward || !addressDetail) {
                this.showToast('Vui lòng điền đầy đủ các thông tin bắt buộc (*)!', 'error');
                return;
            }

            const cleanPhone = phone.replace(/[\s.-]/g, '');
            if (!/^0[0-9]{9,10}$/.test(cleanPhone)) {
                this.showToast('Số điện thoại không hợp lệ (cần 10–11 chữ số bắt đầu bằng 0)!', 'error');
                document.getElementById('checkout-phone')?.focus();
                return;
            }

            const cart = window.StoreModel.getCart();
            if (cart.length === 0) {
                this.showToast('Giỏ hàng trống!', 'error');
                this.initCheckoutPage();
                return;
            }

            const fullAddress = `${addressDetail}, Phường/Xã ${ward}, Quận/Huyện ${district}, ${city}`;
            const shippingMethodName = this.state.checkoutShipping === 'express'
                ? 'Giao Hỏa Tốc Express (2-4 giờ)'
                : 'Giao Tiêu Chuẩn (2-3 ngày)';
            const paymentCode = (this.state.checkoutPayment || 'COD').toLowerCase();

            const submitBtn = document.getElementById('btn-submit-checkout');
            const origBtnHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Đang tạo đơn hàng...';
            }

            try {
                const response = await fetch('api.php?action=checkout_create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        customerName: name,
                        customerPhone: cleanPhone,
                        customerEmail: email,
                        shippingAddress: fullAddress,
                        shippingType: this.state.checkoutShipping || 'standard',
                        shippingMethod: shippingMethodName,
                        paymentCode: paymentCode,
                        couponCode: this.state.appliedCoupon ? this.state.appliedCoupon.code : '',
                        note: note,
                        items: cart
                    })
                });
                const payload = await response.json();
                if (!response.ok || !payload.ok || !payload.order) {
                    throw new Error(payload.message || 'Không thể tạo đơn hàng.');
                }

                if (saveInfo) {
                    localStorage.setItem('vast_customer_info', JSON.stringify({
                        name, phone: cleanPhone, email, city, district, ward, addressDetail, note
                    }));
                }

                window.StoreModel.clearCart();
                this.state.appliedCoupon = null;

                this.renderCheckoutSuccess(payload.order, fullAddress);
                this.showToast(`Đặt hàng thành công! Mã đơn: ${payload.order.id}`, 'success');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } catch (err) {
                this.showToast(err.message || 'Đặt hàng thất bại, vui lòng thử lại.', 'error');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = origBtnHtml;
                }
            }
        },

        renderCheckoutSuccess(order, fullAddress) {
            const container = document.getElementById('checkout-main-content');
            if (!container) return;

            // Update Steps indicator
            const stepCheckout = document.getElementById('step-indicator-checkout');
            const stepDividerSuccess = document.getElementById('step-divider-success');
            const stepSuccess = document.getElementById('step-indicator-success');

            if (stepCheckout) {
                stepCheckout.classList.remove('active');
                stepCheckout.classList.add('done');
                const numEl = stepCheckout.querySelector('.checkout-step-num');
                if (numEl) numEl.innerHTML = window.ICONS?.check || '✓';
            }
            if (stepDividerSuccess) stepDividerSuccess.classList.add('active');
            if (stepSuccess) {
                stepSuccess.classList.add('active');
            }

            const isQR = (order.paymentMethod || '').includes('QR');
            // Dynamic VietQR generator URL
            const vietqrUrl = `https://img.vietqr.io/image/vietcombank-998877665500-compact2.png?amount=${order.total}&addInfo=${encodeURIComponent(order.id + ' ' + order.customerPhone)}&accountName=CONG%20TY%20TNHH%20VAST%20STUDIO%20VIETNAM`;

            container.innerHTML = `
                <div class="order-success-screen">
                    <div class="success-icon-badge">✓</div>
                    <span class="section-label" style="margin-bottom:4px;">Cảm ơn bạn đã mua hàng</span>
                    <h1 style="font-family:var(--store-font-heading); font-size:2rem; font-weight:800; margin:4px 0 12px; text-transform:uppercase;">
                        ĐẶT HÀNG THÀNH CÔNG!
                    </h1>
                    <p style="color:var(--text-secondary); margin:0 auto 16px; font-size:0.95rem; max-width:540px;">
                        Đơn hàng của bạn đã được tiếp nhận vào hệ thống quản lý VAST STUDIO. Đội ngũ CSKH sẽ liên hệ xác nhận và tiến hành đóng gói ngay.
                    </p>

                    <div>
                        <div class="success-order-id-box">
                            <span>Mã đơn hàng:</span>
                            <strong style="color:var(--store-gold);">${order.id}</strong>
                            <button type="button" class="btn-copy-order-id" onclick="Storefront.copyToClipboard('${order.id}', 'Đã sao chép mã đơn hàng!')" title="Sao chép mã đơn">
                                📋
                            </button>
                        </div>
                    </div>

                    ${isQR ? `
                        <!-- VIETQR AUTOMATIC PAYMENT CARD -->
                        <div class="vietqr-card">
                            <div class="vietqr-image-wrap">
                                <img src="${vietqrUrl}" alt="VietQR VAST STUDIO">
                                <div style="font-size:0.75rem; color:var(--text-muted); margin-top:8px;">
                                    Quét bằng app ngân hàng bất kỳ
                                </div>
                            </div>
                            <div>
                                <h3 style="font-family:var(--store-font-heading); margin:0 0 8px; font-size:1.15rem; color:var(--text-primary);">
                                    Hướng Dẫn Thanh Toán VietQR Tự Động
                                </h3>
                                <p style="font-size:0.85rem; color:var(--text-secondary); margin:0 0 14px;">
                                    Mở ứng dụng ngân hàng hoặc ví điện tử bất kỳ, chọn <strong>Quét mã QR</strong> để chuyển tiền với thông tin chính xác tuyệt đối.
                                </p>
                                <div class="bank-info-grid">
                                    <div class="bank-info-row">
                                        <span style="color:var(--text-muted);">Ngân hàng:</span>
                                        <strong>Vietcombank (Ngoại thương VN)</strong>
                                    </div>
                                    <div class="bank-info-row">
                                        <span style="color:var(--text-muted);">Số tài khoản:</span>
                                        <div style="display:flex; align-items:center; gap:6px;">
                                            <strong style="font-family:monospace; color:var(--store-gold); font-size:1.05rem;">9988 7766 5500</strong>
                                            <button type="button" class="coupon-pill-btn" style="padding:2px 6px;" onclick="Storefront.copyToClipboard('998877665500', 'Đã chép số tài khoản!')">Chép STK</button>
                                        </div>
                                    </div>
                                    <div class="bank-info-row">
                                        <span style="color:var(--text-muted);">Chủ tài khoản:</span>
                                        <strong>CONG TY TNHH VAST STUDIO VIETNAM</strong>
                                    </div>
                                    <div class="bank-info-row">
                                        <span style="color:var(--text-muted);">Số tiền chuyển:</span>
                                        <strong style="font-family:var(--store-font-heading); color:var(--store-gold); font-size:1.1rem;">${this.formatMoney(order.total)}</strong>
                                    </div>
                                    <div class="bank-info-row">
                                        <span style="color:var(--text-muted);">Nội dung chuyển:</span>
                                        <div style="display:flex; align-items:center; gap:6px;">
                                            <strong style="font-family:monospace; color:var(--text-primary); font-size:0.95rem;">${order.id} ${order.customerPhone}</strong>
                                            <button type="button" class="coupon-pill-btn" style="padding:2px 6px;" onclick="Storefront.copyToClipboard('${order.id} ${order.customerPhone}', 'Đã chép nội dung chuyển tiền!')">Chép ND</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ` : ''}

                    <!-- ORDER OVERVIEW DETAILS -->
                    <table class="order-overview-table">
                        <tbody>
                            <tr>
                                <td>Người nhận hàng:</td>
                                <td><strong>${order.customerName}</strong> (${order.customerPhone})</td>
                            </tr>
                            ${order.customerEmail ? `
                            <tr>
                                <td>Email nhận thông báo:</td>
                                <td>${order.customerEmail}</td>
                            </tr>
                            ` : ''}
                            <tr>
                                <td>Địa chỉ giao hàng:</td>
                                <td>${order.shippingAddress || fullAddress}</td>
                            </tr>
                            <tr>
                                <td>Phương thức giao:</td>
                                <td>${order.shippingMethod || 'Giao hàng tiêu chuẩn (2-3 ngày)'}</td>
                            </tr>
                            <tr>
                                <td>Hình thức thanh toán:</td>
                                <td><strong>${order.paymentMethod}</strong> (${order.paymentStatus || 'Chờ xác nhận'})</td>
                            </tr>
                            ${order.note ? `
                            <tr>
                                <td>Ghi chú đơn hàng:</td>
                                <td><em>${order.note}</em></td>
                            </tr>
                            ` : ''}
                            <tr>
                                <td>Tổng giá trị thanh toán:</td>
                                <td>
                                    <strong style="font-family:var(--store-font-heading); font-size:1.2rem; color:var(--store-gold);">
                                        ${this.formatMoney(order.total)}
                                    </strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- MINI ITEMS RECEIPT -->
                    <div class="order-items-mini-list">
                        <h4 style="margin:0 0 10px; font-size:0.9rem; text-transform:uppercase; letter-spacing:0.02em;">Danh Sách Sản Phẩm Đặt Mua (${(order.items || []).length})</h4>
                        ${(order.items || []).map(item => `
                            <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px dashed var(--border-subtle); font-size:0.85rem;">
                                <div>
                                    <strong>${item.name}</strong> 
                                    <span style="color:var(--text-muted); font-size:0.78rem;">(Size ${item.size} • ${item.color} • x${item.quantity})</span>
                                </div>
                                <strong>${this.formatMoney(item.price * item.quantity)}</strong>
                            </div>
                        `).join('')}
                    </div>

                    <!-- ACTION BUTTONS -->
                    <div class="success-action-btns">
                        <a href="order-tracking.php?order=${order.id}" class="btn-hero-primary" style="text-decoration:none; padding:14px 24px;">
                            <span data-icon="truck"></span> Tra Cứu Tiến Độ Đơn
                        </a>
                        <button type="button" class="btn-hero-outline" onclick="window.print()" style="padding:14px 20px;">
                            <span data-icon="printer"></span> In Hóa Đơn
                        </button>
                        <a href="products.php" class="btn-hero-outline" style="text-decoration:none; padding:14px 24px;">
                            Tiếp Tục Mua Sắm
                        </a>
                        <a href="admin.php" class="btn-hero-outline" target="_blank" style="text-decoration:none; padding:14px 18px; font-size:0.85rem;" title="Xem đơn trên trang Admin">
                            <span data-icon="dashboard"></span> Quản Trị
                        </a>
                    </div>
                </div>
            `;

            if (window.ICONS) {
                container.querySelectorAll('[data-icon]').forEach(el => {
                    const iconName = el.getAttribute('data-icon');
                    if (window.ICONS[iconName]) el.innerHTML = window.ICONS[iconName];
                });
            }
        },

        copyToClipboard(text, msg) {
            navigator.clipboard.writeText(text).then(() => {
                this.showToast(msg || 'Đã sao chép vào bộ nhớ tạm!', 'success');
            }).catch(() => {
                this.showToast('Đã sao chép: ' + text, 'info');
            });
        },

        // ======================================================================
        // TRANG THÀNH VIÊN & XÁC THỰC (AUTHENTICATION & PROFILE DASHBOARD)
        // ======================================================================
        initAuthPage() {
            const container = document.getElementById('auth-main-container');
            if (!container) return;
            if (container.dataset.ssr === '1') {
                return;
            }
            if (!window.StoreModel) return;

            const currentUser = window.StoreModel.getCurrentUser();
            if (currentUser) {
                this.renderCustomerDashboard(currentUser);
            } else {
                this.renderAuthForms('login');
            }
        },

        renderAuthForms(activeTab = 'login') {
            const container = document.getElementById('auth-main-container');
            if (!container) return;

            const currentBreadcrumb = document.getElementById('auth-breadcrumb-current');
            if (currentBreadcrumb) currentBreadcrumb.textContent = 'Đăng nhập / Đăng ký';

            container.innerHTML = `
                <div class="auth-box-wrapper">
                    <div class="auth-card">
                        <div class="auth-card-header">
                            <div class="auth-brand-monogram">V</div>
                            <h1 class="auth-main-title">VAST MEMBERSHIP</h1>
                            <p class="auth-sub-title">Đặc quyền tích điểm & ưu đãi thành viên thương hiệu</p>
                        </div>

                        <!-- Tab Switcher -->
                        <div class="auth-tabs-nav">
                            <button type="button" class="auth-tab-btn ${activeTab === 'login' ? 'active' : ''}" 
                                    id="tab-btn-login" onclick="Storefront.switchAuthTab('login')">
                                Đăng Nhập
                            </button>
                            <button type="button" class="auth-tab-btn ${activeTab === 'register' ? 'active' : ''}" 
                                    id="tab-btn-register" onclick="Storefront.switchAuthTab('register')">
                                Đăng Ký Tài Khoản
                            </button>
                        </div>

                        <div class="auth-form-body">
                            <!-- TAB PANE 1: ĐĂNG NHẬP -->
                            <div class="auth-form-pane ${activeTab === 'login' ? 'active' : ''}" id="auth-pane-login">
                                <form onsubmit="Storefront.handleLoginSubmit(event)">
                                    <div class="auth-input-group">
                                        <label class="auth-label">Email hoặc Số điện thoại <span style="color:var(--store-red)">*</span></label>
                                        <div class="auth-input-wrap">
                                            <input type="text" id="login-identifier" class="auth-input" 
                                                   placeholder="Nhập email hoặc số điện thoại..." required>
                                        </div>
                                    </div>

                                    <div class="auth-input-group">
                                        <label class="auth-label">Mật khẩu <span style="color:var(--store-red)">*</span></label>
                                        <div class="auth-input-wrap">
                                            <input type="password" id="login-password" class="auth-input" 
                                                   placeholder="Nhập mật khẩu..." required>
                                            <button type="button" class="btn-toggle-eye" onclick="Storefront.togglePasswordVisibility('login-password', this)">
                                                <span data-icon="eye"></span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="auth-extra-row">
                                        <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                                            <input type="checkbox" id="login-remember" style="accent-color:var(--store-gold); cursor:pointer;" checked>
                                            <span style="color:var(--text-secondary);">Ghi nhớ đăng nhập</span>
                                        </label>
                                        <a class="auth-forgot-link" onclick="Storefront.handleForgotPassword()">Quên mật khẩu?</a>
                                    </div>

                                    <button type="submit" class="btn-auth-submit">
                                        <span>Đăng Nhập Ngay</span>
                                        <span data-icon="arrowRight"></span>
                                    </button>
                                </form>

                                <!-- Demo Accounts Quick Fill -->
                                <div class="auth-demo-card">
                                    <div style="font-size:0.78rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; margin-bottom:4px;">
                                        ⚡ Trải nghiệm nhanh với tài khoản mẫu (Click để đăng nhập):
                                    </div>
                                    <div class="demo-pills-list">
                                        <button type="button" class="demo-account-btn" onclick="Storefront.fillDemoAccount('hoangminh.design@gmail.com', '123456')">
                                            <div>
                                                <strong>Nguyễn Hoàng Minh</strong>
                                                <div style="font-size:0.72rem; color:var(--text-muted);">hoangminh.design@gmail.com</div>
                                            </div>
                                            <span class="method-badge-rec">VIP Black Card</span>
                                        </button>
                                        <button type="button" class="demo-account-btn" onclick="Storefront.fillDemoAccount('thaoly.tran@outlook.com', '123456')">
                                            <div>
                                                <strong>Trần Thảo Ly</strong>
                                                <div style="font-size:0.72rem; color:var(--text-muted);">thaoly.tran@outlook.com</div>
                                            </div>
                                            <span class="method-badge-rec" style="background:rgba(217, 119, 6, 0.1);">Gold Member</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="auth-divider-line">
                                    <span>Hoặc đăng nhập với</span>
                                </div>

                                <div class="social-auth-grid">
                                    <button type="button" class="btn-social-auth" onclick="Storefront.handleSocialLogin('Google')">
                                        <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#EA4335" d="M12 5c1.54 0 2.94.55 4.04 1.45l3.03-3.03C17.24 1.7 14.77 1 12 1 7.42 1 3.52 3.55 1.57 7.28l3.66 2.84C6.1 7.22 8.8 5 12 5z"/><path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.47c-.29 1.48-1.14 2.73-2.4 3.58l3.7 2.87c2.16-2 3.72-4.94 3.72-8.69z"/><path fill="#FBBC05" d="M5.23 14.88c-.25-.72-.38-1.49-.38-2.28s.13-1.56.38-2.28L1.57 7.28C.57 9.17 0 11.28 0 12.6s.57 3.43 1.57 5.32l3.66-2.84z"/><path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.7-2.87c-1.08.72-2.45 1.16-4.23 1.16-3.2 0-5.9-2.22-6.77-5.12L1.57 17.1C3.52 20.83 7.42 24 12 24z"/></svg>
                                        Google
                                    </button>
                                    <button type="button" class="btn-social-auth" onclick="Storefront.handleSocialLogin('Apple')">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.37c.62-.75 1.04-1.8 0.93-2.85-.9.04-2 .6-2.65 1.35-.58.67-1.09 1.74-.95 2.78 1.01.08 2.05-.53 2.67-1.28"/></svg>
                                        Apple ID
                                    </button>
                                </div>
                            </div>

                            <!-- TAB PANE 2: ĐĂNG KÝ -->
                            <div class="auth-form-pane ${activeTab === 'register' ? 'active' : ''}" id="auth-pane-register">
                                <form onsubmit="Storefront.handleRegisterSubmit(event)">
                                    <div class="membership-incentive-box">
                                        <span style="font-size:1.4rem;">🎁</span>
                                        <div>
                                            <strong>Ưu đãi thành viên mới:</strong><br>
                                            Tặng ngay voucher <strong>VASTWELCOME</strong> giảm 100K cho đơn hàng đầu tiên!
                                        </div>
                                    </div>

                                    <div class="auth-input-group">
                                        <label class="auth-label">Họ và tên của bạn <span style="color:var(--store-red)">*</span></label>
                                        <div class="auth-input-wrap">
                                            <input type="text" id="reg-name" class="auth-input" 
                                                   placeholder="Ví dụ: Lê Hoàng Nam" required>
                                        </div>
                                    </div>

                                    <div class="auth-input-group">
                                        <label class="auth-label">Số điện thoại <span style="color:var(--store-red)">*</span></label>
                                        <div class="auth-input-wrap">
                                            <input type="tel" id="reg-phone" class="auth-input" 
                                                   placeholder="Ví dụ: 0912 345 678" required>
                                        </div>
                                    </div>

                                    <div class="auth-input-group">
                                        <label class="auth-label">Địa chỉ Email <span style="color:var(--store-red)">*</span></label>
                                        <div class="auth-input-wrap">
                                            <input type="email" id="reg-email" class="auth-input" 
                                                   placeholder="email@example.com" required>
                                        </div>
                                    </div>

                                    <div class="auth-input-group">
                                        <label class="auth-label">Mật khẩu (tối thiểu 6 ký tự) <span style="color:var(--store-red)">*</span></label>
                                        <div class="auth-input-wrap">
                                            <input type="password" id="reg-password" class="auth-input" 
                                                   placeholder="Tạo mật khẩu an toàn..." required minlength="6">
                                            <button type="button" class="btn-toggle-eye" onclick="Storefront.togglePasswordVisibility('reg-password', this)">
                                                <span data-icon="eye"></span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="auth-input-group">
                                        <label class="auth-label">Nhập lại mật khẩu <span style="color:var(--store-red)">*</span></label>
                                        <div class="auth-input-wrap">
                                            <input type="password" id="reg-confirm-password" class="auth-input" 
                                                   placeholder="Xác nhận lại mật khẩu..." required minlength="6">
                                            <button type="button" class="btn-toggle-eye" onclick="Storefront.togglePasswordVisibility('reg-confirm-password', this)">
                                                <span data-icon="eye"></span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="auth-input-group" style="margin-bottom:24px;">
                                        <label style="display:flex; align-items:flex-start; gap:8px; font-size:0.8rem; color:var(--text-secondary); cursor:pointer;">
                                            <input type="checkbox" id="reg-terms" style="margin-top:2px; accent-color:var(--store-gold); cursor:pointer;" required>
                                            <span>Tôi đồng ý với <a href="about.php" target="_blank" style="color:var(--store-gold); font-weight:600;">Điều khoản thành viên</a> & <a href="about.php" target="_blank" style="color:var(--store-gold); font-weight:600;">Chính sách bảo mật</a> của VAST STUDIO.</span>
                                        </label>
                                    </div>

                                    <button type="submit" class="btn-auth-submit">
                                        <span>Tạo Tài Khoản VAST VIP</span>
                                        <span data-icon="arrowRight"></span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            if (window.ICONS) {
                container.querySelectorAll('[data-icon]').forEach(el => {
                    const iconName = el.getAttribute('data-icon');
                    if (window.ICONS[iconName]) el.innerHTML = window.ICONS[iconName];
                });
            }
        },

        switchAuthTab(tab) {
            const btnLogin = document.getElementById('tab-btn-login');
            const btnReg = document.getElementById('tab-btn-register');
            const paneLogin = document.getElementById('auth-pane-login');
            const paneReg = document.getElementById('auth-pane-register');

            if (tab === 'login') {
                btnLogin?.classList.add('active');
                btnReg?.classList.remove('active');
                paneLogin?.classList.add('active');
                paneReg?.classList.remove('active');
            } else {
                btnReg?.classList.add('active');
                btnLogin?.classList.remove('active');
                paneReg?.classList.add('active');
                paneLogin?.classList.remove('active');
            }
        },

        togglePasswordVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            if (btn) {
                btn.style.color = isPassword ? 'var(--store-gold)' : 'var(--text-muted)';
            }
        },

        fillDemoAccount(identifier, pwd) {
            this.switchAuthTab('login');
            const idInput = document.getElementById('login-identifier');
            const pwdInput = document.getElementById('login-password');
            if (idInput && pwdInput) {
                idInput.value = identifier;
                pwdInput.value = pwd;
                this.showToast(`Đã tự động điền tài khoản: ${identifier}`, 'info');
                // Auto trigger login
                const fakeEvent = { preventDefault: () => {} };
                this.handleLoginSubmit(fakeEvent);
            }
        },

        handleLoginSubmit(e) {
            if (e) e.preventDefault();
            if (!window.StoreModel) return;

            const identifier = document.getElementById('login-identifier')?.value.trim();
            const password = document.getElementById('login-password')?.value;

            if (!identifier || !password) {
                this.showToast('Vui lòng nhập đầy đủ thông tin đăng nhập!', 'error');
                return;
            }

            const res = window.StoreModel.loginCustomer(identifier, password);
            if (res.success) {
                this.showToast(`Chào mừng bạn quay trở lại, ${res.user.name}!`, 'success');
                
                // Check if redirected from checkout
                const params = new URLSearchParams(window.location.search);
                const redirect = params.get('redirect');
                if (redirect) {
                    window.location.href = redirect;
                    return;
                }

                this.renderCustomerDashboard(res.user);
            } else {
                this.showToast(res.message, 'error');
            }
        },

        handleRegisterSubmit(e) {
            if (e) e.preventDefault();
            if (!window.StoreModel) return;

            const name = document.getElementById('reg-name')?.value.trim();
            const phone = document.getElementById('reg-phone')?.value.trim();
            const email = document.getElementById('reg-email')?.value.trim();
            const password = document.getElementById('reg-password')?.value;
            const confirmPassword = document.getElementById('reg-confirm-password')?.value;

            if (!name || !phone || !email || !password || !confirmPassword) {
                this.showToast('Vui lòng điền đầy đủ các thông tin bắt buộc!', 'error');
                return;
            }

            const cleanPhone = phone.replace(/[\s.-]/g, '');
            if (!/^(0[3|5|7|8|9])[0-9]{8}$/.test(cleanPhone)) {
                this.showToast('Số điện thoại không hợp lệ (cần 10 chữ số)!', 'error');
                return;
            }

            if (password.length < 6) {
                this.showToast('Mật khẩu phải chứa ít nhất 6 ký tự!', 'error');
                return;
            }

            if (password !== confirmPassword) {
                this.showToast('Mật khẩu xác nhận không khớp. Vui lòng kiểm tra lại!', 'error');
                return;
            }

            const res = window.StoreModel.registerCustomer({
                name, phone: cleanPhone, email, password
            });

            if (res.success) {
                this.showToast(`Đăng ký thành công! Chào mừng thành viên mới ${res.user.name}.`, 'success');
                this.renderCustomerDashboard(res.user);
            } else {
                this.showToast(res.message, 'error');
            }
        },

        handleForgotPassword() {
            const emailOrPhone = prompt('Nhập địa chỉ Email hoặc Số điện thoại để nhận mã khôi phục mật khẩu:');
            if (emailOrPhone && emailOrPhone.trim()) {
                this.showToast(`Hệ thống đã gửi liên kết đặt lại mật khẩu đến: ${emailOrPhone.trim()}`, 'success');
            }
        },

        handleSocialLogin(provider) {
            this.showToast(`Đang kết nối xác thực an toàn qua ${provider}...`, 'info');
            setTimeout(() => {
                const dummyUser = {
                    id: 'CUS-' + Math.floor(100 + Math.random() * 900),
                    name: provider === 'Google' ? 'Khách Google' : 'Khách Apple',
                    email: `user.${provider.toLowerCase()}@vaststudio.vn`,
                    phone: '0988 123 456',
                    city: 'Hà Nội',
                    tier: 'Standard',
                    ordersCount: 1,
                    totalSpent: 380000
                };
                window.StoreModel.setCurrentUser(dummyUser);
                this.showToast(`Đăng nhập thành công qua ${provider}!`, 'success');
                this.renderCustomerDashboard(dummyUser);
            }, 800);
        },

        handleLogout() {
            if (!confirm('Bạn có chắc chắn muốn đăng xuất khỏi tài khoản không?')) return;
            if (window.StoreModel) {
                window.StoreModel.logoutCustomer();
                this.showToast('Đã đăng xuất tài khoản thành công', 'info');
                this.renderAuthForms('login');
            }
        },

        renderCustomerDashboard(user) {
            const container = document.getElementById('auth-main-container');
            if (!container) return;

            const currentBreadcrumb = document.getElementById('auth-breadcrumb-current');
            if (currentBreadcrumb) currentBreadcrumb.textContent = `Tài khoản (${user.name})`;

            // Fetch user orders matching phone or email
            const allOrders = window.StoreModel ? window.StoreModel.getOrders() : [];
            const cleanPhone = (user.phone || '').replace(/\s+/g, '');
            const cleanEmail = (user.email || '').toLowerCase().trim();

            const userOrders = allOrders.filter(o => 
                (cleanPhone && (o.customerPhone || '').replace(/\s+/g, '').includes(cleanPhone)) ||
                (cleanEmail && (o.customerEmail || '').toLowerCase().trim() === cleanEmail)
            );

            const initial = (user.name || 'U').trim().charAt(0).toUpperCase();
            const totalOrders = userOrders.length || user.ordersCount || 0;
            const totalSpent = userOrders.reduce((sum, o) => sum + (o.total || 0), 0) || user.totalSpent || 0;

            container.innerHTML = `
                <div class="profile-dashboard-layout">
                    <!-- LEFT SIDEBAR: USER INFO -->
                    <div class="profile-card-base profile-user-card">
                        <div class="profile-avatar-circle">${initial}</div>
                        <h2 class="profile-user-name">${user.name}</h2>
                        <div class="profile-user-meta">
                            <div>${user.email || 'Chưa cập nhật email'}</div>
                            <div>${user.phone || ''}</div>
                        </div>
                        <div class="profile-tier-badge">
                            <span>⭐</span> ${user.tier || 'Thành viên Standard'}
                        </div>

                        <div class="profile-stats-grid">
                            <div class="profile-stat-item">
                                <div class="profile-stat-val">${totalOrders}</div>
                                <div class="profile-stat-lbl">Đơn hàng đã đặt</div>
                            </div>
                            <div class="profile-stat-item">
                                <div class="profile-stat-val" style="color:var(--store-gold);">${this.formatMoney(totalSpent)}</div>
                                <div class="profile-stat-lbl">Tổng chi tiêu</div>
                            </div>
                        </div>

                        <button type="button" class="btn-profile-logout" onclick="Storefront.handleLogout()">
                            <span data-icon="xCircle"></span> Đăng Xuất Tài Khoản
                        </button>
                    </div>

                    <!-- RIGHT MAIN CONTENT: ORDERS & SETTINGS -->
                    <div class="profile-content-area">
                        <!-- SECTION 1: RECENT ORDERS -->
                        <div class="profile-card-base profile-section-card">
                            <div class="profile-section-title">
                                <span>Đơn Hàng Gần Đây Của Bạn (${userOrders.length})</span>
                                <a href="order-tracking.php" style="font-size:0.8rem; color:var(--store-gold); text-decoration:none; font-weight:600;">
                                    Tra cứu chi tiết →
                                </a>
                            </div>

                            ${userOrders.length === 0 ? `
                                <div style="text-align:center; padding:32px 16px; color:var(--text-muted);">
                                    <div style="font-size:2.5rem; margin-bottom:8px;">📦</div>
                                    <h4 style="margin:0 0 6px; color:var(--text-primary); font-family:var(--store-font-heading);">Chưa có đơn hàng nào</h4>
                                    <p style="font-size:0.85rem; margin:0 0 16px;">Khám phá bộ sưu tập streetwear mới nhất và đặt hàng ngay.</p>
                                    <a href="products.php" class="btn-hero-primary" style="display:inline-block; padding:10px 22px; font-size:0.85rem; text-decoration:none;">
                                        Mua sắm ngay
                                    </a>
                                </div>
                            ` : `
                                <div class="profile-orders-list">
                                    ${userOrders.map(order => {
                                        let statusBadge = '<span class="status-badge status-pending">Chờ xử lý</span>';
                                        if (order.orderStatus === 'processing') statusBadge = '<span class="status-badge status-processing">Đang đóng gói</span>';
                                        else if (order.orderStatus === 'shipping') statusBadge = '<span class="status-badge status-shipping">Đang giao hàng</span>';
                                        else if (order.orderStatus === 'completed') statusBadge = '<span class="status-badge status-completed">Đã giao thành công</span>';
                                        else if (order.orderStatus === 'cancelled') statusBadge = '<span class="status-badge status-cancelled">Đã hủy</span>';

                                        const itemsSummary = (order.items || []).map(i => `${i.name} (x${i.quantity})`).join(', ');

                                        return `
                                            <div class="profile-order-card">
                                                <div class="profile-order-top">
                                                    <div>
                                                        <span class="profile-order-id">${order.id}</span>
                                                        <span style="font-size:0.75rem; color:var(--text-muted); margin-left:8px;">${order.createdAt || ''}</span>
                                                    </div>
                                                    <div>${statusBadge}</div>
                                                </div>
                                                <div class="profile-order-items-snippet">
                                                    <strong>Sản phẩm:</strong> ${itemsSummary || 'Chi tiết đơn hàng'}
                                                </div>
                                                <div class="profile-order-footer">
                                                    <div>
                                                        <span style="color:var(--text-muted);">Tổng tiền:</span>
                                                        <strong style="font-family:var(--store-font-heading); color:var(--store-gold); font-size:1.05rem; margin-left:4px;">
                                                            ${this.formatMoney(order.total)}
                                                        </strong>
                                                    </div>
                                                    <a href="order-tracking.php?order=${order.id}" class="btn-card-addcart" style="text-decoration:none; padding:6px 14px; font-size:0.8rem;">
                                                        Theo dõi đơn
                                                    </a>
                                                </div>
                                            </div>
                                        `;
                                    }).join('')}
                                </div>
                            `}
                        </div>

                        <!-- SECTION 2: MEMBER VOUCHERS -->
                        <div class="profile-card-base profile-section-card">
                            <div class="profile-section-title">
                                <span>Kho Voucher Ưu Đãi Của Bạn</span>
                                <span class="method-badge-rec">Đặc Quyền VIP</span>
                            </div>

                            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:14px;">
                                <div style="background:var(--bg-subtle); border:1px dashed var(--store-gold); border-radius:var(--radius-md); padding:14px; display:flex; justify-content:space-between; align-items:center;">
                                    <div>
                                        <strong style="font-family:monospace; color:var(--store-gold); font-size:1.05rem;">VASTWELCOME</strong>
                                        <div style="font-size:0.78rem; color:var(--text-secondary); margin-top:2px;">Giảm 100K cho đơn từ 500K</div>
                                    </div>
                                    <button type="button" class="coupon-pill-btn" onclick="Storefront.copyToClipboard('VASTWELCOME', 'Đã sao chép mã giảm giá!')">
                                        Sao chép
                                    </button>
                                </div>

                                <div style="background:var(--bg-subtle); border:1px dashed var(--store-emerald); border-radius:var(--radius-md); padding:14px; display:flex; justify-content:space-between; align-items:center;">
                                    <div>
                                        <strong style="font-family:monospace; color:var(--store-emerald); font-size:1.05rem;">FREESHIP</strong>
                                        <div style="font-size:0.78rem; color:var(--text-secondary); margin-top:2px;">Miễn phí ship toàn quốc</div>
                                    </div>
                                    <button type="button" class="coupon-pill-btn" onclick="Storefront.copyToClipboard('FREESHIP', 'Đã sao chép mã giảm giá!')">
                                        Sao chép
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 3: EDIT PROFILE -->
                        <div class="profile-card-base profile-section-card">
                            <div class="profile-section-title">
                                <span>Cập Nhật Thông Tin Cá Nhân</span>
                            </div>

                            <form onsubmit="Storefront.handleProfileUpdate(event)">
                                <div class="checkout-form-grid">
                                    <div>
                                        <label class="checkout-label">Họ và tên</label>
                                        <input type="text" id="prof-name" class="checkout-input" value="${user.name || ''}" required>
                                    </div>
                                    <div>
                                        <label class="checkout-label">Số điện thoại</label>
                                        <input type="tel" id="prof-phone" class="checkout-input" value="${user.phone || ''}" required>
                                    </div>
                                    <div>
                                        <label class="checkout-label">Email</label>
                                        <input type="email" id="prof-email" class="checkout-input" value="${user.email || ''}" required>
                                    </div>
                                    <div>
                                        <label class="checkout-label">Tỉnh / Thành phố sinh sống</label>
                                        <input type="text" id="prof-city" class="checkout-input" value="${user.city || 'Hà Nội'}">
                                    </div>
                                </div>

                                <div style="margin-top:18px; text-align:right;">
                                    <button type="submit" class="btn-hero-primary" style="padding:12px 24px; font-size:0.88rem;">
                                        Lưu Thay Đổi
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            `;

            if (window.ICONS) {
                container.querySelectorAll('[data-icon]').forEach(el => {
                    const iconName = el.getAttribute('data-icon');
                    if (window.ICONS[iconName]) el.innerHTML = window.ICONS[iconName];
                });
            }
        },

        handleProfileUpdate(e) {
            if (e) e.preventDefault();
            if (!window.StoreModel) return;

            const currentUser = window.StoreModel.getCurrentUser();
            if (!currentUser) return;

            const name = document.getElementById('prof-name')?.value.trim();
            const phone = document.getElementById('prof-phone')?.value.trim();
            const email = document.getElementById('prof-email')?.value.trim();
            const city = document.getElementById('prof-city')?.value.trim();

            if (!name || !phone || !email) {
                this.showToast('Vui lòng không để trống thông tin cá nhân!', 'error');
                return;
            }

            const res = window.StoreModel.updateCustomerProfile(currentUser.id, {
                name, phone, email, city
            });

            if (res.success) {
                this.showToast('Cập nhật thông tin tài khoản thành công!', 'success');
                this.renderCustomerDashboard(res.user);
            } else {
                this.showToast(res.message, 'error');
            }
        },

        // ======================================================================
        // WISHLIST DRAWER LOGIC
        // ======================================================================
        openWishlist() {
            this.renderWishlistDrawer();
            document.getElementById('wishlist-drawer-overlay')?.classList.add('active');
            document.getElementById('wishlist-drawer')?.classList.add('active');
            document.body.style.overflow = 'hidden';
        },

        closeWishlist() {
            document.getElementById('wishlist-drawer-overlay')?.classList.remove('active');
            document.getElementById('wishlist-drawer')?.classList.remove('active');
            document.body.style.overflow = '';
        },

        renderWishlistDrawer() {
            if (!window.StoreModel) return;
            const container = document.getElementById('wishlist-items-container');
            if (!container) return;

            const wishlistIds = window.StoreModel.getWishlist();
            const allProducts = window.StoreModel.getProducts();
            const wishlistProducts = allProducts.filter(p => wishlistIds.includes(p.id));

            if (wishlistProducts.length === 0) {
                container.innerHTML = `
                    <div style="text-align:center; padding: 40px 10px; color:var(--text-muted);">
                        <div style="font-size:3rem; margin-bottom:12px;">🤍</div>
                        <h4 style="margin:0 0 6px; color:var(--text-primary); font-family:var(--store-font-heading);">Danh sách yêu thích trống</h4>
                        <p style="font-size:0.85rem; margin:0 0 16px;">Nhấn biểu tượng trái tim để lưu các thiết kế bạn quan tâm.</p>
                        <a href="products.php" class="btn-hero-primary" style="padding:10px 18px; font-size:0.82rem; text-decoration:none;" onclick="Storefront.closeWishlist()">
                            Khám phá sản phẩm
                        </a>
                    </div>
                `;
            } else {
                container.innerHTML = wishlistProducts.map(item => `
                    <div class="wishlist-item">
                        <a href="product-detail.php?id=${item.id}">
                            <img src="${item.image}" alt="${item.name}" style="width:68px; height:84px; object-fit:cover; border-radius:var(--radius-sm);"
                                 onerror="this.onerror=null; this.src='${item.fallbackImage || ''}'">
                        </a>
                        <div style="flex:1; display:flex; flex-direction:column; gap:4px;">
                            <a href="product-detail.php?id=${item.id}" style="text-decoration:none; color:var(--text-primary); font-size:0.88rem; font-weight:700; line-height:1.3;">
                                ${item.name}
                            </a>
                            <span style="font-family:var(--store-font-heading); font-size:0.95rem; font-weight:800;">${this.formatMoney(item.price)}</span>
                            
                            <div style="display:flex; gap:8px; margin-top:4px;">
                                <button class="btn-card-addcart" style="padding:6px 12px; font-size:0.75rem;" onclick="Storefront.quickAddToCart('${item.id}'); Storefront.closeWishlist();">
                                    Thêm giỏ
                                </button>
                                <button class="cart-item-delete" onclick="Storefront.toggleWishlist('${item.id}'); Storefront.renderWishlistDrawer();" title="Xóa khỏi yêu thích">
                                    Xóa
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('');
            }
        },

        // ======================================================================
        // CART DRAWER & CHECKOUT
        // ======================================================================
        openCart() {
            this.renderCartDrawer();
            document.getElementById('cart-drawer-overlay')?.classList.add('active');
            document.getElementById('cart-drawer')?.classList.add('active');
            document.body.style.overflow = 'hidden';
        },

        closeCart() {
            document.getElementById('cart-drawer-overlay')?.classList.remove('active');
            document.getElementById('cart-drawer')?.classList.remove('active');
            document.body.style.overflow = '';
        },

        quickAddToCart(productId) {
            if (!window.StoreModel) return;
            const product = window.StoreModel.getProductById(productId);
            if (!product) return;

            const sizes = Array.isArray(product.sizes) && product.sizes.length ? product.sizes : ['M'];
            const colors = Array.isArray(product.colors) && product.colors.length ? product.colors : ['Tiêu chuẩn'];

            let chosenSize = sizes[0];
            let chosenColor = colors[0];
            let availableStock = window.StoreModel.getVariantStock(product, chosenSize, chosenColor);

            if (availableStock <= 0) {
                let found = false;
                for (const c of colors) {
                    for (const s of sizes) {
                        const st = window.StoreModel.getVariantStock(product, s, c);
                        if (st > 0) {
                            chosenSize = s;
                            chosenColor = c;
                            availableStock = st;
                            found = true;
                            break;
                        }
                    }
                    if (found) break;
                }
            }

            if (availableStock <= 0) {
                this.showToast(`Sản phẩm "${product.name}" hiện đã hết hàng`, 'error');
                return;
            }

            const existing = window.StoreModel.getCart().find(
                item => item.id === product.id && item.size === chosenSize && item.color === chosenColor
            );
            const inCartQty = existing ? Number(existing.quantity || 0) : 0;
            if (inCartQty + 1 > availableStock) {
                this.showToast(`Biến thể ${chosenColor} / ${chosenSize} chỉ còn ${availableStock} sản phẩm trong kho`, 'error');
                return;
            }

            window.StoreModel.addToCart(product, chosenSize, chosenColor, 1);
            this.showToast(`Đã thêm "${product.name}" (${chosenColor} / ${chosenSize}) vào giỏ hàng`, 'success');
            this.openCart();
        },

        renderCartDrawer() {
            if (!window.StoreModel) return;

            const cart = window.StoreModel.getCart();
            const container = document.getElementById('cart-items-container');
            const subtotal = window.StoreModel.getCartSubtotal();
            const freeShipThreshold = 500000;

            const meterText = document.getElementById('shipping-meter-text');
            const meterFill = document.getElementById('shipping-meter-fill');
            if (meterText && meterFill) {
                if (subtotal >= freeShipThreshold) {
                    meterText.innerHTML = `🎉 <strong>Chúc mừng!</strong> Bạn được <strong>MIỄN PHÍ VẬN CHUYỂN</strong>`;
                    meterFill.style.width = '100%';
                    meterFill.style.backgroundColor = 'var(--store-emerald)';
                } else {
                    const diff = freeShipThreshold - subtotal;
                    const percent = Math.min(100, Math.round((subtotal / freeShipThreshold) * 100));
                    meterText.innerHTML = `Mua thêm <strong>${this.formatMoney(diff)}</strong> để được <strong>FREESHIP</strong>`;
                    meterFill.style.width = `${percent}%`;
                }
            }

            if (container) {
                if (cart.length === 0) {
                    container.innerHTML = `
                        <div style="text-align:center; padding: 40px 10px; color:var(--text-muted);">
                            <div style="font-size:3rem; margin-bottom:12px;">🛍️</div>
                            <h4 style="margin:0 0 6px; color:var(--text-primary); font-family:var(--store-font-heading);">Giỏ hàng trống</h4>
                            <p style="font-size:0.85rem; margin:0 0 16px;">Hãy khám phá các thiết kế mới nhất của VAST STUDIO.</p>
                            <a href="products.php" class="btn-hero-primary" style="padding:10px 18px; font-size:0.82rem; text-decoration:none;" onclick="Storefront.closeCart()">
                                Mua sắm ngay
                            </a>
                        </div>
                    `;
                } else {
                    container.innerHTML = cart.map((item, idx) => `
                        <div class="cart-item">
                            <img src="${item.image}" alt="${item.name}" class="cart-item-img"
                                 onerror="this.onerror=null; this.src='${item.fallbackImage || ''}'">
                            
                            <div class="cart-item-details">
                                <h5 class="cart-item-name">${item.name}</h5>
                                <span class="cart-item-variant">Size: <strong>${item.size}</strong> | Màu: <strong>${item.color}</strong></span>
                                <span class="cart-item-price">${this.formatMoney(item.price)}</span>

                                <div class="cart-item-actions">
                                    <div class="qty-control">
                                        <button class="qty-btn" onclick="Storefront.updateCartQty(${idx}, ${item.quantity - 1})">-</button>
                                        <span class="qty-value">${item.quantity}</span>
                                        <button class="qty-btn" onclick="Storefront.updateCartQty(${idx}, ${item.quantity + 1})">+</button>
                                    </div>
                                    <button class="cart-item-delete" onclick="Storefront.removeCartItem(${idx})" title="Xóa khỏi giỏ">
                                        ${window.ICONS?.trash || '✕'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    `).join('');
                }
            }

            let discountAmount = 0;
            if (this.state.appliedCoupon) {
                const valResult = window.StoreModel.validateCoupon(this.state.appliedCoupon.code, subtotal);
                if (valResult.valid) {
                    discountAmount = valResult.discount;
                } else {
                    this.state.appliedCoupon = null;
                }
            }

            const shippingFee = (subtotal >= freeShipThreshold || subtotal === 0) ? 0 : 30000;
            const grandTotal = Math.max(0, subtotal - discountAmount + shippingFee);

            const subtotalEl = document.getElementById('cart-summary-subtotal');
            const discountEl = document.getElementById('cart-summary-discount');
            const discountRow = document.getElementById('cart-discount-row');
            const shippingEl = document.getElementById('cart-summary-shipping');
            const grandTotalEl = document.getElementById('cart-summary-total');

            if (subtotalEl) subtotalEl.textContent = this.formatMoney(subtotal);
            if (discountRow) {
                discountRow.style.display = discountAmount > 0 ? 'flex' : 'none';
                if (discountEl) discountEl.textContent = `-${this.formatMoney(discountAmount)}`;
            }
            if (shippingEl) shippingEl.textContent = shippingFee === 0 ? 'Miễn phí' : this.formatMoney(shippingFee);
            if (grandTotalEl) grandTotalEl.textContent = this.formatMoney(grandTotal);

            const checkoutBtn = document.getElementById('btn-cart-checkout');
            if (checkoutBtn) {
                checkoutBtn.disabled = cart.length === 0;
                checkoutBtn.style.opacity = cart.length === 0 ? '0.5' : '1';
                checkoutBtn.style.pointerEvents = cart.length === 0 ? 'none' : 'auto';
            }
        },

        updateCartQty(index, newQty) {
            if (window.StoreModel) {
                window.StoreModel.updateCartQuantity(index, newQty);
            }
        },

        removeCartItem(index) {
            if (window.StoreModel) {
                window.StoreModel.removeFromCart(index);
                this.showToast('Đã xóa sản phẩm khỏi giỏ', 'info');
            }
        },

        applyCouponCode() {
            const input = document.getElementById('cart-coupon-input');
            if (!input || !window.StoreModel) return;

            const code = input.value.trim();
            const subtotal = window.StoreModel.getCartSubtotal();

            const res = window.StoreModel.validateCoupon(code, subtotal);
            if (!res.valid) {
                this.showToast(res.message, 'error');
                return;
            }

            this.state.appliedCoupon = res.coupon;
            this.showToast(`Áp dụng mã ${res.coupon.code} thành công (-${this.formatMoney(res.discount)})!`, 'success');
            this.renderCartDrawer();
        },

        // --- CHECKOUT MODAL LOGIC ---
        openCheckoutModal() {
            if (!window.StoreModel || window.StoreModel.getCartCount() === 0) {
                this.showToast('Giỏ hàng của bạn đang trống!', 'error');
                return;
            }

            this.closeCart();
            window.location.href = 'checkout.php';
        },

        closeCheckoutModal() {
            const overlay = document.getElementById('checkout-modal-overlay');
            if (overlay) {
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        },

        submitOrderForm(e) {
            if (e) e.preventDefault();
            if (!window.StoreModel) return;

            const name = document.getElementById('checkout-name')?.value.trim();
            const phone = document.getElementById('checkout-phone')?.value.trim();
            const email = document.getElementById('checkout-email')?.value.trim();
            const address = document.getElementById('checkout-address')?.value.trim();
            const note = document.getElementById('checkout-note')?.value.trim();
            const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked')?.value || 'COD';

            if (!name || !phone || !address) {
                this.showToast('Vui lòng điền đầy đủ Tên, Số điện thoại và Địa chỉ nhận hàng!', 'error');
                return;
            }

            const cart = window.StoreModel.getCart();
            const subtotal = window.StoreModel.getCartSubtotal();
            const freeShipThreshold = 500000;
            const shippingFee = (subtotal >= freeShipThreshold || subtotal === 0) ? 0 : 30000;

            let discount = 0;
            let couponCode = null;
            if (this.state.appliedCoupon) {
                const val = window.StoreModel.validateCoupon(this.state.appliedCoupon.code, subtotal);
                if (val.valid) {
                    discount = val.discount;
                    couponCode = this.state.appliedCoupon.code;
                }
            }

            const total = Math.max(0, subtotal - discount + shippingFee);

            const newOrder = window.StoreModel.createOrder({
                customerName: name,
                customerPhone: phone,
                customerEmail: email,
                shippingAddress: address,
                note: note,
                items: cart,
                subtotal: subtotal,
                shippingFee: shippingFee,
                discount: discount,
                couponCode: couponCode,
                total: total,
                paymentMethod: paymentMethod === 'QR' ? 'Chuyển khoản QR (Vietcombank)' : 'COD (Thanh toán khi nhận hàng)'
            });

            const modalBody = document.getElementById('checkout-modal-content');
            if (modalBody) {
                modalBody.innerHTML = `
                    <div class="order-success-box">
                        <div class="success-icon-wrap">${window.ICONS?.checkCircle || '✓'}</div>
                        <h2 style="font-family:var(--store-font-heading); margin:0;">ĐẶT HÀNG THÀNH CÔNG!</h2>
                        <p style="color:var(--text-secondary); margin:0; font-size:0.95rem;">
                            Cảm ơn bạn đã tin tưởng VAST STUDIO. Đơn hàng của bạn đã được tiếp nhận và chuyển đến bộ phận đóng gói.
                        </p>
                        
                        <div style="background:var(--bg-subtle); border:1px dashed var(--border-color); border-radius:var(--radius-md); padding:16px 24px; width:100%; text-align:left; font-size:0.88rem; margin:10px 0;">
                            <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                                <span style="color:var(--text-muted);">Mã đơn hàng:</span>
                                <strong style="font-family:monospace; font-size:1.1rem; color:var(--text-primary);">${newOrder.id}</strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                                <span style="color:var(--text-muted);">Người nhận:</span>
                                <strong>${newOrder.customerName} (${newOrder.customerPhone})</strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                                <span style="color:var(--text-muted);">Địa chỉ giao:</span>
                                <span>${newOrder.shippingAddress}</span>
                            </div>
                            <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                                <span style="color:var(--text-muted);">Phương thức:</span>
                                <span>${newOrder.paymentMethod}</span>
                            </div>
                            <div style="display:flex; justify-content:space-between; padding-top:8px; border-top:1px dashed var(--border-color);">
                                <span style="font-weight:700;">Tổng thanh toán:</span>
                                <strong style="font-family:var(--store-font-heading); font-size:1.2rem; color:var(--store-gold);">
                                    ${this.formatMoney(newOrder.total)}
                                </strong>
                            </div>
                        </div>

                        ${paymentMethod === 'QR' ? `
                            <div style="background:rgba(217, 119, 6, 0.08); border:1px solid rgba(217, 119, 6, 0.2); border-radius:var(--radius-md); padding:14px; width:100%; font-size:0.82rem; text-align:left;">
                                <strong>Thông tin chuyển khoản VAST STUDIO:</strong><br>
                                Ngân hàng: Vietcombank Thăng Long - STK: <strong>9988 7766 5500</strong><br>
                                Chủ TK: <strong>CONG TY TNHH VAST STUDIO VIETNAM</strong><br>
                                Cú pháp chuyển khoản: <strong>${newOrder.id} - ${newOrder.customerPhone}</strong>
                            </div>
                        ` : ''}

                        <div style="display:flex; gap:12px; margin-top:10px; flex-wrap:wrap;">
                            <a href="order-tracking.php?order=${newOrder.id}" class="btn-hero-primary" style="padding:12px 20px; font-size:0.88rem; text-decoration:none;">
                                Tra cứu đơn này →
                            </a>
                            <a href="products.php" class="btn-hero-outline" style="padding:12px 20px; font-size:0.88rem; text-decoration:none;">
                                Tiếp tục mua sắm
                            </a>
                            <a href="admin.php" class="btn-hero-outline" style="padding:12px 16px; font-size:0.82rem; text-decoration:none;" target="_blank">
                                Quản trị xem đơn
                            </a>
                        </div>
                    </div>
                `;
            }

            this.showToast(`Đặt hàng thành công! Mã đơn: ${newOrder.id}`, 'success');
        },

        // ======================================================================
        // QUICK VIEW MODAL (LIVE MYSQL PRODUCT & VARIANT STOCK)
        // ======================================================================
        async openQuickView(productId) {
            if (!window.StoreModel) return;
            let product = window.StoreModel.getProductById(productId);
            const modal = document.getElementById('quickview-modal-overlay');
            const dialog = document.getElementById('quickview-dialog');
            if (!modal || !dialog) return;

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            dialog.innerHTML = `<button class="quickview-close-btn" onclick="Storefront.closeQuickView()">×</button><div style="padding:48px; text-align:center; width:100%; color:var(--text-muted);">Đang tải thông tin sản phẩm từ kho...</div>`;

            try {
                const res = await fetch(`api.php?action=product_quickview&id=${encodeURIComponent(productId)}`);
                const payload = await res.json();
                if (payload && payload.ok && payload.product) {
                    product = payload.product;
                }
            } catch (err) {}

            if (!product) {
                dialog.innerHTML = `<button class="quickview-close-btn" onclick="Storefront.closeQuickView()">×</button><div style="padding:48px; text-align:center; width:100%;">Không tìm thấy sản phẩm.</div>`;
                return;
            }

            this.state.quickViewProduct = product;
            const variants = Array.isArray(product.variants) ? product.variants : [];
            const firstInStock = variants.find(v => Number(v.stock) > 0);
            this.state.selectedColor = firstInStock ? firstInStock.color : (product.colors ? product.colors[0] : 'Tiêu chuẩn');
            this.state.selectedSize = firstInStock ? firstInStock.size : (product.sizes ? product.sizes[0] : 'M');
            this.state.quickViewQty = 1;

            const gallery = Array.isArray(product.gallery) && product.gallery.length > 0
                ? product.gallery
                : [product.image].filter(Boolean);
            const hasSale = product.originalPrice && product.originalPrice > product.price;
            const discountPct = hasSale ? Math.round((1 - product.price / product.originalPrice) * 100) : 0;

            dialog.innerHTML = `
                <button class="quickview-close-btn" onclick="Storefront.closeQuickView()">×</button>

                <div class="quickview-media-col">
                    <div class="quickview-media">
                        <img id="qv-main-img" src="${gallery[0] || product.image}" alt="${product.name}"
                             onerror="this.onerror=null; this.src='${product.fallbackImage || ''}'">
                    </div>
                    ${gallery.length > 1 ? `
                        <div class="quickview-thumbs">
                            ${gallery.map((src, i) => `
                                <button type="button" class="quickview-thumb-btn ${i === 0 ? 'active' : ''}" onclick="Storefront.switchQuickViewImage('${src}', this)">
                                    <img src="${src}" alt="${product.name}">
                                </button>
                            `).join('')}
                        </div>
                    ` : ''}
                </div>

                <div class="quickview-body">
                    <div>
                        <span class="card-category">${product.category || 'Thời trang'}${product.collection ? ' | ' + product.collection : ''}</span>
                        <h2 class="quickview-title">${product.name}</h2>
                        <span class="quickview-sku">SKU: ${product.sku || 'VAST'}</span>
                    </div>

                    <div class="card-price-row" style="margin:0; align-items:center; gap:10px;">
                        <span class="card-price-current" style="font-size:1.4rem;">${this.formatMoney(product.price)}</span>
                        ${hasSale ? `<span class="card-price-old" style="font-size:1rem;">${this.formatMoney(product.originalPrice)}</span>` : ''}
                        ${discountPct > 0 ? `<span class="quickview-discount-pill">-${discountPct}%</span>` : ''}
                    </div>

                    <p class="quickview-desc">${product.description || ''}</p>

                    <div class="quickview-option-group">
                        <span class="option-group-label">
                            Màu sắc: <strong id="qv-selected-color-label">${this.state.selectedColor}</strong>
                        </span>
                        <div class="quickview-pills-row" id="qv-colors-row"></div>
                    </div>

                    <div class="quickview-option-group">
                        <div class="option-group-label">
                            <span>Kích thước: <strong id="qv-selected-size-label">${this.state.selectedSize}</strong></span>
                            <a href="javascript:void(0)" onclick="Storefront.openSizeGuideModal()" style="color:var(--store-gold); text-decoration:underline; font-size:0.78rem;">
                                Bảng chọn size
                            </a>
                        </div>
                        <div class="quickview-pills-row" id="qv-sizes-row"></div>
                    </div>

                    <div class="quickview-stock-hint" id="qv-stock-status">Đang kiểm tra tồn kho...</div>

                    <div style="display:flex; gap:12px; align-items:center; margin-top:4px;">
                        <div class="qty-control" style="height:44px;">
                            <button type="button" class="qty-btn" style="width:36px; height:44px;" onclick="Storefront.adjustQuickViewQty(-1)">-</button>
                            <span class="qty-value" id="qv-qty-display" style="width:40px; font-size:0.95rem;">1</span>
                            <button type="button" class="qty-btn" style="width:36px; height:44px;" onclick="Storefront.adjustQuickViewQty(1)">+</button>
                        </div>

                        <button type="button" id="qv-add-cart-btn" class="btn-hero-primary" onclick="Storefront.submitQuickViewAddToCart()" style="flex:1; justify-content:center; padding:12px 20px;">
                            ${window.ICONS?.shoppingBag || ''} Thêm Vào Giỏ
                        </button>
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px dashed var(--border-color); padding-top:10px; margin-top:4px;">
                        <a href="product-detail.php?id=${encodeURIComponent(product.id)}" style="color:var(--text-primary); font-size:0.85rem; font-weight:700; text-decoration:underline;">
                            Xem chi tiết sản phẩm →
                        </a>
                        <span style="font-size:0.78rem; color:var(--text-muted);">Đổi trả 30 ngày</span>
                    </div>
                </div>
            `;

            this.renderQuickViewVariants();
        },

        switchQuickViewImage(src, btn) {
            const img = document.getElementById('qv-main-img');
            if (img) img.src = src;
            document.querySelectorAll('.quickview-thumb-btn').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');
        },

        renderQuickViewVariants() {
            const product = this.state.quickViewProduct;
            if (!product || !window.StoreModel) return;

            const colorsRow = document.getElementById('qv-colors-row');
            const sizesRow = document.getElementById('qv-sizes-row');
            const variants = Array.isArray(product.variants) ? product.variants : [];

            if (colorsRow) {
                colorsRow.innerHTML = (product.colors || []).map(c => {
                    const colorTotal = variants.length > 0
                        ? variants.filter(v => v.color === c).reduce((s, v) => s + (Number(v.stock) || 0), 0)
                        : (Number(product.stock) || 0);
                    const disabled = colorTotal <= 0;
                    const active = this.state.selectedColor === c;
                    return `<button type="button" class="quickview-pill-btn ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}" ${disabled ? 'disabled title="Màu này đã hết hàng"' : ''} onclick="Storefront.selectQuickViewColor('${c.replace(/'/g, "\\'")}')">${c}${disabled ? ' (Hết)' : ''}</button>`;
                }).join('');
            }

            if (sizesRow) {
                sizesRow.innerHTML = (product.sizes || []).map(s => {
                    const stock = window.StoreModel.getVariantStock(product, s, this.state.selectedColor);
                    const disabled = stock <= 0;
                    const active = this.state.selectedSize === s;
                    return `<button type="button" class="quickview-pill-btn ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}" ${disabled ? 'disabled title="Size này đã hết hàng"' : ''} onclick="Storefront.selectQuickViewSize('${s.replace(/'/g, "\\'")}')">${s}${disabled ? ' (Hết)' : ''}</button>`;
                }).join('');
            }

            const currentStock = window.StoreModel.getVariantStock(product, this.state.selectedSize, this.state.selectedColor);
            const stockEl = document.getElementById('qv-stock-status');
            const addBtn = document.getElementById('qv-add-cart-btn');

            if (currentStock > 0 && this.state.quickViewQty > currentStock) {
                this.state.quickViewQty = currentStock;
            }
            const qtyDisplay = document.getElementById('qv-qty-display');
            if (qtyDisplay) qtyDisplay.textContent = this.state.quickViewQty;

            if (stockEl) {
                stockEl.innerHTML = currentStock > 0
                    ? `Tình trạng kho (${this.state.selectedSize} / ${this.state.selectedColor}): <strong style="color:var(--store-emerald);">Còn ${currentStock} sản phẩm</strong>`
                    : `<strong style="color:var(--store-red);">Phân loại ${this.state.selectedSize} / ${this.state.selectedColor} đã hết hàng</strong>`;
            }
            if (addBtn) {
                addBtn.disabled = currentStock <= 0;
                addBtn.style.opacity = currentStock <= 0 ? '0.45' : '1';
                addBtn.style.cursor = currentStock <= 0 ? 'not-allowed' : 'pointer';
            }
        },

        closeQuickView() {
            document.getElementById('quickview-modal-overlay')?.classList.remove('active');
            document.body.style.overflow = '';
        },

        selectQuickViewSize(size) {
            const product = this.state.quickViewProduct;
            if (!product || !window.StoreModel) return;
            const stock = window.StoreModel.getVariantStock(product, size, this.state.selectedColor);
            if (stock <= 0) return;
            this.state.selectedSize = size;
            const label = document.getElementById('qv-selected-size-label');
            if (label) label.textContent = size;
            this.renderQuickViewVariants();
        },

        selectQuickViewColor(color) {
            const product = this.state.quickViewProduct;
            if (!product || !window.StoreModel) return;
            this.state.selectedColor = color;
            const label = document.getElementById('qv-selected-color-label');
            if (label) label.textContent = color;

            const currentComboStock = window.StoreModel.getVariantStock(product, this.state.selectedSize, color);
            if (currentComboStock <= 0) {
                const availSize = (product.sizes || []).find(s => window.StoreModel.getVariantStock(product, s, color) > 0);
                if (availSize) {
                    this.state.selectedSize = availSize;
                    const sLabel = document.getElementById('qv-selected-size-label');
                    if (sLabel) sLabel.textContent = availSize;
                }
            }
            this.renderQuickViewVariants();
        },

        adjustQuickViewQty(delta) {
            const product = this.state.quickViewProduct;
            const maxStock = product && window.StoreModel
                ? window.StoreModel.getVariantStock(product, this.state.selectedSize, this.state.selectedColor)
                : 99;
            const next = Math.max(1, this.state.quickViewQty + delta);
            if (maxStock > 0 && next > maxStock) {
                this.showToast(`Số lượng tối đa cho phân loại này là ${maxStock}`, 'error');
                this.state.quickViewQty = maxStock;
            } else {
                this.state.quickViewQty = next;
            }
            const display = document.getElementById('qv-qty-display');
            if (display) display.textContent = this.state.quickViewQty;
        },

        submitQuickViewAddToCart() {
            const product = this.state.quickViewProduct;
            if (!product || !window.StoreModel) return;
            const maxStock = window.StoreModel.getVariantStock(product, this.state.selectedSize, this.state.selectedColor);
            if (maxStock <= 0) {
                this.showToast('Phân loại này đã hết hàng!', 'error');
                return;
            }
            if (this.state.quickViewQty > maxStock) {
                this.showToast(`Chỉ còn ${maxStock} sản phẩm trong kho!`, 'error');
                return;
            }

            window.StoreModel.addToCart(
                product,
                this.state.selectedSize,
                this.state.selectedColor,
                this.state.quickViewQty
            );

            this.closeQuickView();
            this.showToast(`Đã thêm ${this.state.quickViewQty}x "${product.name}" vào giỏ hàng`, 'success');
            this.openCart();
        },

        // --- WISHLIST ACTION ---
        toggleWishlist(productId, btn) {
            if (!window.StoreModel) return;
            const added = window.StoreModel.toggleWishlist(productId);

            if (btn) {
                btn.classList.toggle('active', added);
                btn.innerHTML = added ? (window.ICONS?.heartFilled || '♥') : (window.ICONS?.heart || '♡');
            }

            const product = window.StoreModel.getProductById(productId);
            const name = product ? product.name : 'Sản phẩm';

            if (added) {
                this.showToast(`Đã thêm "${name}" vào mục yêu thích`, 'success');
            } else {
                this.showToast(`Đã xóa "${name}" khỏi mục yêu thích`, 'info');
            }
        },

        updateWishlistButtonsState() {
            if (!window.StoreModel) return;
            const wishlist = window.StoreModel.getWishlist();
            document.querySelectorAll('.card-btn-wishlist').forEach(btn => {
                const card = btn.closest('.product-card');
                if (card) {
                    const id = card.dataset.productId;
                    const isWish = wishlist.includes(id);
                    btn.classList.toggle('active', isWish);
                    btn.innerHTML = isWish ? (window.ICONS?.heartFilled || '♥') : (window.ICONS?.heart || '♡');
                }
            });

            // PDP wishlist button
            const pdpWishBtn = document.getElementById('pdp-btn-wishlist');
            if (pdpWishBtn && this.state.pdpProduct) {
                const isWish = wishlist.includes(this.state.pdpProduct.id);
                pdpWishBtn.classList.toggle('active', isWish);
                pdpWishBtn.innerHTML = isWish ? (window.ICONS?.heartFilled || '♥') : (window.ICONS?.heart || '♡');
            }
        },

        // ======================================================================
        // SEARCH OVERLAY MODAL
        // ======================================================================
        openSearchModal() {
            const overlay = document.getElementById('search-modal-overlay');
            const input = document.getElementById('search-modal-input');
            if (overlay) {
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
                if (input) {
                    setTimeout(() => input.focus(), 100);
                }
            }
        },

        closeSearchModal() {
            const overlay = document.getElementById('search-modal-overlay');
            if (overlay) {
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        },

        handleLiveSearch(query) {
            const container = document.getElementById('search-results-list');
            if (!container) return;

            const q = (query || '').trim().toLowerCase();
            if (!q) {
                container.innerHTML = `
                    <div style="padding:20px; text-align:center; color:var(--text-muted); font-size:0.88rem;">
                        Nhập tên mẫu áo, quần, bộ sưu tập để tìm kiếm nhanh...
                    </div>
                `;
                return;
            }

            const matches = this.state.products.filter(p => 
                p.name.toLowerCase().includes(q) ||
                (p.category && p.category.toLowerCase().includes(q)) ||
                (p.collection && p.collection.toLowerCase().includes(q)) ||
                (p.description && p.description.toLowerCase().includes(q))
            );

            if (matches.length === 0) {
                container.innerHTML = `
                    <div style="padding:20px; text-align:center; color:var(--text-muted); font-size:0.88rem;">
                        Không tìm thấy sản phẩm nào với từ khóa "<strong>${query}</strong>"
                    </div>
                `;
            } else {
                container.innerHTML = matches.map(p => `
                    <a href="product-detail.php?id=${p.id}" style="display:flex; align-items:center; gap:12px; padding:10px 14px; border-radius:var(--radius-sm); text-decoration:none; transition:var(--transition); background:var(--bg-app); margin-bottom:6px;">
                        <img src="${p.image}" alt="${p.name}" style="width:48px; height:48px; border-radius:4px; object-fit:cover;">
                        <div style="flex:1;">
                            <h5 style="margin:0; font-size:0.9rem; color:var(--text-primary);">${p.name}</h5>
                            <span style="font-size:0.75rem; color:var(--text-muted);">${p.category} | ${p.collection}</span>
                        </div>
                        <strong style="font-family:var(--store-font-heading); color:var(--text-primary); font-size:0.95rem;">
                            ${this.formatMoney(p.price)}
                        </strong>
                    </a>
                `).join('');
            }
        },

        // --- SIZE GUIDE MODAL ---
        openSizeGuideModal() {
            const overlay = document.getElementById('size-guide-modal-overlay');
            if (overlay) {
                overlay.classList.add('active');
            }
        },

        closeSizeGuideModal() {
            const overlay = document.getElementById('size-guide-modal-overlay');
            if (overlay) {
                overlay.classList.remove('active');
            }
        },

        // --- COPY COUPON CODE ---
        copyCoupon(code) {
            navigator.clipboard.writeText(code).then(() => {
                this.showToast(`Đã sao chép mã ưu đãi: ${code}`, 'success');
            }).catch(() => {
                this.showToast(`Mã ưu đãi: ${code}`, 'info');
            });
        },

        toggleCustomerPanel(panelId, forceOpen) {
            const el = document.getElementById(panelId);
            if (!el) return;
            const shouldOpen = typeof forceOpen === 'boolean' ? forceOpen : el.hasAttribute('hidden');
            if (shouldOpen) {
                el.removeAttribute('hidden');
                el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                el.setAttribute('hidden', '');
            }
        },

        closeAllModals() {
            this.closeCart();
            this.closeWishlist();
            this.closeQuickView();
            this.closeCheckoutModal();
            this.closeSearchModal();
            this.closeSizeGuideModal();
        }
    };

    // Expose to window
    window.Storefront = Storefront;

    // Auto-init on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', () => {
        Storefront.init();
    });

})(window);
