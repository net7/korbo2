<?php

namespace Net7\KorboApiBundle\Tests\Controller;

use Net7\KorboApiBundle\Entity\Basket;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ItemsPaginationTest extends WebTestCase
{

    /**
     * setup
     */
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
    }

    /**
     * No items present into db
     */
    public function testWithNoResults()
    {
        $client = static::createClient();

        $items = $this->getItems(2);
        $links = $this->processRels($items['metadata']['links']);

        $this->assertEquals(0, count($items['data']));
        $this->assertEquals(0, count($links));
    }

    /**
     * requesting no items
     */
    public function testZeroCase()
    {
        $this->loadItems(10);

        $client = static::createClient();

        $items = $this->getItems(0, 0);

        $this->assertEquals(0, count($items['data']));
        $this->assertEquals(0, $items['metadata']['pageCount']);
        $this->assertEquals(10, $items['metadata']['totalCount']);
        $this->assertEquals(0, $items['metadata']['offset']);
        $this->assertEquals(0, $items['metadata']['limit']);
        $this->assertEquals(0, count($items['metadata']['links']));
    }


    public function testLimitCase()
    {
        $this->loadItems(7);

        $client = static::createClient();

        $items = $this->getItems(7);

        $this->assertEquals(7, count($items['data']));
        $this->assertEquals(1, $items['metadata']['pageCount']);
        $this->assertEquals(7, $items['metadata']['totalCount']);
        $this->assertEquals(0, $items['metadata']['offset']);
        $this->assertEquals(7, $items['metadata']['limit']);
        $this->assertEquals(2, count($items['metadata']['links']));
    }

    public function testRequestingMoreItemsThanPresent()
    {
        $this->loadItems(7);

        $client = static::createClient();

        $items = $this->getItems(10);

        $this->assertEquals(7, count($items['data']));
        $this->assertEquals(1, $items['metadata']['pageCount']);
        $this->assertEquals(7, $items['metadata']['totalCount']);
        $this->assertEquals(0, $items['metadata']['offset']);
        $this->assertEquals(10, $items['metadata']['limit']);
        $this->assertEquals(2, count($items['metadata']['links']));
    }




    public function testIsStructureCorrect()
    {
        $this->loadItems(10);

        $client = static::createClient();

        $items = $this->getItems(5);

        $this->assertTrue(array_key_exists("data", $items));
        $this->assertTrue(array_key_exists("metadata", $items));
        $this->assertTrue(array_key_exists("links", $items['metadata']));
        $this->assertTrue(array_key_exists("limit", $items['metadata']));
        $this->assertTrue(array_key_exists("offset", $items['metadata']));
        $this->assertTrue(array_key_exists("pageCount", $items['metadata']));
        $this->assertTrue(array_key_exists("totalCount", $items['metadata']));

        // the result has to be an array (both links and data)
        $this->assertTrue(is_array($items["data"]));
        $this->assertTrue(is_array($items['metadata']["links"]));

        // at least two elements in the links array (first/last)
        $this->assertGreaterThanOrEqual(2, $items['metadata']["links"]);

    }

    /**
     *
     */
    public function testNoOffsetItem()
    {

        $this->loadItems(11);

        $items = $this->getItems(5);

        // there isn't a previous link...the page requested is the first one
        $this->assertEquals(3, count($items['metadata']["links"]));
        $this->assertEquals(5, count($items["data"]));
    }

    public function testPagingMetadata()
    {
        $this->loadItems(11);

        $items = $this->getItems(5);

        $this->assertEquals(5, $items['metadata']['limit']);
        $this->assertEquals(0, $items['metadata']['offset']);
        $this->assertEquals(11, $items['metadata']['totalCount']);
        $this->assertEquals(3, $items['metadata']['pageCount']);


        $items = $this->getItems(5, 5);

        $this->assertEquals(5, $items['metadata']['limit']);
        $this->assertEquals(5, $items['metadata']['offset']);
        $this->assertEquals(11, $items['metadata']['totalCount']);
        $this->assertEquals(3, $items['metadata']['pageCount']);
    }

    public function testLinksEven()
    {

        $this->loadItems(11);

        $items = $this->getItems(4);

        $links = $this->processRels($items['metadata']['links']);

        // is link valid
        foreach ($links as $link => $linkUrl) {
            $this->assertRegExp('|\?offset=[0-9]+&limit=[0-9]+|', $linkUrl);
        }

        // checking limit
        $this->assertRegExp("|limit=4|", $links['first']);
        $this->assertRegExp("|limit=4|", $links['next']);
        $this->assertRegExp("|limit=4|", $links['last']);

        // checking "first" offset link
        $this->assertRegExp("|offset=0|", $links['first']);
        $this->assertRegExp("|offset=4|", $links['next']);
        $this->assertRegExp("|offset=8|", $links['last']);


        // requesting second page
        $items = $this->getItems(4, 4);
        $links = $this->processRels($items['metadata']['links']);

        // checking limit
        $this->assertRegExp("|limit=4|", $links['previous']);
        $this->assertRegExp("|limit=4|", $links['first']);
        $this->assertRegExp("|limit=4|", $links['next']);
        $this->assertRegExp("|limit=4|", $links['last']);

        // checking "first" offset link
        $this->assertRegExp("|offset=0|", $links['previous']);
        $this->assertRegExp("|offset=0|", $links['first']);
        $this->assertRegExp("|offset=8|", $links['next']);
        $this->assertRegExp("|offset=8|", $links['last']);

        $items = $this->getItems(4, 8);
        $links = $this->processRels($items['metadata']['links']);

        // checking limit
        $this->assertRegExp("|limit=4|", $links['previous']);
        $this->assertRegExp("|limit=4|", $links['first']);
        $this->assertRegExp("|limit=4|", $links['last']);

        // checking "first" offset link
        $this->assertRegExp("|offset=4|", $links['previous']);
        $this->assertRegExp("|offset=0|", $links['first']);
        $this->assertRegExp("|offset=8|", $links['last']);
    }

    public function testLinksOdd()
    {

        $this->loadItems(11);

        $items = $this->getItems(5);

        $links = $this->processRels($items['metadata']['links']);

        // is link valid
        foreach ($links as $link => $linkUrl) {
            $this->assertRegExp('|\?offset=[0-9]+&limit=[0-9]+|', $linkUrl);
        }

        // checking limit
        $this->assertRegExp("|limit=5|", $links['first']);
        $this->assertRegExp("|limit=5|", $links['next']);
        $this->assertRegExp("|limit=5|", $links['last']);

        // checking "first" offset link
        $this->assertRegExp("|offset=0|", $links['first']);
        $this->assertRegExp("|offset=5|", $links['next']);
        $this->assertRegExp("|offset=10|", $links['last']);


        // requesting second page
        $items = $this->getItems(5, 5);
        $links = $this->processRels($items['metadata']['links']);

        // checking limit
        $this->assertRegExp("|limit=5|", $links['previous']);
        $this->assertRegExp("|limit=5|", $links['first']);
        $this->assertRegExp("|limit=5|", $links['next']);
        $this->assertRegExp("|limit=5|", $links['last']);

        // checking "first" offset link
        $this->assertRegExp("|offset=0|", $links['previous']);
        $this->assertRegExp("|offset=0|", $links['first']);
        $this->assertRegExp("|offset=10|", $links['next']);
        $this->assertRegExp("|offset=10|", $links['last']);

        $items = $this->getItems(5, 10);
        $links = $this->processRels($items['metadata']['links']);

        // checking limit
        $this->assertRegExp("|limit=5|", $links['previous']);
        $this->assertRegExp("|limit=5|", $links['first']);
        $this->assertRegExp("|limit=5|", $links['last']);

        // checking "first" offset link
        $this->assertRegExp("|offset=5|", $links['previous']);
        $this->assertRegExp("|offset=0|", $links['first']);
        $this->assertRegExp("|offset=10|", $links['last']);

    }

    private function processRels($rels){

        $returnArray = array();
        foreach ($rels as $rel){
            $returnArray[$rel['rel']] = $rel['href'];
        }

        return $returnArray;
    }

    private function getItems($limit, $offset = 0){
        $client = static::createClient();

        $params = array(
            'limit' => $limit
        );

        if ($offset > 0) $params['offset'] = $offset;

        $crawlerJson = $client->request('GET',
            '/v1/baskets/1/items',
            $params,
            array(),
            array(
                'HTTP_ACCEPT'  => 'application/json'
            )
        );

        return json_decode($client->getResponse()->getContent(), true);
    }

    /**
     * @param $numItems
     *
     * @return array|string
     */
    private function loadItems($numItems)
    {
        $client = static::createClient();

        for ($i = 0; $i < $numItems; $i++ ) {
            $crawler = $client->request('POST',
                '/v1/baskets/1/items'
            );
        }

        return $client->getResponse()->headers->get('Location');
    }

    private function loadBasket($label)
    {
        $b = new Basket();
        $b->setLabel($label);

        $this->em->persist($b);
        $this->em->flush();

        return $b->getId();
    }



}
