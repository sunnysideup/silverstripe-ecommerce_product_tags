<?php

class EcommerceProductTag extends DataObject {

	/**
	 * what variables are accessible through  http://mysite.com/api/v1/EcommerceProductTag/
	 * @var array
	 */
	public static $api_access = array(
		'view' => array(
			"Title",
			"Explanation",
			"Products"
		)
	 );
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

	public static $belongs_many_many = array(
		"ProductGroupWithTagsPages" => "ProductGroupWithTags"
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
		//$fields->replaceField("Icon", new TreeDropdownField("IconID", "Icon", "Image"));
		$fields->replaceField("ExplanationPageID", new TreeDropdownField("ExplanationPageID", "Page explaining tag", "SiteTree"));
		//temporary hack, because image fields do not work in modeladmin
		$fields->replaceField("Icon", new TreeDropdownField("IconID", "Icon", "Images"));
		if($this->ID) {
			if($dos = DataObject::get("Product")) {
				$dosArray = $dos->toDropDownMap();
				$fields->replaceField("Products", new CheckboxSetField("Products", "Applies to ...", $dosArray));
			}
		}
		if($this->ID) {
			$dos = DataObject::get("EcommerceProductTag", "EcommerceProductTag.ID <> ".$this->ID);
			if($dos) {
				$dosArray = $dos->toDropDownMap("ID", "Title", "-- do not merge --");
				$fields->addFieldToTab("Root.Merge", new DropdownField("MergeID", "Merge <i>$this->Name</i> into:", $dosArray));
			}
		}
		return $fields;
	}


	protected $mergeInto = null;

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
		if(isset($_REQUEST["MergeID"])) {
			$mergeID = intval($_REQUEST["MergeID"]);
			if($mergeID) {
				$this->mergeInto = DataObject::get_by_id("EcommerceProductTag", $mergeID);
			}
		}
	}



	function onAfterWrite(){
		parent::onAfterWrite();
		if($this->mergeInto) {
			DB::query("UPDATE \"EcommerceProductTag_Products\" SET \"EcommerceProductTagID\" = ".$this->mergeInto->ID." WHERE \"EcommerceProductTagID\"  = ".$this->ID);
			$this->delete();
		}
		if(isset($_REQUEST["MergeID"])) {
			unset($_REQUEST["MergeID"]);
		}
		$this->mergeInto = null;
	}


	static function get_by_code($code) {
		$code = Convert::raw2sql($code);
		return DataObject::get_one("EcommerceProductTag", "\"Code\" = '$code'");
	}

}





