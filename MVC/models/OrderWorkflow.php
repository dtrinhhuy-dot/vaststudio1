<?php

class OrderWorkflow
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPING = 'shipping';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_AWAITING_CONFIRMATION = 'awaiting_confirmation';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCEL_REQUESTED = 'cancel_requested';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_RETURN_REQUESTED = 'return_requested';
    public const STATUS_RETURN_AWAITING_SHIPMENT = 'return_awaiting_shipment';
    public const STATUS_RETURN_RECEIVED = 'return_received';
    public const STATUS_RETURN_COMPLETED = 'return_completed';

    public const RETURN_PENDING_REVIEW = 'pending_review';
    public const RETURN_APPROVED = 'approved';
    public const RETURN_REJECTED = 'rejected';
    public const RETURN_AWAITING_SHIPMENT = 'awaiting_shipment';
    public const RETURN_RECEIVED = 'received';
    public const RETURN_PROCESSING_EXCHANGE = 'processing_exchange';
    public const RETURN_COMPLETED = 'completed';

    public static function orderStatuses(): array
    {
        return [
            self::STATUS_PENDING => ['label' => 'Chờ xác nhận', 'badge' => 'badge-warning', 'icon' => 'clock'],
            self::STATUS_CONFIRMED => ['label' => 'Đã xác nhận', 'badge' => 'badge-info', 'icon' => 'check'],
            self::STATUS_PROCESSING => ['label' => 'Đang xử lý', 'badge' => 'badge-purple', 'icon' => 'package'],
            self::STATUS_SHIPPING => ['label' => 'Đang giao hàng', 'badge' => 'badge-info', 'icon' => 'truck'],
            self::STATUS_DELIVERED => ['label' => 'Giao hàng thành công', 'badge' => 'badge-success', 'icon' => 'checkCircle'],
            self::STATUS_AWAITING_CONFIRMATION => ['label' => 'Chờ xác nhận nhận hàng', 'badge' => 'badge-warning', 'icon' => 'clock'],
            self::STATUS_COMPLETED => ['label' => 'Hoàn tất', 'badge' => 'badge-success', 'icon' => 'checkCircle'],
            self::STATUS_CANCEL_REQUESTED => ['label' => 'Yêu cầu hủy', 'badge' => 'badge-danger', 'icon' => 'alert'],
            self::STATUS_CANCELLED => ['label' => 'Đã hủy', 'badge' => 'badge-danger', 'icon' => 'xCircle'],
            self::STATUS_RETURN_REQUESTED => ['label' => 'Yêu cầu đổi trả', 'badge' => 'badge-warning', 'icon' => 'rotateCcw'],
            self::STATUS_RETURN_AWAITING_SHIPMENT => ['label' => 'Chờ gửi hàng đổi trả', 'badge' => 'badge-purple', 'icon' => 'truck'],
            self::STATUS_RETURN_RECEIVED => ['label' => 'Đã nhận hàng đổi trả', 'badge' => 'badge-info', 'icon' => 'package'],
            self::STATUS_RETURN_COMPLETED => ['label' => 'Đổi trả hoàn tất', 'badge' => 'badge-success', 'icon' => 'checkCircle'],
        ];
    }

    public static function statusLabel(string $status): string
    {
        $all = self::orderStatuses();
        return $all[$status]['label'] ?? $status;
    }

    public static function statusMeta(string $status): array
    {
        $all = self::orderStatuses();
        return $all[$status] ?? ['label' => $status, 'badge' => 'badge-neutral', 'icon' => 'clock'];
    }

    public static function returnStatuses(): array
    {
        return [
            self::RETURN_PENDING_REVIEW => ['label' => 'Chờ duyệt', 'badge' => 'badge-warning'],
            self::RETURN_APPROVED => ['label' => 'Đã chấp thuận', 'badge' => 'badge-info'],
            self::RETURN_REJECTED => ['label' => 'Bị từ chối', 'badge' => 'badge-danger'],
            self::RETURN_AWAITING_SHIPMENT => ['label' => 'Chờ khách gửi hàng', 'badge' => 'badge-purple'],
            self::RETURN_RECEIVED => ['label' => 'Đã nhận hàng đổi trả', 'badge' => 'badge-info'],
            self::RETURN_PROCESSING_EXCHANGE => ['label' => 'Đang xử lý đổi hàng', 'badge' => 'badge-purple'],
            self::RETURN_COMPLETED => ['label' => 'Hoàn tất đổi trả', 'badge' => 'badge-success'],
        ];
    }

    public static function returnStatusLabel(string $status): string
    {
        $all = self::returnStatuses();
        return $all[$status]['label'] ?? $status;
    }

    public static function returnReasons(): array
    {
        return [
            'defective' => 'Sản phẩm bị lỗi',
            'damaged' => 'Sản phẩm bị hư hỏng',
            'wrong_item' => 'Giao sai sản phẩm',
            'wrong_size' => 'Sai kích thước',
            'wrong_color' => 'Sai màu sắc',
            'not_as_described' => 'Sản phẩm không giống mô tả',
            'other' => 'Lý do khác',
        ];
    }

    /**
     * Strict next-step transition allowed for Admin fulfillment.
     * Admin is strictly limited to advancing one step at a time and CANNOT mark 'completed'.
     */
    public static function nextAdminFulfillmentStep(string $currentStatus): ?string
    {
        return match ($currentStatus) {
            self::STATUS_PENDING => self::STATUS_CONFIRMED,
            self::STATUS_CONFIRMED => self::STATUS_PROCESSING,
            self::STATUS_PROCESSING => self::STATUS_SHIPPING,
            self::STATUS_SHIPPING => self::STATUS_DELIVERED,
            self::STATUS_DELIVERED => self::STATUS_AWAITING_CONFIRMATION,
            default => null,
        };
    }

    public static function canAdminAdvanceTo(string $currentStatus, string $targetStatus): bool
    {
        if ($targetStatus === self::STATUS_COMPLETED) {
            return false;
        }
        return self::nextAdminFulfillmentStep($currentStatus) === $targetStatus;
    }

    public static function canAdminDirectCancel(string $currentStatus): bool
    {
        return $currentStatus === self::STATUS_PENDING;
    }

    public static function canCustomerDirectCancel(string $currentStatus): bool
    {
        return $currentStatus === self::STATUS_PENDING;
    }

    public static function canCustomerRequestCancel(string $currentStatus): bool
    {
        return $currentStatus === self::STATUS_CONFIRMED;
    }

    public static function canCustomerConfirmReceipt(string $currentStatus): bool
    {
        return in_array($currentStatus, [self::STATUS_DELIVERED, self::STATUS_AWAITING_CONFIRMATION], true);
    }

    public static function canCustomerRequestReturn(string $currentStatus): bool
    {
        return in_array($currentStatus, [self::STATUS_DELIVERED, self::STATUS_AWAITING_CONFIRMATION], true);
    }

    public static function isRevenueRecognized(string $orderStatus): bool
    {
        return $orderStatus === self::STATUS_COMPLETED;
    }
}
