<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Spryng\Payment\Model;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\OrderRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Checkout\Model\Session as CheckoutSession;
use Spryng\Payment\Helper\General as SpryngHelper;
use Magento\Customer\Api\CustomerRepositoryInterface;

class Spryng extends AbstractMethod
{

    public $objectManager;
    public $spryngHelper;
    public $customerRepositoryInterface;
    public $checkoutSession;
    public $storeManager;
    public $scopeConfig;
    public $order;
    public $invoice;
    public $orderSender;
    public $invoiceSender;
    public $orderRepository;
    public $searchCriteriaBuilder;
    public $creditmemoFactory;
    public $creditmemoService;
    public $invoiceRepository;
    public $orderPaymentRepository;

    protected $_isInitializeNeeded = true;
    protected $_isGateway = true;
    protected $_isOffline = false;

    /**
     * Spryng constructor.
     *
     * @param Context                         $context
     * @param Registry                        $registry
     * @param ExtensionAttributesFactory      $extensionFactory
     * @param AttributeValueFactory           $customAttributeFactory
     * @param Data                            $paymentData
     * @param ScopeConfigInterface            $scopeConfig
     * @param Logger                          $logger
     * @param ObjectManagerInterface          $objectManager
     * @param SpryngHelper                    $spryngHelper
     * @param CustomerRepositoryInterface     $customerRepositoryInterface
     * @param CheckoutSession                 $checkoutSession
     * @param StoreManagerInterface           $storeManager
     * @param Order                           $order
     * @param Invoice                         $invoice
     * @param CreditmemoFactory               $creditmemoFactory
     * @param CreditmemoService               $creditmemoService
     * @param OrderPaymentRepositoryInterface $orderPaymentRepository
     * @param OrderSender                     $orderSender
     * @param InvoiceSender                   $invoiceSender
     * @param InvoiceRepositoryInterface      $invoiceRepository
     * @param OrderRepository                 $orderRepository
     * @param SearchCriteriaBuilder           $searchCriteriaBuilder
     * @param AbstractResource|null           $resource
     * @param AbstractDb|null                 $resourceCollection
     * @param array                           $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        ObjectManagerInterface $objectManager,
        SpryngHelper $spryngHelper,
        CustomerRepositoryInterface $customerRepositoryInterface,
        CheckoutSession $checkoutSession,
        StoreManagerInterface $storeManager,
        Order $order,
        Invoice $invoice,
        CreditmemoFactory $creditmemoFactory,
        CreditmemoService $creditmemoService,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        OrderSender $orderSender,
        InvoiceSender $invoiceSender,
        InvoiceRepositoryInterface $invoiceRepository,
        OrderRepository $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->objectManager = $objectManager;
        $this->spryngHelper = $spryngHelper;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->order = $order;
        $this->invoice = $invoice;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoService = $creditmemoService;
        $this->orderSender = $orderSender;
        $this->invoiceSender = $invoiceSender;
        $this->invoiceRepository = $invoiceRepository;
        $this->orderRepository = $orderRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Extra checks for method availability
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     *
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {

        if ($quote == null) {
            $quote = $this->checkoutSession->getQuote();
        }

        if (!$this->spryngHelper->isAvailable($quote->getStoreId())) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    /**
     * @param string $paymentAction
     * @param object $stateObject
     */
    public function initialize($paymentAction, $stateObject)
    {
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);
        $order->setIsNotified(false);

        $status = $this->spryngHelper->getStatusPending($order->getId());
        $stateObject->setState(\Magento\Sales\Model\Order::STATE_NEW);
        $stateObject->setStatus($status);
        $stateObject->setIsNotified(false);
    }

    /**
     * @param        $orderId
     * @param string $type
     *
     * @return array|string
     */
    public function processTransaction($orderId, $type = 'webhook')
    {
        $msg = '';

        $order = $this->order->load($orderId);
        if (empty($order)) {
            $msg = ['error' => true, 'msg' => __('Order not found')];
            $this->spryngHelper->addTolog('error', $msg);

            return $msg;
        }

        $storeId = $order->getStoreId();

        $transactionId = $order->getSpryngTransactionId();
        if (empty($transactionId)) {
            $msg = ['error' => true, 'msg' => __('Transaction ID not found')];
            $this->spryngHelper->addTolog('error', $msg);

            return $msg;
        }

        $apiKey = $this->spryngHelper->getApiKey($storeId);
        if (empty($apiKey)) {
            $msg = ['error' => true, 'msg' => __('Api key not found')];
            $this->spryngHelper->addTolog('error', $msg);

            return $msg;
        }

        $spryngApi = $this->loadSpryngApi($apiKey, $storeId);
        $transaction = $spryngApi->transaction->getTransactionById($transactionId);
        $this->spryngHelper->addTolog($type, $transaction);
        $statusPending = $this->spryngHelper->getStatusPending($storeId);

        switch ($transaction->status) {
            case 'SETTLEMENT_COMPLETED':
                $amount = $order->getBaseGrandTotal();
                $payment = $order->getPayment();

                if (!$payment->getIsTransactionClosed() && $type == 'webhook') {
                    $payment->setTransactionId($transactionId);
                    $payment->setCurrencyCode('EUR');
                    $payment->setIsTransactionClosed(true);
                    $payment->registerCaptureNotification($amount, true);
                    $order->save();

                    $invoice = $payment->getCreatedInvoice();
                    $statusProcessing = $this->spryngHelper->getStatusProcessing($storeId);
                    $sendInvoice = $this->spryngHelper->sendInvoice($storeId);

                    if (!$order->getEmailSent()) {
                        $this->orderSender->send($order);
                    }
                    if ($invoice && $sendInvoice && !$invoice->getEmailSent()) {
                        $this->invoiceSender->send($invoice);
                    }
                    if ($invoice && ($order->getStatus() != $statusProcessing)) {
                        $order->setStatus($statusProcessing)->save();
                    }
                }

                $msg = [
                    'success'  => true,
                    'status'   => $transaction->status,
                    'order_id' => $orderId,
                    'type'     => $type
                ];

                break;

            case 'SETTLEMENT_REQUESTED':
                if ($type == 'webhook') {
                    $message = __(
                        'Transaction with ID %1 is requested. Your order with ID %2 should be updated 
                        automatically when the status on the payment is updated.',
                        $transactionId,
                        $order->getIncrementId()
                    );

                    if ($statusPending != $order->getStatus()) {
                        $statusPending = $order->getStatus();
                    }

                    $order->addStatusToHistory($statusPending, $message, false)->save();
                }

                $msg = [
                    'success'  => true,
                    'status'   => $transaction->status,
                    'order_id' => $orderId,
                    'type'     => $type
                ];

                break;

            case 'INITIATED':
                if ($type == 'webhook') {
                    $message = __(
                        'Transaction with ID %1 has started. Your iDEAL approval URL is %2. Your order with ID %3 will 
                        be updated automatically when you have paid.',
                        $transactionId,
                        $transaction->details->approval_url,
                        $order->getIncrementId()
                    );

                    if ($statusPending != $order->getStatus()) {
                        $statusPending = $order->getStatus();
                    }

                    $order->addStatusToHistory($statusPending, $message, false)->save();
                }

                $msg = [
                    'success'  => true,
                    'status'   => $transaction->status,
                    'order_id' => $orderId,
                    'type'     => $type
                ];

                break;

            case 'SETTLEMENT_PROCESSED':
                if ($type == 'webhook') {
                    $message = __(
                        'Transaction with ID %1 is processed. Your order with ID %2 should be updated automatically 
                        when the status on the payment is updated.',
                        $transactionId,
                        $order->getIncrementId()
                    );
                    if ($statusPending != $order->getStatus()) {
                        $statusPending = $order->getStatus();
                    }

                    $order->addStatusToHistory($statusPending, $message, false)->save();
                }

                if (!$order->getEmailSent()) {
                    $this->orderSender->send($order);
                }

                $msg = [
                    'success'  => true,
                    'status'   => $transaction->status,
                    'order_id' => $orderId,
                    'type'     => $type
                ];

                break;

            case 'FAILED':
            case 'AUTHORIZATION_VOIDED':
                if ($type == 'webhook') {
                    $this->cancelOrder($order);
                }

                $msg = [
                    'success'  => false,
                    'status'   => 'cancel',
                    'order_id' => $orderId,
                    'type'     => $type,
                    'msg'      => __('Payment was cancelled, please try again')
                ];

                break;

            default:
                if ($type == 'webhook') {
                    $message = __(
                        'The status of your order with ID %1 is %2. The order should be updated automatically 
                        when the status changes',
                        $transactionId,
                        $transaction->status
                    );

                    if ($statusPending != $order->getStatus()) {
                        $statusPending = $order->getStatus();
                    }

                    $order->addStatusToHistory($statusPending, $message, false)->save();
                }

                $msg = [
                    'success'  => true,
                    'status'   => $transaction->status,
                    'order_id' => $orderId,
                    'type'     => $type
                ];

                break;
        }

        $this->spryngHelper->addTolog('success', $msg);

        return $msg;
    }

    /**
     * @param $apiKey
     * @param $storeId
     *
     * @return mixed|string
     */
    public function loadSpryngApi($apiKey, $storeId)
    {
        try {
            $arg = ['apiKey' => $apiKey, 'sandbox' => $this->spryngHelper->isSandbox($storeId)];
            $spryngApi = $this->objectManager->create('SpryngPaymentsApiPhp\Client', $arg);
        } catch (\Exception $e) {
            $this->spryngHelper->addTolog('error', 'Function: loadSpryngApi: ' . $e->getMessage());
            return '';
        }

        return $spryngApi;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @return bool
     */
    public function cancelOrder($order)
    {
        if ($order->getId() && $order->getState() != Order::STATE_CANCELED) {
            $comment = __("The order was canceled");
            $this->spryngHelper->addTolog('info', $order->getIncrementId() . ' ' . $comment);
            $order->registerCancellation($comment)->save();
            return true;
        }
        return false;
    }

    /**
     * @param $refundId
     * @param $storeId
     * @param $type
     *
     * @return string
     */
    public function processRefund($refundId, $storeId, $type)
    {

        $apiKey = $this->spryngHelper->getApiKey($storeId);

        if (empty($apiKey)) {
            $msg = ['error' => true, 'msg' => __('Api key not found')];
            $this->spryngHelper->addTolog('error', $msg);
            return '';
        }

        $spryngApi = $this->loadSpryngApi($apiKey, $storeId);
        $refund = $spryngApi->refund->getRefundById($refundId);
        $this->spryngHelper->addTolog($type, $refund);

        if ($refund->status == 'PROCESSED' && isset($refund->transaction->_id)) {
            $orderId = $this->getOrderIdByTransactionId($refund->transaction->_id);
            if (!empty($orderId)) {
                $order = $this->order->load($orderId);
                if ($order->canCreditmemo()) {
                    $invoiceIncrementId = '';
                    foreach ($order->getInvoiceCollection() as $invoice) {
                        $invoiceIncrementId = $invoice->getIncrementId();
                    }
                    if ($invoiceIncrementId) {
                        $invoice = $this->invoice->loadByIncrementId($invoiceIncrementId);
                        $creditMemo = $this->creditmemoFactory->createByInvoice($invoice);
                        $creditMemo->setInvoice($invoice);
                        $payment = $order->getPayment();
                        $payment->setCreditmemo($creditMemo);
                        $this->orderPaymentRepository->save($payment);
                        $this->creditmemoService->refund($creditMemo);
                        $this->invoiceRepository->save($invoice);
                    }
                }
            }
        }
    }

    /**
     * @param $transactionId
     *
     * @return bool
     */
    public function getOrderIdByTransactionId($transactionId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('spryng_transaction_id', $transactionId, 'eq')->create();
        $orderList = $this->orderRepository->getList($searchCriteria);
        $orderId = $orderList->getFirstItem()->getId();

        if ($orderId) {
            return $orderId;
        } else {
            $this->spryngHelper->addTolog('error', __('No order found for transaction id %1', $transactionId));

            return false;
        }
    }

    /**
     * @param $code
     * @param $storeId
     *
     * @return array
     */
    public function getPClasses($code, $storeId)
    {
        $classes = [];

        $apiKey = $this->spryngHelper->getApiKey($storeId);
        $account = $this->spryngHelper->getAccount($code, $storeId);
        if(!$spryngApi = $this->loadSpryngApi($apiKey, $storeId)) {
           return $classes;
        }

        try {
            $pclasses = $spryngApi->Klarna->getPClasses($account);
            foreach ($pclasses as $pclass) {
                $classes = [
                    'id'   => $pclass->_id,
                    'name' => $pclass->description . ' - (' . ($pclass->interest_rate / 100) . '% interest)'
                ];
            }
        } catch (\Exception $e) {
            $this->spryngHelper->addTolog('error', 'Function: getPClasses: ' . $e->getMessage());
        }

        return $classes;
    }

    /**
     * @param $storeId
     * @param $websiteId
     *
     * @return array
     */
    public function getOrganisations($storeId, $websiteId)
    {
        $organisations = [];

        $apiKey = $this->spryngHelper->getApiKey($storeId, $websiteId);
        if (empty($apiKey)) {
            return ['-1' => __('Please provide a valid API Key first.')];
        }

        if (!$spryngApi = $this->loadSpryngApi($apiKey, $storeId)) {
            return ['-1' => __('Could not load Spryng API.')];
        }

        try {
            $apiOrganisations = $spryngApi->organisation->getAll();
        } catch (\Exception $e) {
            $this->spryngHelper->addTolog('error', $e->getMessage());
            return ['-1' => __('The API Key you provided is invalid.')];
        }

        foreach ($apiOrganisations as $apiOrganisation) {
            $organisations[$apiOrganisation->_id] = $apiOrganisation->name;
        }

        return $organisations;
    }

    /**
     * @param $storeId
     * @param $websiteId
     *
     * @return array
     */
    public function getAccounts($storeId, $websiteId)
    {
        $accounts = [];

        $apiKey = $this->spryngHelper->getApiKey($storeId, $websiteId);
        if (empty($apiKey)) {
            return ['-1' => __('Please provide a valid API Key first.')];
        }

        if (!$spryngApi = $this->loadSpryngApi($apiKey, $storeId)) {
            return ['-1' => __('Could not load Spryng API.')];
        }

        try {
            $apiAccounts = $spryngApi->account->getAll();
        } catch (\Exception $e) {
            $this->spryngHelper->addTolog('error', 'Function: getAccounts: ' . $e->getMessage());
            return ['-1' => __('The API Key you provided is invalid.')];
        }

        foreach ($apiAccounts as $apiAccount) {
            $accounts[$apiAccount->_id] = $apiAccount->name;
        }

        return $accounts;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param                            $spryngApi
     * @param                            $prefix
     * @param string                     $dateOfBirth
     *
     * @return string
     */
    public function getSpryngCustomerId($order, $spryngApi, $prefix, $dateOfBirth = '')
    {
        $customerId = $order->getCustomerId();

        if ($customerId) {
            $customer = $this->customerRepositoryInterface->getById($customerId);
            $spryngCustomerId = $customer->getCustomAttribute('spryng_customer_id');
            if (empty($spryngCustomerId)) {
                $spryngCustomer = $this->createNewSpryngCustomer($order, $spryngApi, $prefix, $dateOfBirth);
            } else {
                $spryngCustomer = $this->updateCustomer(
                    $order,
                    $spryngApi,
                    $spryngCustomerId->getValue(),
                    $prefix,
                    $dateOfBirth
                );
            }
        } else {
            $spryngCustomer = $this->createNewSpryngCustomer($order, $spryngApi, $prefix, $dateOfBirth);
        }

        return $spryngCustomer;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param                            $spryngApi
     * @param                            $prefix
     * @param                            $dateOfBirth
     *
     * @return string
     */
    public function createNewSpryngCustomer($order, $spryngApi, $prefix, $dateOfBirth)
    {
        $spryngCustomer = '';

        if (!$customerId = $order->getCustomerId()) {
            $customerId = $order->getIncrementId();
        }

        $billing = $order->getBillingAddress();

        $postCode = $billing->getPostcode();
        if (strlen($postCode) == 6) {
            $postCode = wordwrap($postCode, 4, ' ', true);
        }

        $phoneNumber = $this->spryngHelper->getFormattedPhoneNumber($billing->getTelephone(), $billing->getCountryId());

        $customerData = [
            'account'        => $customerId,
            'title'          => $prefix,
            'first_name'     => $billing->getFirstname(),
            'last_name'      => $billing->getLastname(),
            'email_address'  => $billing->getEmail(),
            'country_code'   => $billing->getCountryId(),
            'city'           => $billing->getCity(),
            'street_address' => $billing->getStreetLine(1),
            'postal_code'    => $postCode,
            'phone_number'   => $phoneNumber,
            'gender'         => ($prefix == 'ms') ? 'female' : 'male',
        ];

        if (!empty($dateOfBirth)) {
            $customerData['date_of_birth'] = $dateOfBirth;
        }

        $this->spryngHelper->addTolog('request', $customerData);

        try {
            $spryngCustomer = $spryngApi->customer->create($customerData);
            if ($order->getCustomerId()) {
                $customer = $this->customerRepositoryInterface->getById($customerId);
                $customer->setCustomAttribute('spryng_customer_id', $spryngCustomer->_id);
                $this->customerRepositoryInterface->save($customer);
            }
        } catch (\Exception $e) {
            $msg = __('Error creating customer data, %1', $e->getMessage());
            $this->spryngHelper->addTolog('error', 'Function: createNewSpryngCustomer: ' . $msg);
        }

        return $spryngCustomer;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param                            $spryngApi
     * @param                            $spryngCustomerId
     * @param                            $prefix
     * @param                            $dateOfBirth
     *
     * @return string
     */
    public function updateCustomer($order, $spryngApi, $spryngCustomerId, $prefix, $dateOfBirth)
    {
        $spryngCustomer = '';
        $customerId = $order->getCustomerId();
        $billing = $order->getBillingAddress();

        $postCode = $billing->getPostcode();
        if (strlen($postCode) == 6) {
            $postCode = wordwrap($postCode, 4, ' ', true);
        }

        $phoneNumber = $this->spryngHelper->getFormattedPhoneNumber($billing->getTelephone(), $billing->getCountryId());

        $customerData = [
            'account'        => $customerId,
            'title'          => $prefix,
            'first_name'     => $billing->getFirstname(),
            'last_name'      => $billing->getLastname(),
            'email_address'  => $billing->getEmail(),
            'country_code'   => $billing->getCountryId(),
            'city'           => $billing->getCity(),
            'street_address' => $billing->getStreetLine(1),
            'postal_code'    => $postCode,
            'phone_number'   => $phoneNumber,
            'gender'         => ($prefix == 'ms') ? 'female' : 'male',
        ];

        if (!empty($dateOfBirth)) {
            $customerData['date_of_birth'] = $dateOfBirth;
        }

        $this->spryngHelper->addTolog('request', $customerData);

        try {
            $spryngCustomer = $spryngApi->customer->update($spryngCustomerId, $customerData);
        } catch (\Exception $e) {
            $msg = __('Error updating customer data, %1', $e->getMessage());
            $this->spryngHelper->addTolog('error', 'Function: updateCustomer: ' . $msg);
        }

        return $spryngCustomer;
    }
}
