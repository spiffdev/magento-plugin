<?php
/**
 * Spiff
 *
 * NOTICE OF LICENSE
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Spiff
 * @package     Spiff_Personalize
 * @copyright   Copyright (c) Spiff
 * @license     
 */

namespace Spiff\Personalize\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Protocol
 * @package Mageplaza\Smtp\Model\Config\Source
 */
class Region implements ArrayInterface
{
    /**
     * to option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            [
                'value' => 'AU',
                'label' => __('Australia')
            ],
            [
                'value' => 'US',
                'label' => __('United States')
            ]        ];

        return $options;
    }
}