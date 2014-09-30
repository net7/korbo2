<?php

namespace Net7\KorboApiBundle\Tests\Controller;

use Net7\KorboApiBundle\Entity\Basket;
use Net7\KorboApiBundle\Libs\EuropeanaSearchDriver;
use Net7\KorboApiBundle\Libs\FreebaseSearchDriver;
use Net7\KorboApiBundle\Libs\ItemResponseContainer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EuropeanaSearchDriverTest extends WebTestCase
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

        $searchDriver = new EuropeanaSearchDriver( $this->container->getParameter("europeana_search_base_url"),
                                                   $this->container->getParameter("europeana_api_key"),
                                                   '',
                                                   $extraParameters);


        $this->assertEquals(2, count($searchDriver->search("paris")));
    }






}
