<?php

namespace Net7\KorboApiBundle\Tests\Controller;

use Net7\KorboApiBundle\Entity\Basket;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ItemsControllerTest extends WebTestCase
{

    private static $_TEST_CONTENT = 'ùàòùèòùèòùèòù.èù.èù-ùèòèù,òèùòùèòùèò243èò523ù4èòùè78òصخرة';

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    private $itemsUrl;

    private $basketId;

    protected function setUp()
    {
        exec('php app/console doctrine:query:sql --env="test" "delete from item;ALTER TABLE item AUTO_INCREMENT = 1"');
        exec('php app/console doctrine:query:sql --env="test" "delete from basket;ALTER TABLE basket AUTO_INCREMENT = 1"');

        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager()
        ;
        $this->basketId = $this->loadBasket('test-basket');

        $this->itemsUrl = "/v1/baskets/{$this->basketId}/items";
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->em->close();
    }

    /*
     *
     * Call the /items api requesting the extra parameter resource
     */
    public function testItemResourceRequest()
    {
        $client = static::createClient();

        $crawler = $client->request('GET',
            '/v1/items?resource=http://purl.org/net7/korbo/item/68429',
            array(),
            array(),
            array(
                'HTTP_ACCEPT'  => 'application/json'
            )
        );

        $item = json_decode($client->getResponse()->getContent(), true);

        //print_r($item);die;

    }

    public function testGetItemDetails()
    {

    }

    /**
     * Checks if the json response of /v1/items returns an object containing the item fields
     *
     */
    public function testItemFieldPresenceContent()
    {
        $client = static::createClient();

        $this->loadItems(1);

        $crawlerJson = $client->request('GET',
            $this->itemsUrl . '/1',
            array(),
            array(),
            array(
                'HTTP_ACCEPT'  => 'application/json'
            )
        );

        $item = json_decode($client->getResponse()->getContent(), true);

        //print_r($item);die;

        $this->assertTrue(array_key_exists('id', $item));
        $this->assertTrue(array_key_exists('basket_id', $item));
        $this->assertTrue(array_key_exists('label', $item));
        $this->assertTrue(array_key_exists('abstract', $item));
        $this->assertTrue(array_key_exists('type', $item));
        $this->assertTrue(array_key_exists('depiction', $item));
        $this->assertTrue(array_key_exists('uri', $item));
        $this->assertTrue(array_key_exists('language_code', $item));
        $this->assertTrue(array_key_exists('available_languages', $item));

    }

    public function testImportFromFreebase()
    {
        $client = static::createClient();

        $crawler = $client->request('POST',
            $this->itemsUrl,
            array('resourceUrl' => 'https://www.freebase.com/m/02mjmr')
        );

        // everything fine
        $this->assertTrue($client->getResponse()->isRedirect());

        $itemId = substr( $client->getResponse()->headers->get('Location'), strrpos($client->getResponse()->headers->get('Location'), '/') + 1);
        $item = $this->em->getRepository("Net7KorboApiBundle:Item")->find($itemId);

        $this->assertEquals("https://www.freebase.com/m/02mjmr", $item->getResource());

    }

    public function testImportFromFreebaseWrongUrl()
    {
        $client = static::createClient();

        $crawler = $client->request('POST',
             $this->itemsUrl,
            array('resourceUrl' => 'https://www.freebase.com/m/02mjmr1111')
        );

        $this->assertEquals($client->getResponse()->getStatusCode(), 400);
    }

    public function testPostNoBasketIdNoId()
    {
        $client = static::createClient();

        $crawler = $client->request('POST',
            "/v1/baskets/aaa/items"
        );

        $this->assertEquals($client->getResponse()->getStatusCode(), 400);

    }


    /**
     * @group new
     */
    public function testSavedItem()
    {
        $client = static::createClient();

        $crawler = $client->request('POST',
            $this->itemsUrl
        );

        $itemId = substr( $client->getResponse()->headers->get('Location'), strrpos($client->getResponse()->headers->get('Location'), '/') + 1);

        $item = $this->em->getRepository("Net7KorboApiBundle:Item")->find($itemId);

        $this->assertEquals($this->basketId, $item->getBasket()->getId());

    }

    public function testPostStatusCodeAndHeader()
    {
        $client = static::createClient();
        //$client->followRedirects();


        $crawler = $client->request('POST',
            $this->itemsUrl,
            array("basket-id" => 1)
        );

        // check if Location header contains resource path
        $this->assertRegExp('/items/', $client->getResponse()->headers->get('Location'));


        // check redirection, in this case 201
        $this->assertTrue($client->getResponse()->isRedirect());
    }

    /**
     * requesting a non supported accept.
     *
     */
    public function testAcceptNotAllowedGetRequest()
    {
        $client = static::createClient();

        $this->loadItems(1);

        $crawler = $client->request('GET',
            '/v1/baskets/1/items/1',
            array(),
            array(),
            array(
                'HTTP_ACCEPT'  => 'application/msword'
           )
        );

        $this->assertEquals(
            204,
            $client->getResponse()->getStatusCode()
        );
    }




    public function testGetStatusCodeAndHeaderHtml()
    {
        $this->loadItems(1);

        $client = static::createClient();
        $crawler = $client->request('GET',
            '/v1/baskets/1/items/1',
            array(),
            array(),
            array(
                'HTTP_ACCEPT'  => 'application/json'
            )
        );


        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $this->assertTrue(
            $client->getResponse()->headers->contains(
                'Content-Type',
                'application/json'
            )
        );
    }

    public function testGetStatusCodeAndHeaderJson()
    {
        $this->loadItems(1);

        $client = static::createClient();


        $crawler = $client->request('GET',
                                    '/v1/baskets/1/items/1',
                                    array(),
                                    array(),
                                    array(
                                        'HTTP_ACCEPT'  => 'application/json'
                                    ));


        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $this->assertTrue(
            $client->getResponse()->headers->contains(
                'Content-Type',
                'application/json'
            )
        );
    }




    /**
     * requesting a bad page
     */
    public function testBadGetRequest()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', $this->itemsUrl . '/9999999999999999999999999999');

        $this->assertEquals(
            404,
            $client->getResponse()->getStatusCode()
        );
    }


    /**
     * Checks that all the api are returning Allow Access Control Origin: *
     */
    public function testAcceptControlAllowOriginHeader()
    {
        $client = static::createClient();

        $crawler = $client->request('POST',
            $this->itemsUrl,
            array("content" => self::$_TEST_CONTENT)
        );

        $this->assertTrue(
            $client->getResponse()->headers->contains(
                'Access-Control-Allow-Origin',
                '*'
            )
        );
    }



    private function loadBasket($label)
    {
        $b = new Basket();
        $b->setLabel($label);

        $this->em->persist($b);
        $this->em->flush();

        return $b->getId();
    }

    private function loadItems($numItems)
    {
        $client = static::createClient();

        for ($i = 0; $i < $numItems; $i++ ) {
            $crawler = $client->request('POST',
                $this->itemsUrl
            );
        }
    }


}
