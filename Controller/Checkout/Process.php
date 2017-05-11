<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Spryng\Payment\Controller\Checkout;

use Magento\Payment\Helper\Data as PaymentHelper;
use Spryng\Payment\Helper\General as SpryngHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;

class Process extends Action
{

    protected $checkoutSession;
    protected $logger;
    protected $paymentHelper;
    protected $spryngHelper;
    protected $resultPageFactory;

    /**
     * Redirect constructor.
     *
     * @param Context       $context
     * @param Session       $checkoutSession
     * @param PaymentHelper $paymentHelper
     * @param SpryngHelper  $spryngHelper
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        PaymentHelper $paymentHelper,
        SpryngHelper $spryngHelper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->paymentHelper = $paymentHelper;
        $this->spryngHelper = $spryngHelper;
        parent::__construct($context);
    }

    /**
     * Execute Redirect to Spryng after placing order
     */
    public function execute()
    {
        try {
            $order = $this->checkoutSession->getLastRealOrder();
            $method = $order->getPayment()->getMethod();
            $methodInstance = $this->paymentHelper->getMethodInstance($method);
            if ($methodInstance instanceof \Spryng\Payment\Model\Spryng) {
                $transaction = $methodInstance->startTransaction($order);
                if (!empty($transaction['error_msg'])) {
                    $msg = $transaction['error_msg'];
                    $this->messageManager->addError($msg);
                    $this->checkoutSession->restoreQuote();
                    $this->_redirect('checkout/cart');
                }
                if (!empty($transaction['approval_url'])) {
                    $redirectUrl = $transaction['approval_url'];
                    $this->getResponse()->setRedirect($redirectUrl);
                }
            }
        } catch (\Exception $e) {
            $msg = __('An error occured while processing your payment request, please try again later');
            $this->messageManager->addExceptionMessage($e, $msg);
            $this->spryngHelper->addTolog('error', $e->getMessage());
            $this->checkoutSession->restoreQuote();
            $this->_redirect('checkout/cart');
        }
    }
}
