<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Spryng\Payment\Model\Methods;

use Spryng\Payment\Model\Spryng;

class Ideal extends Spryng
{

    protected $_code = 'spryng_methods_ideal';
    protected $_supportedCurrencyCodes = ['EUR'];
    protected $_canRefund = false;

    /**
     * @param $order
     *
     * @return array
     */
    public function startTransaction($order)
    {
        $issuer = null;
        $storeId = $order->getStoreId();
        $orderId = $order->getId();
        $incrementId = $order->getIncrementId();
        $apiKey = $this->spryngHelper->getApiKey($storeId);
        $accountId = $this->spryngHelper->getAccount($this->_code, $storeId);
        $additionalData = $order->getPayment()->getAdditionalInformation();
        if (isset($additionalData['issuer'])) {
            $issuer = $additionalData['issuer'];
        }

        $paymentData = [
            'account'            => $accountId,
            'amount'             => ($order->getBaseGrandTotal() * 100),
            'customer_ip'        => $order->getRemoteIp(),
            'dynamic_descriptor' => $this->spryngHelper->getDynamicDescriptor($incrementId, $storeId),
            'user_agent'         => $this->spryngHelper->getUserAgent(),
            'merchant_reference' => $this->spryngHelper->getMerchantReference($storeId),
            'details'            => [
                'issuer'       => $issuer,
                'redirect_url' => $this->spryngHelper->getReturnUrl($orderId)
            ]
        ];

        $this->spryngHelper->addTolog('request', $paymentData);

        $spryngApi = $this->loadSpryngApi($apiKey, $storeId);
        $transaction = $spryngApi->iDeal->initiate($paymentData);
        $approvalUrl = $transaction->details->approval_url;

        $message = __('Customer redirected to Spryng, url: %1', $approvalUrl);
        $status = $this->spryngHelper->getStatusPending($storeId);
        $order->addStatusToHistory($status, $message, false);
        $order->setSpryngTransactionId($transaction->_id);
        $order->save();

        return ['success' => true, 'approval_url' => $approvalUrl];
    }

    /**
     * @param string $currencyCode
     *
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }
        return true;
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
            $this->getInfoInstance()->setAdditionalInformation('issuer', $data['selected_issuer']);
        } elseif ($data instanceof \Magento\Framework\DataObject) {
            $additional_data = $data->getAdditionalData();
            if (isset($additional_data['selected_issuer'])) {
                $selectedIssuer = $additional_data['selected_issuer'];
                $this->getInfoInstance()->setAdditionalInformation('issuer', $selectedIssuer);
            }
        }
        return $this;
    }
}
