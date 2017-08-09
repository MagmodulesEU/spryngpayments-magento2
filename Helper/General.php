<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Spryng\Payment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Config\Model\ResourceModel\Config;
use Spryng\Payment\Logger\SpryngLogger;

class General extends AbstractHelper
{

    const MODULE_CODE = 'Spryng_Payment';
    const API_ENDPOINT = 'https://api.spryngpayments.com/v1/';
    const API_ENDPOINT_SANDBOX = 'https://sandbox.spryngpayments.com/v1/';
    const XML_PATH_MODULE_ACTIVE = 'payment/spryng_general/enabled';
    const XML_PATH_API_MODUS = 'payment/spryng_general/type';
    const XML_PATH_LIVE_APIKEY = 'payment/spryng_general/apikey_live';
    const XML_PATH_SANDBOX_APIKEY = 'payment/spryng_general/apikey_sandbox';
    const XML_PATH_DYNAMIC_DESCRIPTOR = 'payment/spryng_general/dynamic_descriptor';
    const XML_PATH_DEBUG = 'payment/spryng_general/debug';
    const XML_PATH_STATUS_PROCESSING = 'payment/spryng_general/order_status_processing';
    const XML_PATH_STATUS_PENDING = 'payment/spryng_general/order_status_pending';
    const XML_PATH_INVOICE_NOTIFY = 'payment/spryng_general/invoice_notify';
    const XML_PATH_MERCHANT_REFERENCE = 'payment/spryng_general/merchant_reference';

    /**
     * General constructor.
     *
     * @param Context               $context
     * @param StoreManagerInterface $storeManager
     * @param Config                $resourceConfig
     * @param ModuleListInterface   $moduleList
     * @param SpryngLogger          $logger
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Config $resourceConfig,
        ModuleListInterface $moduleList,
        SpryngLogger $logger
    ) {
        $this->storeManager = $storeManager;
        $this->resourceConfig = $resourceConfig;
        $this->urlBuilder = $context->getUrlBuilder();
        $this->moduleList = $moduleList;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Availabiliy check, on Active, API Client & API Key
     *
     * @param $storeId
     *
     * @return bool
     */
    public function isAvailable($storeId)
    {
        $active = $this->getStoreConfig(self::XML_PATH_MODULE_ACTIVE, $storeId);
        if (!$active) {
            return false;
        }

        return true;
    }

    /**
     * @param     $path
     * @param int $storeId
     * @param int $websiteId
     *
     * @return mixed
     */
    public function getStoreConfig($path, $storeId = 0, $websiteId = 0)
    {
        if ($websiteId > 0) {
            return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $storeId);
        } else {
            return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        }
    }

    /**
     * @param     $storeId
     * @param int $websiteId
     *
     * @return mixed
     */
    public function getApiKey($storeId, $websiteId = 0)
    {
        if ($this->isSandbox($storeId, $websiteId)) {
            return $this->getStoreConfig(self::XML_PATH_SANDBOX_APIKEY, $storeId, $websiteId);
        } else {
            return $this->getStoreConfig(self::XML_PATH_LIVE_APIKEY, $storeId, $websiteId);
        }
    }

    /**
     * @param     $storeId
     * @param int $websiteId
     *
     * @return bool
     */
    public function isSandbox($storeId, $websiteId = 0)
    {
        $modus = $this->getStoreConfig(self::XML_PATH_API_MODUS, $storeId, $websiteId);
        if ($modus == 'sandbox') {
            return true;
        }

        return false;
    }

    /**
     * @param $storeId
     * @param $type
     *
     * @return string
     */
    public function getApiEndpoint($storeId, $type)
    {
        if ($this->isSandbox($storeId)) {
            return self::API_ENDPOINT_SANDBOX . $type;
        } else {
            return self::API_ENDPOINT . $type;
        }
    }

    /**
     * Write to log
     *
     * @param $type
     * @param $data
     */
    public function addTolog($type, $data)
    {
        $debug = $this->getStoreConfig(self::XML_PATH_DEBUG);
        if ($debug) {
            if ($type == 'error') {
                $this->logger->addErrorLog($type, $data);
            } else {
                $this->logger->addInfoLog($type, $data);
            }
        }
    }

    /**
     * Selected processing status
     *
     * @param int $storeId
     *
     * @return mixed
     */
    public function getStatusProcessing($storeId = 0)
    {
        return $this->getStoreConfig(self::XML_PATH_STATUS_PROCESSING, $storeId);
    }

    /**
     * Selected pending (payment) status
     *
     * @param int $storeId
     *
     * @return mixed
     */
    public function getStatusPending($storeId = 0)
    {
        return $this->getStoreConfig(self::XML_PATH_STATUS_PENDING, $storeId);
    }

    /**
     * Send invoice
     *
     * @param int $storeId
     *
     * @return mixed
     */
    public function sendInvoice($storeId = 0)
    {
        return (int)$this->getStoreConfig(self::XML_PATH_INVOICE_NOTIFY, $storeId);
    }

    /**
     * Returns current version of the extension for admin display
     *
     * @return mixed
     */
    public function getExtensionVersion()
    {
        $moduleInfo = $this->moduleList->getOne(self::MODULE_CODE);

        return $moduleInfo['setup_version'];
    }

    /**
     * @return mixed
     */
    public function getUserAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'];
    }

    /**
     * @param $incrementId
     * @param $storeId
     *
     * @return mixed
     */
    public function getDynamicDescriptor($incrementId, $storeId)
    {
        $descriptor = $this->getStoreConfig(self::XML_PATH_DYNAMIC_DESCRIPTOR, $storeId);
        if (!empty($descriptor)) {
            return str_replace('%id%', $incrementId, $descriptor);
        } else {
            return __('Order %1', $incrementId);
        }
    }

    /**
     * @param $storeId
     *
     * @return mixed
     */
    public function getMerchantReference($storeId)
    {
        $merchantReference = $this->getStoreConfig(self::XML_PATH_MERCHANT_REFERENCE, $storeId);
        if (empty($merchantReference)) {
            $baseUrl = parse_url($this->storeManager->getStore($storeId)->getBaseUrl());
            return 'Magento Plugin installed at ' . $baseUrl['host'];
        }
        return $this->getStoreConfig(self::XML_PATH_MERCHANT_REFERENCE, $storeId);
    }

    /**
     * @return mixed|string
     */
    public function getReturnUrl()
    {
        $url = $this->urlBuilder->getUrl('spryng/checkout/success/', ['_secure' => true]);
        return strpos($url, 'https') !== false ? $url : str_replace('http', 'https', $url);
    }

    /**
     * @return mixed|string
     */
    public function getWebhookUrl()
    {
        $url = $this->urlBuilder->getUrl('spryng/checkout/webhook/', ['_secure' => true]);
        return strpos($url, 'https') !== false ? $url : str_replace('http', 'https', $url);
    }

    /**
     * @param     $_code
     * @param int $storeId
     *
     * @return mixed
     */
    public function getAccount($_code, $storeId = 0)
    {
        $path = 'payment/' . $_code . '/account';
        return $this->getStoreConfig($path, $storeId);
    }

    /**
     * @param     $_code
     * @param int $storeId
     *
     * @return mixed
     */
    public function getOrganisation($_code, $storeId = 0)
    {
        $path = 'payment/' . $_code . '/organisation';
        return $this->getStoreConfig($path, $storeId);
    }

    /**
     * @param     $_code
     * @param int $storeId
     *
     * @return mixed
     */
    public function getProjectId($_code, $storeId = 0)
    {
        $path = 'payment/' . $_code . '/project_id';
        return $this->getStoreConfig($path, $storeId);
    }

    /**
     * @param $telephone
     * @param $countryId
     *
     * @return mixed
     */
    public function getFormattedPhoneNumber($telephone, $countryId)
    {
        $libphonenumber = \libphonenumber\PhoneNumberUtil::getInstance();
        $phoneNumber = $libphonenumber->parse($telephone, $countryId);
        $formattedPhoneNumber = $libphonenumber->format($phoneNumber, \libphonenumber\PhoneNumberFormat::E164);
        return $formattedPhoneNumber;
    }
}
