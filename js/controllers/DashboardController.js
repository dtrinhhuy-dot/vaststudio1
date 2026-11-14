// ==========================================================================
// VAST STUDIO — MVC ARCHITECTURE: CONTROLLER LAYER (Dashboard)
// File: js/controllers/DashboardController.js
// Aggregates business analytics from Model and triggers DashboardView
// ==========================================================================

(function(window) {
    'use strict';

    class DashboardController {
        static currentChartRange = 7;

        static init() {
            // Dashboard initial setup if needed
        }

        static render() {
            const products = window.StoreModel.getProducts();
            const orders = window.StoreModel.getOrders();
            const customers = window.StoreModel.getCustomers();

            const completedRevenue = orders
                .filter(o => o.orderStatus === 'completed' || o.orderStatus === 'shipping')
                .reduce((sum, o) => sum + (o.total || 0), 0);
            const totalRevenue = completedRevenue + 2570000;

            const totalInventory = products.reduce((sum, p) => sum + (p.stock || 0), 0);
            const lowStockCount = products.filter(p => p.stock <= 10).length;
            const pendingOrders = orders.filter(o => o.orderStatus === 'pending').length;

            const stats = {
                revenue: totalRevenue,
                totalOrders: orders.length,
                pendingOrders,
                totalInventory,
                lowStockCount,
                totalCustomers: customers.length
            };

            window.DashboardView.renderKPIs(stats);
            window.DashboardView.renderChart(DashboardController.currentChartRange);
            window.DashboardView.renderTopProducts(products);
            window.DashboardView.renderRecentOrders(orders);
        }

        static switchChartRange(days) {
            DashboardController.currentChartRange = days;
            window.DashboardView.renderChart(days);
        }
    }

    window.DashboardController = DashboardController;
})(window);

