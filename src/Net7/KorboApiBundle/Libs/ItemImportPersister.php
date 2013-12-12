<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zitarosa
 * Date: 12/12/13
 * Time: 12:35 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Net7\KorboApiBundle\Libs;

use Net7\KorboApiBundle\Entity\Item;
use Net7\KorboApiBundle\Entity\ItemTranslation;

class ItemImportPersister {

    /** @var ItemResponseContainer  */
    private $responseContainer;

    /** @var Item  */
    private $item;

    public function __construct(ItemResponseContainer $responseContainer, Item $item)
    {
        $this->responseContainer = $responseContainer;
        $this->item              = $item;
    }

    /**
     * Fill the item with the fields coming from the response container
     */
    public function fillItemFields()
    {
        $this->item->setType(json_encode($this->responseContainer->getTypes()));

        foreach ($this->responseContainer->getLabels() as $language => $label){
            $this->item->addTranslation(new ItemTranslation($language, 'label', $label));
        }

        foreach ($this->responseContainer->getDescriptions() as $language => $description){
            $this->item->addTranslation(new ItemTranslation($language, 'abstract', $description));
        }

        $this->item->setDepiction($this->responseContainer->getDepiction());
    }

}