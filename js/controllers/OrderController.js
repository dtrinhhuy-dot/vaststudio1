// ==========================================================================
// VAST STUDIO — MVC ARCHITECTURE: CONTROLLER LAYER (Order)
// File: js/controllers/OrderController.js
// Handles order events, status updates, invoice printing & detail modal
// ==========================================================================

(function(window) {
    'use strict';

    class OrderController {
        static currentStatusFilter = 'all';

        static init() {
            document.getElementById('order-search-input')?.addEventListener('input', () => OrderController.render());
        }

        static filterByStatus(status) {
            OrderController.currentStatusFilter = status;
            document.querySelectorAll('#order-status-tabs .filter-pill').forEach(btn => {
                btn.classList.toggle('active', btn.getAttribute('onclick')?.includes(`'${status}'`));
            });
            OrderController.render();
        }

        static render() {
            const orders = window.StoreModel.getOrders();
            const searchVal = (document.getElementById('order-search-input')?.value || '').toLowerCase().trim();

            const counts = {
                all: orders.length,
                pending: orders.filter(o => o.orderStatus === 'pending').length,
                processing: orders.filter(o => o.orderStatus === 'processing').length,
                shipping: orders.filter(o => o.orderStatus === 'shipping').length,
                completed: orders.filter(o => o.orderStatus === 'completed').length,
                cancelled: orders.filter(o => o.orderStatus === 'cancelled').length
            };
            window.OrderView.updateTabCounters(counts);

            let filtered = orders.filter(o => {
                const matchStatus = OrderController.currentStatusFilter === 'all' || o.orderStatus === OrderController.currentStatusFilter;
                const matchSearch = o.id.toLowerCase().includes(searchVal) ||
                                    o.customerName.toLowerCase().includes(searchVal) ||
                                    o.customerPhone.includes(searchVal);
                return matchStatus && matchSearch;
            });

            window.OrderView.renderTable(filtered);
            window.AppController.updateCounters();
        }

        static handleStatusChange(orderId, newStatus) {
            window.StoreModel.updateOrderStatus(orderId, newStatus);
            OrderController.render();
            window.DashboardController.render();
            window.CommonView.showToast(`Đơn hàng #${orderId} đã cập nhật sang "${newStatus}".`);
        }

        static openDetailsModal(orderId) {
            const order = window.StoreModel.getOrderById(orderId);
            if (!order) return;
            window.OrderView.renderDetailsModal(order);
            window.CommonView.openModal('order-modal');
        }

        static printInvoice() {
            window.print();
        }
    }

    window.OrderController = OrderController;
})(window);

