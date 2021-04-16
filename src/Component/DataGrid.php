<?php

namespace Doomy\DataGrid\Component;

use Doomy\DataGrid\Model\DataGridEntry;
use Doomy\DataGrid\DataGridEntryFactory;
use Doomy\Components\Component\BaseComponent;
use Doomy\Components\Component\DynamicPopupForm;
use Doomy\Components\FlashMessage;
use Doomy\ExtendedNetteForm\Form;
use Doomy\Ormtopus\DataEntityManager;
use Doomy\Translator\Service\DummyTranslator;
use Doomy\Translator\Service\Translator;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Controls\UploadControl;
use Nette\Localization\ITranslator;

class DataGrid extends BaseComponent
{
    const EVENT_ITEM_SAVED = 'event_item_saved';
    const EVENT_ITEM_DELETED = 'event_item_deleted';
    const EVENT_ITEMS_REORDERED = 'event_items_reordered';

    const ROW_FORM_HTML_ID = 'addRowForm';

    private $keys;
    /**
     * @var array
     */
    private $items;
    private $readOnly = TRUE;
    private $flashes = [];
    /**
     * @var DataGridEntryFactory
     */
    private $dataGridEntryFactory;
    private $popupForm;

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var bool
     */
    private $preventPopup = FALSE;

    /**
     * @var Form
     */
    private $collapsedForm;

    /**
     * @var array
     */
    private $criteria = [];

    /**
     * @var string
     */
    private $identityKey;

    /**
     * @var array
     */
    private $hiddenFields = [];

    /**
     * @var string
     */
    private $customOrderBy;
    /**
     * @var DataEntityManager
     */
    private DataEntityManager $data;

    private bool $skipIdentity = TRUE;
    private bool $preventAdd = FALSE;

    protected ITranslator $translator;

    public function __construct(
        DataGridEntryFactory $dataGridEntryFactory,
        DataEntityManager $data,
        string $entityClass,
        array $hiddenFields = [],
        bool $preventPopup = FALSE,
        bool $skipIdentity = TRUE
    )
    {
        parent::__construct();
        $this->preventPopup = $preventPopup;
        $this['addForm'] = $this->preventPopup ? $this->getCollapsedRowForm() : $this->getRowPopupComponent();
        $this->dataGridEntryFactory = $dataGridEntryFactory;
        $this->entityClass = $entityClass;
        $this->hiddenFields = $hiddenFields;
        $this->skipIdentity = $skipIdentity;
        $this->setupFields();
        $this->data = $data;
    }

    public function render()
    {
        $this->template->keys = $this->keys;
        $this->template->items = $this->getItems();
        $this->template->readOnly = $this->readOnly;
        $this->template->flashes = $this->flashes;
        $this->template->preventPopup = $this->preventPopup;
        $this->template->rowFormHtmlId = static::ROW_FORM_HTML_ID;
        $this->template->identityKey = $this->identityKey;
        $this->template->preventAdd = $this->preventAdd;
        parent::render();
    }

    public function handleDeleteItem($identity) {
        $this->triggerEvent(static::EVENT_ITEM_DELETED, $identity);
        $this->flashes[] = new FlashMessage($this->getTranslator()->translate("Item deleted"));
        $this->redrawControl();
    }

    public function handleReorder() {
        if (!isset($_REQUEST['ids'])) {
            return;
        }
        $ids = $_REQUEST['ids'];
        $this->triggerEvent(static::EVENT_ITEMS_REORDERED, $ids);
    }

    public function setReadOnly($readOnly) {
        $this->readOnly = $readOnly;
    }

    public function getRowForm() {
        return $this->preventPopup ? $this->getCollapsedRowForm() : $this->getRowPopupComponent()->getDynamicForm();
    }

    public function setCriteria(array $criteria) {
        $this->criteria = $criteria;
    }

    public function setHiddenFields(array $hiddenFields): void
    {
        $this->hiddenFields = $hiddenFields;
    }

    public function setCustomOrderBy(string $orderBy) {
        $this->customOrderBy = $orderBy;
    }

    public function setPreventAdd(bool $preventAdd = FALSE): void
    {
        $this->preventAdd = $preventAdd;
    }

    private function setupItem($item) {
        $processedItem = [];
        $entityClass = get_class($item);
        foreach ($item->__toArray() as $key => $value) {
            $entry = $this->dataGridEntryFactory->assembleEntry($key, $value, $entityClass);
            $processedItem[$entry->getFieldName()] = $entry;
        }

        return $processedItem;
    }

    private function getRowPopupComponent(): DynamicPopupForm
    {
        if (isset($this->popupForm)) {
            return $this->popupForm;
        }

        $popup = new DynamicPopupForm();
        $popup->setModalHtmlId(self::ROW_FORM_HTML_ID);
        $popup->setModalTitle($this->getTranslator()->translate('New row'));
        $popup->onEvent(DynamicPopupForm::EVENT_DYNAMIC_FORM_SAVE, function($values) {
            $this->addFormFinished((array) $values['formValues']);
        });

        return $this->popupForm = $popup;
    }

    private function getCollapsedRowForm(): Form
    {
        if (isset($this->collapsedForm)) {
            return $this->collapsedForm;
        }

        $form = new Form();
        $form->getElementPrototype()->class('ajax collapse');
        $form->getElementPrototype()->setAttribute('id', static::ROW_FORM_HTML_ID);
        $form->onSuccess[] = function ($form, $values) {
            $this->addFormFinished((array) $values);
        };
        return $this->collapsedForm = $form;
    }

    private function addFormFinished(array $values) {
        $this->triggerEvent(static::EVENT_ITEM_SAVED, $values);
        $this->flashes[] = new FlashMessage($this->getTranslator()->translate("Item saved"));

        $this->getRowForm()->reset();
        $this->redrawControl();
    }

    private function setupField(DataGridEntry $entry) {
        switch ($entry->getType()) {
            case DataGridEntry::TYPE_SCALAR:
                $field = new TextInput($entry->getFieldName());
                break;
            case DataGridEntry::TYPE_REFERENCE:
                $field = new SelectBox($entry->getFieldName(), $this->getReferenceOptions($entry));
                break;
            case DataGridEntry::TYPE_FILE:
                $field = new UploadControl($entry->getFieldName());
                $field->setRequired($this->getTranslator()->translate('Please upload a file'));
                break;
        }
        return $field;
    }

    /**
     * @param DataGridEntry $entry
     */
    private function getReferenceOptions(DataGridEntry $entry)
    {
        $possibleReferences = $this->data->findAll($entry->getReferenceClass());

        $options = [];
        foreach ($possibleReferences as $possibleReference) {
            $options[$possibleReference->getIdentity()] = $possibleReference->__toString();
        }

        return $options;
    }

    private function getItems(): array
    {
        $items = [];
        $rawData = $this->data->findAll($this->entityClass, $this->criteria, $this->customOrderBy);
        foreach ($rawData as $row) {
            $items[] = $this->setupItem($row);
        }
        return $items;
    }

    private function setupFields()
    {
        $keys = [];

        /** @var Form $rowForm */
        $rowForm = $this->getRowForm();

        foreach ($this->entityClass::getFields() as $key) {
            if ($key == $this->entityClass::IDENTITY_COLUMN) {
                $this->identityKey = $key;
                if ($this->skipIdentity) {
                    continue;
                }
            }

            if (in_array($key, $this->hiddenFields)) {
                continue;
            }

            $emptyEntry = $this->dataGridEntryFactory->assembleEntry($key, NULL, $this->entityClass);

            if (!isset($rowForm[$key])) {
                $rowForm[$key] = $this->setupField($emptyEntry);
            }

            $keys[]  = $emptyEntry->getFieldName();
        }
        if ($this->preventPopup) {
             $rowForm->addSubmit('save_row', 'Save')
                 // Nette ajax is not happy with being in modals, therefore we need this nasty hack which reinitializes
                 // ajax handlers:
                 ->setAttribute("onclick", "$.nette.load()");
        }
        $this->keys = $keys;
    }

    private function getTranslator(): ITranslator
    {
        if (isset($this->translator)) {
            return $this->translator;
        }

        return $this->translator = new DummyTranslator();
    }
}
