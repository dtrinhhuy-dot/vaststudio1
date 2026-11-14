// ==========================================================================
// VAST STUDIO — MVC ARCHITECTURE: VIEW LAYER (Dashboard)
// File: js/views/DashboardView.js
// Responsible for rendering business metrics, SVG chart & summaries
// ==========================================================================

(function(window) {
    'use strict';

    class DashboardView {
        static renderKPIs(stats) {
            document.getElementById('stat-revenue').textContent = window.CommonView.formatVND(stats.revenue);
            document.getElementById('stat-orders-count').textContent = `${stats.totalOrders} đơn`;
            document.getElementById('stat-pending-sub').textContent = `${stats.pendingOrders} đơn chờ duyệt`;
            document.getElementById('stat-inventory-count').textContent = `${stats.totalInventory} cái`;

            const lowStockBadge = document.getElementById('stat-low-stock-count');
            if (lowStockBadge) {
                lowStockBadge.textContent = `${stats.lowStockCount} mẫu sắp hết`;
                lowStockBadge.className = stats.lowStockCount > 0 ? 'badge badge-warning' : 'badge badge-success';
            }

            document.getElementById('stat-customers-count').textContent = `${stats.totalCustomers} thành viên`;
        }

        static renderChart(days = 7) {
            const container = document.getElementById('revenue-chart-container');
            if (!container) return;

            document.getElementById('btn-chart-7d')?.classList.toggle('active', days === 7);
            document.getElementById('btn-chart-30d')?.classList.toggle('active', days === 30);

            const data7 = [
                { label: 'T2 (08/11)', value: 1250000, orders: 3 },
                { label: 'T3 (09/11)', value: 2150000, orders: 4 },
                { label: 'T4 (10/11)', value: 1680000, orders: 2 },
                { label: 'T5 (11/11)', value: 3120000, orders: 5 },
                { label: 'T6 (12/11)', value: 2840000, orders: 4 },
                { label: 'T7 (13/11)', value: 4350000, orders: 7 },
                { label: 'CN (Hôm nay)', value: 3820000, orders: 6 }
            ];

            const data30 = [
                { label: 'Tuần 1', value: 14200000, orders: 28 },
                { label: 'Tuần 2', value: 19800000, orders: 36 },
                { label: 'Tuần 3', value: 24500000, orders: 45 },
                { label: 'Tuần 4', value: 31200000, orders: 58 }
            ];

            const data = days === 7 ? data7 : data30;
            const maxVal = Math.max(...data.map(d => d.value)) * 1.15;
            const chartHeight = 200;
            const barWidth = days === 7 ? 42 : 68;
            const gap = (100 / data.length);

            let barsSvg = '';
            data.forEach((d, i) => {
                const heightPct = (d.value / maxVal);
                const barH = Math.round(chartHeight * heightPct);
                const y = chartHeight - barH;
                const xPct = (i + 0.5) * gap;

                barsSvg += `
                    <g class="chart-group">
                        <rect x="${xPct}%" y="${y}" width="${barWidth}" height="${barH}" 
                              rx="6" transform="translate(-${barWidth/2}, 0)"
                              fill="url(#chartGrad)" class="chart-bar">
                            <title>${d.label}: ${window.CommonView.formatVND(d.value)} (${d.orders} đơn)</title>
                        </rect>
                        <text x="${xPct}%" y="${chartHeight + 25}" text-anchor="middle" 
                              fill="var(--text-muted)" font-size="11" font-weight="500">${d.label}</text>
                        <text x="${xPct}%" y="${y - 8}" text-anchor="middle" 
                              fill="var(--text-primary)" font-size="11" font-weight="700">${(d.value / 1000000).toFixed(1)}M</text>
                    </g>
                `;
            });

            container.innerHTML = `
                <svg class="chart-svg" viewBox="0 0 700 240" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.9"/>
                            <stop offset="100%" stop-color="#1d4ed8" stop-opacity="0.3"/>
                        </linearGradient>
                    </defs>
                    <line x1="0" y1="40" x2="100%" y2="40" stroke="var(--border-subtle)" stroke-dasharray="4"/>
                    <line x1="0" y1="100" x2="100%" y2="100" stroke="var(--border-subtle)" stroke-dasharray="4"/>
                    <line x1="0" y1="160" x2="100%" y2="160" stroke="var(--border-subtle)" stroke-dasharray="4"/>
                    <line x1="0" y1="${chartHeight}" x2="100%" y2="${chartHeight}" stroke="var(--border-color)"/>
                    ${barsSvg}
                </svg>
            `;
        }

        static renderTopProducts(products) {
            const listEl = document.getElementById('dashboard-top-products');
            if (!listEl) return;

            const sorted = [...products].sort((a, b) => (b.salesCount || 0) - (a.salesCount || 0)).slice(0, 4);

            listEl.innerHTML = sorted.map(item => `
                <div class="top-item-row">
                    <div class="top-item-info">
                        <img class="top-item-img" src="${item.image}" alt="${item.name}" onerror="this.src='${item.fallbackImage}'">
                        <div>
                            <div class="top-item-name">${item.name}</div>
                            <div class="top-item-sub">${item.category} • ${item.collection || 'VAST Collection'}</div>
                        </div>
                    </div>
                    <div class="top-item-sales">
                        <div class="top-item-count">${item.salesCount} đã bán</div>
                        <div class="top-item-revenue">${window.CommonView.formatVND(item.price)}</div>
                    </div>
                </div>
            `).join('');
        }

        static renderRecentOrders(orders, onOpenDetails) {
            const tbody = document.getElementById('dashboard-recent-orders');
            if (!tbody) return;

            const recents = orders.slice(0, 5);
            tbody.innerHTML = recents.map(order => `
                <tr>
                    <td>
                        <span style="font-family: monospace; font-weight: 700; color: var(--text-primary);">${order.id}</span>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: var(--text-primary);">${order.customerName}</div>
                        <div style="font-size: 0.72rem; color: var(--text-muted);">${order.customerPhone}</div>
                    </td>
                    <td>
                        <span style="font-size: 0.82rem;">${order.items.map(i => `${i.name} (x${i.quantity})`).join(', ')}</span>
                    </td>
                    <td>
                        <span class="price-display">${window.CommonView.formatVND(order.total)}</span>
                    </td>
                    <td>
                        <span style="font-size: 0.76rem;">${order.paymentMethod}</span>
                    </td>
                    <td>
                        ${DashboardView.getStatusBadge(order.orderStatus)}
                    </td>
                    <td>
                        <button class="btn-icon-sm" title="Xem chi tiết đơn" onclick="window.OrderController.openDetailsModal('${order.id}')">
                            <span data-icon="eye"></span>
                        </button>
                    </td>
                </tr>
            `).join('');

            window.CommonView.renderIcons(tbody);
        }

        static getStatusBadge(status) {
            switch (status) {
                case 'pending': return `<span class="badge badge-warning"><span data-icon="clock"></span> Chờ xác nhận</span>`;
                case 'processing': return `<span class="badge badge-purple"><span data-icon="package"></span> Đang đóng gói</span>`;
                case 'shipping': return `<span class="badge badge-info"><span data-icon="truck"></span> Đang giao</span>`;
                case 'completed': return `<span class="badge badge-success"><span data-icon="checkCircle"></span> Hoàn thành</span>`;
                case 'cancelled': return `<span class="badge badge-danger"><span data-icon="xCircle"></span> Đã hủy</span>`;
                default: return `<span class="badge">${status}</span>`;
            }
        }
    }

    window.DashboardView = DashboardView;
})(window);

