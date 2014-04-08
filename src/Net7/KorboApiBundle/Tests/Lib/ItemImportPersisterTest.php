<?php

namespace Net7\KorboApiBundle\Tests\Controller;

use Net7\KorboApiBundle\Entity\Basket;
use Net7\KorboApiBundle\Entity\Item;
use Net7\KorboApiBundle\Libs\ItemImportPersister;
use Net7\KorboApiBundle\Libs\ItemResponseContainer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ItemImportPersisterTest extends WebTestCase
{

    public function testFillItemFields()
    {
        $types  = array(
            'http://type1.com',
            'http://type2.com',
        );
        $item = new Item();
        $responseContainer = new ItemResponseContainer();
        $responseContainer->setDepiction('http://depiction.com');

        $responseContainer->setTypes($types);
        $responseContainer->setDescription("desc1", 'it');
        $responseContainer->setDescription("desc2", 'en');
        $responseContainer->setDescription("desc3", 'de');

        $responseContainer->setLabel("1", 'it');
        $responseContainer->setLabel("2", 'en');
        $responseContainer->setLabel("3", 'de');

        $importPersister = new ItemImportPersister($responseContainer, $item);
        $importPersister->fillItemFields();

        $this->assertEquals(json_encode($types), $item->getType());
        $this->assertEquals(2, count(json_decode($item->getType(), true)));
        $this->assertEquals("http://depiction.com", $item->getDepiction());

        $item->setTranslatableLocale('it');
        $this->assertEquals('1', $item->getLabelTranslated());
        $this->assertEquals('desc1', $item->getAbstractTranslated());
        $item->setTranslatableLocale('en');
        $this->assertEquals('2', $item->getLabelTranslated());
        $this->assertEquals('desc2', $item->getAbstractTranslated());
        $item->setTranslatableLocale('de');
        $this->assertEquals('3', $item->getLabelTranslated());
        $this->assertEquals('desc3', $item->getAbstractTranslated());
    }

}
