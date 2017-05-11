<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Spryng\Payment\Controller\Checkout;

use Spryng\Payment\Model\Spryng as SpryngModel;
use Magento\Payment\Helper\Data as PaymentHelper;
use Spryng\Payment\Helper\General as SpryngHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;

class Success extends Action
{

    private $checkoutSession;
    private $paymentHelper;
    private $spryngModel;
    private $spryngHelper;

    /**
     * Success constructor.
     *
     * @param Context       $context
     * @param Session       $checkoutSession
     * @param PaymentHelper $paymentHelper
     * @param SpryngModel   $spryngModel
     * @param SpryngHelper  $spryngHelper
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        PaymentHelper $paymentHelper,
        SpryngModel $spryngModel,
        SpryngHelper $spryngHelper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->paymentHelper = $paymentHelper;
        $this->spryngModel = $spryngModel;
        $this->spryngHelper = $spryngHelper;
        parent::__construct($context);
    }

    /**
     * Execute Redirect to Spryng after placing order
     */
    public function execute()
    {
        try {
            $orderId = $this->checkoutSession->getLastOrderId();
            $status = $this->spryngModel->processTransaction($orderId, 'success');
        } catch (\Exception $e) {
            $this->spryngHelper->addTolog('error', $e);
            $this->messageManager->addExceptionMessage($e, __('There was an error checking the transaction status.'));
            $this->_redirect('checkout/cart');
        }

        if (!empty($status['success'])) {
            $this->checkoutSession->start();
            $this->_redirect('checkout/onepage/success?utm_nooverride=1');
        } else {
            $this->checkoutSession->restoreQuote();
            if (!empty($status['msg'])) {
                $this->messageManager->addNoticeMessage($status['msg']);
            } else {
                $this->messageManager->addNoticeMessage(__('Something went wrong.'));
            }
            $this->_redirect('checkout/cart');
        }
    }
}
