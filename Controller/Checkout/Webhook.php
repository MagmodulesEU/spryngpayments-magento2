<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Spryng\Payment\Controller\Checkout;

use Spryng\Payment\Model\Spryng as SpryngModel;
use Spryng\Payment\Helper\General as SpryngHelper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;

class Webhook extends Action
{

    private $checkoutSession;
    private $paymentHelper;
    private $spryngModel;
    private $spryngHelper;
    private $storeManager;
    private $order;

    /**
     * Webhook constructor.
     *
     * @param Context               $context
     * @param Session               $checkoutSession
     * @param PaymentHelper         $paymentHelper
     * @param SpryngModel           $spryngModel
     * @param SpryngHelper          $spryngHelper
     * @param StoreManagerInterface $storeManager
     * @param Order                 $order
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        PaymentHelper $paymentHelper,
        SpryngModel $spryngModel,
        SpryngHelper $spryngHelper,
        StoreManagerInterface $storeManager,
        Order $order
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->paymentHelper = $paymentHelper;
        $this->spryngModel = $spryngModel;
        $this->spryngHelper = $spryngHelper;
        $this->storeManager = $storeManager;
        $this->order = $order;
        parent::__construct($context);
    }

    /**
     * Spryng webhook
     */
    public function execute()
    {
        $payload = file_get_contents('php://input');
        $this->spryngHelper->addTolog('webhook', $payload);
        $json = json_decode($payload);

        if ($json && $json->type == 'transaction') {
            $orderId = $this->spryngModel->getOrderIdByTransactionId($json->_id);
            if (!$orderId) {
                $msg = ['error' => true, 'msg' => __('Order not found for transaction id: %1', $json->_id)];
                $this->spryngHelper->addTolog('error', $msg);
                return;
            }
            $this->spryngModel->processTransaction($orderId, 'webhook');
        }

        if ($json && $json->type == 'refund') {
            $storeId = $this->getRequest()->getParams('store_id');
            if (empty($storeId)) {
                $storeId = $this->storeManager->getStore()->getId();
            }
            $this->spryngModel->processRefund($json->_id, $storeId, 'webhook');
        }
    }
}
