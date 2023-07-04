<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Spiff\Personalize\Block\Product\View\Options\Type;

/**
 * Product options text type block
 *
 * @api
 * @since 100.0.2
 */
class Text extends \Magento\Catalog\Block\Product\View\Options\AbstractOptions
{
    /**
     * Returns default value to show in text input
     *
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->getProduct()->getPreconfiguredValues()->getData('options/' . $this->getOption()->getId());
    }
    public function getValue($id){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Session');
        $cartItems = $cart->getQuote()->getAllVisibleItems();
        $items = [];
        foreach($cartItems as $cartItem){
            if($cartItem->getSpiffTransactionId()!=null){
                $items[] = [
                    'productid' => $cartItem->getProductId(),
                    'spiff_transaction_id' => $cartItem->getSpiffTransactionId()
                ];
            }  
        }
        $spiff_transition_id = '';
        foreach($items as $item){
            if($id == $item['productid']){
                $spiff_transition_id = $item['spiff_transaction_id'];
            }
        }
        return $spiff_transition_id;
    }
}
