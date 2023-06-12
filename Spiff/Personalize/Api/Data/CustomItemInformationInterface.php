<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Spiff\Personalize\Api\Data;

/**
 * Interface CustomItemInformationInterface
 * @api
 * @since 100.0.2
 */
interface CustomItemInformationInterface
{
    const CUSTOM_PRICE = 'custom_price';
    const TRANSACTION_ID = 'transaction_id';

    /**
     * Returns the item custom price.
     *
     * @return float Item ID. Otherwise, null.
     */
    public function getCustomPrice();

    /**
     * Sets the item custom price.
     *
     * @param float $customPrice
     * @return $this
     */
    public function setCustomPrice($customPrice);

    /**
     * Returns the item transaction id.
     *
     * @return string Item ID. Otherwise, null.
     */
    public function getTransactionId();

    /**
     * Sets the item transaction id.
     *
     * @param string $transactionId
     * @return $this
     */
    public function setTransactionId($transactionId);
}
