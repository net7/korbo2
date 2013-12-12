<?php

namespace Net7\OpenpalApiBundle\Tests\Controller;

use Net7\KorboApiBundle\Entity\Basket;
use Net7\KorboApiBundle\Libs\FreebaseSearchDriver;
use Net7\KorboApiBundle\Libs\ItemResponseContainer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ItemsControllerTest extends WebTestCase
{

    private static $_URL_TO_IMPORT_FULL = 'https://www.freebase.com/m/02mjmr';

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    private $itemsUrl;

    private $basketId;

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

    public function testFreebaseInvalidResourceId()
    {
        $this->setExpectedException('Exception', 'Invalid Freebase Resource ID');

        $searchDriver = new FreebaseSearchDriver(
            $this->container->getParameter("freebase_search_base_url"),
            $this->container->getParameter("freebase_api_key"),
            $this->container->getParameter("freebase_topic_base_url"),
            $this->container->getParameter("freebase_base_mql_url"),
            $this->container->getParameter("freebase_image_search"),
            $this->container->getParameter("freebase_languages_to_retrieve")
        );

        $searchDriver->getEntityMetadata('https://www.freebase.com/m/0sxbv4d111');
    }

    public function testFreebaseInvalidResourceUrl()
    {
        $this->setExpectedException('Exception', 'Invalid Freebase Resource URL');

        $searchDriver = new FreebaseSearchDriver(
            $this->container->getParameter("freebase_search_base_url"),
            $this->container->getParameter("freebase_api_key"),
            $this->container->getParameter("freebase_topic_base_url"),
            $this->container->getParameter("freebase_base_mql_url"),
            $this->container->getParameter("freebase_image_search"),
            $this->container->getParameter("freebase_languages_to_retrieve")
        );

        $searchDriver->getEntityMetadata('/m/0sxbv4d111');
    }


    public function testFreebaseCountResults()
    {
        $searchDriver = new FreebaseSearchDriver(
            $this->container->getParameter("freebase_search_base_url"),
            $this->container->getParameter("freebase_api_key"),
            $this->container->getParameter("freebase_topic_base_url"),
            $this->container->getParameter("freebase_base_mql_url"),
            $this->container->getParameter("freebase_image_search"),
            $this->container->getParameter("freebase_languages_to_retrieve")
        );

        /* @var ItemResponseContainer OBAMA */
        $itemResponseContainer = $searchDriver->getEntityMetadata('https://www.freebase.com/m/02mjmr');

        $this->assertEquals(4, count($itemResponseContainer->getLabels()));
        $this->assertEquals(4, count($itemResponseContainer->getDescriptions()));
        $this->assertEquals($this->container->getParameter("freebase_image_search") . "/m/02mjmr", $itemResponseContainer->getDepiction());



    }
    // TODO Testare la presenza di tutte le sezioni nella risposta di freebase, morte quelle non va più il driver




}