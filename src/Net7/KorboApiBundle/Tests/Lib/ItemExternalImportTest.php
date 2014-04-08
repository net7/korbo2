<?php

namespace Net7\KorboApiBundle\Tests\Controller;

use Net7\KorboApiBundle\Entity\Basket;
use Net7\KorboApiBundle\Entity\Item;
use Net7\KorboApiBundle\Libs\FreebaseSearchDriver;
use Net7\KorboApiBundle\Libs\ItemExternalImport;
use Net7\KorboApiBundle\Libs\ItemResponseContainer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ItemExternalImportTest extends WebTestCase
{

    private static $_URL_TO_IMPORT_FULL = 'https://www.freebase.com/m/02mjmr';

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    private $container;

    protected function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->container = static::$kernel->getContainer();
    }


    /**
     * {@inheritDoc}
     */
    /*protected function tearDown()
    {
        parent::tearDown();
        $this->em->close();
    }*/

    public function testResourceValid()
    {
        // set method accessible...so I can test private and protected methods
        $class = new \ReflectionClass('Net7\KorboApiBundle\Libs\ItemExternalImport');
        $methodIsResourceValid = $class->getMethod('isResourceValid');
        $methodIsResourceValid->setAccessible(true);

        $itemExternalImport = new ItemExternalImport(self::$_URL_TO_IMPORT_FULL, new Item(), true, $this->container);
        $itemExternalImportWrongURL = new ItemExternalImport('http://www.goolge.it', new Item(), true, $this->container);

        $this->assertTrue($methodIsResourceValid->invokeArgs($itemExternalImport, array()));
        $this->assertFalse($methodIsResourceValid->invokeArgs($itemExternalImportWrongURL, array()));

    }


    public function testGetSearchDriverForResource()
    {
        // set method accessible...so I can test private and protected methods
        $class = new \ReflectionClass('Net7\KorboApiBundle\Libs\ItemExternalImport');
        $methodGetSearchDriver = $class->getMethod('getSearchDriverForResource');
        $methodGetSearchDriver->setAccessible(true);

        $itemExternalImport = new ItemExternalImport(self::$_URL_TO_IMPORT_FULL, new Item(), true, $this->container);

        $obj = $methodGetSearchDriver->invokeArgs($itemExternalImport, array());

        $resultClass = new \ReflectionClass(get_class($obj));
        $this->assertEquals($resultClass->getShortName(), 'FreebaseSearchDriver');
    }

    /**
     * NO drivers for the requested resourfce
     */
    public function testNOSearchDriverForResource()
    {
        $invalidResource = 'http://this-is-an-invalid-resource.com';
        $this->setExpectedException('Exception', "No driver found for the resource {$invalidResource}");

        // set method accessible...so I can test private and protected methods
        $class = new \ReflectionClass('Net7\KorboApiBundle\Libs\ItemExternalImport');
        $methodGetSearchDriver = $class->getMethod('getSearchDriverForResource');
        $methodGetSearchDriver->setAccessible(true);

        $itemExternalImport = new ItemExternalImport($invalidResource, new Item(), true, $this->container);

        $methodGetSearchDriver->invokeArgs($itemExternalImport, array());
    }

    public function testImportResourceNoDriverFound()
    {
        $invalidResource = 'http://this-is-an-invalid-resource.com';
        $this->setExpectedException('Exception', "No driver found for the resource {$invalidResource}");

        $itemExternalImport = new ItemExternalImport($invalidResource, new Item(), true, $this->container);
        $itemExternalImport->importResource();

    }



}
