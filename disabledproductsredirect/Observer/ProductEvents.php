<?php
namespace MediaLounge\DisabledProductsRedirect\Observer;


use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;


class ProductEvents implements ObserverInterface

{
  public function __construct
  (
    CategoryRepositoryInterface $categoryInterface,
    ResponseInterface $response,
    Http $request,
    ProductRepositoryInterface $productRepository,
    ManagerInterface $messageManager,
    ScopeConfigInterface $scopeConfig
  ){

    $this->response = $response;
    $this->request = $request;
    $this->productRepository = $productRepository;
    $this->categoryInterface = $categoryInterface;
    $this->messageManager = $messageManager;
    $this->_scopeConfig = $scopeConfig;
  }

  /**

   * Below is the method that will fire whenever the event runs!
   * @param Observer $observer
   */
  public function execute(Observer $observer)
  {

    // If redirect is disabled in system_config bail out.
    if($this->_scopeConfig->getValue("medialounge/general/enable", "websites") == 1){

      // Get productId.
      $productId = (int)$this->request->getParam('id');

      // Load product object.
      $product = $this->productRepository->getById($productId);

      // Get associated category id(s).
      $catIds = $product->getCategoryIds();

      if ($catIds) {

        // Get first value from cats.
        $firstCategoryId = $catIds[0];

        // Load category object.
        $category = $this->categoryInterface->get($firstCategoryId);

        // Get category url.
        $catUrl = $category->getUrl();

        // If product status is 2 (disabled).
        if ($product->getStatus() == 2) {

          // Set the 404 message and assign to message object.
          $msg = 'The product you tried to view is no longer available but here are some other options instead.';
          $this->messageManager->addNoticeMessage($msg);

          // Redirect to the catUrl.
          $this->response->setRedirect($catUrl, 301)->sendResponse();

        }
      }
    }
  }
}
