<?php

namespace Comfino\ComfinoGateway\Controller\Api;

use Comfino\DebugLogger;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;

class PaymentState implements HttpPostActionInterface
{
    private RequestInterface $request;
    private JsonFactory $jsonFactory;
    private CheckoutSession $checkoutSession;

    public function __construct(RequestInterface $request, JsonFactory $jsonFactory, CheckoutSession $checkoutSession)
    {
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->checkoutSession = $checkoutSession;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        $loanAmount = (int) $this->request->getParam('loan_amount', 0);
        $loanType = (string) $this->request->getParam('loan_type', '');
        $loanTerm = (int) $this->request->getParam('loan_term', 0);

        if ($loanAmount <= 0 || empty($loanType)) {
            DebugLogger::logEvent(
                '[PAYMENT_STATE]',
                'PaymentState::execute: Invalid parameters.',
                ['loan_amount' => $loanAmount, 'loan_type' => $loanType, 'loan_term' => $loanTerm]
            );

            return $result->setData(['success' => false, 'error' => 'Invalid parameters.']);
        }

        DebugLogger::logEvent(
            '[PAYMENT_STATE]',
            'PaymentState::execute: Updating checkout session.',
            ['loan_amount' => $loanAmount, 'loan_type' => $loanType, 'loan_term' => $loanTerm]
        );

        $this->checkoutSession->setComfinoLoanAmount($loanAmount);
        $this->checkoutSession->setComfinoLoanType($loanType);
        $this->checkoutSession->setComfinoLoanTerm($loanTerm);

        return $result->setData(['success' => true]);
    }
}
