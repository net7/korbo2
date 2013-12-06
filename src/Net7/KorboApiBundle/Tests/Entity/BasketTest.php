<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zitarosa
 * Date: 10/11/13
 * Time: 12:46 PM
 * To change this template use File | Settings | File Templates.
 */

use Net7\KorboApiBundle\Entity\Basket;


/**
 * Class BasketTest
 */
class BasketTest extends \PHPUnit_Framework_TestCase
{
    private static $_testLabel = 'ùàòùèòùèòùèòù.èù.èù-ùèòèù,òèùòùèòùèò243èò523ù4èòùè78ò';

    /**
     * Test basket label
     */
    public function testBasketLabel()
    {
        // First, mock the object to be used in the test
        $basket = $this->getMock('\Net7\KorboApiBundle\Entity\Basket');

        $basket->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(1));

        $basket->expects($this->once())
            ->method('getLabel')
            ->will($this->returnValue(self::$_testLabel));



        $this->assertEquals(1, $basket->getId());
        $this->assertEquals(self::$_testLabel, $basket->getLabel());


        // testing a real object
        $realBasket = new Basket();

        $realBasket->setLabel("label");


        // check all the translation are registered
        $this->assertEquals("label", $realBasket->getLabel());
    }

}