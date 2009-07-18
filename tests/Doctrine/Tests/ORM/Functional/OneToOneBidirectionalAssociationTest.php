<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\ECommerce\ECommerceCart;
use Doctrine\Tests\Models\ECommerce\ECommerceCustomer;
use Doctrine\ORM\Mapping\AssociationMapping;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Tests a bidirectional one-to-one association mapping (without inheritance).
 */
class OneToOneBidirectionalAssociationTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $customer;
    private $cart;

    protected function setUp()
    {
        $this->useModelSet('ecommerce');
        parent::setUp();
        $this->customer = new ECommerceCustomer();
        $this->customer->setName('John Doe');
        $this->cart = new ECommerceCart();
        $this->cart->setPayment('Credit card');
    }

    public function testSavesAOneToOneAssociationWithCascadeSaveSet() {
        $this->customer->setCart($this->cart);
        $this->_em->save($this->customer);
        $this->_em->flush();
        
        $this->assertCartForeignKeyIs($this->customer->getId());
    }

    public function testDoesNotSaveAnInverseSideSet() {
        $this->customer->brokenSetCart($this->cart);
        $this->_em->save($this->customer);
        $this->_em->flush();
        
        $this->assertCartForeignKeyIs(null);
    }

    public function testRemovesOneToOneAssociation()
    {
        $this->customer->setCart($this->cart);
        $this->_em->save($this->customer);
        $this->customer->removeCart();

        $this->_em->flush();

        $this->assertCartForeignKeyIs(null);
    }

    public function testEagerLoad()
    {
        $this->_createFixture();

        $query = $this->_em->createQuery('select c, ca from Doctrine\Tests\Models\ECommerce\ECommerceCustomer c join c.cart ca');
        $result = $query->getResultList();
        $customer = $result[0];
        
        $this->assertTrue($customer->getCart() instanceof ECommerceCart);
        $this->assertEquals('paypal', $customer->getCart()->getPayment());
    }
    
    public function testLazyLoad() {
        $this->markTestSkipped();
        $this->_createFixture();
        $this->_em->getConfiguration()->setAllowPartialObjects(false);
        $metadata = $this->_em->getClassMetadata('Doctrine\Tests\Models\ECommerce\ECommerceCustomer');
        $metadata->getAssociationMapping('cart')->fetchMode = AssociationMapping::FETCH_LAZY;

        $query = $this->_em->createQuery('select c from Doctrine\Tests\Models\ECommerce\ECommerceCustomer c');
        $result = $query->getResultList();
        $customer = $result[0];
        
        $this->assertTrue($customer->getCart() instanceof ECommerceCart);
        $this->assertEquals('paypal', $customer->getCart()->getPayment());
    }

    protected function _createFixture()
    {
        $customer = new ECommerceCustomer;
        $customer->setName('Giorgio');
        $cart = new ECommerceCart;
        $cart->setPayment('paypal');
        $customer->setCart($cart);
        
        $this->_em->save($customer);
        
        $this->_em->flush();
        $this->_em->clear();
    }

    public function assertCartForeignKeyIs($value) {
        $foreignKey = $this->_em->getConnection()->execute('SELECT customer_id FROM ecommerce_carts WHERE id=?', array($this->cart->getId()))->fetchColumn();
        $this->assertEquals($value, $foreignKey);
    }
}
