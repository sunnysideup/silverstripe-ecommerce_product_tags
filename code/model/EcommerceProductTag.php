<?php

class EcommerceProductTag extends DataObject {

	public static $db = array(
		"Title" => "Varchar(100)",
		"Explanation" => "Varchar(255)",
		"Code" => "Varchar(30)"
	);

	public static $has_one = array(
		"Icon" => "Image",
		"ExplanationPage" => "SiteTree"
	);

	public static $many_many = array(
		"Products" => "Product"
	);

	public static $casting = array(
		"TinyIcon" => "HTMLText",
		"Link" => "Varchar"
	); //adds computed fields that can also have a type (e.g.

	public static $searchable_fields = array(
		"Title" => "PartialMatchFilter",
		"Code" => "PartialMatchFilter"
	);

	public static $field_labels = array();

	public static $summary_fields = array("Title" => "Name", "TinyIcon" => "Icon");

	public static $singular_name = "Product Tag";

	public static $plural_name = "Product Tags";

	//CRUD settings
	//defaults
	public static $default_sort = "\"Title\" ASC";

	public function populateDefaults() {
		parent::populateDefaults();
	}

	public function TinyIcon() {return $this->getTinyIcon();}
	public function getTinyIcon() {
		return $this->Icon()->CroppedImage(32,32);
	}

	public function Link() {return $this->getLink();}
	public function getLink() {
		$page = DataObject::get_one("ProductGroupWithTags");
		if($page) {
			return $page->Link("show")."/".$this->Code."/";
		}
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->replaceField("ExplanationPageID", new TreeDropdownField("ExplanationPageID", "Page explaining tag", "SiteTree"));
		if($this->ID) {
			if($dos = DataObject::get("Product")) {
				$dosArray = $dos->toDropDownMap();
				$fields->replaceField("Products", new CheckboxSetField("Products", "Applies to ...", $dosArray));
			}
		}
		return $fields;
	}


	public function onBeforeWrite(){
		parent::onBeforeWrite();
		if(!$this->Code) {
			$this->Code =  ereg_replace("[^A-Za-z0-9]", "", $this->Title);
		}
		$id = intval($this->ID);
		if(!$id) {
			$id = 0;
		}
		$i = 0;
		$startCode = $this->Code;
		while(DataObject::get_one($this->ClassName, "\"Code\" = '".$this->Code."' AND \"".$this->ClassName."\".\"ID\" <> ".$id) && $i < 10) {
			$i++;
			$this->Code = $startCode."_".$i;
		}
	}

	static function get_by_code($code) {
		$code = Convert::raw2sql($code);
		return DataObject::get("EcommerceProductTag", "\"Code\" = '$code'");
	}


}





