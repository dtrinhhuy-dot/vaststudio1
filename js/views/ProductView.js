// ==========================================================================
// VAST STUDIO — MVC ARCHITECTURE: VIEW LAYER (Product)
// File: js/views/ProductView.js
// Responsible for rendering the product table, stock alerts & product modal
// ==========================================================================

(function(window) {
    'use strict';

    class ProductView {
        static renderTable(products) {
            const tbody = document.getElementById('products-table-body');
            if (!tbody) return;

            if (products.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 48px; color: var(--text-muted);">
                            <p style="font-size: 0.95rem; margin-bottom: 6px;">Không tìm thấy sản phẩm nào phù hợp với bộ lọc.</p>
                            <button class="btn-secondary" onclick="window.ProductController.resetFilters()">Đặt lại bộ lọc</button>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = products.map(p => {
                const sizesText = Array.isArray(p.sizes) ? p.sizes.join(', ') : p.sizes;
                const colorsText = Array.isArray(p.colors) ? p.colors.join(', ') : p.colors;
                
                let stockBadge = `<span class="badge badge-success">${p.stock} cái</span>`;
                if (p.stock <= 10 && p.stock > 0) {
                    stockBadge = `<span class="badge badge-warning">${p.stock} cái (Sắp hết)</span>`;
                } else if (p.stock === 0) {
                    stockBadge = `<span class="badge badge-danger">Hết hàng</span>`;
                }

                const statusBadge = p.status === 'active' 
                    ? `<span class="badge badge-success">Đang bán</span>` 
                    : `<span class="badge" style="background: var(--bg-subtle); color: var(--text-muted);">Bản nháp</span>`;

                return `
                    <tr>
                        <td>
                            <div class="product-cell">
                                <img class="product-thumb" src="${p.image}" alt="${p.name}" onerror="this.src='${p.fallbackImage}'">
                                <div>
                                    <div class="product-meta-title">${p.name}</div>
                                    <div class="product-meta-sku">${p.sku}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge badge-purple">${p.category}</span></td>
                        <td><span style="font-size: 0.8rem; color: var(--text-secondary);">${p.collection || '—'}</span></td>
                        <td>
                            <span class="price-display">${window.CommonView.formatVND(p.price)}</span>
                            ${p.originalPrice ? `<span class="original-price">${window.CommonView.formatVND(p.originalPrice)}</span>` : ''}
                        </td>
                        <td>
                            <div style="font-size: 0.78rem;"><strong>Size:</strong> ${sizesText}</div>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">${colorsText}</div>
                        </td>
                        <td>${stockBadge}</td>
                        <td><strong>${p.salesCount || 0}</strong></td>
                        <td>${statusBadge}</td>
                        <td style="text-align: right;">
                            <div class="actions-cell" style="justify-content: flex-end;">
                                <button class="btn-icon-sm" title="Chỉnh sửa sản phẩm" onclick="window.ProductController.openEditModal('${p.id}')">
                                    <span data-icon="edit"></span>
                                </button>
                                <button class="btn-icon-sm delete-btn" title="Xóa sản phẩm" onclick="window.ProductController.confirmDelete('${p.id}', '${p.name.replace(/'/g, "\\'")}')">
                                    <span data-icon="trash"></span>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            window.CommonView.renderIcons(tbody);
        }

        static populateForm(product = null) {
            const form = document.getElementById('product-form');
            if (!form) return;

            if (!product) {
                document.getElementById('product-modal-title').textContent = 'Thêm Sản Phẩm Mới (VAST STUDIO)';
                form.reset();
                document.getElementById('prd-id').value = '';
                document.getElementById('prd-status').value = 'active';
            } else {
                document.getElementById('product-modal-title').textContent = `Chỉnh Sửa Sản Phẩm: ${product.name}`;
                document.getElementById('prd-id').value = product.id;
                document.getElementById('prd-name').value = product.name;
                document.getElementById('prd-sku').value = product.sku;
                document.getElementById('prd-category').value = product.category;
                document.getElementById('prd-collection').value = product.collection || 'Streetwear Core';
                document.getElementById('prd-price').value = product.price;
                document.getElementById('prd-originalPrice').value = product.originalPrice || '';
                document.getElementById('prd-sizes').value = Array.isArray(product.sizes) ? product.sizes.join(', ') : product.sizes;
                document.getElementById('prd-colors').value = Array.isArray(product.colors) ? product.colors.join(', ') : product.colors;
                document.getElementById('prd-stock').value = product.stock;
                document.getElementById('prd-status').value = product.status || 'active';
                document.getElementById('prd-image').value = product.image && !product.image.startsWith('data:') ? product.image : '';
                document.getElementById('prd-description').value = product.description || '';
            }
        }
    }

    window.ProductView = ProductView;
})(window);

