<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Spryng\Payment\Model\Methods;

use Spryng\Payment\Model\Spryng;

class Bancontact extends Spryng
{

    protected $_code = 'spryng_methods_bancontact';
    protected $_canRefund = false;

    /**
     * @param $order
     *
     * @return array
     */
    public function startTransaction($order)
    {
        $cardToken = null;
        $storeId = $order->getStoreId();
        $orderId = $order->getId();
        $incrementId = $order->getIncrementId();
        $apiKey = $this->spryngHelper->getApiKey($storeId);
        $accountId = $this->spryngHelper->getAccount($this->_code, $storeId);
        $additionalData = $order->getPayment()->getAdditionalInformation();
        if (isset($additionalData['card_token'])) {
            $cardToken = $additionalData['card_token'];
        }

        $paymentData = [
            'account'            => $accountId,
            'amount'             => ($order->getBaseGrandTotal() * 100),
            'card'               => $cardToken,
            'payment_product'    => 'bancontact',
            'dynamic_descriptor' => $this->spryngHelper->getDynamicDescriptor($incrementId, $storeId),
            'customer_ip'        => $order->getRemoteIp(),
            'user_agent'         => $this->spryngHelper->getUserAgent(),
            'merchant_reference' => $this->spryngHelper->getMerchantReference($storeId),
            'details'            => [
                'redirect_url' => $this->spryngHelper->getReturnUrl($orderId)
            ]
        ];

        $this->spryngHelper->addTolog('request', $paymentData);

        $spryngApi = $this->loadSpryngApi($apiKey, $storeId);
        $transaction = $spryngApi->Bancontact->initiate($paymentData);
        $approvalUrl = $transaction->details->approval_url;

        $message = __('Customer redirected to Spryng, url: %1', $approvalUrl);
        $status = $this->spryngHelper->getStatusPending($storeId);
        $order->addStatusToHistory($status, $message, false);
        $order->setSpryngTransactionId($transaction->_id);
        $order->save();

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
            $this->getInfoInstance()->setAdditionalInformation('card_token', $data['card_token']);
        } elseif ($data instanceof \Magento\Framework\DataObject) {
            $additional_data = $data->getAdditionalData();
            if (isset($additional_data['card_token'])) {
                $cardToken = $additional_data['card_token'];
                $this->getInfoInstance()->setAdditionalInformation('card_token', $cardToken);
            }
        }
        return $this;
    }
}
