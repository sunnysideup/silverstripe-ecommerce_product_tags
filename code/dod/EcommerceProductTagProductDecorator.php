<?php


class EcommerceProductTagProductDecorator extends DataObjectDecorator {

	function extraStatics () {
		return array(
			"belongs_many_many" => array(
				"EcommerceProductTags" => "EcommerceProductTag"
			)
		);
	}

	function updateCMSFields(&$fields) {
		$dos = DataObject::get("EcommerceProductTag");
		if($dos && $this->owner->ID) {
			$dosArray = $dos->toDropDownMap();
			$fields->addFieldsToTab(
				"Root.Content.Tags",
				array(
					new CheckboxSetField("EcommerceProductTags", "Select Relevant Tags", $dosArray),
					new TextField("AddATag", "Add a Tag")
				)
			);
		}
	}

	protected $newTag = null;

	function onAfterWrite(){
		if($this->newTag) {
			$this->newTag->Product()->add($this->owner);
			$this->owner->EcommerceProductTags()->add($this->newTag);
		}
	}

	function onBeforeWrite() {
		if(isset($_REQUEST["AddATag"])) {
			$name = Convert::raw2sql($_REQUEST["AddATag"]);
			if($name) {
				$this->newTag = DataObject::get_one("EcommerceProductTag", "\"Title\" = '$name' OR \"Code\" = '$name'");
				if(!$this->newTag) {
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

