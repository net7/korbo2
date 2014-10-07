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
     * @param $basketId
     *
     * @return mixed
     */
    public function countItemsByLocaleAndQueryString($locale, $queryString, $basketId = false)
    {
        return $this->getItemsByLocaleAndQueryStringQuery($queryString, $locale, false, $basketId)->getSingleScalarResult();
    }

    /**
     * Retrieves the items by locale and query string
     *
     * @param $locale
     * @param $queryString
     * @param bool $limit
     * @param bool $offset
     * @param integer $basketId
     *
     * @return array
     */
    public function findByLocaleAndQueryString($locale, $queryString, $limit = false, $offset = false, $basketId = false, $updatedAfter  = false)
    {
        $query = $this->getItemsByLocaleAndQueryStringQuery($queryString, $locale, false, $basketId, $updatedAfter);

        if ($limit !== false) {
            $query->setMaxResults($limit);
        }

        if ($offset !== false) {
            $query->setFirstResult($offset);
        }

        //print_r($query->getSQL());die;
        return $query->getResult();

    }


    /**
     * Building the query for counting items
     *
     * @param $queryString
     * @param $locale
     * @param $isCount
     *
     * @return \Doctrine\ORM\Query
     */
    private function getItemsByLocaleAndQueryStringQuery($queryString, $locale = false, $isCount = false, $basketId = false, $updatedAfter = false)
    {

        $select  = ($isCount) ? 'count(distinct it.object)' : 'i';
        $localeQueryPart = ($locale) ? "it.locale = '{$locale}' AND" : '';
        $basketIdQueryPart = ($basketId) ? "IDENTITY(i.basket)= '{$basketId}' AND" : '';
        $groupBy = ($isCount) ? '' :  "GROUP BY i.id" ;
        $updatedAfterQueryPart = ($updatedAfter) ? "i.updatedAt > '{$updatedAfter}' AND" : '';
        //$updatedAfterQueryPart = ($updatedAfter) ? "i.updatedAt = 1" : '';

        $dql = <<<___SQL
              SELECT {$select}
              FROM Net7KorboApiBundle:Item i
              JOIN i.translations it
              WHERE
                {$basketIdQueryPart}
                {$localeQueryPart}
                {$updatedAfterQueryPart}
                ((it.field = 'label' AND it.content like '%{$queryString}%') OR
                (it.field = 'abstract' AND it.content like '%{$queryString}%'))
              {$groupBy}
___SQL;

        $query = $this->getEntityManager()->createQuery($dql);
        //die("asdfasd" . $query->getSQL());
        //print_r($query->g);die;

        $query->setHint(
            \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
            'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
        );

        if ($locale !== false) {
            $query->setHint(
                \Gedmo\Translatable\TranslatableListener::HINT_TRANSLATABLE_LOCALE,
                $locale
            );
        }

        return $query;
    }

    public static function getSearchItemsCountQuery($em, $locale, $queryString, $basketId = false, $updateAfter = false)
    {
        $i = new ItemRepository($em, new ClassMetadata( 'Net7KorboApiBundle:Item'));

        return $i->getItemsByLocaleAndQueryStringQuery($queryString, $locale, true, $basketId, $updateAfter);
    }


    public static function getSearchItemsQuery($em, $locale, $queryString, $basketId = false)
    {
        $i = new ItemRepository($em, new ClassMetadata( 'Net7KorboApiBundle:Item'));

        return $i->getItemsByLocaleAndQueryStringQuery($queryString, $locale, false, $basketId);
    }

    public static function getItemsCountQueryString()
    {
        return  'SELECT count(l)
                 FROM Net7KorboApiBundle:Item l';
    }

    public static function createItemsCountQuery($em, $parameters = array()) {
        $queryString = self::getItemsCountQueryString();
        $params = array();
        if (count($parameters) > 0){
            $queryString .= " WHERE ";
            foreach ($parameters as $parameter => $value) {
                $params[] = 'l.' . $parameter . $value['operator'] . $value['placeholder'];
            }
        }

        $q = $em->createQuery($queryString . implode(" AND ", $params));
        //die ($queryString . implode(" AND ", $params));
        if (count($parameters) > 0){
            foreach ($parameters as $parameter => $value) {
                $q->setParameter($value["placeholder"], $value['value']);
            }
        }

        return $q;
    }



}