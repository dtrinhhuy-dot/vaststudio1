// ==========================================================================
// VAST STUDIO — STOREFRONT CONTROLLER & VIEW LOGIC
// File: js/storefront.js
// Modern Streetwear E-Commerce Client Architecture
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
            quickViewQty: 1
        },

        // --- INIT STOREFRONT ---
        init() {
            this.loadData();
            this.initTheme();
            this.bindEvents();
            this.updateHeaderBadges();

            // Detect current page
            const path = window.location.pathname.toLowerCase();
            if (path.endsWith('products.html') || path.endsWith('shop.html')) {
                this.initShopPage();
            } else {
                this.initHomePage();
            }
        },

        loadData() {
            if (window.StoreModel) {
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
            });

            // ESC key to close any active modal
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.closeAllModals();
                }
            });
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

            // Generate color dots
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
                        <img src="${product.image}" alt="${product.name}" 
                             onerror="this.onerror=null; this.src='${product.fallbackImage || ''}'" loading="lazy">
                        
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
                        <h4 class="card-title" onclick="Storefront.openQuickView('${product.id}')" title="${product.name}">
                            ${product.name}
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
                    <a href="products.html?category=${encodeURIComponent(cat.name)}" class="category-card">
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
                    <a href="products.html?collection=${encodeURIComponent(col.name)}" 
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

            // Sort products by salesCount desc
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

            // Update active pill styling
            document.querySelectorAll('.tab-pill-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.category === category);
            });
        },

        // ======================================================================
        // TRANG DANH SÁCH SẢN PHẨM (SHOP / PRODUCTS PAGE) LOGIC
        // ======================================================================
        initShopPage() {
            // Read URL Params
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
            // Price slider
            const slider = document.getElementById('price-range-slider');
            const label = document.getElementById('price-range-value');
            if (slider && label) {
                slider.value = this.state.filter.maxPrice;
                label.textContent = this.formatMoney(this.state.filter.maxPrice);
            }

            // Sort select
            const sortSelect = document.getElementById('shop-sort-select');
            if (sortSelect) {
                sortSelect.value = this.state.filter.sort;
            }

            // Search input
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

            // 1. Category filter
            if (this.state.filter.category !== 'all') {
                result = result.filter(p => p.category === this.state.filter.category);
            }

            // 2. Collection filter
            if (this.state.filter.collection !== 'all') {
                result = result.filter(p => p.collection === this.state.filter.collection);
            }

            // 3. Price filter
            result = result.filter(p => p.price <= this.state.filter.maxPrice);

            // 4. Size filter
            if (this.state.filter.size !== 'all') {
                result = result.filter(p => (p.sizes || []).includes(this.state.filter.size));
            }

            // 5. Color filter
            if (this.state.filter.color !== 'all') {
                result = result.filter(p => (p.colors || []).some(c => c.toLowerCase().includes(this.state.filter.color.toLowerCase())));
            }

            // 6. In stock only
            if (this.state.filter.inStockOnly) {
                result = result.filter(p => p.stock > 0);
            }

            // 7. Search query
            if (this.state.filter.search) {
                const q = this.state.filter.search.toLowerCase();
                result = result.filter(p => 
                    p.name.toLowerCase().includes(q) ||
                    (p.category && p.category.toLowerCase().includes(q)) ||
                    (p.collection && p.collection.toLowerCase().includes(q)) ||
                    (p.description && p.description.toLowerCase().includes(q))
                );
            }

            // 8. Sorting
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

            // Render Product Count
            const countLabel = document.getElementById('shop-product-count');
            if (countLabel) {
                countLabel.innerHTML = `Đang hiển thị <strong>${result.length}</strong> sản phẩm`;
            }

            // Render Active Filter Chips
            this.renderActiveFilterChips();

            // Render Grid
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

            const size = product.sizes ? product.sizes[0] : 'M';
            const color = product.colors ? product.colors[0] : 'Tiêu chuẩn';

            window.StoreModel.addToCart(product, size, color, 1);
            this.showToast(`Đã thêm "${product.name}" vào giỏ hàng`, 'success');
            this.openCart();
        },

        renderCartDrawer() {
            if (!window.StoreModel) return;

            const cart = window.StoreModel.getCart();
            const container = document.getElementById('cart-items-container');
            const subtotal = window.StoreModel.getCartSubtotal();
            const freeShipThreshold = 500000;

            // Free ship progress meter
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

            // Items list
            if (container) {
                if (cart.length === 0) {
                    container.innerHTML = `
                        <div style="text-align:center; padding: 40px 10px; color:var(--text-muted);">
                            <div style="font-size:3rem; margin-bottom:12px;">🛍️</div>
                            <h4 style="margin:0 0 6px; color:var(--text-primary); font-family:var(--store-font-heading);">Giỏ hàng trống</h4>
                            <p style="font-size:0.85rem; margin:0 0 16px;">Hãy khám phá các thiết kế mới nhất của VAST STUDIO.</p>
                            <a href="products.html" class="btn-hero-primary" style="padding:10px 18px; font-size:0.82rem; text-decoration:none;" onclick="Storefront.closeCart()">
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

            // Calculate Totals & Discount
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

            // Update UI Total labels
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

            // Update Checkout Button State
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
                this.showToast('Giỏ hàng trống!', 'error');
                return;
            }

            this.closeCart();
            const overlay = document.getElementById('checkout-modal-overlay');
            if (overlay) {
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
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

            // Create Order in StoreModel
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

            // Show Success Order Confirmation
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

                        <div style="display:flex; gap:12px; margin-top:10px;">
                            <a href="products.html" class="btn-hero-primary" style="padding:12px 24px; font-size:0.9rem; text-decoration:none;">
                                Tiếp tục mua sắm
                            </a>
                            <a href="admin.html" class="btn-hero-outline" style="padding:12px 20px; font-size:0.85rem; text-decoration:none;" target="_blank">
                                Xem đơn trong Admin →
                            </a>
                        </div>
                    </div>
                `;
            }

            this.showToast(`Đặt hàng thành công! Mã đơn: ${newOrder.id}`, 'success');
        },

        // ======================================================================
        // QUICK VIEW MODAL
        // ======================================================================
        openQuickView(productId) {
            if (!window.StoreModel) return;
            const product = window.StoreModel.getProductById(productId);
            if (!product) return;

            this.state.quickViewProduct = product;
            this.state.selectedSize = product.sizes ? product.sizes[0] : 'M';
            this.state.selectedColor = product.colors ? product.colors[0] : 'Tiêu chuẩn';
            this.state.quickViewQty = 1;

            const modal = document.getElementById('quickview-modal-overlay');
            const dialog = document.getElementById('quickview-dialog');
            if (!modal || !dialog) return;

            const hasSale = product.originalPrice && product.originalPrice > product.price;

            dialog.innerHTML = `
                <button class="quickview-close-btn" onclick="Storefront.closeQuickView()">×</button>
                
                <div class="quickview-media">
                    <img src="${product.image}" alt="${product.name}" 
                         onerror="this.onerror=null; this.src='${product.fallbackImage || ''}'">
                </div>

                <div class="quickview-body">
                    <div>
                        <span class="card-category">${product.category} | ${product.collection}</span>
                        <h2 class="quickview-title">${product.name}</h2>
                        <span class="quickview-sku">SKU: ${product.sku}</span>
                    </div>

                    <div class="card-price-row" style="margin:0;">
                        <span class="card-price-current" style="font-size:1.4rem;">${this.formatMoney(product.price)}</span>
                        ${hasSale ? `<span class="card-price-old" style="font-size:1rem;">${this.formatMoney(product.originalPrice)}</span>` : ''}
                    </div>

                    <p class="quickview-desc">${product.description}</p>

                    <!-- Color Selector -->
                    <div class="quickview-option-group">
                        <span class="option-group-label">
                            Màu sắc: <strong id="qv-selected-color-label">${this.state.selectedColor}</strong>
                        </span>
                        <div class="quickview-pills-row">
                            ${(product.colors || []).map((c, i) => `
                                <button class="quickview-pill-btn ${i === 0 ? 'active' : ''}" 
                                        onclick="Storefront.selectQuickViewColor('${c}', this)">
                                    ${c}
                                </button>
                            `).join('')}
                        </div>
                    </div>

                    <!-- Size Selector -->
                    <div class="quickview-option-group">
                        <div class="option-group-label">
                            <span>Kích thước: <strong id="qv-selected-size-label">${this.state.selectedSize}</strong></span>
                            <a href="javascript:void(0)" onclick="Storefront.openSizeGuideModal()" style="color:var(--store-gold); text-decoration:underline; font-size:0.78rem;">
                                Bảng chọn size
                            </a>
                        </div>
                        <div class="quickview-pills-row">
                            ${(product.sizes || []).map((s, i) => `
                                <button class="quickview-pill-btn ${i === 0 ? 'active' : ''}" 
                                        onclick="Storefront.selectQuickViewSize('${s}', this)">
                                    ${s}
                                </button>
                            `).join('')}
                        </div>
                    </div>

                    <!-- Quantity & Add to Cart -->
                    <div style="display:flex; gap:12px; align-items:center; margin-top:8px;">
                        <div class="qty-control" style="height:44px;">
                            <button class="qty-btn" style="width:36px; height:44px;" onclick="Storefront.adjustQuickViewQty(-1)">-</button>
                            <span class="qty-value" id="qv-qty-display" style="width:40px; font-size:0.95rem;">1</span>
                            <button class="qty-btn" style="width:36px; height:44px;" onclick="Storefront.adjustQuickViewQty(1)">+</button>
                        </div>

                        <button class="btn-hero-primary" onclick="Storefront.submitQuickViewAddToCart()" style="flex:1; justify-content:center; padding:12px 20px;">
                            ${window.ICONS?.shoppingBag || ''} Thêm Vào Giỏ Hàng
                        </button>
                    </div>

                    <div style="font-size:0.8rem; color:var(--text-muted); display:flex; gap:16px; margin-top:6px;">
                        <span>✓ Đổi trả miễn phí 30 ngày</span>
                        <span>✓ Freeship đơn từ 500k</span>
                    </div>
                </div>
            `;

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        },

        closeQuickView() {
            document.getElementById('quickview-modal-overlay')?.classList.remove('active');
            document.body.style.overflow = '';
        },

        selectQuickViewSize(size, btn) {
            this.state.selectedSize = size;
            const label = document.getElementById('qv-selected-size-label');
            if (label) label.textContent = size;
            btn.parentElement.querySelectorAll('.quickview-pill-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        },

        selectQuickViewColor(color, btn) {
            this.state.selectedColor = color;
            const label = document.getElementById('qv-selected-color-label');
            if (label) label.textContent = color;
            btn.parentElement.querySelectorAll('.quickview-pill-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        },

        adjustQuickViewQty(delta) {
            this.state.quickViewQty = Math.max(1, this.state.quickViewQty + delta);
            const display = document.getElementById('qv-qty-display');
            if (display) display.textContent = this.state.quickViewQty;
        },

        submitQuickViewAddToCart() {
            const product = this.state.quickViewProduct;
            if (!product || !window.StoreModel) return;

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
                (p.collection && p.collection.toLowerCase().includes(q))
            );

            if (matches.length === 0) {
                container.innerHTML = `
                    <div style="padding:20px; text-align:center; color:var(--text-muted); font-size:0.88rem;">
                        Không tìm thấy sản phẩm nào với từ khóa "<strong>${query}</strong>"
                    </div>
                `;
            } else {
                container.innerHTML = matches.map(p => `
                    <div style="display:flex; align-items:center; gap:12px; padding:10px 14px; border-radius:var(--radius-sm); cursor:pointer; transition:var(--transition); background:var(--bg-app); margin-bottom:6px;"
                         onclick="Storefront.closeSearchModal(); Storefront.openQuickView('${p.id}')">
                        <img src="${p.image}" alt="${p.name}" style="width:48px; height:48px; border-radius:4px; object-fit:cover;">
                        <div style="flex:1;">
                            <h5 style="margin:0; font-size:0.9rem; color:var(--text-primary);">${p.name}</h5>
                            <span style="font-size:0.75rem; color:var(--text-muted);">${p.category} | ${p.collection}</span>
                        </div>
                        <strong style="font-family:var(--store-font-heading); color:var(--text-primary); font-size:0.95rem;">
                            ${this.formatMoney(p.price)}
                        </strong>
                    </div>
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

        closeAllModals() {
            this.closeCart();
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
