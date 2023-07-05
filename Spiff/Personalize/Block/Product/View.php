<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Spiff\Personalize\Block\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
/**
 * Product View block
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class View extends \Magento\Catalog\Block\Product\View 
{
    /**
     * @var  \Magento\Catalog\Model\Product
     */
    protected $Product;
    /**
     * @var  \Magento\Catalog\Model\Product\Option
     */
    protected $Option;
    /**
     * @param Context $context
     * @param \Magento\Framework\Url\EncoderInterface $urlEncoder
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Customer\Model\Session $customerSession
     * @param ProductRepositoryInterface|\Magento\Framework\Pricing\PriceCurrencyInterface $productRepository
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Catalog\Model\Product $Product
     * @param \Magento\Catalog\Model\Product\Option $Option
     * @param array $data
     * @codingStandardsIgnoreStart
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
            \Magento\Catalog\Block\Product\Context $context,
            \Magento\Framework\Url\EncoderInterface $urlEncoder,
            \Magento\Framework\Json\EncoderInterface $jsonEncoder,
            \Magento\Framework\Stdlib\StringUtils $string,
            \Magento\Catalog\Helper\Product $productHelper,
            \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig,
            \Magento\Framework\Locale\FormatInterface $localeFormat,
            \Magento\Customer\Model\Session $customerSession,
            ProductRepositoryInterface $productRepository,
            \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
            \Magento\Catalog\Model\Product $Product,
            \Magento\Catalog\Model\Product\Option $Option,
            array $data = []
    ) {
        $this->Product = $Product; 
        $this->Option = $Option;
        parent::__construct($context, $urlEncoder, $jsonEncoder, $string, $productHelper, $productTypeConfig, $localeFormat, $customerSession, $productRepository, $priceCurrency,$data);
    }
    public function getOptionData($_product)
    {
          $product = $this->Product->load($_product->getId());
          $customOptions = $this->Option->getProductOptionCollection($product);
          $optionsArray = $customOptions->getItems();
          $optionId=null;
          if($optionsArray!=null){
             foreach($optionsArray as $option){
                $optionId = $option->getOptionId();
             }
         }
        return $optionId;
    }
}