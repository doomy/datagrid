<?php

namespace Doomy\DataGrid;

use Doomy\Helper\StringTools;
use Doomy\Ormtopus\DataEntityManager;
use Doomy\DataGrid\Model\DataGridEntry;

class DataGridEntryFactory
{
    private DataEntityManager $data;

    public function __construct(DataEntityManager $data)
    {
        $this->data = $data;
    }

    public function assembleEntry($key, $value = NULL, $entityClass) {
        $entry = new DataGridEntry();
        $entry->setKey($key);
        if (substr(strtolower($key), -3) == "_id") {
            try {
                $entry->setReferenceClass($this->getReferencedClass($key, $entityClass));
                $entry->setReference($this->data->findById($entry->getReferenceClass(), $value));
                $entry->setFieldName($this->getReferencedEntityName($key));
                $entry->setType(DataGridEntry::TYPE_REFERENCE);
                return $entry;
            } catch (\Doomy\DataGrid\Exception\ReferenceMethodNotFoundException $e) {

            }
        }
        $entry->setType($this->resolveEntryType($key, $entityClass));
        $entry->setValue($value);
        $entry->setFieldName($key);
        return $entry;
    }

    private function getReferencedEntityName($referenceColumnName)
    {
        $entityKey = substr($referenceColumnName,0, -3);
        return StringTools::underscoresToCamelCase($entityKey, TRUE);
    }

    /**
     * @param $key
     * @param $entityClass
     * @return mixed
     * @throws \Doomy\DataGrid\Exception\ReferenceMethodNotFoundException
     */
    private function getReferencedClass($key, $entityClass)
    {
        $entityName = $this->getReferencedEntityName($key);
        $referenceGetterName = "get{$entityName}Reference";
        if (!method_exists($entityClass, $referenceGetterName)) {
            throw new \Doomy\DataGrid\Exception\ReferenceMethodNotFoundException();
        }
        return $entityClass::$referenceGetterName();
    }

    private function resolveEntryType(string $key, string $entityClass): string
    {
        $entityName = StringTools::underscoresToCamelCase($key);
        $typeGetterName = "get{$entityName}FieldType";
        if (method_exists($entityClass, $typeGetterName)) {
            return $entityClass::$typeGetterName();
        }

        return DataGridEntry::TYPE_SCALAR;
    }
}
