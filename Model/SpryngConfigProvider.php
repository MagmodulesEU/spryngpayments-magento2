<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Spryng\Payment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Spryng\Payment\Model\Spryng as SpryngModel;
use Spryng\Payment\Helper\General as SpryngHelper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Model\Config as PaymentConfig;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Escaper;

class SpryngConfigProvider implements ConfigProviderInterface
{

    private $methodCodes = [
        'spryng_methods_creditcard',
        'spryng_methods_ideal',
        'spryng_methods_paypal',
        'spryng_methods_sepa',
        'spryng_methods_sofort',
        'spryng_methods_klarna',
        'spryng_methods_bancontact'
    ];

    private $methods = [];
    private $spryngModel;
    private $spryngHelper;
    private $paymentConfig;
    private $escaper;
    private $scopeConfig;
    private $storeManager;

    /**
     * SpryngConfigProvider constructor.
     *
     * @param Spryng                $spryngModel
     * @param SpryngHelper          $spryngHelper
     * @param PaymentHelper         $paymentHelper
     * @param PaymentConfig         $paymentConfig
     * @param ScopeConfigInterface  $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Escaper               $escaper
     */
    public function __construct(
        SpryngModel $spryngModel,
        SpryngHelper $spryngHelper,
        PaymentHelper $paymentHelper,
        PaymentConfig $paymentConfig,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Escaper $escaper
    ) {
        $this->spryngModel = $spryngModel;
        $this->spryngHelper = $spryngHelper;
        $this->paymentConfig = $paymentConfig;
        $this->escaper = $escaper;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;

        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $paymentHelper->getMethodInstance($code);
        }
    }

    /**
     * Config Data for checkout
     *
     * @return array
     */
    public function getConfig()
    {
        $config = [];
        $storeId = $this->storeManager->getStore()->getId();
        foreach ($this->methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['payment']['instructions'][$code] = $this->getInstructions($code);
                if ($code == 'spryng_methods_ideal') {
                    $config['payment']['issuers'] = $this->getIssuers();
                }
                if ($code == 'spryng_methods_sepa' || $code == 'spryng_methods_klarna') {
                    $config['payment']['prefix'] = $this->getCustomerPrefixes();
                }
                if ($code == 'spryng_methods_klarna') {
                    $config['payment']['pclasses'] = $this->spryngModel->getPClasses($code, $storeId);
                }
                if ($code == 'spryng_methods_creditcard') {
                    $config['payment']['api_endpoint'][$code] = $this->getCardApiEndpoint($storeId);
                    $config['payment']['organisation'][$code] = $this->spryngHelper->getOrganisation($code, $storeId);
                    $config['payment']['account'][$code] = $this->spryngHelper->getAccount($code, $storeId);
                    $config['payment']['months'][$code] = $this->getCcMonths();
                    $config['payment']['years'][$code] = $this->getCcYears();
                }
                if ($code == 'spryng_methods_bancontact') {
                    $config['payment']['api_endpoint'][$code] = $this->getCardApiEndpoint($storeId);
                    $config['payment']['organisation'][$code] = $this->spryngHelper->getOrganisation($code, $storeId);
                    $config['payment']['account'][$code] = $this->spryngHelper->getAccount($code, $storeId);
                    $config['payment']['months'][$code] = $this->getCcMonths();
                    $config['payment']['years'][$code] = $this->getCcYears();
                }
            }
        }

        return $config;
    }

    /**
     * Instruction data
     *
     * @param $code
     *
     * @return string
     */
    public function getInstructions($code)
    {
        return nl2br($this->escaper->escapeHtml($this->methods[$code]->getInstructions()));
    }

    /**
     * iDEAL Issuers
     *
     * @return array
     */
    public function getIssuers()
    {
        return [
            ['id' => 'ABNANL2A', 'name' => 'ABN Ambro'],
            ['id' => 'ASNBNL21', 'name' => 'ASN Bank'],
            ['id' => 'BUNQNL2A', 'name' => 'Bunq'],
            ['id' => 'FVLBNL22', 'name' => 'Van Lanschot Bankiers'],
            ['id' => 'INGBNL2A', 'name' => 'ING'],
            ['id' => 'KNABNL2H', 'name' => 'Knab'],
            ['id' => 'RABONL2U', 'name' => 'Rabobank'],
            ['id' => 'RBRBNL21', 'name' => 'Regiobank'],
            ['id' => 'SNSNML2A', 'name' => 'SNS Bank'],
            ['id' => 'TRIONL2U', 'name' => 'Triodos Bank']
        ];
    }

    /**
     * Customer prefix array (for SEPA & KLARNA)
     *
     * @return array
     */
    public function getCustomerPrefixes()
    {
        return [
            ['id' => 'mr', 'name' => 'Mr.'],
            ['id' => 'ms', 'name' => 'Ms.']
        ];
    }

    /**
     * @param $storeId
     *
     * @return string
     */
    public function getCardApiEndpoint($storeId)
    {
        return $this->spryngHelper->getApiEndpoint($storeId, 'card');
    }

    /**
     * Creditcards Months
     *
     * @return array
     */
    public function getCcMonths()
    {
        $data = [];
        $months = $this->paymentConfig->getMonths();
        foreach ($months as $k => $v) {
            $data[] = ['id' => $k, 'name' => $v];
        }
        return $data;
    }

    /**
     * Credicard Years
     *
     * @return array
     */
    public function getCcYears()
    {
        $data = [];
        $years = $this->paymentConfig->getYears();
        foreach ($years as $k => $v) {
            $data[] = ['id' => substr($k, -2), 'name' => $v];
        }
        return $data;
    }

    /**
     * Date of Birth - Days (KLARNA)
     *
     * @return array
     */
    public function getDobDays()
    {
        $days = [];
        for ($i = 0; $i < 31; $i++) {
            $days[] = ['id' => $i, 'name' => $i];
        }
        return $days;
    }

    /**
     * Date of Birth - Months (KLARNA)
     *
     * @return array
     */
    public function getDobMonths()
    {
        $months = [];
        for ($i = 0; $i < 8; $i++) {
            $timestamp = mktime(0, 0, 0, date('n') - $i, 1);
            $months[] = ['id' => date('n', $timestamp), 'name' => date('F', $timestamp)];
        }
        return $months;
    }

    /**
     * Date of Birth - Years (KLARNA)
     *
     * @return array
     */
    public function getDobYears()
    {
        $years = [];
        for ($i = 1930; $i < 2017; $i++) {
            $days[] = ['id' => $i, 'name' => $i];
        }
        return $years;
    }
}
