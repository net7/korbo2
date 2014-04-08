<?php

namespace Net7\KorboApiBundle\Tests\Controller;

use Net7\KorboApiBundle\Entity\Basket;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LetterModifyTest extends WebTestCase
{

    private static $_TEST_CONTENT = 'ùàòùèòùèòùèòù.èù.èù-ùèòèù,òèùòùèòùèò243èò523ù4èòùè78ò';

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


    /**
     * Modify stored item
     */
    public function testModifyItem(){

        $postedLetterLocation = $this->loadItems(1);

        $client = static::createClient();

        $crawler = $client->request('POST',
            $this->itemsUrl,
            array(
                "id"      => 1,
                "asbstract" => self::$_TEST_CONTENT,
                "label"   => "test-label-en"
            ),
            array(),
            array(
                'HTTP_CONTENT_LANGUAGE'  => 'en',
            )
        );

        $this->assertEquals(
            204,
            $client->getResponse()->getStatusCode()
        );


        // adding fields values to letter
        $this->postTranslationForField($client, 'type',  array("http://sample.com/type/1", "http://sample.com/type/2"));

        $this->assertEquals(
            204,
            $client->getResponse()->getStatusCode()
        );

        $this->postTranslationForField($client, 'depiction', "http://sample.com/depiction/1.jpg");

        $this->assertEquals(
            204,
            $client->getResponse()->getStatusCode()
        );

        $item = $this->em->getRepository('Net7KorboApiBundle:Item')->find(1);

        $this->assertEquals("http://sample.com/depiction/1.jpg", $item->getDepiction());
        $this->assertEquals(array("http://sample.com/type/1", "http://sample.com/type/2"), json_decode($item->getType(), true));
        $this->assertEquals($this->basketId, $item->getBasket()->getId());
    }

    // TODO: test inserimento e modifica con custom language Accept-Language


    /**
     * Modify
     */
    public function testModifyItemI18n(){
        $itemUrl = $this->loadItems(1);

        $client = static::createClient();

        // posting translation for english
        $this->postTranslationForField($client, 'label', 'test-label-en');

        // posting translation for german
        $this->postTranslationForField($client, 'label', 'test-label-de', 1, 'de');



        // $jsonItemAttributes = $this->getItemJsonAttribute($letterUrl);
        // $this->assertEquals('test-title-en', $jsonLetterAttributes['title']);
        // checking that the english translation is still there
        $item = $this->getItem(1);

        $this->assertEquals('test-label-en', $item->getLabelTranslated());

        // posting all the fields except for title and the title field is still there (german)
//        $crawler = $client->request('POST',
//            $this->itemsUrl,
//            array(
//                "id"      => 1,
//                "abstract"   => 'abstract-de',
//                "sendingDate"   => '2011-11-10',
//                "receivingDate"   => '2011-12-10',
//                "sender"   =>    array("http://sample.it/sender/1"),
//                "receiver"   =>    array("http://sample.it/receiver/1"),
//                "creator"   =>    array("http://sample.it/creator/1"),
//                "sendingPlace"   =>    array("http://sample.it/sendingPlace/1"),
//                "receivingPlace"   =>    array("http://sample.it/receivingPlace/1")
//            ),
//            array(),
//            array(
//                'HTTP_CONTENT_LANGUAGE'  => 'de',
//            )
//        );
//
//        $jsonLetterAttributes = $this->getLetterJsonAttribute($letterUrl, 'de');
//
//        $this->assertEquals('test-title-de', $jsonLetterAttributes['title']);
//        $this->assertEquals('abstract-de', $jsonLetterAttributes['abstract']);



    }

    /**
     * testing availble languages
     */
    public function testAvailableLanguages(){
        $this->loadItems(1);

        $client = static::createClient();

        // posting translation for english, german, italian and checking the available languages
        $this->postTranslationForField($client, 'label', 'test-label-en');
        $this->postTranslationForField($client, 'label', 'test-label-de', 1, 'de');
        $this->postTranslationForField($client, 'abstract', 'abstract-it', 1, 'it');

        $item = $this->getItem(1);

        $this->assertEquals(array('en', 'de', 'it'), $item->getAvailableLanguages());
    }

    /**
     * testing requested locale
    public function testRequestedLocale()
    {
    $client = static::createClient();

    $this->loadItems(1);
    $this->postTranslationForField($client, 'title', 'test-title-it', 1, 'it');

    $letterAttributes = $this->getLetterJsonAttribute($letterUrl, 'it');

    $this->assertEquals('it', $letterAttributes["language_code"]);

    }
     */

    /**
     * testing default locale
    public function testDefaultRequestedLocale()
    {
    $client = static::createClient();

    $letterUrl = $this->loadItems(1);
    $this->postTranslationForField($client, 'title', 'test-title-en');

    $letterAttributes = $this->getLetterJsonAttribute($letterUrl, 'it');

    $this->assertEquals('en', $letterAttributes["language_code"]);
    $this->assertEquals(array('en'), $letterAttributes["available_languages"]);
    }
     */

    /**
     * testing item field modified two times in the same language
     */
    public function testOverwritingPreviousTranslation()
    {
        $client = static::createClient();

        $this->loadItems(1);

        $this->postTranslationForField($client, 'label', 'test-title-en');
        $this->postTranslationForField($client, 'label', 'test-title-en-modified');

        $item = $this->getItem(1);

        $this->assertEquals('test-title-en-modified', $item->getLabelTranslated());
    }


    public function testAllTranslations()
    {
        $client = static::createClient();

        $this->loadItems(1);

        $this->postTranslationForField($client, 'label', 'test-title-en');
        $this->postTranslationForField($client, 'label', 'test-title-it', 1, 'it');
        $this->postTranslationForField($client, 'label', 'test-title-de', 1, 'de');
        $this->postTranslationForField($client, 'abstract', 'test-abstract-en');
        $this->postTranslationForField($client, 'abstract', 'test-abstract-it', 1, 'it');
        $this->postTranslationForField($client, 'abstract', 'test-abstract-de', 1, 'de');


        $item = $this->getItem(1);
        $this->assertEquals('test-title-en', $item->getLabelTranslated());

        $item = $this->getItem(1, 'de');
        $this->assertEquals('test-title-de', $item->getLabelTranslated());

        $item = $this->getItem(1, 'it');
        $this->assertEquals('test-title-it', $item->getLabelTranslated());

        $item = $this->getItem(1);
        $this->assertEquals('test-abstract-en', $item->getAbstractTranslated());

        $item = $this->getItem(1, 'de');
        $this->assertEquals('test-abstract-de', $item->getAbstractTranslated());

        $item = $this->getItem(1, 'it');
        $this->assertEquals('test-abstract-it', $item->getAbstractTranslated());
    }

    /**
     * Utility function
     *
     * @param $client
     * @param $field
     * @param $fieldValue
     * @param int $itemId
     * @param string $language
     * @return mixed
     */
    private function postTranslationForField($client, $field, $fieldValue, $itemId = 1, $language = "en") {
        $crawler = $client->request('POST',
            $this->itemsUrl,
            array(
                "id"      => $itemId,
                $field   => $fieldValue
            ),
            array(),
            array(
                'HTTP_CONTENT_LANGUAGE'  => $language,
            )
        );

        return $client->getResponse()->headers->get('Location');
    }

    /**
     * Retrieves the json representation of the item
     *
     * @param $itemUrl
     * @param string $language
     * @return mixed
     */
    private function getItemJsonAttribute($itemUrl, $language = 'en')
    {
        $client = static::createClient();

        $crawlerJson = $client->request('GET',
            $itemUrl,
            array(),
            array(),
            array(
                'HTTP_ACCEPT_LANGUAGE'  => $language,
                'HTTP_ACCEPT'  => 'application/json'
            )
        );

        return json_decode($client->getResponse()->getContent(), true);
    }


    /**
     * Posts to store as many items as passed as parameter
     *
     * @param $numItems
     */
    private function loadItems($numItems)
    {
        $client = static::createClient();

        for ($i = 0; $i < $numItems; $i++ ) {
            $crawler = $client->request('POST',
                $this->itemsUrl
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

    private function getItem($id, $lang = 'en')
    {
        $item = $this->em->getRepository('Net7KorboApiBundle:Item')->find(1);
        $item->setTranslatableLocale($lang);
        $this->em->refresh($item);

        return $item;
    }


}
