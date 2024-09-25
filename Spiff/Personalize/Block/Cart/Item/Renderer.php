<?php
/**
 * Copyright � Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Spiff\Personalize\Block\Cart\Item;

use Magento\Catalog\Pricing\Price\ConfiguredPriceInterface;
use Magento\Checkout\Block\Cart\Item\Renderer\Actions;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Message\InterpretationStrategyInterface;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Framework\App\ObjectManager;
use Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\HTTP\Client\Curl;

/**
 * Shopping cart item render block
 *
 * @api
 * @author      Magento Core Team <core@magentocommerce.com>
 *
 * @method \Magento\Checkout\Block\Cart\Item\Renderer setProductName(string)
 * @method \Magento\Checkout\Block\Cart\Item\Renderer setDeleteUrl(string)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @since 100.0.2
 */
    class Renderer extends \Magento\Framework\View\Element\Template implements
        \Magento\Framework\DataObject\IdentityInterface
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    const SPIFF_API_AU = 'https://api.au.spiffcommerce.com';
    const SPIFF_API_US = 'https://api.us.spiffcommerce.com';
    const SPIFF_API_TRANSACTIONS_PATH = '/api/transactions';
   
    /**
     * @var AbstractItem
     */
    protected $_item;
    protected $curl;
    /**
     * @var string
     */
    protected $_productUrl;
    protected $productRepository;

    /**
     * Whether qty will be converted to number
     *
     * @var bool
     */
    protected $_strictQtyMode = true;

    /**
     * Check, whether product URL rendering should be ignored
     *
     * @var bool
     */
    protected $_ignoreProductUrl = false;

    /**
     * Catalog product configuration
     *
     * @var \Magento\Catalog\Helper\Product\Configuration
     */
    protected $_productConfig = null;

    /**
     * @var \Magento\Framework\Url\Helper\Data
     */
    protected $_urlHelper;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Catalog\Block\Product\ImageBuilder
     */
    protected $imageBuilder;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    public $moduleManager;
    public $clientFactor;
    /**
     * @var InterpretationStrategyInterface
     */
    private $messageInterpretationStrategy;

    /** @var ItemResolverInterface */
    private $itemResolver;
    protected $moduleDirReader;
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Catalog\Helper\Product\Configuration $productConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param InterpretationStrategyInterface $messageInterpretationStrategy
     * @param array $data
     * @param ItemResolverInterface|null $itemResolver
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Helper\Product\Configuration $productConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Module\Manager $moduleManager,
        InterpretationStrategyInterface $messageInterpretationStrategy,
        Reader $moduleDirReader,
        Curl $curl,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        array $data = [],
        
        ItemResolverInterface $itemResolver = null
    ) {
       
        $this->priceCurrency = $priceCurrency;
        $this->imageBuilder = $imageBuilder;
        $this->_urlHelper = $urlHelper;
        $this->_productConfig = $productConfig;
        $this->_checkoutSession = $checkoutSession;
        $this->messageManager = $messageManager;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $data);
        $this->_isScopePrivate = true;
        $this->moduleManager = $moduleManager;
        $this->messageInterpretationStrategy = $messageInterpretationStrategy;
        $this->moduleDirReader  = $moduleDirReader;
        $this->curl = $curl;
        $this->productRepository = $productRepository;
        $this->itemResolver = $itemResolver ?: ObjectManager::getInstance()->get(ItemResolverInterface::class);
    }

    /**
     * Set item for render
     *
     * @param AbstractItem $item
     * @return $this
     * @codeCoverageIgnore
     */
    public function setItem(AbstractItem $item)
    {
        $this->_item = $item;
        return $this;
    }

    /**
     * Get quote item
     *
     * @return AbstractItem
     * @codeCoverageIgnore
     */
    public function getItem()
    {
        return $this->_item;
    }

    /**
     * Get item product
     *
     * @return \Magento\Catalog\Model\Product
     * @codeCoverageIgnore
     */
    public function getProduct()
    {
        return $this->getItem()->getProduct();
    }

    /**
     * Identify the product from which thumbnail should be taken.
     *
     * @return \Magento\Catalog\Model\Product
     * @codeCoverageIgnore
     */
    public function getProductForThumbnail()
    {
        return $this->itemResolver->getFinalProduct($this->getItem());
    }

    /**
     * Override product url.
     *
     * @param string $productUrl
     * @return $this
     * @codeCoverageIgnore
     */
    public function overrideProductUrl($productUrl)
    {
        $this->_productUrl = $productUrl;
        return $this;
    }

    /**
     * Check Product has URL
     *
     * @return bool
     */
    public function hasProductUrl()
    {
        if ($this->_ignoreProductUrl) {
            return false;
        }

        if ($this->_productUrl || $this->getItem()->getRedirectUrl()) {
            return true;
        }

        $product = $this->getProduct();
        $option = $this->getItem()->getOptionByCode('product_type');
        if ($option) {
            $product = $option->getProduct();
        }

        if ($product->isVisibleInSiteVisibility()) {
            return true;
        } else {
            if ($product->hasUrlDataObject()) {
                $data = $product->getUrlDataObject();
                if (in_array($data->getVisibility(), $product->getVisibleInSiteVisibilities())) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Retrieve URL to item Product
     *
     * @return string
     */
    public function getProductUrl()
    {
        if ($this->_productUrl !== null) {
            return $this->_productUrl;
        }
        if ($this->getItem()->getRedirectUrl()) {
            return $this->getItem()->getRedirectUrl();
        }

        $product = $this->getProduct();
        $option = $this->getItem()->getOptionByCode('product_type');
        if ($option) {
            $product = $option->getProduct();
        }

        return $product->getUrlModel()->getUrl($product);
    }

    /**
     * Get item product name
     *
     * @return string
     */
    public function getProductName()
    {
        if ($this->hasProductName()) {
            return $this->getData('product_name');
        }
        return $this->getProduct()->getName();
    }

    /**
     * Get product customize options
     *
     * @return array
     */
    public function getProductOptions()
    {
        /* @var $helper \Magento\Catalog\Helper\Product\Configuration */
        $helper = $this->_productConfig;
        return $helper->getCustomOptions($this->getItem());
    }

    /**
     * Get list of all options for product
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getOptionList()
    {
        return $this->getProductOptions();
    }

	public function getSelectedOptionsOfQuoteItem(\Magento\Catalog\Model\Product\Configuration\Item\ItemInterface $item)
    {
		$writer = new \Zend_Log_Writer_Stream(BP . '/var/log/getCustomOptions.log');
		$logger = new \Zend_Log();
		$logger->addWriter($writer);
		$logger->info("getSelectedOptionsOfQuoteItem");
		$logger->info(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,15));
        return $this->_productConfig->getCustomOptions($item);
    }

    /**
     * Get quote item qty
     *
     * @return float|int
     */
    public function getQty()
    {
        if (!$this->_strictQtyMode && (string)$this->getItem()->getQty() == '') {
            return '';
        }
        return $this->getItem()->getQty() * 1;
    }

    /**
     * Get checkout session
     *
     * @return \Magento\Checkout\Model\Session
     * @codeCoverageIgnore
     */
    public function getCheckoutSession()
    {
        return $this->_checkoutSession;
    }

    /**
     * Retrieve item messages, return array with keys, text => the message text, type => type of a message
     *
     * @return array
     */
    public function getMessages()
    {
        $messages = [];
        $quoteItem = $this->getItem();

        // Add basic messages occurring during this page load
        $baseMessages = $quoteItem->getMessage(false);
        if ($baseMessages) {
            foreach ($baseMessages as $message) {
                $messages[] = ['text' => $message, 'type' => $quoteItem->getHasError() ? 'error' : 'notice'];
            }
        }

        /* @var $collection \Magento\Framework\Message\Collection */
        $collection = $this->messageManager->getMessages(true, 'quote_item' . $quoteItem->getId());
        if ($collection) {
            $additionalMessages = $collection->getItems();
            foreach ($additionalMessages as $message) {
                /* @var $message \Magento\Framework\Message\MessageInterface */
                $messages[] = [
                    'text' => $this->messageInterpretationStrategy->interpret($message),
                    'type' => $message->getType()
                ];
            }
        }
        $this->messageManager->getMessages(true, 'quote_item' . $quoteItem->getId())->clear();

        return $messages;
    }

    /**
     * Accept option value and return its formatted view
     *
     * @param string|array $optionValue
     * Method works well with these $optionValue format:
     *      1. String
     *      2. Indexed array e.g. array(val1, val2, ...)
     *      3. Associative array, containing additional option info, including option value, e.g.
     *          array
     *          (
     *              [label] => ...,
     *              [value] => ...,
     *              [print_value] => ...,
     *              [option_id] => ...,
     *              [option_type] => ...,
     *              [custom_view] =>...,
     *          )
     *
     * @return array
     */
    public function getFormatedOptionValue($optionValue)
    {
        /* @var $helper \Magento\Catalog\Helper\Product\Configuration */
        $helper = $this->_productConfig;
        $params = [
            'max_length' => 55,
            'cut_replacer' => ' <a href="#" class="dots tooltip toggle" onclick="return false">...</a>'
        ];
        return $helper->getFormattedOptionValue($optionValue, $params);
    }

    /**
     * Check whether Product is visible in site
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isProductVisible()
    {
        return $this->getProduct()->isVisibleInSiteVisibility();
    }

    /**
     * Return product additional information block
     *
     * @return AbstractBlock
     * @codeCoverageIgnore
     */
    public function getProductAdditionalInformationBlock()
    {
        return $this->getLayout()->getBlock('additional.product.info');
    }

    /**
     * Set qty mode to be strict or not
     *
     * @param bool $strict
     * @return $this
     * @codeCoverageIgnore
     */
    public function setQtyMode($strict)
    {
        $this->_strictQtyMode = $strict;
        return $this;
    }

    /**
     * Set ignore product URL rendering
     *
     * @param bool $ignore
     * @return $this
     * @codeCoverageIgnore
     */
    public function setIgnoreProductUrl($ignore = true)
    {
        $this->_ignoreProductUrl = $ignore;
        return $this;
    }

    /**
     * Return identifiers for produced content
     *
     * @return array
     */
    public function getIdentities()
    {
        $identities = [];
        if ($this->getItem()) {
            $identities = $this->getProduct()->getIdentities();
        }
        return $identities;
    }

    /**
     * Get product price formatted with html (final price, special price, mrp price)
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getProductPriceHtml(\Magento\Catalog\Model\Product $product)
    {
        $priceRender = $this->getPriceRender();
        $priceRender->setItem($this->getItem());

        $price = '';
        if ($priceRender) {
            $price = $priceRender->render(
                ConfiguredPriceInterface::CONFIGURED_PRICE_CODE,
                $product,
                [
                    'include_container' => true,
                    'display_minimal_price' => true,
                    'zone' => \Magento\Framework\Pricing\Render::ZONE_ITEM_LIST
                ]
            );
        }

        return $price;
    }

    /**
     * Get price renderer.
     *
     * @return \Magento\Framework\Pricing\Render
     * @codeCoverageIgnore
     */
    protected function getPriceRender()
    {
        return $this->getLayout()->getBlock('product.price.render.default');
    }

    /**
     * Convert prices for template
     *
     * @param float $amount
     * @param bool $format
     * @return float
     */
    public function convertPrice($amount, $format = false)
    {
        return $format
            ? $this->priceCurrency->convertAndFormat($amount)
            : $this->priceCurrency->convert($amount);
    }

    /**
     * Return the unit price html
     *
     * @param AbstractItem $item
     * @return string
     */
    public function getUnitPriceHtml(AbstractItem $item)
    {
        /** @var Renderer $block */
        $block = $this->getLayout()->getBlock('checkout.item.price.unit');
        $block->setItem($item);
        return $block->toHtml();
    }

    /**
     * Return row total html
     *
     * @param AbstractItem $item
     * @return string
     */
    public function getRowTotalHtml(AbstractItem $item)
    {
        /** @var Renderer $block */
        $block = $this->getLayout()->getBlock('checkout.item.price.row');
        $block->setItem($item);
        return $block->toHtml();
    }

    /**
     * Return item price html for sidebar
     *
     * @param AbstractItem $item
     * @return string
     */
    public function getSidebarItemPriceHtml(AbstractItem $item)
    {
        /** @var Renderer $block */
        $block = $this->getLayout()->getBlock('checkout.cart.item.price.sidebar');
        $block->setItem($item);
        return $block->toHtml();
    }

    /**
     * Get unit price excluding tax html
     *
     * @param AbstractItem $item
     * @return string
     */
    public function getUnitPriceExclTaxHtml(AbstractItem $item)
    {
        /** @var Renderer $block */
        $block = $this->getLayout()->getBlock('checkout.onepage.review.item.price.unit.excl');
        $block->setItem($item);
        return $block->toHtml();
    }

    /**
     * Get unit price including tax html
     *
     * @param AbstractItem $item
     * @return string
     */
    public function getUnitPriceInclTaxHtml(AbstractItem $item)
    {
        /** @var Renderer $block */
        $block = $this->getLayout()->getBlock('checkout.onepage.review.item.price.unit.incl');
        $block->setItem($item);
        return $block->toHtml();
    }

    /**
     * Get row total excluding tax html
     *
     * @param AbstractItem $item
     * @return string
     */
    public function getRowTotalExclTaxHtml(AbstractItem $item)
    {
        /** @var Renderer $block */
        $block = $this->getLayout()->getBlock('checkout.onepage.review.item.price.rowtotal.excl');
        $block->setItem($item);
        return $block->toHtml();
    }

    /**
     * Get row total including tax html
     *
     * @param AbstractItem $item
     * @return string
     */
    public function getRowTotalInclTaxHtml(AbstractItem $item)
    {
        /** @var Renderer $block */
        $block = $this->getLayout()->getBlock('checkout.onepage.review.item.price.rowtotal.incl');
        $block->setItem($item);
        return $block->toHtml();
    }

    /**
     * Get row total including tax html
     *
     * @param AbstractItem $item
     * @return string
     */
    public function getActions(AbstractItem $item)
    {
        
        /** @var Actions $block */
        $block = $this->getChildBlock('actions');
        if ($block instanceof Actions) {
            $block->setItem($item);
            return $block->toHtml();
        } else {
         
            return '';
        }
    }

    /**
     * Retrieve product image
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param string $imageId
     * @param array $attributes
     * @return \Magento\Catalog\Block\Product\Image
     */
    public function getImage($product, $imageId, $attributes = [])
    {
        return $this->imageBuilder->create($product, $imageId, $attributes);
    }


    public function spiff_get_data_transaction_api($transaction_id) {
        $data = [];
        $api_region = $this->scopeConfig->getValue( 
            'spiff_personalize/general/region', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE, 
         );
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/region.log');
		$logger = new \Zend_Log();
		$logger->addWriter($writer);
		$logger->info($api_region);
        switch ($api_region) {
            case 'US':
                $this->curl->get(self::SPIFF_API_US.self::SPIFF_API_TRANSACTIONS_PATH.'/'.$transaction_id);
                break;
            default:
                $this->curl->get(self::SPIFF_API_AU.self::SPIFF_API_TRANSACTIONS_PATH.'/'.$transaction_id);
                break;
        }

        
        $responseCode = $this->curl->getStatus();
        $responseData = $this->curl->getBody();
        if($responseCode ===200){
            $dataTransaction = json_decode($responseData, true);
            $href = $dataTransaction['links'][0]['href'];
            if ($href === '') {
                return null;
            }
        }  
        $this->curl->setOption(CURLOPT_FOLLOWLOCATION, false);

        switch ($api_region) {
            case 'US':
                $this->curl->get(self::SPIFF_API_US.$href);
                break;
            default:
                $this->curl->get(self::SPIFF_API_AU.$href);
                break;
        }
        
      
        $response = $this->curl->getBody();
       
        $dataDesgn = json_decode($response, true);
        $data['spiff_image'] = $dataDesgn['links'][0]['href'];
        $data['metadata'] =$dataDesgn['data']['metadata'];
        return $data;
    }
    
    public function spiff_return_data_in_cart($product, $cart_item) {	
		
		if ($product->getTypeId() == 'bundle' ) {
			return $product;
		}
        if (!array_key_exists('spiff_transaction_id', $cart_item->getData())) {
          return $product;
        }
        $baseProduct = $this->productRepository->get($cart_item->getSku());
        if($cart_item->getSpiffTransactionId()!==null && $baseProduct->getRedirectProduct()===null ){
            $data = $this->spiff_get_data_transaction_api($cart_item->getSpiffTransactionId());
            return $data;
        }else{
            return $product;
        }
    }
    public function CheckMetaData($data){   
        if($data == null){
            return null;
        }else{
            if(!array_key_exists('Text 1 text',$data)&&
            !array_key_exists('Text 2 text',$data)&&
            !array_key_exists('Text 3 text',$data)&&
            !array_key_exists('Text 4 text',$data)&&
            !array_key_exists('To text',$data)&&
            !array_key_exists('Message text',$data)){
               return true;
            }else{
                return false;
            }
        }
    }
    public function removeWhitespace($key){
        $trimmedKey = trim($key);
        $compressedKey = preg_replace('/\s+/', ' ', $trimmedKey);
        return $compressedKey;
    }
}
