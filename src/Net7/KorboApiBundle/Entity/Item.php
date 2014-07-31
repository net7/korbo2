<?php

namespace Net7\KorboApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Swagger\Annotations as SWG;
use FS\SolrBundle\Doctrine\Annotation as Solr;

use Gedmo\Mapping\Annotation as Gedmo,
    Gedmo\Translatable\Translatable;

use Doctrine\Common\Collections\ArrayCollection;
use Net7\KorboApiBundle\Entity\ItemTranslation;
use Net7\KorboApiBundle\Entity\Basket;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Item
 *
 * @Solr\Document(repository="Net7\KorboApiBundle\Entity\Item")
 * @ORM\Table(name="item")
 * @ORM\Entity(repositoryClass="Net7\KorboApiBundle\Entity\ItemRepository")
 *
 * @SWG\Model(
 *      id="Item",
 *      required="['id']"
 * )
 */
class Item
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
     *      description="Unique identifier for the Item"
     * )
     */
    private $id;

    /**
     * @var string
     *
     * @ Solr\Field(type="string")
     *
     * @ ORM\Column(name="label", type="text")
     */
    private $label;

    /**
     * @var string
     *
     * @Solr\Field(type="string")
     *
     * @ORM\Column(name="resource", type="string")
     */
    private $resource;

    /**
     * @var string
     *
     * @Solr\Field(type="string")
     *
     * @ Gedmo\Translatable
     *
     * @ ORM\Column(name="abstract", type="text")
     */
    private $abstract;

    /**
     * @var array
     *
     * @Solr\Field(type="string")
    private $abstracts;
     */


    /**
     * @var string
     *
     * @Solr\Field(type="string")
     *
     * @ORM\Column(name="type", type="text")
     */
    private $type;


    /**
     * @var string
     *
     * @Solr\Field(type="string")
     *
     * @ORM\Column(name="depiction", type="string")
     */
    private $depiction;


    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    private $locale;

    /**
     * @ORM\OneToMany(
     *   targetEntity="ItemTranslation",
     *   mappedBy="object",
     *   cascade={"persist", "remove"}
     * )
     */
    private $translations;


    /**
     * Used to store a temp language code
     *
     * @var string
     */
    private $languageCode;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Basket", inversedBy="items")
     * @ORM\JoinColumn(name="basket_id", referencedColumnName="id")
     *
     */
    private $basket;

    /**
     * Base Item uri (purl)
     *
     * @var string
     */
    private $baseItemUri;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->translations   = new ArrayCollection();
        $this->type           = json_encode(array());
        $this->depiction      = "";
        $this->languageCode   = "";
        $this->resource       = "";
        $this->baseItemUri    = "";
    }



   public function getSOLRBasketId(){
       return $this->getBasketId();
   }

    /**
     * Sets the base item uri
     *
     * @param $uri
     */
    public function setBaseItemUri($uri)
    {
        $this->baseItemUri = $uri;
    }

    /**
     * Returns all the translations of the field abstract
     *
     * @return string
     */
    public function getAbstract() {
        return $this->getFieldTranslations('abstract');
    }

    /**
     * Sets the resource
     *
     * @param string $resource
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
    }

    /**
     * Return the resource
     *
     * @return string
     */
    public function getResource()
    {
        return $this->resource;
    }

    public function getResourceIfPresent()
    {
        if ($this->resource) {
            return $this->resource;
        }

        return $this->getUri();

    }


    /**
     * Sets the basket associated to the Item
     *
     * @param Basket $basket
     */
    public function setBasket($basket)
    {
        $this->basket = $basket;
    }

    /**
     * Returns the basket associated with the Item
     *
     * @return Basket
     */
    public function getBasket()
    {
        return $this->basket;
    }

    /**
     * Returns the basketId
     *
     * @return int
     */
    public function getBasketId()
    {
        if($this->basket) return $this->basket->getId();
        return null;
    }



    /**
     * @param string $depiction
     */
    public function setDepiction($depiction)
    {
        $this->depiction = $depiction;
    }

    /**
     * @return string
     */
    public function getDepiction()
    {
        return $this->depiction;
    }


    /**
     * Return all the translations
     *
     * @return ArrayCollection
     */
    public function getTranslations()
    {
        return $this->translations;
    }


    /**
     * Sets a new translation for the item
     *
     * @param ItemTranslation $t
     * @param integer         $translationIndex
     */
    public function addTranslation(ItemTranslation $t, $translationIndex = null)
    {
        if ($translationIndex == null) {
            $this->translations[] = $t;
        } else {
            $this->translations[$translationIndex] = $t;
        }
        $t->setObject($this);

    }


    /**
     * Returns the index of the trannslation passed as parameter if present in the list of translations, false otherwise
     *
     * @param ItemTranslation $translation
     *
     * @return bool|int
     */
    public function containsTranslation(ItemTranslation $translation)
    {
        for ($i = 0; $i < $this->translations->count(); $i++) {
            $t = $this->translations[$i];
            if ($t->getLocale() == $translation->getLocale() && $t->getField() == $translation->getField()) {
                return $i;
            }
        }

        return false;
    }

    /**
     * Sets the current locale
     *
     * @param string $locale
     */
    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }


    /**
     * Set content
     *
     * @param string $abstract
     *
     * @SWG\Property(
     *      name="abstract",
     *      type="string",
     *      format="string",
     *      description="Abstract of the Item"
     * )
     *
     * @return Item
     */
    public function setAbstract($abstract)
    {
        $this->abstract = $abstract;

        return $this;
    }


    /**
     * Retrieves the translation of "abstract" field in all the available languages
     *
     * @return string
     */
    public function getAbstractTranslated()
    {
        return $this->getFieldTranslated("abstract");
    }


    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
    * Retrieves the translation in all the available languages
    *
    * @return string
    */
    public function getLabelTranslated()
    {
        return $this->getFieldTranslated("label");
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Retrieves all the translations (used to index SOLR)
     *
     * @return string
     */
    public function getLabelTranslations()
    {
        return $this->getFieldTranslations("label");
    }

    /**
     * @param string $type [json representation]
     */
    public function setType($type)
    {
        $this->type = $type;
        //$type = str_replace('"', "", $type);
        //$this->type = serialize(explode(',', $type));
    }

    /**
     * @return json string
     */
    public function getType()
    {
        return $this->type;
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


    /**
     * Returns all the availble translation languages for the current obj
     *
     * @return array
     */
    public function getAvailableLanguages()
    {
        $languages = array();
        foreach ($this->translations as $t) {
            $languages[] = $t->getLocale();
        }

        return array_values(array_unique($languages));
    }


    /**
     * Returns the actual language code for the current obj
     *
     * @return string
     */
    public function getLanguageCode()
    {
        return $this->languageCode;

    }

    /**
     * Sets the code returned as default item language
     *
     * @param string $languageCode
     */
    public function setLanguageCode($languageCode)
    {
        $this->languageCode = $languageCode;
    }

    public function getTypesArray()
    {
        return json_decode($this->type, true);
//        return unserialize($this->type);
    }

    /**
     * Returns a URI that represent the item
     *
     * @return mixed
     */
    public function getUri()
    {
        return $this->baseItemUri . $this->id;
    }

    /**
     * Retrieves the translation of the field passed as parameter
     *
     * @param string $fieldName
     *
     * @return string
     */
    private function getFieldTranslated($fieldName)
    {
        foreach ($this->translations as $translation) {
            if ($translation->getField() != $fieldName || $translation->getLocale() != $this->locale) {
                continue;
            }

            return $translation->getContent();

        }

        return "";
    }

    /**
     * Returns all the translations of the field passed as parameter
     *
     * @param $fieldName
     * @return string
     */
    private function getFieldTranslations($fieldName)
    {
        $translations = array();
        foreach ($this->translations as $translation) {
            if ($translation->getField() == $fieldName) {
                $translations[] = $translation->getContent();
            }
        }

        return $translations;
    }

    /**
     * Returns true if the language passed as parameter is available as a item translation, false otherwise
     *
     * @param string $language
     *
     * @return string
     */
    public function hasLanguageAvailable($language)
    {
        return in_array($language, $this->getAvailableLanguages());
    }



}
