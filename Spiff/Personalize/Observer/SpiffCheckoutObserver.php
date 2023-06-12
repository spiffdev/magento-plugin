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
    const SPIFF_ACCESS_KEY_PATH = 'spiff_access_key';
    const SPIFF_SECRET_PATH = 'spiff_secret';
    const SPIFF_API_BASE = 'https://api.spiff.com.au';
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

    function spiff_hex_to_base64($hex)
    {
        $return = "";
        foreach (str_split($hex, 2) as $pair) {
            $return .= chr(hexdec($pair));
        }
        return base64_encode($return);
    }
    
    function spiff_auth_header($access_key, $secret_key, $method, $body, $content_type, $date_string, $path)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/custom.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
		$hashed = hash("sha512", $body);
        $string_to_sign = $method . "\n" . $hashed . "\n" . $content_type . "\n" . $date_string . "\n" . $path;
        $signature = $this->spiff_hex_to_base64(hash_hmac("sha1", $string_to_sign, $secret_key));
        $logger->info('signature: ' . $signature);

        return 'SOA '  . $access_key . ':' . $signature;
    }
    
    function spiff_request_headers($access_key, $secret_key, $body, $path)
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
        $logger->info('path: ' . $path);
        return [
            'Authorization' => $this->spiff_auth_header($access_key, $secret_key, 'POST', $body, $content_type, $date_string, $path),
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
            $access_key = $this->spiffHelperData->getGeneralConfig(self::SPIFF_ACCESS_KEY_PATH);
            $secret_key = $this->spiffHelperData->getGeneralConfig(self::SPIFF_SECRET_PATH);
            $logger->info("access_key: " . $access_key);
            $logger->info("secret_key: " . $secret_key);
            $logger->info("body: " . json_encode($body));
            $headers = $this->spiff_request_headers($access_key, $secret_key, json_encode($body), self::SPIFF_API_ORDERS_PATH);
            $logger->info("headers: " . json_encode($headers));
            
            $this->curl->setHeaders($headers);
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->post(self::SPIFF_API_BASE . self::SPIFF_API_ORDERS_PATH, json_encode($body));
    
            $result = $this->curl->getBody();
            $header = $this->curl->getHeaders();
    
            $logger->info("response: " . json_encode($result));
        } catch (\Exception $e) {
            $logger->info("error: " . $e->getMessage());
        }
    }
}
