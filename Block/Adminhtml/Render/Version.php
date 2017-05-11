<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Spryng\Payment\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Spryng\Payment\Helper\General as SpryngHelper;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Version extends Field
{

    private $spryngHelper;

    /**
     * Version constructor.
     *
     * @param Context      $context
     * @param SpryngHelper $spryngHelper
     */
    public function __construct(
        Context $context,
        SpryngHelper $spryngHelper
    ) {
        $this->spryngHelper = $spryngHelper;
        parent::__construct($context);
    }

    /**
     * Render block: extension version
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $html = '<tr id="row_' . $element->getHtmlId() . '">';
        $html .= '  <td class="label">' . $element->getData('label') . '</td>';
        $html .= '  <td class="value">' . $this->spryngHelper->getExtensionVersion() . '</td>';
        $html .= '  <td></td>';
        $html .= '</tr>';

        return $html;
    }
}
