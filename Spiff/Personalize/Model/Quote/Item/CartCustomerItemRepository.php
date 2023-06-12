<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Spiff\Personalize\Model\Quote\Item;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor;
use Spiff\Personalize\Api\CartCustomItemRepositoryInterface;

/**
 * CartCustomerItemRepository for quote item.
 */
class CartCustomerItemRepository implements CartCustomItemRepositoryInterface
{
    /**
     * Quote repository.
     *
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * Product repository.
     *
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var CartItemInterfaceFactory
     */
    protected $itemDataFactory;

    /**
     * @var CartItemProcessorInterface[]
     */
    protected $cartItemProcessors;

    /**
     * @var CartItemOptionsProcessor
     */
    private $cartItemOptionsProcessor;


    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param ProductRepositoryInterface $productRepository
     * @param CartItemInterfaceFactory $itemDataFactory
     * @param CartItemOptionsProcessor $cartItemOptionsProcessor
     * @param CartItemProcessorInterface[] $cartItemProcessors
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        ProductRepositoryInterface $productRepository,
        CartItemInterfaceFactory $itemDataFactory,
        CartItemOptionsProcessor $cartItemOptionsProcessor,
        array $cartItemProcessors = []
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->productRepository = $productRepository;
        $this->itemDataFactory = $itemDataFactory;
        $this->cartItemOptionsProcessor = $cartItemOptionsProcessor;
        $this->cartItemProcessors = $cartItemProcessors;
    }

    /**
     * @inheritdoc
     */
    public function save(
        \Magento\Quote\Api\Data\CartItemInterface $cartItem,
        \Spiff\Personalize\Api\Data\CustomItemInformationInterface $customItemInformation,
        bool $useSellPoint = false
    ) {
        /** @var \Magento\Quote\Model\Quote $quote */
        $cartId = $cartItem->getQuoteId();
        if (!$cartId) {
            throw new InputException(
                __('"%fieldName" is required. Enter and try again.', ['fieldName' => 'quoteId'])
            );
        }

        $quote = $this->quoteRepository->getActive($cartId);
        $quoteItems = $quote->getItems();
        
        $quoteItems[] = $cartItem;
        $quote->setItems($quoteItems);
        $this->quoteRepository->save($quote);
        
        $quote->collectTotals();

        return $quote->getLastAddedItem();
    }

    /**
     * @inheritdoc
     */
    public function updateCustomItems(
        \Magento\Quote\Api\Data\CartItemInterface $cartItem,
        \Spiff\Personalize\Api\Data\CustomItemInformationInterface $customItemInformation,
        bool $useSellPoint = false
    ) {
        /** @var \Magento\Quote\Model\Quote $quote */
        $cartId = $cartItem->getQuoteId();
        if (!$cartId) {
            throw new InputException(
                __('"%fieldName" is required. Enter and try again.', ['fieldName' => 'quoteId'])
            );
        }

        $quote = $this->quoteRepository->getActive($cartId);
        $items = $quote->getItems();
        foreach ($items as $item) {
            if ($item->getSku() === $cartItem->getSku()) {
                $product = $this->productRepository->get($item->getSku());
                
                if ($useSellPoint) {
                    $mpSellProduct  = $product->getData('mp_reward_sell_product');
                    $price = 0;
                    $item->setCustomPrice($price);
                    $item->setOriginalCustomPrice($price);
                    $item->setBaseOriginalPrice($price);
                    $item->setMpRewardSellPoints($mpSellProduct);
                    $item->getProduct()->setIsSuperMode(true);
                } elseif ($product->getUseSpiffPrice()) {
                    $item->setCustomPrice($customItemInformation->getCustomPrice());
                    $item->setOriginalCustomPrice($customItemInformation->getCustomPrice());
                    $item->getProduct()->setIsSuperMode(true);
                }
                $item->setSpiffTransactionId($customItemInformation->getTransactionId());
                break;
            }
        }
        $this->quoteRepository->save($quote);
        
        $quote->collectTotals();

        return $item;
    }
}
