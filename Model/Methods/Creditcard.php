<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Spryng\Payment\Model\Methods;

use Spryng\Payment\Model\Spryng;

class Creditcard extends Spryng
{

    protected $_code = 'spryng_methods_creditcard';
    protected $_canRefund = true;

    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @return array
     */
    public function startTransaction($order)
    {
        $cardToken = null;
        $storeId = $order->getStoreId();
        $incrementId = $order->getIncrementId();
        $apiKey = $this->spryngHelper->getApiKey($storeId);
        $accountId = $this->spryngHelper->getAccount($this->_code, $storeId);
        $additionalData = $order->getPayment()->getAdditionalInformation();
        if (isset($additionalData['card_token'])) {
            $cardToken = $additionalData['card_token'];
        }

        $paymentData = [
            'account'                    => $accountId,
            'amount'                     => ($order->getBaseGrandTotal() * 100),
            'card'                       => $cardToken,
            'dynamic_descriptor'         => $this->spryngHelper->getDynamicDescriptor($incrementId, $storeId),
            'payment_product'            => 'card',
            'customer_ip'                => $order->getRemoteIp(),
            'user_agent'                 => $this->spryngHelper->getUserAgent(),
            'capture'                    => true,
            'merchant_reference'         => $this->spryngHelper->getMerchantReference($storeId),
            'webhook_transaction_update' => $this->spryngHelper->getWebhookUrl(),
        ];

        $this->spryngHelper->addTolog('request', $paymentData);
        $spryngApi = $this->loadSpryngApi($apiKey, $storeId);
        $transaction = $spryngApi->transaction->create($paymentData);
        $this->spryngHelper->addTolog('creditcard', $transaction);
        $transactionId = $transaction->_id;
        $order->setSpryngTransactionId($transactionId)->save();

        $approvalUrl = $this->spryngHelper->getReturnUrl();
        return ['success' => true, 'approval_url' => $approvalUrl];
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float                                $amount
     *
     * @return $this|array
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $order = $payment->getOrder();
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
        try {
            $amount = $amount * 100;
            $spryngApi->transaction->refund($transactionId, $amount, '');
        } catch (\Exception $e) {
            $this->spryngHelper->addTolog('error', $e->getMessage());
        }
        return $this;
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
            $additionalData = $data->getAdditionalData();
            if (isset($additionalData['card_token'])) {
                $cardToken = $additionalData['card_token'];
                $this->getInfoInstance()->setAdditionalInformation('card_token', $cardToken);
            }
        }
        return $this;
    }
}
