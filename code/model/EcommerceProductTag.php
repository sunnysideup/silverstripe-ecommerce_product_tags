<?php

class EcommerceProductTag extends DataObject {

	$db = array(
		"Title" => "Varchar(100)",
		"Code" => "Varchar(30)"
	);

	$has_one = array(
		"Icon" => "Image"
	);

	public static $many_many = array(
		"Products" => "Product"
	); 

	public static $casting = array("TinyIcon" -> "Varchar(100)"); //adds computed fields that can also have a type (e.g. 

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
	public static $default_sort = "Title ASC";

	public function populateDefaults() {
		parent::populateDefaults();
	}

	public function TinyIcon() {
		return $this->Pixie()->CroppedImage(100,100);
	} 

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		if($dos = DataObject::get("Products")) {
			$dosArray = $dos->toDropDownMap();
			$fields->addFieldToTab("Root.Content.Tags", new CheckboxSetField("Products", "Applies to ...", $dosArray));
		}
		
	}
	

	public function onBeforeWrite(){
		parent::onBeforeWrite();
		if($this->Code) {
			$this->Code =  ereg_replace("[^A-Za-z0-9]", "", $this->Title);
			$id = intval($this->ID);
			if(!$id) {
				$id = 0;
			}
			$i = 0;
			$startCode = $this->Code
			while(DataObject::get_one($this->ClassName, "\"Code\" = '".$this->Code."' AND \"".$this->ClassName."\"ID <> ".$id) && $i < 10) {
				$i++;
				$this->Code = $startCode."_".$i;
			}
		}
	}




}





