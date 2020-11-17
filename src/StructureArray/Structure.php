<?php

namespace mortalswat\d3connector\StructureArray;

/**
 * Class Structure
 * @package mortalswat\d3connector\StructureArray
 */
class Structure
{
    /** @var string */
    private $name;
    /** @var bool */
    private $multiple;
    /** @var bool */
    private $numeric;
    /** @var bool */
    private $undefined;
    /** @var Structure[] */
    private $object;

    /**
     * Structure constructor.
     * @param $name
     * @param $multiple
     * @param $numeric
     * @param $undefined
     * @param array $object
     */
    public function __construct(
        $name,
        $multiple,
        $numeric,
        $undefined,
        array $object = []
    )
    {
        $this->name = $name;
        $this->multiple = $multiple;
        $this->numeric = $numeric;
        $this->undefined = $undefined;
        $this->object = $object;
    }

    /**
     * @param $name
     * @param bool $multiple
     * @param bool $numeric
     * @param bool $undefined
     * @return Structure
     */
    public static function create(
        $name,
        $multiple = false,
        $numeric = false,
        $undefined = false
    )
    {
        return new Structure($name, $multiple, $numeric, $undefined);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return bool
     */
    public function isMultiple()
    {
        return $this->multiple;
    }

    /**
     * @param $multiple
     * @return $this
     */
    public function setMultiple($multiple)
    {
        $this->multiple = $multiple;
        return $this;
    }

    /**
     * @return bool
     */
    public function isNumeric()
    {
        return $this->numeric;
    }

    /**
     * @return bool
     */
    public function isUndefined()
    {
        return $this->undefined;
    }

    /**
     * @return bool
     */
    public function isObject()
    {
        return !empty($this->object);
    }

    /**
     * @return Structure[]
     */
    public function getProperties()
    {
        return $this->object;
    }

    /**
     * @param $object
     * @return $this
     */
    public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }
}