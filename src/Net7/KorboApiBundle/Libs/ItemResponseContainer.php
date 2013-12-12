<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zitarosa
 */

namespace Net7\KorboApiBundle\Libs;


class ItemResponseContainer {

    private $types;

    private $labels;

    private $descriptions;

    private $depiction;

    public function __construct()
    {
        $this->types        = array();

        // associative array [lang] = label
        $this->labels        = array();
        $this->descriptions = array();
        $this->depiction = '';
    }

    /**
     * Sets depiction
     *
     * @param $depiction
     */
    public function setDepiction($depiction)
    {
        $this->depiction = $depiction;
    }

    /**
     * Gets depiction
     *
     * @return string
     */
    public function getDepiction()
    {
        return $this->depiction;
    }


    /**
     * Sets a single label
     *
     * @param string $label
     * @param string $lang
     */
    public function setLabel($label, $lang)
    {
        $this->labels[$lang] = $label;
    }

    /**
     * Sets a single description
     *
     * @param string $description
     * @param string $lang
     */
    public function setDescription($description, $lang)
    {
        $this->descriptions[$lang] = $description;
    }

    /**
     * Sets the descriptions
     *
     * @param Array $descriptions
     */
    public function setDescriptions($descriptions)
    {
        $this->descriptions = $descriptions;
    }

    public function getDescriptions()
    {
        return $this->descriptions;
    }

    /**
     * Sets the labels
     *
     * @param Array $labels
     */
    public function setLabels($labels)
    {
        $this->labels = $labels;
    }

    public function getLabels()
    {
        return $this->labels;
    }

    /**
     * Sets the types
     *
     * @param Array $types
     */
    public function setTypes($types)
    {
        $this->types = $types;
    }

    /**
     * Returns types
     *
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }




}