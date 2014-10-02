<?php

namespace Net7\KorboApiBundle\Utility;


class GeonamesPaginator extends Paginator{

    public function __construct($baseApiPath, $locale, $limit = 0, $offset = 0)
    {
        $this->setBaseApiPath($baseApiPath);
        $this->setOffset($offset);
        $this->setLimit($limit);
    }
}