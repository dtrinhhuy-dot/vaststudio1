// ==========================================================================
// VAST STUDIO — MVC ARCHITECTURE: CONTROLLER LAYER (Product)
// File: js/controllers/ProductController.js
// Handles product business logic, user inputs, interacts with Model & View
// ==========================================================================

(function(window) {
    'use strict';

    class ProductController {
        static init() {
            // Event listeners for product filters
            document.getElementById('product-search-input')?.addEventListener('input', () => ProductController.render());
            document.getElementById('product-category-filter')?.addEventListener('change', () => ProductController.render());
            document.getElementById('product-stock-filter')?.addEventListener('change', () => ProductController.render());
            document.getElementById('product-sort-filter')?.addEventListener('change', () => ProductController.render());

            // Product form submission
            document.getElementById('product-form')?.addEventListener('submit', (e) => ProductController.handleFormSubmit(e));
        }

        static render() {
            const products = window.StoreModel.getProducts();
            const searchVal = (document.getElementById('product-search-input')?.value || '').toLowerCase().trim();
            const catVal = document.getElementById('product-category-filter')?.value || 'all';
            const stockVal = document.getElementById('product-stock-filter')?.value || 'all';
            const sortVal = document.getElementById('product-sort-filter')?.value || 'newest';

            let filtered = products.filter(p => {
                const matchSearch = p.name.toLowerCase().includes(searchVal) || 
                                    p.sku.toLowerCase().includes(searchVal) ||
                                    (p.collection && p.collection.toLowerCase().includes(searchVal));
                const matchCat = catVal === 'all' || p.category === catVal;
                let matchStock = true;
                if (stockVal === 'in_stock') matchStock = p.stock > 10;
                if (stockVal === 'low_stock') matchStock = p.stock > 0 && p.stock <= 10;
                if (stockVal === 'out_of_stock') matchStock = p.stock === 0;

                return matchSearch && matchCat && matchStock;
            });

            filtered.sort((a, b) => {
                if (sortVal === 'price_asc') return a.price - b.price;
                if (sortVal === 'price_desc') return b.price - a.price;
                if (sortVal === 'stock_desc') return b.stock - a.stock;
                if (sortVal === 'sales_desc') return (b.salesCount || 0) - (a.salesCount || 0);
                return new Date(b.createdAt || 0) - new Date(a.createdAt || 0);
            });

            window.ProductView.renderTable(filtered);
            window.AppController.updateCounters();
        }

        static resetFilters() {
            if (document.getElementById('product-search-input')) document.getElementById('product-search-input').value = '';
            if (document.getElementById('product-category-filter')) document.getElementById('product-category-filter').value = 'all';
            if (document.getElementById('product-stock-filter')) document.getElementById('product-stock-filter').value = 'all';
            if (document.getElementById('product-sort-filter')) document.getElementById('product-sort-filter').value = 'newest';
            ProductController.render();
        }

        static openAddModal() {
            window.ProductView.populateForm(null);
            window.CommonView.openModal('product-modal');
        }

        static openEditModal(id) {
            const product = window.StoreModel.getProductById(id);
            if (!product) return;
            window.ProductView.populateForm(product);
            window.CommonView.openModal('product-modal');
        }

        static handleFormSubmit(e) {
            e.preventDefault();
            const id = document.getElementById('prd-id').value;
            const name = document.getElementById('prd-name').value.trim();
            const sku = document.getElementById('prd-sku').value.trim();
            const category = document.getElementById('prd-category').value;
            const collection = document.getElementById('prd-collection').value;
            const price = parseInt(document.getElementById('prd-price').value) || 0;
            const originalPrice = parseInt(document.getElementById('prd-originalPrice').value) || 0;
            const sizes = document.getElementById('prd-sizes').value.split(',').map(s => s.trim()).filter(Boolean);
            const colors = document.getElementById('prd-colors').value.split(',').map(c => c.trim()).filter(Boolean);
            const stock = parseInt(document.getElementById('prd-stock').value) || 0;
            const status = document.getElementById('prd-status').value;
            const imageInput = document.getElementById('prd-image').value.trim();
            const description = document.getElementById('prd-description').value.trim();

            const fallbackImage = window.createFashionPlaceholder(name);
            const image = imageInput || fallbackImage;

            const productData = {
                id: id || undefined,
                name,
                sku,
                category,
                collection,
                price,
                originalPrice,
                sizes,
                colors,
                stock,
                status,
                image,
                fallbackImage,
                description
            };

            window.StoreModel.saveProduct(productData);
            window.CommonView.closeModal('product-modal');
            ProductController.render();
            window.DashboardController.render();
            window.CommonView.showToast(id ? `Đã cập nhật sản phẩm "${name}" thành công!` : `Đã thêm sản phẩm "${name}" vào kho!`);
        }

        static confirmDelete(id, name) {
            if (confirm(`Bạn có chắc chắn muốn xóa sản phẩm "${name}" khỏi kho VAST STUDIO?`)) {
                window.StoreModel.deleteProduct(id);
                ProductController.render();
                window.DashboardController.render();
                window.CommonView.showToast(`Đã xóa sản phẩm "${name}".`, 'info');
            }
        }
    }

    window.ProductController = ProductController;
})(window);

