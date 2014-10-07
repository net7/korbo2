<?php

namespace Net7\KorboApiBundle\Tests\Controller;

use Net7\KorboApiBundle\Entity\Basket;
use Net7\KorboApiBundle\Libs\DandelionDbpediaSearchDriver;
use Net7\KorboApiBundle\Libs\EuropeanaSearchDriver;
use Net7\KorboApiBundle\Libs\FreebaseSearchDriver;
use Net7\KorboApiBundle\Libs\ItemResponseContainer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DandelionDbpediaSearchDriverTest extends WebTestCase
{


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



    /**
     * Expected certain number of results from europeana
     */
    public function testEuropeanaCountResults()
    {
        $extraParameters = array(
              "content-type" => "application/json",
              "limit" => 2
        );

        $searchDriver = new DandelionDbpediaSearchDriver(
                $this->container->getParameter('dandelion_dbpedia_search_base_url'),
                $this->container->getParameter('dandelion_dbpedia_app_key'),
                $this->container->getParameter('dandelion_dbpedia_app_id'),
                '',
                $extraParameters
        );

        $searchDriver->setDefaultLanguage('en');

        $r = $searchDriver->search("bush");
        //print_r($r);die;

        $this->assertEquals(2, count($searchDriver->search("bush")));
    }






}
