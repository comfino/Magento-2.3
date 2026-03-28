<?php

namespace Comfino\ComfinoGateway\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class DataAssignObserver extends AbstractDataAssignObserver
{
    public const PAYMENT_LOAN_TYPE = 'loanType';
    public const PAYMENT_LOAN_TERM = 'loanTerm';

    public function execute(Observer $observer): void
    {
        $data = $this->readDataArgument($observer);
        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        $paymentInfo = $this->readPaymentModelArgument($observer);

        if (!is_array($additionalData)) {
            return;
        }

        $loanType = $additionalData[self::PAYMENT_LOAN_TYPE] ?? null;
        $loanTerm = $additionalData[self::PAYMENT_LOAN_TERM] ?? null;

        if (!empty($loanType)) {
            $paymentInfo->setAdditionalInformation(self::PAYMENT_LOAN_TYPE, $loanType);
        }

        if (!empty($loanTerm)) {
            $paymentInfo->setAdditionalInformation(self::PAYMENT_LOAN_TERM, $loanTerm);
        }
    }
}
