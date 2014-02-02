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
    private function getItemsByLocaleAndQueryStringQuery($queryString, $locale = false, $isCount = false)
    {

        $select  = ($isCount) ? 'count(distinct it.object)' : 'i';
        $localeQueryPart = ($locale) ? "it.locale = '{$locale}' AND" : '';
        $groupBy = ($isCount) ? '' :  "GROUP BY i.id" ;
        //$localeQueryPart =  '';

        $dql = <<<___SQL
              SELECT {$select}
              FROM Net7KorboApiBundle:Item i
              JOIN i.translations it
              WHERE
                {$localeQueryPart}
                ((it.field = 'label' AND it.content like '%{$queryString}%') OR
                (it.field = 'abstract' AND it.content like '%{$queryString}%'))
              {$groupBy}
___SQL;

        $query = $this->getEntityManager()->createQuery($dql);

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

    public static function getSearchItemsCountQuery($em, $locale, $queryString)
    {
        $i = new ItemRepository($em, new ClassMetadata( 'Net7KorboApiBundle:Item'));

        return $i->getItemsByLocaleAndQueryStringQuery($queryString, $locale, true);
    }


    public static function getSearchItemsQuery($em, $locale, $queryString)
    {
        $i = new ItemRepository($em, new ClassMetadata( 'Net7KorboApiBundle:Item'));

        return $i->getItemsByLocaleAndQueryStringQuery($queryString, $locale);
    }

    public static function getItemsCountQueryString()
    {
        return  'SELECT count(l)
                 FROM Net7KorboApiBundle:Item l';
    }



}