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
     * @param string $name
     * @param bool $multiple
     * @param bool $numeric
     * @param bool $undefined
     * @param array $object
     */
    public function __construct(string $name, bool $multiple, bool $numeric, bool $undefined, array $object = []
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
    public static function create($name, $multiple = false, $numeric = false, $undefined = false): Structure
    {
        return new Structure($name, $multiple, $numeric, $undefined);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return bool
     */
    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * @param $multiple
     * @return $this
     */
    public function setMultiple($multiple): self
    {
        $this->multiple = $multiple;
        return $this;
    }

    /**
     * @return bool
     */
    public function isNumeric(): bool
    {
        return $this->numeric;
    }

    /**
     * @return bool
     */
    public function isUndefined(): bool
    {
        return $this->undefined;
    }

    /**
     * @return bool
     */
    public function isObject(): bool
    {
        return !empty($this->object);
    }

    /**
     * @return Structure[]
     */
    public function getProperties(): array
    {
        return $this->object;
    }

    /**
     * @param $object
     * @return $this
     */
    public function setObject($object): self
    {
        $this->object = $object;
        return $this;
    }
}