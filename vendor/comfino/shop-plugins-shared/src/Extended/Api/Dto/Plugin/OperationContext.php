<?php

declare(strict_types=1);

namespace Comfino\Extended\Api\Dto\Plugin;

class OperationContext
{
    public const Unknown = 'unknown';
    public const WidgetRendering = 'widget_rendering';
    public const PaywallRendering = 'paywall_rendering';
    public const PaymentProcessing = 'payment_processing';
    public const OrderCreation = 'order_creation';
    public const OrderCancellation = 'order_cancellation';
    public const OrderStatusChange = 'order_status_change';
    public const WebhookProcessing = 'webhook_processing';
    public const ApiCommunication = 'api_communication';
    public const Configuration = 'configuration';
    public const ShopSynchronization = 'shop_synchronization';
}
