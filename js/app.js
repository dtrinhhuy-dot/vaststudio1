// ==========================================================================
// VAST STUDIO — MVC BOOTSTRAP & ENTRY POINT
// File: js/app.js
// Bootstraps MVC layers and exposes backward-compatible global helpers
// ==========================================================================

document.addEventListener('DOMContentLoaded', () => {
    // Start MVC Application
    window.AppController.init();
});

// Backward-compatible global wrappers for inline HTML event handlers
window.navigateToTab = (tabId) => window.AppController.navigateTo(tabId);
window.toggleSidebar = () => window.AppController.toggleSidebar();
window.toggleTheme = () => window.AppController.toggleTheme();
window.toggleNotificationsModal = () => {
    const modal = document.getElementById('notif-modal');
    if (modal?.classList.contains('active')) {
        window.CommonView.closeModal('notif-modal');
    } else {
        window.CommonView.openModal('notif-modal');
    }
};
window.handleGlobalSearch = (e) => window.AppController.handleGlobalSearch(e);
window.confirmResetData = () => window.AppController.confirmResetData();
window.openStorefrontDemo = () => {
    window.location.href = 'index.html';
};

// Product Actions
window.openAddProductModal = () => window.ProductController.openAddModal();
window.openEditProductModal = (id) => window.ProductController.openEditModal(id);
window.confirmDeleteProduct = (id, name) => window.ProductController.confirmDelete(id, name);
window.handleProductFormSubmit = (e) => window.ProductController.handleFormSubmit(e);
window.renderProductsTable = () => window.ProductController.render();

// Order Actions
window.filterOrdersByStatus = (status) => window.OrderController.filterByStatus(status);
window.renderOrdersTable = () => window.OrderController.render();
window.openOrderDetailsModal = (id) => window.OrderController.openDetailsModal(id);
window.handleOrderStatusChange = (id, status) => window.OrderController.handleStatusChange(id, status);
window.handleModalStatusChange = (id, status) => window.OrderController.handleStatusChange(id, status);
window.printInvoice = () => window.OrderController.printInvoice();

// Dashboard Actions
window.switchChartRange = (days) => window.DashboardController.switchChartRange(days);

// Collection Actions
window.openAddCollectionModal = () => {
    document.getElementById('collection-form')?.reset();
    window.CommonView.openModal('collection-modal');
};
window.handleCollectionFormSubmit = (e) => window.AppController.handleCollectionSubmit(e);

// Customer Actions
window.renderCustomersTable = () => window.AppController.renderCustomers();

// Coupon Actions
window.openAddCouponModal = () => {
    document.getElementById('coupon-form')?.reset();
    const expiry = document.getElementById('cpn-expiryDate');
    if (expiry) expiry.value = '2026-12-31';
    window.CommonView.openModal('coupon-modal');
};
window.handleCouponFormSubmit = (e) => window.AppController.handleCouponSubmit(e);
window.confirmDeleteCoupon = (id, code) => window.AppController.confirmDeleteCoupon(id, code);

// Settings Actions
window.handleSaveSettings = (e) => window.AppController.handleSaveSettings(e);
window.handleSavePaymentSettings = (e) => window.AppController.handleSavePaymentSettings(e);

// Modal Helpers
window.openModal = (id) => window.CommonView.openModal(id);
window.closeModal = (id) => window.CommonView.closeModal(id);
window.showToast = (msg, type) => window.CommonView.showToast(msg, type);
