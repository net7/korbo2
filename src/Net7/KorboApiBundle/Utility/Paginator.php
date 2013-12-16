<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zitarosa
 */

namespace Net7\KorboApiBundle\Utility;


use Doctrine\ORM\AbstractQuery;

class Paginator {

    private $offset;
    private $limit;
    private $baseApiPath;

    // @var Doctrine\ORM\AbstractQuery;
    private $baseQuery;

    /**
     * Default constructor
     */
    public function __construct()
    { }

    /**
     * Returns the metadata section of list response
     *
     * @return array
     */
    public function getPaginationMetadata()
    {
        $totalNumberOfItems = $this->baseQuery->getSingleScalarResult();
        $totalPages = ($this->limit > 0) ? ceil( $totalNumberOfItems / $this->limit ) : 0;



        return  array(
        'pageCount'  => $totalPages,
        'totalCount' => $totalNumberOfItems,
        'offset'     => $this->offset,
        'limit'      => $this->limit,
        'links'      => $this->getLinksMetadata($totalPages, $totalNumberOfItems)
        );
    }

    /**
     * Computes and returns the links section of metadata
     *
     * @param $totalPages
     * @param $totalNumberOfItems
     *
     * @return array
     */
    private function getLinksMetadata($totalPages, $totalNumberOfItems)
    {
        // no results or limit = 0
        if ($totalPages == 0) {
            return array();
        }

        $lastOffset     = ($totalPages - 1) * $this->limit;
        $previousOffset = (floor($this->offset / $this->limit) > 0) ? $this->offset - $this->limit : false;
        $nextOffset     = (($num = ($this->offset + $this->limit)) < $totalNumberOfItems) ? $num : max($totalNumberOfItems - $this->limit, $this->offset);

        $links = array(
            array(
                "rel"  => "first",
                "href" => "{$this->baseApiPath}?offset=0&limit={$this->limit}"
                    ),
                array(
                "rel"  => "last",
                "href" => "{$this->baseApiPath}?offset=" . $lastOffset . "&limit={$this->limit}"
            )
        );

        // not first page
        if ($previousOffset !== false) {
            $links[] = array(
                "rel"  => "previous",
                "href" => "{$this->baseApiPath}?offset=" . $previousOffset . "&limit={$this->limit}"
            );
        }

        // not last page
        if ($nextOffset !== $this->offset) {
            $links[] = array(
                "rel"  => "next",
                "href" => "{$this->baseApiPath}?offset=" . $nextOffset . "&limit={$this->limit}"
            );
        }

        return $links;
    }

    /**
     *    setBaseApiPath
     *
     * @param $baseApiPath
     */
    public function setBaseApiPath($baseApiPath)
    {
        $this->baseApiPath = $baseApiPath;
    }

    /**
     *       getBaseApiPath
     *
     * @return mixed
     */
    public function getBaseApiPath()
    {
        return $this->baseApiPath;
    }

    /**
     *          setBaseQuery
     *
     * @param $baseQuery
     */
    public function setBaseQuery($baseQuery)
    {
        $this->baseQuery = $baseQuery;
    }

    /**
     *        getBaseQuery
     *
     * @return AbstractQuery
     */
    public function getBaseQuery()
    {
        return $this->baseQuery;
    }

    /**
     *  setLimit
     *
     * @param $limit
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     *  getLimit
     *
     * @return mixed
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * setOffset
     *
     * @param $offset
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
    }

    /**
     * getOffset
     *
     * @return mixed
     */
    public function getOffset()
    {
        return $this->offset;
    }




}