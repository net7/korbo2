<?php

namespace Net7\OpenpalApiBundle\Tests\Controller;

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

    public function testItemsContent()
    {
        $client = static::createClient();

        $crawler = $client->request('POST',
            $this->itemsUrl,
            array("content" => self::$_TEST_CONTENT)
        );
        $postedLetterLocation = $client->getResponse()->headers->get('Location');

    }

    /**
     * Tests empty POST

    public function testPostBadValue()
    {
        $client = static::createClient();

        $crawler = $client->request('POST', $this->itemsUrl);

        $this->assertEquals(
            400,
            $client->getResponse()->getStatusCode()
        );

    }
*/
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
                $this->itemsUrl,
                array("content" => self::$_TEST_CONTENT)
            );
        }
    }


}
