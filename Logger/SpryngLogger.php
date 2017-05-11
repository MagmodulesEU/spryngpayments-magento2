<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Spryng\Payment\Logger;

use Monolog\Logger;

class SpryngLogger extends Logger
{

    /**
     * Add info data to Spryng Log
     *
     * @param $type
     * @param $data
     */
    public function addInfoLog($type, $data)
    {
        if (is_array($data)) {
            $this->addInfo($type . ': ' . json_encode($data, true));
        } elseif (is_object($data)) {
            $this->addInfo($type . ': ' . json_encode($data, true));
        } else {
            $this->addInfo($type . ': ' . $data);
        }
    }

    /**
     * Add error data to spryng Log
     *
     * @param $type
     * @param $data
     */
    public function addErrorLog($type, $data)
    {
        if (is_array($data)) {
            $this->addError($type . ': ' . json_encode($data, true));
        } elseif (is_object($data)) {
            $this->addError($type . ': ' . json_encode($data));
        } else {
            $this->addError($type . ': ' . $data);
        }
    }
}
