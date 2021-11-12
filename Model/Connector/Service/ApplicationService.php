<?php

namespace Comperia\ComperiaGateway\Model\Connector\Service;

use Comperia\ComperiaGateway\Api\ApplicationServiceInterface;
use Comperia\ComperiaGateway\Exception\InvalidSignatureException;
use Comperia\ComperiaGateway\Model\ComperiaApplicationFactory;
use Comperia\ComperiaGateway\Api\ComperiaStatusManagementInterface;
use Comperia\ComperiaGateway\Model\Connector\Transaction\Response\ApplicationResponse;
use Comperia\ComperiaGateway\Helper\Data;
use Comperia\ComperiaGateway\Helper\TransactionHelper;
use Comperia\ComperiaGateway\Api\Data\ApplicationResponseInterface;
use Comperia\ComperiaGateway\Model\ResourceModel\ComperiaApplication as ApplicationResource;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Webapi\Rest\Request;
use Psr\Log\LoggerInterface;
use Magento\Checkout\Model\Session;

class ApplicationService extends ServiceAbstract implements ApplicationServiceInterface
{
    const COMPERIA_API_TRANSACTION_URI = '/v1/orders';
    const NOTIFICATION_URL = 'rest/V1/comperia-gateway/application/status';

    /**
     * @var TransactionHelper
     */
    private $transactionHelper;

    /**
     * @var ComperiaApplicationFactory
     */
    private $comperiaApplicationFactory;

    /**
     * @var ApplicationResource
     */
    private $applicationResource;

    /**
     * @var ComperiaStatusManagementInterface
     */
    private $statusManagement;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;


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
     * @param ComperiaApplicationFactory $comperiaApplicationFactory
     * @param ApplicationResource $applicationResource
     * @param Request $request
     * @param ComperiaStatusManagementInterface $statusManagement
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Curl $curl,
        LoggerInterface $logger,
        SerializerInterface $serializer,
        Data $helper,
        Session $session,
        ProductMetadataInterface $productMetadata,
        TransactionHelper $transactionHelper,
        ComperiaApplicationFactory $comperiaApplicationFactory,
        ApplicationResource $applicationResource,
        Request $request,
        ComperiaStatusManagementInterface $statusManagement,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($curl, $logger, $serializer, $helper, $session, $productMetadata, $request);

        $this->transactionHelper = $transactionHelper;
        $this->comperiaApplicationFactory = $comperiaApplicationFactory;
        $this->applicationResource = $applicationResource;
        $this->statusManagement = $statusManagement;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Create Application in Comperia API and Db Model.
     *
     * @return array
     * @throws AlreadyExistsException
     */
    public function save(): array
    {
        // Connect to Comperia API and create new application/transaction
        $response = $this->createApplicationTransaction();

        if (!$response->isSuccessful()) {
            $this->statusManagement->applicationFailureStatus($this->session->getLastRealOrder());
            $this->logger->emergency(
                __('Unsuccessful attempt to open the application. Communication error with Comperia API.'),
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
        $model = $this->comperiaApplicationFactory->create()->addData($data);
        $this->applicationResource->save($model);

        return [['redirectUrl' => $response->getRedirectUri()]];
    }

    /**
     * Send POST request to Comperia API to create new application/transaction.
     *
     * @return ApplicationResponseInterface
     */
    public function createApplicationTransaction(): ApplicationResponseInterface
    {
        $apiUrl = $this->getApiUrl() . self::COMPERIA_API_TRANSACTION_URI;
        $transaction = $this->transactionHelper->getTransactionData();
        $this->sendPostRequest($apiUrl, $transaction);

        return new ApplicationResponse($this->curl->getStatus(), $this->decode($this->curl->getBody()));
    }

    /**
     * Change status for application and related order.
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
