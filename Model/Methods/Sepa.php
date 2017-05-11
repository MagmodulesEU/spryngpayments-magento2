<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Spryng\Payment\Model\Methods;

use Spryng\Payment\Model\Spryng;

class Sepa extends Spryng
{

    protected $_code = 'spryng_methods_sepa';
    protected $_canRefund = false;

    /**
     * @param $order
     *
     * @return array
     */
    public function startTransaction($order)
    {
        $prefix = null;
        $storeId = $order->getStoreId();
        $orderId = $order->getId();
        $incrementId = $order->getIncrementId();
        $additionalData = $order->getPayment()->getAdditionalInformation();
        $prefix = null;
        if (isset($additionalData['prefix'])) {
            $prefix = $additionalData['prefix'];
        }

        $apiKey = $this->spryngHelper->getApiKey($storeId);
        $spryngApi = $this->loadSpryngApi($apiKey, $storeId);
        $accountId = $this->spryngHelper->getAccount($this->_code, $storeId);
        $customer = $this->getSpryngCustomerId($order, $spryngApi, $prefix);

        if (empty($customer)) {
            return ['success' => false, 'error_msg' => __('Error creating SEPA customer data')];
        }

        $paymentData = [
            'account'            => $accountId,
            'customer'           => $customer->_id,
            'amount'             => ($order->getBaseGrandTotal() * 100),
            'customer_ip'        => $order->getRemoteIp(),
            'user_agent'         => $this->spryngHelper->getUserAgent(),
            'dynamic_descriptor' => $this->spryngHelper->getDynamicDescriptor($incrementId, $storeId),
            'merchant_reference' => $this->spryngHelper->getMerchantReference($storeId),
            'details'            => [
                'redirect_url' => $this->spryngHelper->getReturnUrl($orderId)
            ]
        ];

        $this->spryngHelper->addTolog('request', $paymentData);
        $transaction = $spryngApi->Sepa->initiate($paymentData);

        if (isset($transaction->details->approval_url)) {
            $approvalUrl = $transaction->details->approval_url;
            $message = __('Customer redirected to Spryng, url: %1', $approvalUrl);
            $status = $this->spryngHelper->getStatusPending($storeId);
            $order->addStatusToHistory($status, $message, false);
            $order->setSpryngTransactionId($transaction->_id);
            $order->save();
        } else {
            $transactionId = $transaction->_id;
            $order->setSpryngTransactionId($transactionId)->save();
            $approvalUrl = $this->spryngHelper->getReturnUrl($order->getId());
        }

        return ['success' => true, 'approval_url' => $approvalUrl];
    }

    /**
     * @param \Magento\Framework\DataObject $data
     *
     * @return $this
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        if (is_array($data)) {
            $this->getInfoInstance()->setAdditionalInformation('prefix', $data['selected_prefix']);
        } elseif ($data instanceof \Magento\Framework\DataObject) {
            $additional_data = $data->getAdditionalData();
            if (isset($additional_data['selected_prefix'])) {
                $selectedPrefix = $additional_data['selected_prefix'];
                $this->getInfoInstance()->setAdditionalInformation('prefix', $selectedPrefix);
            }
        }
        return $this;
    }
}
