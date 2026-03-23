<?php

declare(strict_types=1);

namespace Comfino\Common\Shop\Order;

use Comfino\Common\Shop\OrderStatusAdapterInterface;

class StatusManager
{
    /**
     * @var \Comfino\Common\Shop\OrderStatusAdapterInterface
     */
    private $orderStatusAdapter;
    public const STATUS_CREATED = 'CREATED';
    public const STATUS_WAITING_FOR_FILLING = 'WAITING_FOR_FILLING';
    public const STATUS_WAITING_FOR_CONFIRMATION = 'WAITING_FOR_CONFIRMATION';
    public const STATUS_WAITING_FOR_PAYMENT = 'WAITING_FOR_PAYMENT';
    public const STATUS_ACCEPTED = 'ACCEPTED';
    public const STATUS_PAID = 'PAID';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_RESIGN = 'RESIGN';
    public const STATUS_CANCELLED_BY_SHOP = 'CANCELLED_BY_SHOP';
    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUSES = [
        self::STATUS_CREATED,
        self::STATUS_WAITING_FOR_FILLING,
        self::STATUS_WAITING_FOR_CONFIRMATION,
        self::STATUS_WAITING_FOR_PAYMENT,
        self::STATUS_ACCEPTED,
        self::STATUS_PAID,
        self::STATUS_REJECTED,
        self::STATUS_RESIGN,
        self::STATUS_CANCELLED_BY_SHOP,
        self::STATUS_CANCELLED,
    ];

    public const DEFAULT_IGNORED_STATUSES = [
        self::STATUS_WAITING_FOR_FILLING,
        self::STATUS_WAITING_FOR_CONFIRMATION,
        self::STATUS_WAITING_FOR_PAYMENT,
        self::STATUS_PAID,
    ];

    public const DEFAULT_FORBIDDEN_STATUSES = [self::STATUS_RESIGN];

    /**
     * @var $this|null
     */
    private static $instance;

    /**
     * @param \Comfino\Common\Shop\OrderStatusAdapterInterface $orderStatusAdapter
     */
    public static function getInstance($orderStatusAdapter): self
    {
        if (self::$instance === null) {
            self::$instance = new self($orderStatusAdapter);
        }

        return self::$instance;
    }

    private function __construct(OrderStatusAdapterInterface $orderStatusAdapter)
    {
        $this->orderStatusAdapter = $orderStatusAdapter;
    }

    /**
     * @param string $externalId
     * @param string $status
     */
    public function setOrderStatus($externalId, $status): void
    {
        $this->orderStatusAdapter->setStatus($externalId, $status);
    }
}
