<?php
namespace Spiff\Personalize\Helper;

use Magento\Framework\Registry;
use Magento\Framework\App\Helper\AbstractHelper;
class ProductHelper extends AbstractHelper
{
      /**
     * @var registry
     */
    private $registry;
     /**
     * @param Registry $registry
     */
    public function __construct(
        Registry $registry
    ) {
        $this->registry = $registry;
    }
  public function getSpiffProductRegistry(){
     return $this->registry->registry('spiff_product');
  }
}
