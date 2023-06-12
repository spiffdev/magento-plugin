<?php
namespace Spiff\Personalize\Model;

use Spiff\Personalize\Api\CustomerRepositoryInterface;
use Spiff\Personalize\Api\Data\CustomerSessionOutputDataInterfaceFactory;
use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\Visitor;
use Magento\Customer\Model\Customer;
use Magento\Quote\Model\QuoteFactory;
use Magento\Integration\Model\Oauth\TokenFactory as TokenModelFactory;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface as CoreCustomerRepositoryInterface;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;

class CustomerRepository implements CustomerRepositoryInterface
{
    /**
     * @var CoreCustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var AccountManagementInterface
     */
    protected $customerAccountManagement;

    /**
     * @var CustomerOutputDataInterfaceFactory
     */
    protected $outputFactory;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var CustomerSessionOutputDataInterfaceFactory
     */
    protected $customerSessionOutputDataInterfaceFactory;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var Visitor
     */
    protected $visitor;

    /**
     * @var Customer
     */
    protected $customer;


    /**
     * @var Session
     */
    protected $session;

    /**
     * @var TokenModelFactory
     */
    protected $tokenModelFactory;

    /**
     * @var QuoteIdToMaskedQuoteIdInterface
     */
    protected $quoteIdToMaskedQuoteIdInterface;

    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * Customr REpository Constructor
     *
     * @param Cart $cart
     * @param Visitor $visitor
     * @param Customer $customer
     * @param Session $session
     * @param TokenModelFactory $tokenModelFactory
     * @param QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteIdInterface
     * @param CustomerSessionOutputDataInterfaceFactory $customerSessionOutputDataInterfaceFactory
     * @param QuoteFactory $quoteFactory
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     */
    public function __construct(
        Cart $cart,
        Visitor $visitor,
        Customer $customer,
        Session $session,
        TokenModelFactory $tokenModelFactory,
        QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteIdInterface,
        CustomerSessionOutputDataInterfaceFactory $customerSessionOutputDataInterfaceFactory,
        QuoteFactory $quoteFactory,
        QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
        $this->cart = $cart;
        $this->visitor = $visitor;
        $this->customer = $customer;
        $this->session = $session;
        $this->tokenModelFactory = $tokenModelFactory;
        $this->quoteIdToMaskedQuoteIdInterface = $quoteIdToMaskedQuoteIdInterface;
        $this->customerSessionOutputDataInterfaceFactory = $customerSessionOutputDataInterfaceFactory;
        $this->quoteFactory = $quoteFactory;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
    }

    /**
     * Check customer information by phpsessionID
     */
    public function syncSession($customerId = null)
    {
        $return = $this->customerSessionOutputDataInterfaceFactory->create();
        $token = null;
        $maskedId = null;
        if (!$this->session->isLoggedIn() && $customerId) {
            $token = $this->login($customerId);
        }
        if ($this->session->isLoggedIn()) {
            $customerId = $this->session->getCustomerId();
            $token = $this->tokenModelFactory->create()->createCustomerToken($customerId)->getToken();
        }
        $cart = $this->cart;
        $quoteId = $cart->getQuote()->getId();
        if (!$quoteId && $customerId) {
            $quote = $this->quoteFactory->create()->loadByCustomer($customerId);
            $quoteId = $quote->getId();
        }
        if (!$quoteId && !$customerId) {
            $quoteIdMaskFactory = $this->quoteIdMaskFactory->create();
            $quoteManagement  = $this->quoteFactory->create()->save();
            $cartId = $quoteManagement->getId();
            $cart->setQuote($quoteManagement)->save();
            $quoteIdMaskFactory->setQuoteId($cartId)->save();
            $maskedId = $quoteIdMaskFactory->getMaskedId();
        }
        if ($quoteId && !$customerId) {
            $maskedId = $this->quoteIdToMaskedQuoteIdInterface->execute($quoteId);
        }
        $return->setCartId($maskedId?:$quoteId);
        $return->setCustomerToken($token);
        $return->setSessionId(session_id());
        return $return;
    }

    public function login($customerId)
    {
        $token = null;
        $customer = $this->customer->load($customerId);
        $this->session->setCustomerAsLoggedIn($customer);
        if ($this->session->isLoggedIn()) {
            $token = $this->tokenModelFactory->create()->createCustomerToken($customerId)->getToken();
        }
        return $token;
    }
}
