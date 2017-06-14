<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Spryng\Payment\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;
use Spryng\Payment\Helper\General as GeneralHelper;

class Header extends Field
{
    private $general;
    protected $_template = 'Spryng_Payment::system/config/fieldset/header.phtml';

    /**
     * Header constructor.
     *
     * @param Context       $context
     * @param GeneralHelper $general
     */
    public function __construct(
        Context $context,
        GeneralHelper $general
    ) {
        $this->general = $general;
        parent::__construct($context);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->addClass('spryng');
        return $this->toHtml();
    }
}
