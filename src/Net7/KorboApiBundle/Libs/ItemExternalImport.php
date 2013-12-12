<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zitarosa
 */

namespace Net7\KorboApiBundle\Libs;


use Net7\KorboApiBundle\Entity\Item;


class ItemExternalImport {

    private $isSync;

    private $resourceToImport;

    private $item;

    private $container;

    public function __construct($resourceToImport, Item $item, $isSync, $container)
    {
        $this->isSync           = $isSync;
        $this->item             = $item;
        $this->resourceToImport = $resourceToImport;
        $this->container        = $container;
    }

    /**
     * Imports the resource
     *
     * @throws \Exception
     */
    public function importResource()
    {
        if ($this->isResourceValid()){
            $this->item->setResource($this->resourceToImport);

            if ($this->isSync){
                try {
                    $importPersister = new ItemImportPersister($this->getSearchDriverForResource()->getEntityMetadata($this->resourceToImport),
                                            $this->item
                                           );

                    $importPersister->fillItemFields();
                } catch (\Exception $e){
                    throw $e;
                }
           }
        } else {
            throw new \Exception("Resource not valid");
        }
    }

    private function getSearchDriverForResource()
    {
        if (strpos($this->resourceToImport, 'www.freebase.com') !== -1)
        {
            return new FreebaseSearchDriver(
                $this->container->getParameter("freebase_search_base_url"),
                                       $this->container->getParameter("freebase_api_key"),
                                       $this->container->getParameter("freebase_topic_base_url"),
                                       $this->container->getParameter("freebase_base_mql_url"),
                                       $this->container->getParameter("freebase_languages_to_retrieve")
                );
        } else {
            throw new \Exception("No driver found for the resource {$this->resourceToImport}");
        }
    }

    /**
     * Checks if the resource to import is valid
     *
     * @return bool
     */
    private function isResourceValid()
    {
        if (strpos($this->resourceToImport, 'www.freebase.com') === -1)
            return false;

        return true;
    }



}