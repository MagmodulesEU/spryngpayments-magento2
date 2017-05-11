<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Spryng\Payment\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\App\Request\Http;
use Spryng\Payment\Model\Spryng as SpryngModel;

class Organisation implements ArrayInterface
{

    private $spryngModel;
    private $request;

    /**
     * Organisation constructor.
     *
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

        $organisation = $this->spryngModel->getOrganisations($storeId, $websiteId);
        foreach ($organisation as $value => $label) {
            $optionArray[] = ['value' => $value, 'label' => $label];
        }
        return $optionArray;
    }
}
