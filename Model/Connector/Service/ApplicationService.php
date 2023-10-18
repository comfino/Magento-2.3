<?php

namespace Comfino\ComfinoGateway\Model\Connector\Service;

use Comfino\ComfinoGateway\Api\ApplicationServiceInterface;
use Comfino\ComfinoGateway\Logger\ErrorLogger;
use Comfino\ComfinoGateway\Model\ComfinoApplicationFactory;
use Comfino\ComfinoGateway\Api\ComfinoStatusManagementInterface;
use Comfino\ComfinoGateway\Model\ComfinoStatusManagement;
use Comfino\ComfinoGateway\Model\Connector\Transaction\Response\ApplicationResponse;
use Comfino\ComfinoGateway\Helper\Data;
use Comfino\ComfinoGateway\Helper\TransactionHelper;
use Comfino\ComfinoGateway\Api\Data\ApplicationResponseInterface;
use Comfino\ComfinoGateway\Model\ResourceModel\ComfinoApplication as ApplicationResource;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Webapi\Rest\Request;
use Psr\Log\LoggerInterface;
use Magento\Checkout\Model\Session;

class ApplicationService extends ServiceAbstract implements ApplicationServiceInterface
{
    /**
     * @var TransactionHelper
     */
    private $transactionHelper;

    /**
     * @var ComfinoApplicationFactory
     */
    private $comfinoApplicationFactory;

    /**
     * @var ApplicationResource
     */
    private $applicationResource;

    /**
     * @var ComfinoStatusManagementInterface
     */
    private $statusManagement;

    /**
     * ApplicationService constructor.
     *
     * @param Curl $curl
     * @param LoggerInterface $logger
     * @param SerializerInterface $serializer
     * @param Data $helper
     * @param Session $session
     * @param TransactionHelper $transactionHelper
     * @param ComfinoApplicationFactory $comfinoApplicationFactory
     * @param ApplicationResource $applicationResource
     * @param Request $request
     * @param ComfinoStatusManagementInterface $statusManagement
     */
    public function __construct(
        Curl $curl,
        LoggerInterface $logger,
        SerializerInterface $serializer,
        Data $helper,
        Session $session,
        TransactionHelper $transactionHelper,
        ComfinoApplicationFactory $comfinoApplicationFactory,
        ApplicationResource $applicationResource,
        Request $request,
        ComfinoStatusManagementInterface $statusManagement
    ) {
        parent::__construct($curl, $logger, $serializer, $helper, $session, $request);

        $this->transactionHelper = $transactionHelper;
        $this->comfinoApplicationFactory = $comfinoApplicationFactory;
        $this->applicationResource = $applicationResource;
        $this->statusManagement = $statusManagement;
    }

    /**
     * Creates application in the Comfino API and db model.
     *
     * @throws AlreadyExistsException
     */
    public function save(): array
    {
        // Connect to the Comfino API and create new application/transaction.
        $response = $this->createApplicationTransaction();

        if (!$response->isSuccessful()) {
            $this->statusManagement->applicationFailureStatus($this->session->getLastRealOrder());

            ErrorLogger::sendError(
                'Communication error with Comfino API',
                $response->getCode(),
                $response->getStatus(),
                $this->lastUrl,
                null,
                $response->getBody()
            );

            return [[
                'redirectUrl' => 'onepage/failure',
                'error' => __('Unsuccessful attempt to open the application. Please try again later.'),
            ]];
        }

        $data = $this->transactionHelper->parseModel($response);
        $model = $this->comfinoApplicationFactory->create()->addData($data);

        $this->applicationResource->save($model);
        $this->changeStatus($response->getExternalId(), ComfinoStatusManagement::CREATED);

        return [['redirectUrl' => $response->getRedirectUri()]];
    }

    /**
     * Sends POST request to the Comfino API to create new application/transaction.
     */
    public function createApplicationTransaction(): ApplicationResponseInterface
    {
        $this->sendPostRequest($this->helper->getApiHost() . '/v1/orders', $this->transactionHelper->getTransactionData());

        return new ApplicationResponse($this->curl->getStatus(), $this->decode($this->curl->getBody()), $this->curl->getBody());
    }

    /**
     * Sends PUT request to the Comfino API to cancel application/transaction.
     */
    public function cancelApplicationTransaction(string $orderId): void
    {
        $this->sendPutRequest($this->helper->getApiHost() . "/v1/orders/$orderId/cancel");
    }

    /**
     * Returns widget key received from Comfino API.
     */
    public function getWidgetKey(): string
    {
        if ($this->sendGetRequest($this->helper->getApiHost() . '/v1/widget-key')) {
            return $this->decode($this->curl->getBody());
        }

        return '';
    }

    /**
     * Returns list of available product types (offer types) for Comfino widget.
     */
    public function getProductTypes(): ?array
    {
        if ($this->sendGetRequest($this->helper->getApiHost() . '/v1/product-types') && strpos($this->curl->getBody(), 'errors') === false) {
            return $this->decode($this->curl->getBody());
        }

        return null;
    }

    public function isShopAccountActive(): bool
    {
        $accountActive = false;

        if (!empty($this->helper->getApiKey())) {
            if ($this->sendGetRequest($this->helper->getApiHost() . '/v1/user/is-active')) {
                $accountActive = $this->decode($this->curl->getBody());
            }
        }

        return $accountActive;
    }

    public function getLogoUrl(): string
    {
        return $this->helper->getApiHost(true) . '/v1/get-logo-url';
    }

    /**
     * Changes status for application and related order.
     */
    public function changeStatus(string $externalId, string $status): bool
    {
        return $this->statusManagement->changeApplicationAndOrderStatus($externalId, $status);
    }
}
