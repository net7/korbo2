<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zitarosa
 */

namespace Net7\KorboApiBundle\Libs;


interface SearchDriver {

    function search($wordToSearch);

    function getEntityMetadata($freebaseEntityId);

    function getEntityDetails($freebaseEntityId);

    function getPaginationMetadata();

}