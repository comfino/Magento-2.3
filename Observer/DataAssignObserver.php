<?php

namespace Comfino\ComfinoGateway\Observer;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class DataAssignObserver extends AbstractDataAssignObserver
{
    public const PAYMENT_LOAN_TYPE = 'type';
    public const PAYMENT_LOAN_TERM = 'term';

    protected array $additionalInformationList = [
        self::PAYMENT_LOAN_TYPE,
        self::PAYMENT_LOAN_TERM,
    ];

    private CheckoutSession $checkoutSession;

    public function __construct(CheckoutSession $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    public function execute(Observer $observer): void
    {
        $data = $this->readDataArgument($observer);
        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        $paymentInfo = $this->readPaymentModelArgument($observer);

        // Try additional_data from checkout form (new hidden field names).
        $loanType = null;
        $loanTerm = null;

        if (is_array($additionalData)) {
            $loanType = $additionalData[self::PAYMENT_LOAN_TYPE] ?? $additionalData['comfino_loan_type'] ?? null;
            $loanTerm = $additionalData[self::PAYMENT_LOAN_TERM] ?? $additionalData['comfino_loan_term'] ?? null;
        }

        // Fall back to checkout session (set by Controller/Api/PaymentState)
        if (empty($loanType)) {
            $loanType = $this->checkoutSession->getComfinoLoanType();
            $loanTerm = $this->checkoutSession->getComfinoLoanTerm();
        }

        if (!empty($loanType)) {
            $paymentInfo->setAdditionalInformation(self::PAYMENT_LOAN_TYPE, $loanType);
        }

        if (!empty($loanTerm)) {
            $paymentInfo->setAdditionalInformation(self::PAYMENT_LOAN_TERM, $loanTerm);
        }
    }
}
