<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Spiff\Personalize\Api;

/**
 * Interface CartCustomItemRepositoryInterface
 * @api
 * @since 100.0.2
 */
interface CartCustomItemRepositoryInterface
{
    /**
     * Add/update the specified cart item.
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItem The item.
     * @param \Spiff\Personalize\Api\Data\CustomItemInformationInterface $customItemInformation
     * @param bool $useSellPoint
     * @return \Magento\Quote\Api\Data\CartItemInterface Item.
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     * @throws \Magento\Framework\Exception\CouldNotSaveException The specified item could not be saved to the cart.
     * @throws \Magento\Framework\Exception\InputException The specified item or cart is not valid.
     */
    public function save(
        \Magento\Quote\Api\Data\CartItemInterface $cartItem,
        \Spiff\Personalize\Api\Data\CustomItemInformationInterface $customItemInformation,
        bool $useSellPoint = false
    );

    /**
     * Update the specified cart item.
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItem The item.
     * @param \Spiff\Personalize\Api\Data\CustomItemInformationInterface $customItemInformation
     * @param bool $useSellPoint
     * @return \Magento\Quote\Api\Data\CartItemInterface Item.
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     * @throws \Magento\Framework\Exception\CouldNotSaveException The specified item could not be saved to the cart.
     * @throws \Magento\Framework\Exception\InputException The specified item or cart is not valid.
     */
    public function updateCustomItems(
        \Magento\Quote\Api\Data\CartItemInterface $cartItem,
        \Spiff\Personalize\Api\Data\CustomItemInformationInterface $customItemInformation,
        bool $useSellPoint = false
    );
}
