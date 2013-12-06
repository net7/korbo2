<?php

namespace Net7\KorboApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Swagger\Annotations as SWG;
use FS\SolrBundle\Doctrine\Annotation as Solr;

use Gedmo\Mapping\Annotation as Gedmo,
    Gedmo\Translatable\Translatable;

use Doctrine\Common\Collections\ArrayCollection;
use Net7\KorboApiBundle\Entity\ItemTranslation;

/**
 * Basket
 *
 * @ORM\Table(name="basket")
 * @ORM\Entity
 */
class Basket
{
    /**
     * @var integer
     *
     * @Solr\Id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @SWG\Property(
     *      name="id",
     *      type="integer",
     *      format="int64",
     *      description="Unique identifier for the Basket"
     * )
     */
    private $id;

    /**
     * @var string
     *
     * @Solr\Field(type="string")
     *
     * @ORM\Column(name="label", type="text")
     */
    private $label;




    /**
     * Constructor
     */
    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->label          = "";
    }



    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }



    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets id
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
