<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Spryng\Payment\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\App\Request\Http;
use Spryng\Payment\Model\Spryng as SpryngModel;

class Account implements ArrayInterface
{

    private $spryngModel;
    private $request;

    /**
     * Account constructor.
     *
     * @param Http        $request
     * @param SpryngModel $spryngModel
     */
    public function __construct(
        Http $request,
        SpryngModel $spryngModel
    ) {
        $this->spryngModel = $spryngModel;
        $this->request = $request;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $optionArray = [];
        $storeId = (int)$this->request->getParam('store', 0);
        $websiteId = (int)$this->request->getParam('website', 0);
        $accounts = $this->spryngModel->getAccounts($storeId, $websiteId);

        if (isset($accounts['-1'])) {
            return ['value' => '', 'label' => $accounts['-1']];
        }

        $optionArray[] = ['value' => '', 'label' => __('--Please Select--')];
        foreach ($accounts as $value => $label) {
            $optionArray[] = ['value' => $value, 'label' => $label];
        }
        return $optionArray;
    }
}
