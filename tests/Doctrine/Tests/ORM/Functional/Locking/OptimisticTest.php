<?php

namespace Doctrine\Tests\ORM\Functional\Locking;

use Doctrine\Tests\Mocks\MetadataDriverMock;
use Doctrine\Tests\Mocks\DatabasePlatformMock;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\Mocks\DriverMock;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\Tests\TestUtil;

require_once __DIR__ . '/../../../TestInit.php';

class OptimisticTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\Locking\OptimisticJoinedParent'),
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\Locking\OptimisticJoinedChild'),
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\Locking\OptimisticStandard')
            ));
        } catch (\Exception $e) {
            // Swallow all exceptions. We do not test the schema tool here.
        }
        $this->_conn = $this->_em->getConnection();
    }

    public function testJoinedChildInsertSetsInitialVersionValue()
    {
        $test = new OptimisticJoinedChild();
        $test->name = 'child';
        $test->whatever = 'whatever';
        $this->_em->persist($test);
        $this->_em->flush();

        $this->assertEquals(1, $test->version);
    }

    /**
     * @expectedException Doctrine\ORM\OptimisticLockException
     */
    public function testJoinedChildFailureThrowsException()
    {
        $q = $this->_em->createQuery('SELECT t FROM Doctrine\Tests\ORM\Functional\Locking\OptimisticJoinedChild t WHERE t.name = :name');
        $q->setParameter('name', 'child');
        $test = $q->getSingleResult();

        // Manually update/increment the version so we can try and save the same
        // $test and make sure the exception is thrown saying the record was 
        // changed or updated since you read it
        $this->_conn->execute('UPDATE optimistic_joined_parent SET version = ? WHERE id = ?', array(2, $test->id));

        // Now lets change a property and try and save it again
        $test->whatever = 'ok';
        $this->_em->flush();
    }

    public function testJoinedParentInsertSetsInitialVersionValue()
    {
        $test = new OptimisticJoinedParent();
        $test->name = 'parent';
        $this->_em->persist($test);
        $this->_em->flush();

        $this->assertEquals(1, $test->version);
    }

    /**
     * @expectedException Doctrine\ORM\OptimisticLockException
     */
    public function testJoinedParentFailureThrowsException()
    {
        $q = $this->_em->createQuery('SELECT t FROM Doctrine\Tests\ORM\Functional\Locking\OptimisticJoinedParent t WHERE t.name = :name');
        $q->setParameter('name', 'parent');
        $test = $q->getSingleResult();

        // Manually update/increment the version so we can try and save the same
        // $test and make sure the exception is thrown saying the record was 
        // changed or updated since you read it
        $this->_conn->execute('UPDATE optimistic_joined_parent SET version = ? WHERE id = ?', array(2, $test->id));

        // Now lets change a property and try and save it again
        $test->name = 'WHATT???';
        $this->_em->flush();
    }

    public function testStandardInsertSetsInitialVersionValue()
    {
        $test = new OptimisticStandard();
        $test->name = 'test';
        $this->_em->persist($test);
        $this->_em->flush();

        $this->assertEquals(1, $test->version);
    }

    /**
     * @expectedException Doctrine\ORM\OptimisticLockException
     */
    public function testStandardFailureThrowsException()
    {
        $q = $this->_em->createQuery('SELECT t FROM Doctrine\Tests\ORM\Functional\Locking\OptimisticStandard t WHERE t.name = :name');
        $q->setParameter('name', 'test');
        $test = $q->getSingleResult();

        // Manually update/increment the version so we can try and save the same
        // $test and make sure the exception is thrown saying the record was 
        // changed or updated since you read it
        $this->_conn->execute('UPDATE optimistic_standard SET version = ? WHERE id = ?', array(2, $test->id));

        // Now lets change a property and try and save it again
        $test->name = 'WHATT???';
        $this->_em->flush();
    }
}

/**
 * @Entity
 * @Table(name="optimistic_joined_parent")
 * @DiscriminatorValue("parent")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @SubClasses({"Doctrine\Tests\ORM\Functional\Locking\OptimisticJoinedChild"})
 */
class OptimisticJoinedParent
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Column(type="string", length=255)
     */
    public $name;

    /**
     * @Version @Column(type="integer")
     */
    public $version;
}

/**
 * @Entity
 * @Table(name="optimistic_joined_child")
 * @DiscriminatorValue("child")
 */
class OptimisticJoinedChild extends OptimisticJoinedParent
{
    /**
     * @Column(type="string", length=255)
     */
    public $whatever;
}

/**
 * @Entity
 * @Table(name="optimistic_standard")
 */
class OptimisticStandard
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Column(type="string", length=255)
     */
    public $name;

    /**
     * @Version @Column(type="integer")
     */
    public $version;
}