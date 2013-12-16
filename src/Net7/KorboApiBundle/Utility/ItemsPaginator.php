<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zitarosa
 */

namespace Net7\KorboApiBundle\Utility;

use Net7\KorboApiBundle\Utility\Paginator;

class ItemsPaginator extends Paginator {

    /**
     * @param $baseQuery
     * @param $baseApiPath
     * @param int $limit
     * @param int $offset
     */
    public function __construct($baseQuery, $baseApiPath, $limit = 0, $offset = 0)
    {
        $this->setBaseApiPath($baseApiPath);
        $this->setOffset($offset);
        $this->setLimit($limit);
        $this->setBaseQuery($baseQuery);
    }

}