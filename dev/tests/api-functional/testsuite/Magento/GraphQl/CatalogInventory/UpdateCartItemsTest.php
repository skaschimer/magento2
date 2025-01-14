<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\CatalogInventory;

use Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId;
use Magento\GraphQl\Quote\GetQuoteItemIdByReservedQuoteIdAndSku;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for updating/removing shopping cart items
 */
class UpdateCartItemsTest extends GraphQlAbstract
{
    /**
     * @var GetMaskedQuoteIdByReservedOrderId
     */
    private $getMaskedQuoteIdByReservedOrderId;

    /**
     * @var GetQuoteItemIdByReservedQuoteIdAndSku
     */
    private $getQuoteItemIdByReservedQuoteIdAndSku;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->getMaskedQuoteIdByReservedOrderId = $objectManager->get(GetMaskedQuoteIdByReservedOrderId::class);
        $this->getQuoteItemIdByReservedQuoteIdAndSku = $objectManager->get(
            GetQuoteItemIdByReservedQuoteIdAndSku::class
        );
    }

    /**
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     */
    public function testUpdateCartItemDecimalQuantity()
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $itemId = $this->getQuoteItemIdByReservedQuoteIdAndSku->execute('test_quote', 'simple_product');

        $quantity = 0.5;
        $query = $this->getMutation($maskedQuoteId, $itemId, $quantity);
        $response = $this->graphQlMutation($query);

        $this->assertArrayHasKey('updateCartItems', $response);
        $this->assertArrayHasKey('errors', $response['updateCartItems']);

        $responseError = $response['updateCartItems']['errors'][0];
        $this->assertEquals(
            "Could not update the product with SKU simple_product: The fewest you may purchase is 1.",
            $responseError['message']
        );
        $this->assertEquals('INVALID_PARAMETER_VALUE', $responseError['code']);
    }

    /**
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     */
    public function testUpdateCartItemSetUnavailableQuantity()
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $itemId = $this->getQuoteItemIdByReservedQuoteIdAndSku->execute('test_quote', 'simple_product');

        $quantity = 100;
        $query = $this->getMutation($maskedQuoteId, $itemId, $quantity);
        $response = $this->graphQlMutation($query);
        $this->assertArrayHasKey('updateCartItems', $response);
        $this->assertArrayHasKey('errors', $response['updateCartItems']);

        $responseError = $response['updateCartItems']['errors'][0];
        $this->assertEquals(
            "Could not update the product with SKU simple_product: Not enough items for sale",
            $responseError['message']
        );
        $this->assertEquals('INSUFFICIENT_STOCK', $responseError['code']);
    }

    /**
     * @param string $maskedQuoteId
     * @param int $itemId
     * @param float $quantity
     * @return string
     */
    private function getMutation(string $maskedQuoteId, int $itemId, float $quantity): string
    {
        return <<<MUTATION
mutation {
  updateCartItems(input: {
    cart_id: "{$maskedQuoteId}"
    cart_items:[
      {
        cart_item_id: {$itemId}
        quantity: {$quantity}
      }
    ]
  }) {
    cart {
      items {
        id
        quantity
      }
    }
    errors {
      message
      code
    }
  }
}
MUTATION;
    }
}
