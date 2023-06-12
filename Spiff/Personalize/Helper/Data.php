<?php
namespace Spiff\Personalize\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Data extends AbstractHelper
{
    const XML_PATH_SPIFF = 'spiff_personalize/';

    /**
     * @var TokenFactory
     */
    protected $tokenFactory;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     *
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Constructor
     *
     * @param TokenFactory $tokenFactory
     * @param CustomerSession $customerSession
     */

    public function __construct(
        TokenFactory $tokenFactory,
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->tokenFactory = $tokenFactory;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getGeneralConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_SPIFF .'general/'. $code, $storeId);
    }

    /**
     * Get current store currency code
     *
     * @return string
     */
    public function getCurrentCurrencyCode()
    {
        return $this->storeManager->getStore()->getCurrentCurrencyCode();
    }

    /**
     * Get customer token
     *
     * @return string
     */
    public function getCustomerToken()
    {
        $customerId = $this->customerSession->getCustomer()->getId();
        $customerToken = $this->tokenFactory->create();
        
        return $customerToken->createCustomerToken($customerId)->getToken();
    }
}
