<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zitarosa
 * Date: 11/22/13
 * Time: 3:40 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Net7\KorboApiBundle\Entity;


use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;

class ItemRepository extends EntityRepository{

    /**
     * Counts the number of Items
     *
     * @return mixed
     */
    public function countItems()
    {
        return  $this->getCountItemsQuery()->getSingleScalarResult();
    }

    /**
     * Create the count query
     *
     * @return \Doctrine\ORM\Query
     */
    public function getCountItemsQuery()
    {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT count(l.id)
                 FROM Net7KorboApiBundle:Item i');
    }




}