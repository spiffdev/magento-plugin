<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Spiff\Personalize\Api;

/**
 * Check/Edit Customer Information.
 * @api
 * @since 100.0.2
 */
interface CustomerRepositoryInterface
{
    /**
     * Check customer information by phpsessionID.
     *
     * @param int $customerId
     * @return \Spiff\Personalize\Api\Data\CustomerSessionOutputDataInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function syncSession($customerId = null);
}
