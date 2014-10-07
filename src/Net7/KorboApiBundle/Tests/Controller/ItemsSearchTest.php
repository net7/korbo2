<?php

namespace Net7\KorboApiBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Net7\KorboApiBundle\Entity\Basket;
use Symfony\Component\Validator\Constraints\DateTime;

class ItemsSearchTest extends WebTestCase
{

    private static $_TEST_CONTENT = '';

    private static $contents = array('This is a sample content I need to test the search (èaiiiùù)',
                                     'Simone mi a pupperà la favona',
                                     'Romeo a  Simone scurreggia come un drago');



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

        $this->itemsUrl = "/v1/baskets/{$this->basketId}/items";
    }


    public function testItemsResultsFail()
    {
        $this->loadItemsI18n(7, array(
                'en' => 'en en',
                'it' => 'it it',
            )
        );

        $items = $this->getItems('it', 20, 0, 'it');
        $this->assertEquals(7, count($items['data']));

        $items = $this->getItems('en', 20, 0, false);
        $this->assertEquals(7, count($items['data']));

    }


    public function testSearchWithNoResults()
    {
        $postedItemLocation = $this->loadItems(10);

        $client = static::createClient();

        $items = $this->getItems('no-results', 2, 0);
        $links = $this->processRels($items['metadata']['links']);

        $this->assertEquals(0, count($items['data']));
        $this->assertEquals(0, count($links));
    }

    /**
     * @group fail
     */
    public function testZeroCase()
    {
        $postedItemLocation = $this->loadItems(10);

        $client = static::createClient();

        $items = $this->getItems('e', 2, 0);

        $this->assertEquals(2, count($items['data']));
        $this->assertEquals(5, $items['metadata']['pageCount']);
        $this->assertEquals(10, $items['metadata']['totalCount']);
        $this->assertEquals(0, $items['metadata']['offset']);
        $this->assertEquals(2, $items['metadata']['limit']);
        $this->assertEquals(3, count($items['metadata']['links']));
    }


    public function testLimitCase()
    {
        $postedItemLocation = $this->loadItems(7);

        $client = static::createClient();

        $items = $this->getItems("this", 7);

        $this->assertEquals(7, count($items['data']));
        $this->assertEquals(1, $items['metadata']['pageCount']);
        $this->assertEquals(7, $items['metadata']['totalCount']);
        $this->assertEquals(0, $items['metadata']['offset']);
        $this->assertEquals(7, $items['metadata']['limit']);
        $this->assertEquals(2, count($items['metadata']['links']));
    }

    public function testRequestingMoreItemsThanPresent()
    {
        $postedItemLocation = $this->loadItems(7);

        $client = static::createClient();

        $items = $this->getItems("this", 10);

        $this->assertEquals(7, count($items['data']));
        $this->assertEquals(1, $items['metadata']['pageCount']);
        $this->assertEquals(7, $items['metadata']['totalCount']);
        $this->assertEquals(0, $items['metadata']['offset']);
        $this->assertEquals(10, $items['metadata']['limit']);
        $this->assertEquals(2, count($items['metadata']['links']));
    }

    


    public function testIsStructureCorrect()
    {
        $postedItemLocation = $this->loadItems(10);

        $client = static::createClient();

        $items = $this->getItems("this", 5);

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

    public function testNoOffsetItem()
    {

        $this->loadItems(11);

        $items = $this->getItems("this", 5);

        // there isn't a previous link...the page requested is the first one
        $this->assertEquals(3, count($items['metadata']["links"]));
        $this->assertEquals(5, count($items["data"]));
    }

    public function testPagingMetadata()
    {
        $this->loadItems(11);

        $items = $this->getItems("this", 5);

        $this->assertEquals(5, $items['metadata']['limit']);
        $this->assertEquals(0, $items['metadata']['offset']);
        $this->assertEquals(11, $items['metadata']['totalCount']);
        $this->assertEquals(3, $items['metadata']['pageCount']);


        $items = $this->getItems("this", 5, 5);

        $this->assertEquals(5, $items['metadata']['limit']);
        $this->assertEquals(5, $items['metadata']['offset']);
        $this->assertEquals(11, $items['metadata']['totalCount']);
        $this->assertEquals(3, $items['metadata']['pageCount']);
    }

    /**
     * @group fail
     */
    public function testLinksEven()
    {

        $postedItemLocation = $this->loadItems(11);

        $items = $this->getItems("this", 4);

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
        $items = $this->getItems("this",4, 4);

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

        $items = $this->getItems("this",4, 8);
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

        $postedItemLocation = $this->loadItems(11);

        $items = $this->getItems("this", 5);

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
        $items = $this->getItems("this",5, 5);
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

        $items = $this->getItems("this", 5, 10);
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

    public function testRequestAllLinks()
    {
        $client = static::createClient();

        $postedItemLocation = $this->loadItems(11);

        $items = $this->getItems("this", 5);

        $links = $this->processRels($items['metadata']['links']);

        // getting next page
        $crawlerJson = $client->request('GET',
            $links['next'],
            array(),
            array(),
            array(
                'HTTP_ACCEPT'  => 'application/json'
            )
        );

        $items = json_decode($client->getResponse()->getContent(), true);
        $links = $this->processRels($items['metadata']['links']);

        $this->assertEquals(5, count($items['data']));
        $this->assertEquals(4, count($links));

        // getting prev page
        $crawlerJson = $client->request('GET',
            $links['previous'],
            array(),
            array(),
            array(
                'HTTP_ACCEPT'  => 'application/json'
            )
        );

        $items = json_decode($client->getResponse()->getContent(), true);
        $links = $this->processRels($items['metadata']['links']);

        $this->assertEquals(5, count($items['data']));
        $this->assertEquals(3, count($links));

        // getting first page
        $crawlerJson = $client->request('GET',
            $links['first'],
            array(),
            array(),
            array(
                'HTTP_ACCEPT'  => 'application/json'
            )
        );

        $items = json_decode($client->getResponse()->getContent(), true);
        $links = $this->processRels($items['metadata']['links']);

        $this->assertEquals(5, count($items['data']));
        $this->assertEquals(3, count($links));

        // getting the last page
        $crawlerJson = $client->request('GET',
            $links['last'],
            array(),
            array(),
            array(
                'HTTP_ACCEPT'  => 'application/json'
            )
        );

        $items = json_decode($client->getResponse()->getContent(), true);
        $links = $this->processRels($items['metadata']['links']);

        $this->assertEquals(1, count($items['data']));
        $this->assertEquals(3, count($links));

    }

    /**
     * @group updated-after
     */
    public function testSearchUpdatedAfter() {
        $client = static::createClient();
        $dateTime = new \DateTime();

        $postedItemLocation = $this->loadItems(11);

        $items = $this->getItems("this", 5, 0, 'en', '2014-01-01');

        $this->assertEquals(5, count($items['data']));

        $items = $this->getItems("this", 5, 0, 'en', $dateTime->format('Y') + 1 . '-01-01');
        $this->assertEquals(0, count($items['data']));

        $items = $this->getItems("this", 5);
        $this->assertEquals(5, count($items['data']));
    }


    private function processRels($rels){

        $returnArray = array();
        foreach ($rels as $rel){
            $returnArray[$rel['rel']] = $rel['href'];
        }

        return $returnArray;
    }

    private function getItems($query, $limit, $offset = 0, $lang = 'en', $updatedAfter = false){
        $client = static::createClient();

        $params = array(
            'q'     => $query,
            'limit' => $limit
        );

        $headers = array(
            'HTTP_ACCEPT'  => 'application/json',
        );

        if ($lang !== false) {
            $params['lang'] = $lang;
        }

        if ($updatedAfter !== false) {
            $params['updatedAfter'] = $updatedAfter;
        }

        if ($offset > 0) $params['offset'] = $offset;


        $crawlerJson = $client->request('GET',
            '/v1/search/items',
            $params,
            array(),
            $headers
        );

        return json_decode($client->getResponse()->getContent(), true);
    }


    /**
     * Posts to store as many items as passed as parameter
     *
     * @param $numItems
     * @param string $lang
     *
     * @return array|string
     */
    private function loadItems($numItems, $lang = 'en', $contentNumber = 0)
    {
        $client = static::createClient();

        $content = self::$contents[$contentNumber];


        for ($i = 0; $i < $numItems; $i++ ) {
            $crawler = $client->request('POST',
                $this->itemsUrl,
                array(
                    "label"      => $content,
                    'abstract'   => $content
                ),
                array(),
                array(
                    'HTTP_CONTENT_LANGUAGE'  => $lang,
                )
            );
        }

        return $client->getResponse()->headers->get('Location');
    }

    /**
     * Posts to store as many items as passed as parameter
     *
     * @param $numItems
     * @param string $lang
     *
     * @return array|string
     */
    private function loadItemsI18n($numItems, $langs = array('en', 'it'))
    {
        $client = static::createClient();


        for ($i = 0; $i < $numItems; $i++ ) {
            $crawler = $client->request('POST',
                $this->itemsUrl,
                array(
                    "content" => self::$_TEST_CONTENT
                )
            );

            $postedItemLocation = $client->getResponse()->headers->get('Location');
            foreach ($langs as $lang => $contentText) {
                $crawler = $client->request('POST',
                    $this->itemsUrl,
                    array(
                        "label"    => $contentText,
                        "abstract" => $contentText,
                        "id"       => substr($postedItemLocation , strrpos($postedItemLocation, '/') + 1)
                    ),
                    array(),
                    array(
                        'HTTP_CONTENT_LANGUAGE'  => $lang,
                    )
                );
            }
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
