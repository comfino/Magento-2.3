<?php

namespace Comfino\ComfinoGateway\Model\Adminhtml\System\Config\Source;

use Comfino\ComfinoGateway\Model\Connector\Service\ApplicationService;
use Magento\Framework\Data\OptionSourceInterface;

class WidgetOfferType implements OptionSourceInterface
{
    /**
     * @var array
     */
    private static $productTypes;

    /**
     * @var ApplicationService
     */
    private $applicationService;

    public function __construct(ApplicationService $applicationService)
    {
        $this->applicationService = $applicationService;
    }

    public function toOptionArray(): array
    {
        if (self::$productTypes !== null) {
            return self::$productTypes;
        }

        if (($externalProductTypes = $this->applicationService->getProductTypes()) !== null) {
            self::$productTypes = [];

            foreach ($externalProductTypes as $productTypeCode => $productTypeName) {
                self::$productTypes[] = ['value' => $productTypeCode, 'label' => $productTypeName];
            }

            return self::$productTypes;
        }

        return [
            ['value' => 'INSTALLMENTS_ZERO_PERCENT', 'label' => __('Zero percent installments')],
            ['value' => 'CONVENIENT_INSTALLMENTS', 'label' => __('Convenient installments')],
            ['value' => 'PAY_LATER', 'label' => __('Pay later')],
        ];
    }
}
