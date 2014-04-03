<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zitarosa
 * Date: 12/12/13
 * Time: 12:30 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Net7\KorboApiBundle\Libs;


class AbstractSearchDriver implements SearchDriver{

    function search($wordToSearch){}

    function getEntityMetadata($freebaseEntityId){}

    function getEntityDetails($freebaseEntityId){}

    function getPaginationMetadata() {}

}