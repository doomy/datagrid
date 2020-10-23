<?php

namespace Doomy\DataGrid\Model;

class DataGridEntry
{
    private $type;
    private $value;
    private $reference;
    private $fieldName;
    private $key;
    /** @var string|NULL */
    private $referenceClass;

    const TYPE_SCALAR = 'scalar';
    const TYPE_REFERENCE = 'reference';
    const TYPE_FILE = 'file';

    public function getReference()
    {
        return $this->reference;
    }

    public function setReference($reference)
    {
        $this->reference = $reference;
    }

    public function getValue()
    {
        switch ($this->type) {
            case static::TYPE_SCALAR:
            case static::TYPE_FILE:
                return $this->value;
            case static::TYPE_REFERENCE:
                return $this->getReference()->__toString();
        }

        throw new UnsupportedDataGridItemTypeException();
    }


    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $validTypes = [static::TYPE_SCALAR, static::TYPE_FILE, static::TYPE_REFERENCE];
        if (!empty($type) && !in_array($type, $validTypes)) {
            throw new UnsupportedDataGridItemTypeException();
        }

        $this->type = $type;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * @param mixed $fieldName
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
    }

    /**
     * @param mixed $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param NULL|string $referenceClass
     */
    public function setReferenceClass($referenceClass)
    {
        $this->referenceClass = $referenceClass;
    }

    /**
     * @return NULL|string
     */
    public function getReferenceClass()
    {
        return $this->referenceClass;
    }
}
