<?php

namespace Comfino\Order;

use Comfino\Common\Shop\Order\StatusManager;
use Magento\Sales\Model\Order;

/**
 * Magento-specific order status definitions for Comfino.
 *
 * Defines the mapping between Comfino API statuses and Magento custom order statuses, using Magento's native
 * state/status two-level system.
 *
 * Each custom Comfino status is assigned to a standard Magento state in CUSTOM_STATUS_LABELS. This means the custom
 * status IS the final, visible status on the order - the merchant sees e.g. "Credit granted (Comfino)" in the order
 * grid, not just the generic "Processing" label.
 *
 * Status flow (single-step, idiomatic Magento):
 *   - Set order state (from CUSTOM_STATUS_LABELS['state']) and custom status code simultaneously.
 *   - Add one history entry with the Comfino status string as a comment for audit trail.
 *   - No second state transition is needed - state is already encoded in CUSTOM_STATUS_LABELS.
 *
 * Intermediate statuses (WAITING_FOR_FILLING, WAITING_FOR_CONFIRMATION, WAITING_FOR_PAYMENT, PAID) are filtered
 * upstream by StatusNotification via DEFAULT_IGNORED_STATUSES. RESIGN is filtered via DEFAULT_FORBIDDEN_STATUSES.
 *
 * @see StatusManager::DEFAULT_IGNORED_STATUSES
 * @see StatusManager::DEFAULT_FORBIDDEN_STATUSES
 * @see StatusAdapter
 */
final class ShopStatusManager
{
    /**
     * Default mapping between Comfino statuses and Magento custom order status codes.
     *
     * Used as the default value for the COMFINO_STATUS_MAP configuration option, which is sent to the Comfino API
     * via the /configuration endpoint so the backend knows which shop statuses the plugin uses.
     * The store owner may override these via the developer settings.
     *
     * @var array<string, string>
     */
    public const DEFAULT_STATUS_MAP = [
        StatusManager::STATUS_ACCEPTED => 'comfino_accepted',
        StatusManager::STATUS_CANCELLED => 'comfino_cancelled',
        StatusManager::STATUS_REJECTED => 'comfino_rejected',
        StatusManager::STATUS_CANCELLED_BY_SHOP => 'comfino_cancelled_by_shop',
    ];

    /**
     * Comfino API status -> custom Magento status code displayed in the order admin grid.
     *
     * Covers all Comfino statuses that reach StatusAdapter (i.e. not filtered by ignored/forbidden lists).
     * CREATED is included so Comfino orders are identifiable from the very first webhook.
     *
     * The target Magento state for each custom status code is defined in CUSTOM_STATUS_LABELS['state'].
     *
     * @var array<string, string>
     */
    public const CUSTOM_STATUS_MAP = [
        StatusManager::STATUS_CREATED => 'comfino_created',
        StatusManager::STATUS_ACCEPTED => 'comfino_accepted',
        StatusManager::STATUS_CANCELLED => 'comfino_cancelled',
        StatusManager::STATUS_REJECTED => 'comfino_rejected',
        StatusManager::STATUS_CANCELLED_BY_SHOP => 'comfino_cancelled_by_shop',
    ];

    /**
     * Custom Magento status code -> display labels and Magento state assignment.
     *
     * Used by Setup\Patch\Data\AddComfinoOrderStatuses to register custom statuses in the database, and by
     * Setup\Uninstall to remove them on module uninstall.
     *
     * The 'state' value assigns each custom status to the correct Magento order workflow state, so Magento routes and
     * permissions work correctly (e.g. cancellation, invoicing).
     *
     * @var array<string, array<string, string>>
     */
    public const CUSTOM_STATUS_LABELS = [
        'comfino_created' => [
            'label' => 'Order created - waiting for payment (Comfino)',
            'label_pl' => 'Zamówienie utworzone - oczekiwanie na płatność (Comfino)',
            'state' => Order::STATE_PENDING_PAYMENT,
        ],
        'comfino_accepted' => [
            'label' => 'Credit granted (Comfino)',
            'label_pl' => 'Kredyt udzielony (Comfino)',
            'state' => Order::STATE_PROCESSING,
        ],
        'comfino_rejected' => [
            'label' => 'Credit rejected (Comfino)',
            'label_pl' => 'Wniosek kredytowy odrzucony (Comfino)',
            'state' => Order::STATE_CANCELED,
        ],
        'comfino_cancelled' => [
            'label' => 'Canceled (Comfino)',
            'label_pl' => 'Anulowano (Comfino)',
            'state' => Order::STATE_CANCELED,
        ],
        'comfino_cancelled_by_shop' => [
            'label' => 'Canceled by shop (Comfino)',
            'label_pl' => 'Anulowano przez sklep (Comfino)',
            'state' => Order::STATE_CANCELED,
        ],
    ];
}
