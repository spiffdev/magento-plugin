<?php
namespace Spiff\Personalize\Api\Data;

/**
 * @api
 */
interface CustomerSessionOutputDataInterface
{
    /**
     * @return string
     */
    public function getCustomerToken();

    /**
     * @param string $customerToken
     * @return $this
     */
    public function setCustomerToken($customerToken);

    /**
     * @return string
     */
    public function getCartId();

    /**
     * @param string $cartId
     * @return $this
     */
    public function setCartId($cartId);

    /**
     * @return string
     */
    public function getSessionId();

    /**
     * @param string $sessionId
     * @return $this
     */
    public function setSessionId($sessionId);
}
