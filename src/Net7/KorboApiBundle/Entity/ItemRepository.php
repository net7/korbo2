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




   /**
     * Counts the number of items by locale and query string
     *
     * @param $locale
     * @param $queryString
    *
     * @return mixed
     */
    public function countItemsByLocaleAndQueryString($locale, $queryString)
    {
        return $this->getItemsByLocaleAndQueryStringQuery($queryString, $locale)->getSingleScalarResult();
    }

    /**
     * Retrieves the items by locale and query string
     *
     * @param $locale
     * @param $queryString
     * @param bool $limit
     * @param bool $offset
     *
     * @return array
     */
    public function findByLocaleAndQueryString($locale, $queryString, $limit = false, $offset = false)
    {
        $query = $this->getItemsByLocaleAndQueryStringQuery($queryString, $locale);

        if ($limit !== false) {
            $query->setMaxResults($limit);
        }

        if ($offset !== false) {
            $query->setFirstResult($offset);
        }

        return $query->getResult();

    }


    public static function getItemsCountQueryString()
    {
        return  'SELECT count(l)
                 FROM Net7KorboApiBundle:Item l';
    }



}