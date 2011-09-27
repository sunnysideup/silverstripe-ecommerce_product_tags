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

	function onBeforeWrite() {
		if(isset($_REQUEST["AddATag"])) {
			$name = Convert::raw2sql($_REQUEST["AddATag"]);
			if($name) {
				if(!DataObject::get_one("EcommerceProductTag", "\"Title\" = '$name' OR \"Code\" = '$name'")) {
					$obj = new EcommerceProductTag();
					$obj->Title = $name;
					$obj->Code = $name;
					$obj->write;
					//TO DO - does not work!!!
					$obj->Products()->add($this->owner);
					$this->owner->EcommerceProductTags()->add($obj);
					$obj->write();
				}
			}
		}
	}

}

