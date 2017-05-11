<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Spryng\Payment\Model\Methods;

use Spryng\Payment\Model\Spryng;

class Paypal extends Spryng
{

    protected $_code = 'spryng_methods_paypal';
    protected $_canRefund = false;

    /**
     * @param $order
     *
     * @return array
     */
    public function startTransaction($order)
    {
        $storeId = $order->getStoreId();
        $orderId = $order->getId();
        $incrementId = $order->getIncrementId();
        $apiKey = $this->spryngHelper->getApiKey($storeId);
        $accountId = $this->spryngHelper->getAccount($this->_code, $storeId);

        $paymentData = [
            'account'            => $accountId,
            'amount'             => ($order->getBaseGrandTotal() * 100),
            'customer_ip'        => $order->getRemoteIp(),
            'dynamic_descriptor' => $this->spryngHelper->getDynamicDescriptor($incrementId, $storeId),
            'user_agent'         => $this->spryngHelper->getUserAgent(),
            'merchant_reference' => $this->spryngHelper->getMerchantReference($storeId),
            'details'            => [
                'redirect_url' => $this->spryngHelper->getReturnUrl($orderId),
                'capture_now'  => true
            ]
        ];

        $this->spryngHelper->addTolog('request', $paymentData);
        $spryngApi = $this->loadSpryngApi($apiKey, $storeId);
        $transaction = $spryngApi->Paypal->initiate($paymentData);
        $approvalUrl = $transaction->details->approval_url;

        $message = __('Customer redirected to Spryng, url: %1', $approvalUrl);
        $status = $this->spryngHelper->getStatusPending($storeId);
        $order->addStatusToHistory($status, $message, false);
        $order->setSpryngTransactionId($transaction->_id);
        $order->save();

        return ['success' => true, 'approval_url' => $approvalUrl];
    }
}
