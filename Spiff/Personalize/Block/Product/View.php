<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Spiff\Personalize\Block\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Option;
/**
 * Product View block
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class View extends \Magento\Catalog\Block\Product\View 
{
    public $Product;
    public $Option;
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