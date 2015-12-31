<?php


class EcommerceProductTagProductDecorator extends DataExtension
{

    private static $belongs_many_many = array(
        "EcommerceProductTags" => "EcommerceProductTag"
    );

    public function updateCMSFields(FieldList $fields)
    {
        $dos = EcommerceProductTag::get();
        if ($dos && $this->owner->ID) {
            $dosArray = $dos->map()->toArray();
            $fields->addFieldsToTab(
                "Root.Tags",
                array(
                    new CheckboxSetField("EcommerceProductTags", "Select Relevant Tags", $dosArray),
                    new TextField("AddATag", "Add a Tag")
                )
            );
        }
    }

    protected $newTag = null;

    public function onAfterWrite()
    {
        if ($this->newTag) {
            $this->newTag->Products()->add($this->owner);
            $this->owner->EcommerceProductTags()->add($this->newTag);
        }
        if (isset($_REQUEST["AddATag"])) {
            unset($_REQUEST["AddATag"]);
        }
        $this->newTag = null;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (isset($_REQUEST["AddATag"])) {
            $name = Convert::raw2sql($_REQUEST["AddATag"]);
            if ($name) {
                $this->newTag = EcommerceProductTag::get()->filterAny(array("Title" => $name, "Code" => $name))->first();
                if (!$this->newTag) {
                    $this->newTag = new EcommerceProductTag();
                    $this->newTag->Title = $name;
                    $this->newTag->Code = $name;
                    $this->newTag->write();
                    //TO DO - does not work!!!
                }
            }
        }
    }
}
