<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\App;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\GraphQl\App\State\GraphQlStateDiff;
use Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId;

/**
 * Tests the dispatch method in the GraphQl Controller class using a simple product query
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 * @magentoAppArea graphql
 */
class GraphQlCheckoutMutationsStateTest extends \PHPUnit\Framework\TestCase
{
    private $graphQlStateDiff;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->graphQlStateDiff = new GraphQlStateDiff();
        parent::setUp();
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        $this->graphQlStateDiff->tearDown();
        parent::tearDown();
    }

    private function getCartIdHash(): string
    {
        $getMaskedQuoteIdByReservedOrderId = $this->graphQlStateDiff->
            getTestObjectManager()->get(GetMaskedQuoteIdByReservedOrderId::class);
        return $getMaskedQuoteIdByReservedOrderId->execute('test_quote');
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testCreateEmptyCart() : void
    {
        $this->graphQlStateDiff->
            testState($this->getEmptyCart(),
            [],
            [],
            [],
            'createEmptyCart',
            '"data":{"createEmptyCart":',
            $this
        );
    }


    /**
     * @magentoDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @return void
     * @throws \Exception
     */
    public function testAddSimpleProductToCart()
    {
        $cartId = $this->getCartIdHash();
        $query = $this->getAddProductToCartQuery();
        $this->graphQlStateDiff->testState(
            $query,
            ['cartId' => $cartId, 'qty' => 1, 'sku' => 'simple_product'],
            [],
            [],
            'addSimpleProductsToCart',
            '"data":{"addSimpleProductsToCart":',
            $this
        );
    }
    /**
     * @magentoDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoDataFixture Magento/GraphQl/Catalog/_files/virtual_product.php
     * @return void
     * @throws \Exception
     */
    public function testAddVirtualProductToCart()
    {
        $cartId = $this->getCartIdHash();
        $query = $this->getAddVirtualProductToCartQuery();
        $this->graphQlStateDiff->testState(
            $query,
            ['cartId' => $cartId, 'quantity' => 1, 'sku' => 'virtual_product'],
            [],
            [],
            'addSimpleProductsToCart',
            '"data":{"addVirtualProductsToCart":',
            $this
        );
    }

    /**
     * @magentoDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoDataFixture Magento/Bundle/_files/product.php
     * @return void
     */
    public function testAddBundleProductToCart()
    {
        $cartId = $this->getCartIdHash();
        $query = $this->getAddBundleProductToCartQuery($cartId, 'bundle-product');
        $this->graphQlStateDiff->testState(
            $query,
            [],
            [],
            [],
            'addBundleProductsToCart',
            '"data":{"addBundleProductsToCart":',
            $this
        );
    }

    /**
     * @magentoDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoDataFixture Magento/ConfigurableProduct/_files/product_configurable.php
     * @return void
     */
    public function testAddConfigurableProductToCart(): void
    {
        $cartId = $this->getCartIdHash();
        $query = $this->getAddConfigurableProductToCartQuery();
        $this->graphQlStateDiff->testState(
            $query,
            ['cartId' => $cartId, 'quantity' => 2, 'parentSku' => 'configurable', 'childSku' => 'simple_20'],
            [],
            [],
            'addConfigurableProductsToCart',
            '"data":{"addConfigurableProductsToCart":',
            $this
        );
    }

    /**
     * @magentoDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoDataFixture Magento/Downloadable/_files/product_downloadable_with_purchased_separately_links.php
     * @return void
     */
    public function testAddDownloadableProductToCart(): void
    {
        $cartId = $this->getCartIdHash();
        $sku = 'downloadable-product-with-purchased-separately-links';
        $links = $this->getProductsLinks($sku);
        $linkId = key($links);
        $query = $this->getAddDownloadableProductToCartQuery();
        $this->graphQlStateDiff->testState(
            $query,
            ['cartId' => $cartId, 'qty' => 1, 'sku' => $sku, 'linkId' => $linkId],
            [],
            [],
            'addDownloadableProductsToCart',
            '"data":{"addDownloadableProductsToCart":',
            $this
        );
    }

    /**
     * @magentoDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @return void
     */
    public function testSetShippingAddressOnCart(): void
    {
        $cartId = $this->getCartIdHash();
        $query = $this->getShippingAddressQuery();
        $this->graphQlStateDiff->testState(
            $query,
            ['cartId' => $cartId],
            [],
            [],
            'setShippingAddressesOnCart',
            '"data":{"setShippingAddressesOnCart":',
            $this
        );
    }

    /**
     * @magentoDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoDataFixture Magento/GraphQl/Quote/_files/set_new_shipping_address.php
     * @return void
     */
    public function testSetBillingAddressOnCart(): void
    {
        $cartId = $this->getCartIdHash();
        $query = $this->getBillingAddressQuery();
        $this->graphQlStateDiff->testState(
            $query,
            ['cartId' => $cartId],
            [],
            [],
            'setBillingAddressOnCart',
            '"data":{"setBillingAddressOnCart":',
            $this
        );
    }

    /**
     * @magentoDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoDataFixture Magento/GraphQl/Quote/_files/set_new_shipping_address.php
     * @magentoDataFixture Magento/GraphQl/Quote/_files/set_new_billing_address.php
     * @return void
     */
    public function testSetShippingMethodsOnCart(): void
    {
        $cartId = $this->getCartIdHash();
        $query = $this->getShippingMethodsQuery();
        $this->graphQlStateDiff->testState(
            $query,
            ['cartId' => $cartId, 'shippingMethod' => 'flatrate'],
            [],
            [],
            'setShippingMethodsOnCart',
            '"data":{"setShippingMethodsOnCart":',
            $this
        );
    }

    /**
     * @magentoDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoDataFixture Magento/GraphQl/Quote/_files/set_new_shipping_address.php
     * @magentoDataFixture Magento/GraphQl/Quote/_files/set_new_billing_address.php
     * @magentoDataFixture Magento/GraphQl/Quote/_files/set_flatrate_shipping_method.php
     */
    public function testSetPaymentMethodOnCart(): void
    {
        $cartId = $this->getCartIdHash();
        $query = $this->getPaymentMethodQuery();
        $this->graphQlStateDiff->testState(
            $query,
            ['cartId' => $cartId],
            [],
            [],
            'setPaymentMethodOnCart',
            '"data":{"setPaymentMethodOnCart":',
            $this
        );

    }

    /**
     * @magentoDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoDataFixture Magento/GraphQl/Quote/_files/set_new_shipping_address.php
     * @magentoDataFixture Magento/GraphQl/Quote/_files/set_new_billing_address.php
     * @magentoDataFixture Magento/GraphQl/Quote/_files/set_flatrate_shipping_method.php
     * @magentoDataFixture Magento/GraphQl/Quote/_files/set_checkmo_payment_method.php
     * @magentoDataFixture Magento/GraphQl/Quote/_files/guest/set_guest_email.php
     */
    public function testPlaceOrder(): void
    {
        $cartId = $this->getCartIdHash();
        $query = $this->getPlaceOrderQuery();
        $this->graphQlStateDiff->testState(
            $query,
            ['cartId' => $cartId],
            [],
            [],
            'placeOrder',
            '"data":{"placeOrder":',
            $this
        );

    }

    private function getBillingAddressQuery(): string
    {
        return <<<'QUERY'
            mutation($cartId: String!) {
              setBillingAddressOnCart(
                input: {
                  cart_id: $cartId
                  billing_address: {
                    address: {
                      firstname: "John"
                      lastname: "Doe"
                      street: ["123 Main Street"]
                      city: "New York"
                      region: "NY"
                      postcode: "10001"
                      country_code: "US"
                      telephone: "555-555-5555"
                    }
                  }
                }
              ) {
                cart {
                  id
                  billing_address {
                    firstname
                    lastname
                    street
                    city
                    region {
                      code
                      label
                    }
                    postcode
                    country {
                      code
                      label
                    }
                    telephone
                  }
                }
              }
            }
            QUERY;
    }

    private function getShippingAddressQuery(): string
    {
        return <<<'QUERY'
            mutation($cartId: String!) {
              setShippingAddressesOnCart(
                input: {
                  cart_id: $cartId
                  shipping_addresses: [
                    {
                      address: {
                        firstname: "John"
                        lastname: "Doe"
                        street: ["123 Main Street"]
                        city: "New York"
                        region: "NY"
                        postcode: "10001"
                        country_code: "US"
                        telephone: "555-555-5555"
                      }
                    }
                  ]
                }
              ) {
                cart {
                  id
                  shipping_addresses {
                    firstname
                    lastname
                    street
                    city
                    region {
                      code
                      label
                    }
                    postcode
                    country {
                      code
                      label
                    }
                    telephone
                    available_shipping_methods {
                      carrier_code
                      method_code
                      amount {
                        value
                      }
                    }
                  }
                }
              }
            }
            QUERY;
    }
    /**
     * Function returns array of all product's links
     *
     * @param string $sku
     * @return array
     */
    private function getProductsLinks(string $sku) : array
    {
        $result = [];
        $productRepository = $this->graphQlStateDiff->getTestObjectManager()
            ->get(ProductRepositoryInterface::class);
        $product = $productRepository->get($sku, false, null, true);

        foreach ($product->getDownloadableLinks() as $linkObject) {
            $result[$linkObject->getLinkId()] = [
                'title' => $linkObject->getTitle(),
                'link_type' => null, //deprecated field
                'price' => $linkObject->getPrice(),
            ];
        }
        return $result;
    }

    private function getAddDownloadableProductToCartQuery(): string
    {
        return <<<'MUTATION'
                    mutation($cartId: String!, $qty: Float!, $sku: String!, $linkId: Int!) {
                        addDownloadableProductsToCart(
                            input: {
                                cart_id: $cartId,
                                cart_items: [
                                    {
                                        data: {
                                            quantity: $qty,
                                            sku: $sku
                                        },
                                        downloadable_product_links: [
                                            {
                                                link_id: $linkId
                                            }
                                        ]
                                    }
                                ]
                            }
                        ) {
                            cart {
                                items {
                                    quantity
                                    ... on DownloadableCartItem {
                                        links {
                                            title
                                            link_type
                                            price
                                        }
                                        samples {
                                            id
                                            title
                                        }
                                    }
                                }
                            }
                        }
                    }
                MUTATION;
    }

    private function getAddConfigurableProductToCartQuery(): string
    {
        return <<<'QUERY'
            mutation($cartId: String!, $quantity: Float!, $parentSku: String!, $childSku: String!) {
              addConfigurableProductsToCart(
                input:{
                  cart_id: $cartId
                  cart_items:{
                    parent_sku: $parentSku
                    data:{
                      sku: $childSku
                      quantity:$quantity
                    }
                  }
                }
              ) {
                cart {
                  id
                  items {
                    id
                    quantity
                    product {
                      sku
                    }
                    ... on ConfigurableCartItem {
                      configurable_options {
                        id
                        option_label
                        value_id
                        value_label
                      }
                    }
                  }
                }
              }
            }
            QUERY;
    }

    private function getAddBundleProductToCartQuery(string $cartId, string $sku)
    {
        $productRepository = $this->graphQlStateDiff->getTestObjectManager()->get(ProductRepositoryInterface::class);
        $product = $productRepository->get($sku);
        /** @var $typeInstance \Magento\Bundle\Model\Product\Type */
        $typeInstance = $product->getTypeInstance();
        $typeInstance->setStoreFilter($product->getStoreId(), $product);
        /** @var $option \Magento\Bundle\Model\Option */
        $option = $typeInstance->getOptionsCollection($product)->getFirstItem();
        /** @var \Magento\Catalog\Model\Product $selection */
        $selection = $typeInstance->getSelectionsCollection([$option->getId()], $product)->getFirstItem();
        $optionId = $option->getId();
        $selectionId = $selection->getSelectionId();

        return <<<QUERY
            mutation {
              addBundleProductsToCart(input:{
                cart_id:"{$cartId}"
                cart_items:[
                  {
                    data:{
                      sku:"{$sku}"
                      quantity:1
                    }
                    bundle_options:[
                      {
                        id:{$optionId}
                        quantity:1
                        value:[
                          "{$selectionId}"
                        ]
                      }
                    ]
                  }
                ]
              }) {
                cart {
                  items {
                    id
                    uid
                    quantity
                    product {
                      sku
                    }
                    ... on BundleCartItem {
                      bundle_options {
                        id
                        uid
                        label
                        type
                        values {
                          id
                          uid
                          label
                          price
                          quantity
                        }
                      }
                    }
                  }
                }
              }
            }
            QUERY;

    }


    /**
     * @param string $cartId
     * @param float $qty
     * @param string $sku
     * @return void
     */
    private function getAddProductToCartQuery(): string
    {
        return <<<'QUERY'
            mutation($cartId: String!, $qty: Float!, $sku: String!) {
              addSimpleProductsToCart(
                input: {
                  cart_id: $cartId
                  cart_items: [
                    {
                      data: {
                        quantity: $qty
                        sku: $sku
                      }
                    }
                  ]
                }
              ) {
                cart {
                  items {
                    quantity
                    product {
                      sku
                    }
                  }
                }
              }
            }
        QUERY;
    }

    /**
     * @param string $maskedQuoteId
     * @param string $sku
     * @param float $quantity
     * @return string
     */
    private function getAddVirtualProductToCartQuery(): string
    {
        return <<<'QUERY'
            mutation($cartId: String!, $quantity: Float!, $sku: String!) {
              addVirtualProductsToCart(
                input: {
                  cart_id: $cartId
                  cart_items: [
                    {
                      data: {
                        quantity: $quantity
                        sku: $sku
                      }
                    }
                  ]
                }
              ) {
                cart {
                  id
                  items {
                    quantity
                    product {
                      sku
                    }
                  }
                }
              }
            }
            QUERY;
    }

    /**
     * Queries, variables, operation names, and expected responses for test
     *
     * @return string
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function getEmptyCart(): string
    {
        return <<<QUERY
                 mutation {
                    createEmptyCart
                 }
            QUERY;
    }

    private function getShippingMethodsQuery()
    {
        return <<<'QUERY'
            mutation($cartId: String!, $shippingMethod: String!) {
              setShippingMethodsOnCart(
                input: {
                  cart_id: $cartId
                  shipping_methods: [
                    {
                      carrier_code: $shippingMethod
                      method_code: $shippingMethod
                    }
                  ]
                }
              ) {
                cart {
                  id
                  shipping_addresses {
                    selected_shipping_method {
                      carrier_code
                      method_code
                      carrier_title
                      method_title
                    }
                  }
                }
              }
            }
            QUERY;

    }

    private function getPaymentMethodQuery()
    {
        return <<<'QUERY'
            mutation($cartId: String!) {
              setPaymentMethodOnCart(
                input: {
                  cart_id: $cartId
                  payment_method: {
                    code: "checkmo"
                  }
                }
              ) {
                cart {
                  id
                  selected_payment_method {
                    code
                    title
                  }
                }
              }
            }
            QUERY;
    }

    private function getPlaceOrderQuery(): string
    {
        return <<<'QUERY'
            mutation($cartId: String!) {
              placeOrder(
                input: {
                  cart_id: $cartId
                }
              ) {
                order {
                  order_number
                }
              }
            }
            QUERY;
    }
}
