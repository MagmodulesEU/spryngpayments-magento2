<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Spryng\Payment\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

class SandboxModus implements ArrayInterface
{

    /**
     * Live/Test Key Array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'sandbox', 'label' => __('Enabled (sandbox mode)')],
            ['value' => 'live', 'label' => __('Disabled (live)')]
        ];
    }
}
