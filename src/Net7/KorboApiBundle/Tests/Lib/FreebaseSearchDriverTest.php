<?php

namespace Net7\KorboApiBundle\Tests\Controller;

use Net7\KorboApiBundle\Entity\Basket;
use Net7\KorboApiBundle\Libs\FreebaseSearchDriver;
use Net7\KorboApiBundle\Libs\ItemResponseContainer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FreebaseSearchDriverTest extends WebTestCase
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

    public function testFreebaseInvalidResourceId()
    {
        $this->setExpectedException('Exception', 'Invalid Freebase Resource ID');

        $searchDriver = new FreebaseSearchDriver(
            $this->container->getParameter("freebase_search_base_url"),
            $this->container->getParameter("freebase_api_key"),
            $this->container->getParameter("freebase_topic_base_url"),
            $this->container->getParameter("freebase_base_mql_url"),
            $this->container->getParameter("freebase_image_search"),
            $this->container->getParameter("freebase_languages_to_retrieve"),
            ''
        );
        $searchDriver->setDefaultLanguage("en");
        $searchDriver->getEntityMetadata(self::$_URL_TO_IMPORT_FULL . '11111');
    }

    /**
     * Passing a not valid resource
     */
    public function testFreebaseInvalidResourceUrl()
    {
        $this->setExpectedException('Exception', 'Invalid Freebase Resource URL');

        $searchDriver = new FreebaseSearchDriver(
            $this->container->getParameter("freebase_search_base_url"),
            $this->container->getParameter("freebase_api_key"),
            $this->container->getParameter("freebase_topic_base_url"),
            $this->container->getParameter("freebase_base_mql_url"),
            $this->container->getParameter("freebase_image_search"),
            $this->container->getParameter("freebase_languages_to_retrieve"),
            ''
        );
        $searchDriver->setDefaultLanguage("en");
        $searchDriver->getEntityMetadata('/m/0sxbv4d111');
    }

    /**
     * Expected results from freebase
     */
    public function testFreebaseCountResults()
    {
        $searchDriver = new FreebaseSearchDriver(
            $this->container->getParameter("freebase_search_base_url"),
            $this->container->getParameter("freebase_api_key"),
            $this->container->getParameter("freebase_topic_base_url"),
            $this->container->getParameter("freebase_base_mql_url"),
            $this->container->getParameter("freebase_image_search"),
            $this->container->getParameter("freebase_languages_to_retrieve"),
            '',
            array("limit" => 10, 'offset' => 0)
        );

        /* @var ItemResponseContainer OBAMA */
        $searchDriver->setDefaultLanguage("en");
        $itemResponseContainer = $searchDriver->getEntityMetadata(self::$_URL_TO_IMPORT_FULL);

        $this->assertEquals(1, count($itemResponseContainer->getLabels()));
        $this->assertEquals(1, count($itemResponseContainer->getDescriptions()));
        $this->assertEquals($this->container->getParameter("freebase_image_search") . "/m/02mjmr", $itemResponseContainer->getDepiction());
        $types = $itemResponseContainer->getTypes();

        // at least one type
        $this->assertGreaterThan(1, count($types));

       // Check if types has absolute url http://www.freebase.com/*

      if (count($types)>0){
          foreach ($types as $type) 
           $this->assertEquals(1,preg_match('|http://www.freebase.com/|',$type));
      }
    }






}
