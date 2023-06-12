<?php
namespace Spiff\Personalize\Model\Data;

use Magento\Framework\Model\AbstractModel;

/**
 * @api
 */
class CustomerSessionOutputData extends AbstractModel implements \Spiff\Personalize\Api\Data\CustomerSessionOutputDataInterface
{

    /**
     * {@inheritdoc}
     */
    public function getCustomerToken()
    {
        return $this->getData('customer_token');
    }

    /**
     * @param string $customerToken
     * @return $this
     */
    public function setCustomerToken($customerToken)
    {
        return $this->setData('customer_token', $customerToken);
    }

    /**
     * {@inheritdoc}
     */
    public function getCartId()
    {
        return $this->getData('cart_id');
    }

    /**
     * @param string $cartId
     * @return $this
     */
    public function setCartId($cartId)
    {
        return $this->setData('cart_id', $cartId);
    }

    /**
     * {@inheritdoc}
     */
    public function getSessionId()
    {
        return $this->getData('session_id');
    }

    /**
     * @param string $sessionId
     * @return $this
     */
    public function setSessionId($sessionId)
    {
        return $this->setData('session_id', $sessionId);
    }
}
