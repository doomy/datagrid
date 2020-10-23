<?php

use Doomy\DataGrid\Model\DataGridEntry;
use Doomy\DataGrid\DataGridEntryFactory;
use Doomy\Ormtopus\DataEntityManager;
use Mockery;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../../src/DataGridEntryFactory.php";

class DataGridEntryFactoryTest extends TestCase
{
    public function testAssembleEntry(): void
    {
        $data = Mockery::mock(DataEntityManager::class);
        $factory = new DataGridEntryFactory($data);
        $entry = $factory->assembleEntry('mock-key', 'mock-value', MockEntryClass::class);
        $this->assertEquals(DataGridEntry::TYPE_SCALAR, $entry->getType(), 'type ok');
        $this->assertEquals('mock-value', $entry->getValue(), 'value ok');
        $this->assertEquals('mock-key', $entry->getFieldName(), 'field name ok');
        $this->assertInstanceOf(DataGridEntry::class, $entry, 'Entry object class ok');
    }


}

class MockEntryClass {

}
