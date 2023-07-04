<?php
namespace Spiff\Personalize\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Registry;
use Magento\Catalog\Api\ProductRepositoryInterface;
class SpiffGetRedirectProductObserver implements ObserverInterface
{
    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var registry
     */
    private $registry;

    /**
     *
     * @param ProductRepositoryInterface $productRepository
     * @param Registry $registry
     */
    public function __construct(
        Registry $registry,
        ProductRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
        $this->registry = $registry;
      
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        //get product from observer event
        $product = $observer->getEvent()->getProduct();
        //get redirect product from product
        $RedirectProduct = $product->getRedirectProduct();
        if($RedirectProduct){
            //load spiffProduct
            $spiffProduct = $this->productRepository->get($RedirectProduct);
            // save spiffProduct in register
            $this->registry->register('spiff_product', $spiffProduct);
        }    
    }
  }
