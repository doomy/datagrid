# datagrid

## Requirements

- bootstrap
- font awesome

## Usage

```php
public function createComponentClientDataGrid(): IComponent
{
    $dataGrid =  new DataGrid(
        $this->dataGridEntryFactory,
        $this->data,
        DataEntity::class,
        [],
    );
    $dataGrid->setReadOnly(FALSE);
    $dataGrid->onEvent(DataGrid::EVENT_ITEM_SAVED, function($values) {
        $this->data->save(DataEntity::class, $values);
    });
    return $dataGrid;
}
 ```


note: readme is work in progress, to be updated
