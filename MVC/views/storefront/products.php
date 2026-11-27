<?php
require_once __DIR__ . '/../../../includes/config.php';
$pageTitle = "Danh Sách Sản Phẩm — VAST STUDIO | Cửa Hàng Streetwear & Minimalist";
$pageDesc = "Danh sách đầy đủ sản phẩm thời trang VAST STUDIO: Áo thun Boxy Tee, Sơ mi Oxford, Quần Cargo dù, Wide-leg Denim, Áo khoác Bomber và phụ kiện thời trang.";
$activeNav = "products";
include __DIR__ . '/../partials/header.php';
?>

<!-- 3. SHOP PAGE MAIN WRAPPER -->
    <main class="shop-page-wrapper">
        <div class="store-container">
            <!-- Breadcrumbs -->
            <nav class="breadcrumbs" aria-label="Breadcrumb">
                <a href="index.php">Trang chủ</a>
                <span>/</span>
                <a href="products.php">Cửa hàng thời trang</a>
                <span>/</span>
                <span style="color: var(--text-primary); font-weight: 600;">Danh sách sản phẩm</span>
            </nav>

            <!-- Header Banner -->
            <div class="shop-header-banner">
                <div>
                    <h1 class="shop-header-title">DANH SÁCH SẢN PHẨM</h1>
                    <p class="shop-header-desc">
                        Toàn bộ các thiết kế streetwear đương đại: Form boxy phóng khoáng, chất vải kỹ thuật cao cấp chuẩn form Á Đông.
                    </p>
                </div>

                <div style="position: relative; width: 100%; max-width: 340px;">
                    <input type="text" id="shop-search-input" class="checkout-input" 
                           placeholder="Tìm nhanh theo tên, mã SKU..." 
                           style="padding-left: 38px; width: 100%; border-radius: var(--radius-full);"
                           oninput="Storefront.handleSearchInput(this.value)">
                    <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); display: flex;" data-icon="search"></span>
                </div>
            </div>

            <!-- Shop Layout: Sidebar + Catalog -->
            <div class="shop-layout">
                <!-- ================= LEFT FILTER SIDEBAR ================= -->
                <aside class="filter-sidebar" id="shop-filter-sidebar">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                        <h3 style="margin: 0; font-family: var(--store-font-heading); font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                            <span data-icon="sliders"></span> Bộ Lọc Sản Phẩm
                        </h3>
                        <button class="cart-close-btn" style="display: none;" id="btn-close-mobile-filter" onclick="Storefront.toggleMobileFilterSidebar()">
                            <span data-icon="x"></span>
                        </button>
                    </div>

                    <!-- 1. Categories Filter -->
                    <div>
                        <h4 class="filter-section-title">
                            <span>Danh mục</span>
                            <span data-icon="chevronDown"></span>
                        </h4>
                        <ul class="filter-list" id="shop-category-filters">
                            <!-- Populated by Storefront.js -->
                        </ul>
                    </div>

                    <!-- 2. Collections Filter -->
                    <div style="border-top: 1px solid var(--border-subtle); padding-top: 16px;">
                        <h4 class="filter-section-title">
                            <span>Bộ sưu tập</span>
                            <span data-icon="chevronDown"></span>
                        </h4>
                        <ul class="filter-list" id="shop-collection-filters">
                            <!-- Populated by Storefront.js -->
                        </ul>
                    </div>

                    <!-- 3. Price Filter Slider -->
                    <div style="border-top: 1px solid var(--border-subtle); padding-top: 16px;">
                        <h4 class="filter-section-title">
                            <span>Mức giá tối đa</span>
                            <span id="price-range-value" style="color: var(--store-gold); font-size: 0.85rem; font-weight: 800;">2.000.000 ₫</span>
                        </h4>
                        <div class="price-slider-box">
                            <input type="range" class="price-slider-input" id="price-range-slider" 
                                   min="200000" max="2000000" step="50000" value="2000000"
                                   oninput="Storefront.handlePriceSliderChange(this.value)">
                            <div class="price-range-labels">
                                <span>200.000 ₫</span>
                                <span>2.000.000 ₫</span>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Sizes Filter -->
                    <div style="border-top: 1px solid var(--border-subtle); padding-top: 16px;">
                        <h4 class="filter-section-title">
                            <span>Kích cỡ (Size)</span>
                        </h4>
                        <div class="size-filter-grid">
                            <button class="size-pill-btn" data-size="S" onclick="Storefront.setSizeFilter('S')">S</button>
                            <button class="size-pill-btn" data-size="M" onclick="Storefront.setSizeFilter('M')">M</button>
                            <button class="size-pill-btn" data-size="L" onclick="Storefront.setSizeFilter('L')">L</button>
                            <button class="size-pill-btn" data-size="XL" onclick="Storefront.setSizeFilter('XL')">XL</button>
                            <button class="size-pill-btn" data-size="Freesize" onclick="Storefront.setSizeFilter('Freesize')">Free</button>
                            <button class="size-pill-btn" data-size="29" onclick="Storefront.setSizeFilter('29')">29</button>
                            <button class="size-pill-btn" data-size="30" onclick="Storefront.setSizeFilter('30')">30</button>
                            <button class="size-pill-btn" data-size="31" onclick="Storefront.setSizeFilter('31')">31</button>
                            <button class="size-pill-btn" data-size="32" onclick="Storefront.setSizeFilter('32')">32</button>
                        </div>
                    </div>

                    <!-- 5. Colors Filter -->
                    <div style="border-top: 1px solid var(--border-subtle); padding-top: 16px;">
                        <h4 class="filter-section-title">
                            <span>Gam màu</span>
                        </h4>
                        <div class="color-filter-grid">
                            <button class="color-filter-btn" data-color="Đen" style="background-color: #09090b;" title="Đen" onclick="Storefront.setColorFilter('Đen')"></button>
                            <button class="color-filter-btn" data-color="Trắng" style="background-color: #ffffff;" title="Trắng" onclick="Storefront.setColorFilter('Trắng')"></button>
                            <button class="color-filter-btn" data-color="Xám" style="background-color: #71717a;" title="Xám" onclick="Storefront.setColorFilter('Xám')"></button>
                            <button class="color-filter-btn" data-color="Xanh" style="background-color: #38bdf8;" title="Xanh" onclick="Storefront.setColorFilter('Xanh')"></button>
                            <button class="color-filter-btn" data-color="Rêu" style="background-color: #65a30d;" title="Rêu" onclick="Storefront.setColorFilter('Rêu')"></button>
                            <button class="color-filter-btn" data-color="Be" style="background-color: #d6d3d1;" title="Be" onclick="Storefront.setColorFilter('Be')"></button>
                            <button class="color-filter-btn" data-color="Kem" style="background-color: #fef08a;" title="Kem" onclick="Storefront.setColorFilter('Kem')"></button>
                        </div>
                    </div>

                    <!-- 6. Stock Checkbox -->
                    <div style="border-top: 1px solid var(--border-subtle); padding-top: 16px;">
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.85rem; cursor: pointer; color: var(--text-secondary);">
                            <input type="checkbox" id="filter-in-stock-checkbox" onchange="Storefront.toggleInStockFilter(this.checked)">
                            <span>Chỉ hiển thị mẫu còn hàng</span>
                        </label>
                    </div>

                    <!-- 7. Reset Filter Button -->
                    <button class="btn-reset-filters" onclick="Storefront.resetFilters()">
                        <span data-icon="refresh"></span> Đặt Lại Tất Cả Bộ Lọc
                    </button>
                </aside>

                <!-- ================= RIGHT PRODUCT CATALOG ================= -->
                <section>
                    <!-- Shop Controls Toolbar -->
                    <div class="shop-toolbar">
                        <div class="toolbar-left">
                            <button class="btn-mobile-filter" onclick="Storefront.toggleMobileFilterSidebar()">
                                <span data-icon="sliders"></span> Bộ lọc
                            </button>
                            <span class="product-count-text" id="shop-product-count">
                                Đang tải danh sách...
                            </span>
                        </div>

                        <div class="toolbar-right">
                            <div class="sort-select-wrap">
                                <span>Sắp xếp:</span>
                                <select class="sort-select" id="shop-sort-select" onchange="Storefront.handleSortChange(this.value)">
                                    <option value="popular">Bán chạy nhất</option>
                                    <option value="newest">Mới lên kệ</option>
                                    <option value="price-asc">Giá: Thấp đến Cao</option>
                                    <option value="price-desc">Giá: Cao đến Thấp</option>
                                    <option value="discount">Giảm giá nhiều nhất</option>
                                </select>
                            </div>

                            <div class="view-mode-toggles">
                                <button class="view-btn active" data-view="grid-4" title="4 Cột" onclick="Storefront.setViewMode('grid-4')">
                                    <span data-icon="grid"></span>
                                </button>
                                <button class="view-btn" data-view="grid-3" title="3 Cột" onclick="Storefront.setViewMode('grid-3')">
                                    <span data-icon="grid"></span>
                                </button>
                                <button class="view-btn" data-view="list" title="Dạng danh sách" onclick="Storefront.setViewMode('list')">
                                    <span data-icon="list"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Active Filter Chips Container -->
                    <div class="active-filter-chips" id="shop-active-chips">
                        <!-- Populated by Storefront.js -->
                    </div>

                    <!-- Product Grid -->
                    <div class="products-grid" id="shop-products-grid">
                        <!-- Populated dynamically by Storefront.js -->
                    </div>
                </section>
            </div>
        </div>
    </main>

<?php
include __DIR__ . '/../partials/footer.php';
?>
