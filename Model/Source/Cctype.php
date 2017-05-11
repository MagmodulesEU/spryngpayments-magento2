<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 *
 */

namespace Spryng\Payment\Model\Source;

class Cctype extends \Magento\Payment\Model\Source\Cctype
{

    /**
     * @return array
     */
    public function getAllowedTypes()
    {
        return ['VI', 'MC', 'MI'];
    }
}
