<?php

namespace Spiff\Personalize\Observer;

use DateTimeZone;
use DateTime;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\HTTP\Client\Curl;
use Spiff\Personalize\Helper\Data as SpiffHelperData;
use Magento\Directory\Model\CountryFactory;

class SpiffCheckoutObserver implements ObserverInterface
{
    const SPIFF_REGION_PATH = 'region';
    const SPIFF_APPLICATION_KEY_PATH = 'application_key';
    const SPIFF_API_AU = 'https://api.au.spiffcommerce.com';
    const SPIFF_API_US = 'https://api.us.spiffcommerce.com';
    const SPIFF_API_ORDERS_PATH = '/api/v2/orders';

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var SpiffHelperData
     */
    protected $spiffHelperData;

    /**
     * @var CountryFactory
     */
    protected $countryFactory;

    /**
     * Constructor.
     *
     * @param Curl $curl
     * @param SpiffHelperData $spiffHelperData
     */
    public function __construct(
        Curl $curl,
        SpiffHelperData $spiffHelperData,
        CountryFactory $countryFactory
    ) {
        $this->curl = $curl;
        $this->spiffHelperData = $spiffHelperData;
        $this->countryFactory = $countryFactory;
    }

    public function execute(Observer $observer)
    {
		
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/custom.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        try {
            $order = $observer->getEvent()->getOrder();
            $quote = $observer->getEvent()->getQuote();
			
			if (empty($order) || empty($quote)){
				return;
			}

            $quoteItems = $quote->getItems();
            $orderItems = $order->getItems();
            $items = [];
            $logger->info('Observer run1');
            foreach ($orderItems as $item) {
                $quoteItemId = $item->getQuoteItemId();
                $items[$quoteItemId] = $item;
            }

            $logger->info('Observer run2');
            $spiffOrderItems = [];
            $lineItems = [];
    
            foreach ($quoteItems as $item) {
                if ($item->getSpiffTransactionId() !== null) {
                    // Save spiff transaction id to order item
                    $quoteItemId = $item->getItemId();
                    $orderItem = $items[$quoteItemId];
                    $orderItem->setSpiffTransactionId($item->getSpiffTransactionId());
                    $orderItem->save();

                    $spiffOrderItem = [];
                    $spiffOrderItem['amountToOrder'] = $item->getQty();
                    $spiffOrderItem['transactionId'] = $item->getSpiffTransactionId();
                    $lineItems[] = [
                        'productId' => $item->getProductId()
                    ];
                    array_push($spiffOrderItems, $spiffOrderItem);
                }
            }
            if (count($spiffOrderItems) > 0) {
                // Request data to spiff
                $shippingAddress = $order->getShippingAddress();
                $billingAddress = $order->getBillingAddress();
                $shippingCountry = $this->countryFactory->create()->loadByCode($shippingAddress->getCountryId());
                $billingCountry = $this->countryFactory->create()->loadByCode($billingAddress->getCountryId());
                $shippingAddressData = [
                    'address1'      => isset($shippingAddress->getStreet()[0]) ? $shippingAddress->getStreet()[0] : '',
                    'address2'      => isset($shippingAddress->getStreet()[1]) ? $shippingAddress->getStreet()[1] : '',
                    'city'          => $shippingAddress->getCity(),
                    'company'       => $shippingAddress->getCompany(),
                    'country'       => $shippingCountry->getName(),
                    'countryCode'   => $shippingAddress->getCountryId(),
                    'firstName'     => $shippingAddress->getFirstname(),
                    'lastName'      => $shippingAddress->getLastname(),
                    'name'          => $shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname(),
                    'phone'         => $shippingAddress->getTelephone(),
                    'province'      => $shippingAddress->getRegion(),
                    'provinceCode'  => $shippingAddress->getRegionId(),
                    'zip'           => $shippingAddress->getPostcode()
                ];
                $logger->info('address: ', json_encode($shippingAddressData));
                $billingAddressData = [
                    'address1'      => isset($billingAddress->getStreet()[0]) ? $billingAddress->getStreet()[0] : '',
                    'address2'      => isset($billingAddress->getStreet()[1]) ? $billingAddress->getStreet()[1] : '',
                    'city'          => $billingAddress->getCity(),
                    'company'       => $billingAddress->getCompany(),
                    'country'       => $billingCountry->getName(),
                    'countryCode'   => $billingAddress->getCountryId(),
                    'firstName'     => $billingAddress->getFirstname(),
                    'lastName'      => $billingAddress->getLastname(),
                    'name'          => $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname(),
                    'phone'         => $billingAddress->getTelephone(),
                    'province'      => $billingAddress->getRegion(),
                    'provinceCode'  => $billingAddress->getRegionId(),
                    'zip'           => $billingAddress->getPostcode()
                ];
                $externalData = [
                    'orderNumber'       => $order->getIncrementId(),
                    'customerEmail'     => $order->getCustomerEmail(),
                    'customerPhone'     => $shippingAddress->getTelephone(),
                    'billingAddress'    => $billingAddressData,
                    'shippingAddress'   => $shippingAddressData,
                    'discountCodes'     => $order->getCouponCode() === null ? null : [$order->getCouponCode()],
                    'lineItems'        => $lineItems,
                    'note'              => $order->getCustomerNote()
                ];
                $logger->info('externalData: ', json_encode($externalData));
                $body = [
                    'externalId'    => $order->getIncrementId(),
                    'autoPrint'     => false,
                    'orderItems'    => $spiffOrderItems,
                    'externalData'  => $externalData
                ];
                $this->sendRequestToSpiff($body);
            }
        } catch (\Exception $e) {
            $logger->info("error: " . $e->getMessage());
        }
    }
    
    function spiff_request_headers($application_key)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/custom.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $content_type = 'application/json';
        $date = new DateTime("now", new DateTimeZone("GMT"));
        $date_string = $date->format("D, d M Y H:i:s") . " GMT";

        $logger->info('method: ' . 'POST');
        $logger->info('content_type: ' . $content_type);
        $logger->info('date: ' . $date_string);
        return [
            'X-Application-Key' => $application_key,
            'Content-Type' => $content_type,
            'Date' => $date_string,
        ];
    }

    function sendRequestToSpiff($body)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/custom.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        try {
            $api_region = $this->spiffHelperData->getGeneralConfig(self::SPIFF_REGION_PATH);
            $application_key = $this->spiffHelperData->getGeneralConfig(self::SPIFF_APPLICATION_KEY_PATH);
            $logger->info("body: " . json_encode($body));
            $headers = $this->spiff_request_headers($application_key);
            $logger->info("headers: " . json_encode($headers));
            
            $this->curl->setHeaders($headers);
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);

            switch ($api_region) {
                case 'US':
                    $this->curl->post(self::SPIFF_API_US . self::SPIFF_API_ORDERS_PATH, json_encode($body));
                    break;
                default:
                $this->curl->post(self::SPIFF_API_AU . self::SPIFF_API_ORDERS_PATH, json_encode($body));
                    break;
            }
    
            $result = $this->curl->getBody();
    
            $logger->info("response: " . json_encode($result));
        } catch (\Exception $e) {
            $logger->info("error: " . $e->getMessage());
        }
    }
}
