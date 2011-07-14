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
			$fields->addFieldToTab("Root.Content.Tags", new CheckboxSetField("EcommerceProductTags", "Select Relevant Tags", $dosArray));
		}
	}


} 
 
