<?php

namespace Comfino\ComfinoGateway\Model\Connector\Service;

use Comfino\ComfinoGateway\Api\ApplicationServiceInterface;
use Comfino\ComfinoGateway\Exception\InvalidSignatureException;
use Comfino\ComfinoGateway\Model\ComfinoApplicationFactory;
use Comfino\ComfinoGateway\Api\ComfinoStatusManagementInterface;
use Comfino\ComfinoGateway\Model\Connector\Transaction\Response\ApplicationResponse;
use Comfino\ComfinoGateway\Helper\Data;
use Comfino\ComfinoGateway\Helper\TransactionHelper;
use Comfino\ComfinoGateway\Api\Data\ApplicationResponseInterface;
use Comfino\ComfinoGateway\Model\ResourceModel\ComfinoApplication as ApplicationResource;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Webapi\Rest\Request;
use Psr\Log\LoggerInterface;
use Magento\Checkout\Model\Session;

class ApplicationService extends ServiceAbstract implements ApplicationServiceInterface
{
    public const NOTIFICATION_URL = 'rest/V1/comfino-gateway/application/status';

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
     * @param ProductMetadataInterface $productMetadata
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
        ProductMetadataInterface $productMetadata,
        TransactionHelper $transactionHelper,
        ComfinoApplicationFactory $comfinoApplicationFactory,
        ApplicationResource $applicationResource,
        Request $request,
        ComfinoStatusManagementInterface $statusManagement
    ) {
        parent::__construct($curl, $logger, $serializer, $helper, $session, $productMetadata, $request);

        $this->transactionHelper = $transactionHelper;
        $this->comfinoApplicationFactory = $comfinoApplicationFactory;
        $this->applicationResource = $applicationResource;
        $this->statusManagement = $statusManagement;
    }

    /**
     * Creates application in the Comfino API and db model.
     *
     * @return array
     * @throws AlreadyExistsException
     */
    public function save(): array
    {
        // Connect to the Comfino API and create new application/transaction
        $response = $this->createApplicationTransaction();

        if (!$response->isSuccessful()) {
            $this->statusManagement->applicationFailureStatus($this->session->getLastRealOrder());
            $this->logger->emergency(
                __('Unsuccessful attempt to open the application. Communication error with Comfino API.'),
                [
                    'body' => $response->getData(),
                    'code' => $response->getCode(),
                ]
            );
            $this->logger->info('Redirect url: '.$response->getRedirectUri());

            return [[
                'redirectUrl' => 'onepage/failure',
                'error' => __('Unsuccessful attempt to open the application. Please try again later.')
            ]];
        }

        $data = $this->transactionHelper->parseModel($response);
        $model = $this->comfinoApplicationFactory->create()->addData($data);
        $this->applicationResource->save($model);

        return [['redirectUrl' => $response->getRedirectUri()]];
    }

    /**
     * Sends POST request to the Comfino API to create new application/transaction.
     *
     * @return ApplicationResponseInterface
     */
    public function createApplicationTransaction(): ApplicationResponseInterface
    {
        $this->sendPostRequest($this->getApiUrl().'/v1/orders', $this->transactionHelper->getTransactionData());

        return new ApplicationResponse($this->curl->getStatus(), $this->decode($this->curl->getBody()));
    }

    /**
     * Sends PUT request to the Comfino API to cancel application/transaction.
     *
     * @param string $orderId
     *
     * @return void
     */
    public function cancelApplicationTransaction(string $orderId): void
    {
        $this->sendPutRequest($this->getApiUrl()."/v1/orders/$orderId/cancel");
    }

    /**
     * Returns widget key received from Comfino API.
     *
     * @return string
     */
    public function getWidgetKey(): string
    {
        if ($this->sendGetRequest($this->getApiUrl()."/v1/widget-key", [])) {
            return $this->decode($this->curl->getBody());
        }

        return '';
    }

    /**
     * Changes status for application and related order.
     *
     * @return void
     * @throws InvalidSignatureException
     * @throws ValidatorException
     */
    public function changeStatus(): void
    {
        $params = $this->request->getBodyParams();

        if (!isset($params['externalId'])) {
            throw new ValidatorException(__('Empty content.'));
        }
        if (!$this->isValidSignature($this->encode($params))) {
            throw new InvalidSignatureException(__('Failed comparison of CR-Signature and shop hash.'));
        }

        $this->statusManagement->changeApplicationAndOrderStatus($params['externalId'], $params['status']);
    }
}
