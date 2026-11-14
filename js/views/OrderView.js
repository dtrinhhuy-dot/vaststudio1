// ==========================================================================
// VAST STUDIO — MVC ARCHITECTURE: VIEW LAYER (Order)
// File: js/views/OrderView.js
// Responsible for rendering order lists, status tabs, detail modal & invoices
// ==========================================================================

(function(window) {
    'use strict';

    class OrderView {
        static updateTabCounters(counts) {
            if (document.getElementById('count-order-all')) document.getElementById('count-order-all').textContent = counts.all;
            if (document.getElementById('count-order-pending')) document.getElementById('count-order-pending').textContent = counts.pending;
            if (document.getElementById('count-order-processing')) document.getElementById('count-order-processing').textContent = counts.processing;
            if (document.getElementById('count-order-shipping')) document.getElementById('count-order-shipping').textContent = counts.shipping;
            if (document.getElementById('count-order-completed')) document.getElementById('count-order-completed').textContent = counts.completed;
            if (document.getElementById('count-order-cancelled')) document.getElementById('count-order-cancelled').textContent = counts.cancelled;
        }

        static renderTable(orders) {
            const tbody = document.getElementById('orders-table-body');
            if (!tbody) return;

            if (orders.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 48px; color: var(--text-muted);">
                            Không có đơn hàng nào trong mục này.
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = orders.map(o => `
                <tr>
                    <td>
                        <span style="font-family: monospace; font-weight: 700; color: var(--text-primary);">${o.id}</span>
                    </td>
                    <td>
                        <span style="font-size: 0.78rem; color: var(--text-muted);">${o.createdAt}</span>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: var(--text-primary);">${o.customerName}</div>
                        <div style="font-size: 0.72rem; color: var(--text-muted);">${o.customerPhone}</div>
                    </td>
                    <td>
                        <div style="font-size: 0.82rem;">${o.items.map(i => `${i.name} (x${i.quantity})`).join('<br>')}</div>
                    </td>
                    <td>
                        <span class="price-display">${window.CommonView.formatVND(o.total)}</span>
                    </td>
                    <td>
                        <div style="font-size: 0.78rem;">${o.paymentMethod}</div>
                        <div style="font-size: 0.7rem; color: ${o.paymentStatus === 'Đã thanh toán' ? '#10b981' : '#f59e0b'}; font-weight: 600;">
                            ${o.paymentStatus}
                        </div>
                    </td>
                    <td>
                        <select class="filter-select" style="padding: 4px 8px; font-size: 0.75rem;" onchange="window.OrderController.handleStatusChange('${o.id}', this.value)">
                            <option value="pending" ${o.orderStatus === 'pending' ? 'selected' : ''}>Chờ xác nhận</option>
                            <option value="processing" ${o.orderStatus === 'processing' ? 'selected' : ''}>Đang đóng gói</option>
                            <option value="shipping" ${o.orderStatus === 'shipping' ? 'selected' : ''}>Đang giao</option>
                            <option value="completed" ${o.orderStatus === 'completed' ? 'selected' : ''}>Hoàn thành</option>
                            <option value="cancelled" ${o.orderStatus === 'cancelled' ? 'selected' : ''}>Đã hủy</option>
                        </select>
                    </td>
                    <td style="text-align: right;">
                        <div class="actions-cell" style="justify-content: flex-end;">
                            <button class="btn-icon-sm" title="Xem chi tiết đơn" onclick="window.OrderController.openDetailsModal('${o.id}')">
                                <span data-icon="eye"></span>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');

            window.CommonView.renderIcons(tbody);
        }

        static renderDetailsModal(order) {
            document.getElementById('order-modal-title').textContent = `Chi Tiết Đơn Hàng #${order.id} — VAST STUDIO`;
            document.getElementById('order-modal-date').textContent = `Thời gian đặt hàng: ${order.createdAt}`;

            const content = document.getElementById('order-modal-content');
            content.innerHTML = `
                <div class="order-detail-header-card">
                    <div>
                        <span class="form-label" style="margin: 0; font-size: 0.7rem;">Khách Hàng</span>
                        <strong style="font-size: 0.95rem;">${order.customerName}</strong>
                        <p style="font-size: 0.78rem; color: var(--text-muted);">${order.customerPhone}</p>
                        <p style="font-size: 0.78rem; color: var(--text-muted);">${order.customerEmail}</p>
                    </div>
                    <div>
                        <span class="form-label" style="margin: 0; font-size: 0.7rem;">Địa Chỉ Nhận Hàng</span>
                        <p style="font-size: 0.85rem; line-height: 1.4;">${order.shippingAddress}</p>
                        ${order.note ? `<p style="font-size: 0.76rem; color: #f59e0b; margin-top: 4px;"><strong>Ghi chú:</strong> ${order.note}</p>` : ''}
                    </div>
                    <div>
                        <span class="form-label" style="margin: 0; font-size: 0.7rem;">Thanh Toán & Vận Chuyển</span>
                        <p style="font-size: 0.82rem;"><strong>PTTT:</strong> ${order.paymentMethod}</p>
                        <p style="font-size: 0.82rem;"><strong>Trạng thái:</strong> ${order.paymentStatus}</p>
                        <div style="margin-top: 6px;">
                            <label class="form-label" style="font-size: 0.7rem; margin-bottom: 2px;">Cập Nhật Trạng Thái:</label>
                            <select class="form-control" style="padding: 6px; font-size: 0.8rem;" onchange="window.OrderController.handleStatusChange('${order.id}', this.value)">
                                <option value="pending" ${order.orderStatus === 'pending' ? 'selected' : ''}>Chờ xác nhận</option>
                                <option value="processing" ${order.orderStatus === 'processing' ? 'selected' : ''}>Đang đóng gói</option>
                                <option value="shipping" ${order.orderStatus === 'shipping' ? 'selected' : ''}>Đang giao</option>
                                <option value="completed" ${order.orderStatus === 'completed' ? 'selected' : ''}>Hoàn thành</option>
                                <option value="cancelled" ${order.orderStatus === 'cancelled' ? 'selected' : ''}>Đã hủy</option>
                            </select>
                        </div>
                    </div>
                </div>

                <h4 style="font-size: 0.9rem; font-weight: 700; margin-bottom: 12px; font-family: var(--font-heading);">Sản Phẩm Đã Mua (${order.items.length})</h4>
                <div class="order-detail-items-list">
                    ${order.items.map(item => `
                        <div class="order-detail-item">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <img src="${item.image || window.createFashionPlaceholder(item.name)}" style="width: 46px; height: 46px; border-radius: 6px; object-fit: cover;" alt="${item.name}">
                                <div>
                                    <div style="font-weight: 600; font-size: 0.88rem;">${item.name}</div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">Size: <strong>${item.size}</strong> • Màu: <strong>${item.color}</strong> • SL: <strong>x${item.quantity}</strong></div>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div class="price-display">${window.CommonView.formatVND(item.price * item.quantity)}</div>
                                <div style="font-size: 0.72rem; color: var(--text-muted);">${window.CommonView.formatVND(item.price)} / cái</div>
                            </div>
                        </div>
                    `).join('')}
                </div>

                <div class="order-summary-box">
                    <div class="summary-line">
                        <span>Tạm tính tiền hàng:</span>
                        <span>${window.CommonView.formatVND(order.subtotal)}</span>
                    </div>
                    <div class="summary-line">
                        <span>Phí vận chuyển:</span>
                        <span>${order.shippingFee === 0 ? '<strong style="color: #10b981;">Miễn phí (Freeship)</strong>' : window.CommonView.formatVND(order.shippingFee)}</span>
                    </div>
                    ${order.discount ? `
                        <div class="summary-line" style="color: #ef4444;">
                            <span>Giảm giá (${order.couponCode || 'Voucher'}):</span>
                            <span>-${window.CommonView.formatVND(order.discount)}</span>
                        </div>
                    ` : ''}
                    <div class="summary-line summary-total">
                        <span>Tổng giá trị thanh toán:</span>
                        <span style="color: #3b82f6;">${window.CommonView.formatVND(order.total)}</span>
                    </div>
                </div>
            `;
        }
    }

    window.OrderView = OrderView;
})(window);

