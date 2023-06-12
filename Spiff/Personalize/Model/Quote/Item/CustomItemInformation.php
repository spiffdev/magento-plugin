<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Spiff\Personalize\Model\Quote\Item;

use Spiff\Personalize\Api\Data\CustomItemInformationInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

/**
 * Sales Quote Item Model
 *
 * @api
 * @since 100.0.2
 */
class CustomItemInformation extends AbstractExtensibleModel implements CustomItemInformationInterface
{
    /**
     * @inheritdoc
     */
    public function getCustomPrice()
    {
        return $this->getData(CustomItemInformationInterface::CUSTOM_PRICE);
    }

    /**
     * @inheritdoc
     */
    public function setCustomPrice($customPrice)
    {
        return $this->setData(CustomItemInformationInterface::CUSTOM_PRICE, $customPrice);
    }

    /**
     * @inheritdoc
     */
    public function getTransactionId()
    {
        return $this->getData(CustomItemInformationInterface::TRANSACTION_ID);
    }

    /**
     * @inheritdoc
     */
    public function setTransactionId($transactionId)
    {
        return $this->setData(CustomItemInformationInterface::TRANSACTION_ID, $transactionId);
    }
}
