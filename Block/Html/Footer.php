<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_Blog
 * @copyright   Copyright (c) 2016 Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */
namespace Mageplaza\Blog\Block\Html;

use \Magento\Framework\View\Element\Template\Context;
use \Mageplaza\Blog\Helper\Data;

class Footer extends \Magento\Framework\View\Element\Html\Link
{
    public $helper;

    public function __construct(
        Context $context,
        Data $helper,
        array $data = []
    ) {
    
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    public function getHref()
    {
        return $this->helper->getBlogUrl('');
    }
}
