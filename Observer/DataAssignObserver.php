<?php

namespace Comfino\ComfinoGateway\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class DataAssignObserver extends AbstractDataAssignObserver
{
    public const PAYMENT_LOAN_TYPE = 'type';
    public const PAYMENT_LOAN_TERM = 'term';

    /**
     * @var array
     */
    protected $additionalInformationList = [
        self::PAYMENT_LOAN_TYPE,
        self::PAYMENT_LOAN_TERM,
    ];

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer): void
    {
        $additionalData = $this->readDataArgument($observer)->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        if (!is_array($additionalData)) {
            return;
        }

        $paymentInfo = $this->readPaymentModelArgument($observer);

        foreach ($this->additionalInformationList as $additionalInformationKey) {
            if (isset($additionalData[$additionalInformationKey])) {
                $paymentInfo->setAdditionalInformation(
                    $additionalInformationKey,
                    $additionalData[$additionalInformationKey]
                );
            }
        }
    }
}
