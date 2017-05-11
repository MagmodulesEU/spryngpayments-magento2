<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Spryng\Payment\Model\Methods;

use Spryng\Payment\Model\Spryng;

class Sofort extends Spryng
{

    protected $_code = 'spryng_methods_sofort';
    protected $_allowedCountryCodes = ['AT', 'BE', 'CZ', 'DE', 'HU', 'IT', 'NL', 'PL', 'SK', 'ES', 'CH', 'GB'];
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
        $countryId = $order->getBillingAddress()->getCountryId();

        $paymentData = [
            'account'            => $accountId,
            'amount'             => ($order->getBaseGrandTotal() * 100),
            'customer_ip'        => $order->getRemoteIp(),
            'dynamic_descriptor' => $this->spryngHelper->getDynamicDescriptor($incrementId, $storeId),
            'user_agent'         => $this->spryngHelper->getUserAgent(),
            'country_code'       => $countryId,
            'merchant_reference' => $this->spryngHelper->getMerchantReference($storeId),
            'details'            => [
                'project_id'   => $this->spryngHelper->getProjectId($this->_code, $storeId),
                'redirect_url' => $this->spryngHelper->getReturnUrl($orderId)
            ]
        ];

        $this->spryngHelper->addTolog('request', $paymentData);

        $spryngApi = $this->loadSpryngApi($apiKey, $storeId);
        $transaction = $spryngApi->SOFORT->initiate($paymentData);
        $approvalUrl = $transaction->details->approval_url;

        $message = __('Customer redirected to Spryng, url: %1', $approvalUrl);
        $status = $this->spryngHelper->getStatusPending($storeId);
        $order->addStatusToHistory($status, $message, false);
        $order->setSpryngTransactionId($transaction->_id);
        $order->save();

        return ['success' => true, 'approval_url' => $approvalUrl];
    }

    /**
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

        $country = $quote->getBillingAddress()->getCountryId();
        if (!in_array($country, $this->_allowedCountryCodes)) {
            return false;
        }

        return parent::isAvailable($quote);
    }
}
