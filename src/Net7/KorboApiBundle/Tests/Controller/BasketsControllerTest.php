<?php

namespace Net7\OpenpalApiBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BasketsControllerTest extends WebTestCase
{

    private static $_TEST_CONTENT = 'ùàòùèòùèòùèòù.èù.èù-ùèòèù,òèùòùèòùèò243èò523ù4èòùè78òصخرة';

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    protected function setUp()
    {
        exec('php app/console doctrine:query:sql --env="test" "delete from basket;ALTER TABLE basket AUTO_INCREMENT = 1"');

        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager()
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->em->close();
    }

   public function testPostStatusCodeAndHeader()
    {
        $client = static::createClient();

        $crawler = $client->request('POST',
            '/v1/baskets',
            array("label" => self::$_TEST_CONTENT)
        );

        // check if Location header contains resource path
        $this->assertRegExp('/baskets/', $client->getResponse()->headers->get('Location'));


        // check redirection, in this case 201
        $this->assertTrue($client->getResponse()->isRedirect());
    }

    /**
     * Tests empty POST
     */
    public function testPostBadValue()
    {
        $client = static::createClient();

        $crawler = $client->request('POST', '/v1/baskets');

        $this->assertEquals(
            400,
            $client->getResponse()->getStatusCode()
        );

    }

    /**


    /**
     * Checks that all the api are returning Allow Access Control Origin: *
     */
    public function testAcceptControlAllowOriginHeader()
    {
        $client = static::createClient();

        $crawler = $client->request('POST',
            '/v1/baskets',
            array("label" => self::$_TEST_CONTENT)
        );

        $this->assertTrue(
            $client->getResponse()->headers->contains(
                'Access-Control-Allow-Origin',
                '*'
            )
        );

    }

    /**
     * Modify stored basket
     */
    public function testModifyBasket(){

        $this->loadBaskets(1);

        $client = static::createClient();

        $crawler = $client->request('POST',
            '/v1/baskets',
            array(
                "id"      => 1,
                "label"   => "modified label"
            )
        );

        $this->assertEquals(
            204,
            $client->getResponse()->getStatusCode()
        );

        $basket = $this->em
            ->getRepository('Net7KorboApiBundle:Basket')
            ->find(1);

        $baskets = $this->em
            ->getRepository('Net7KorboApiBundle:Basket')
            ->findAll();


        $this->assertEquals("modified label", $basket->getLabel());

        //only one basket present
        $this->assertEquals(1, count($baskets));


    }

    private function loadBaskets($numBaskets)
    {
        $client = static::createClient();

        for ($i = 0; $i < $numBaskets; $i++ ) {
            $crawler = $client->request('POST',
                '/v1/baskets',
                array("label" => self::$_TEST_CONTENT)
            );
        }
    }


}
