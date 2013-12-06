<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zitarosa
 * Date: 11/12/13
 * Time: 11:49 AM
 * To change this template use File | Settings | File Templates.
 */

namespace Net7\KorboApiBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Net7\KorboApiBundle\Entity\Item,
    Net7\KorboApiBundle\Entity\ItemTranslation;


/**
 * Class LoadItemData
 *
 * @package Net7\KorboApiBundle\DataFixtures\ORM
 */
class LoadItemData implements FixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        /*
        $letter = new Letter();
        $letter->setContent("letter-content");

        $manager->persist($letter);
        $manager->flush();
        */
    }
}