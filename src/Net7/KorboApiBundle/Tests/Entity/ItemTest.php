<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zitarosa
 * Date: 10/11/13
 * Time: 12:46 PM
 * To change this template use File | Settings | File Templates.
 */

use Net7\KorboApiBundle\Entity\ItemTranslation,
    Net7\KorboApiBundle\Entity\Item;


/**
 * Class ItemTest
 */
class ItemTest extends \PHPUnit_Framework_TestCase
{
    private static $_testContent = 'ùàòùèòùèòùèòù.èù.èù-ùèòèù,òèùòùèòùèò243èò523ù4èòùè78ò';

    /**
     * Test letter content
     */
    public function testItem()
    {
        // First, mock the object to be used in the test
        $item = $this->getMock('\Net7\KorboApiBundle\Entity\Item');

        $item->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(1));

        $item->expects($this->once())
            ->method('getLabelTranslated')
            ->will($this->returnValue('label-en'));

        $item->expects($this->once())
            ->method('getAbstractTranslated')
            ->will($this->returnValue('abstract-en'));


        $this->assertEquals(1, $item->getId());
        $this->assertEquals('label-en', $item->getLabelTranslated());
        $this->assertEquals('abstract-en', $item->getAbstractTranslated());


        // testing a real object
        $realItem = new Item();

        $realItem->addTranslation(new ItemTranslation("it", 'label', 'label-it'));
        $realItem->addTranslation(new ItemTranslation("en", 'label', 'label-en'));
        $realItem->addTranslation(new ItemTranslation("it", 'abstract', 'abstract-it'));
        $realItem->addTranslation(new ItemTranslation("en", 'abstract', 'abstract-en'));

        // check all the translation are registered
        $this->assertTrue($realItem->hasLanguageAvailable("it"));
        $this->assertTrue($realItem->hasLanguageAvailable("en"));
        $this->assertFalse($realItem->hasLanguageAvailable("de"));
    }

}