<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Spryng\Payment\Model\Methods;

use Spryng\Payment\Model\Spryng;

class Klarna extends Spryng
{

    const FLAG_SHIPMENT_FEE = 8;
    const FLAG_INCL_VAT = 32;

    protected $_code = 'spryng_methods_klarna';
    protected $_canRefund = false;

    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @return array
     */
    public function startTransaction($order)
    {
        $prefix = null;
        $pclass = null;
        $dateOfBirth = null;
        $storeId = $order->getStoreId();
        $incrementId = $order->getIncrementId();
        $additionalData = $order->getPayment()->getAdditionalInformation();

        if (isset($additionalData['prefix'])) {
            $prefix = $additionalData['prefix'];
        }
        if (isset($additionalData['pclass'])) {
            $pclass = $additionalData['pclass'];
        }
        if (isset($additionalData['dob'])) {
            $dateOfBirth = $additionalData['dob'];
        }

        $apiKey = $this->spryngHelper->getApiKey($storeId);
        $spryngApi = $this->loadSpryngApi($apiKey, $storeId);
        $accountId = $this->spryngHelper->getAccount($this->_code, $storeId);
        $customer = $this->getSpryngCustomerId($order, $spryngApi, $prefix, $dateOfBirth);

        if (empty($customer)) {
            return ['success' => false, 'error_msg' => __('Error creating Klarna customer data')];
        }

        $paymentData = [
            'account'                    => $accountId,
            'customer'                   => $customer->_id,
            'amount'                     => ($order->getBaseGrandTotal() * 100),
            'customer_ip'                => $order->getRemoteIp(),
            'user_agent'                 => $this->spryngHelper->getUserAgent(),
            'dynamic_descriptor'         => $this->spryngHelper->getDynamicDescriptor($incrementId, $storeId),
            'merchant_reference'         => $this->spryngHelper->getMerchantReference($storeId),
            'webhook_transaction_update' => $this->spryngHelper->getWebhookUrl(),
            'details'                    => [
                'redirect_url' => $this->spryngHelper->getReturnUrl(),
                'pclass'       => $pclass,
                'goods_list'   => $this->generateOrderListForOrder($order),
            ]
        ];

        $this->spryngHelper->addTolog('request', $paymentData);
        $transaction = $spryngApi->Klarna->initiate($paymentData);
        $this->spryngHelper->addTolog('klarna', $transaction);
        $transactionId = $transaction->_id;
        $order->setSpryngTransactionId($transactionId)->save();

        $approvalUrl = $this->spryngHelper->getReturnUrl($order->getId());
        return ['success' => true, 'approval_url' => $approvalUrl];
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @return \SpryngPaymentsApiPhp\Object\GoodsList
     */
    public function generateOrderListForOrder($order)
    {
        $goods = new \SpryngPaymentsApiPhp\Object\GoodsList();

        foreach ($order->getAllVisibleItems() as $item) {
            $good = new \SpryngPaymentsApiPhp\Object\Good();
            $good->title = preg_replace("/[^a-zA-Z0-9]+/", "", $item->getName());
            $good->reference = preg_replace("/[^a-zA-Z0-9]+/", "", $item->getSku());
            $good->quantity = round($item->getQtyOrdered());
            $good->price = ($item->getPriceInclTax() * 100);

            if ($item->getOriginalPrice() > $item->getPrice()) {
                $discountRate = (100 - (($item->getPrice() / $item->getOriginalPrice()) * 100));
                $good->discount = (int)round($discountRate);
            } else {
                $good->discount = 0;
            }

            if ($item->getTaxPercent() > 0) {
                $good->flags = [self::FLAG_INCL_VAT];
                $good->vat = round($item->getTaxPercent());
            } else {
                $good->flags = [];
                $good->vat = 0;
            }

            $goods->add($good);
        }

        if ($order->getBaseShippingAmount() > 0) {
            $good = new \SpryngPaymentsApiPhp\Object\Good();
            $good->title = 'Shipping';
            $good->reference = 'Shipping';
            $good->quantity = 1;
            $good->price = ($order->getShippingInclTax() * 100);
            $good->discount = '0';

            if ($order->getShippingTaxAmount() > 0) {
                $good->flags = [self::FLAG_SHIPMENT_FEE, self::FLAG_INCL_VAT];
                $good->vat = round(($order->getShippingTaxAmount() / $order->getShippingAmount()) * 100);
            } else {
                $good->flags = [self::FLAG_SHIPMENT_FEE];
                $good->vat = 0;
            }

            $goods->add($good);
        }

        return $goods;
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
            $this->getInfoInstance()->setAdditionalInformation('pclass', $data['selected_payment_class']);
            $this->getInfoInstance()->setAdditionalInformation('dob', $data['dob']);
        } elseif ($data instanceof \Magento\Framework\DataObject) {
            $additionalData = $data->getAdditionalData();
            if (isset($additionalData['selected_prefix'])) {
                $this->getInfoInstance()->setAdditionalInformation('prefix', $additionalData['selected_prefix']);
            }
            if (isset($additionalData['selected_payment_class'])) {
                $this->getInfoInstance()->setAdditionalInformation(
                    'pclass',
                    $additionalData['selected_payment_class']
                );
            }
            if (isset($additionalData['dob'])) {
                $this->getInfoInstance()->setAdditionalInformation('dob', $additionalData['dob']);
            }
        }
        return $this;
    }
}
