<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zitarosa
 * Date: 11/22/13
 * Time: 3:31 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Net7\KorboApiBundle\Utility;


use Net7\KorboApiBundle\Entity\ItemRepository;

/**
 * Class SearchPaginator
 *
 * @package Net7\KorboApiBundle\Utility
 */
class SearchPaginator extends Paginator {

    private $em;
    private $queryString;
    private $basketId;
    private $updatedAfter;

    /**
     * Default construcutor
     *
     * @param $em
     * @param $baseApiPath
     * @param $locale
     * @param $queryString
     * @param int $limit
     * @param int $offset
     * @param bool $basketId
     * @param bool $updatedAfter
     */
    public function __construct($em, $baseApiPath, $locale, $queryString, $limit = 0, $offset = 0, $basketId = false, $updatedAfter = false)
    {
        $this->setBaseApiPath($baseApiPath);
        $this->setOffset($offset);
        $this->setLimit($limit);
        $this->em = $em;
        $this->queryString = $queryString;
        $this->basketId = $basketId;
        $this->updatedAfter = $updatedAfter;
        $baseQuery = ItemRepository::getSearchItemsCountQuery($em, $locale, $queryString, $basketId, $updatedAfter);

        $this->setBaseQuery($baseQuery);
    }

    public function getPaginationMetadata()
    {
        $metadata = parent::getPaginationMetadata();

        $allLanguagesCount = ItemRepository::getSearchItemsCountQuery($this->em, false, $this->queryString, $this->basketId, $this->updatedAfter)->getSingleScalarResult();

        $metadata['allLanguagesCount'] = $allLanguagesCount;

        return $metadata;
    }
}