<?php

class EcommerceProductTag extends DataObject {

	/**
	 * what variables are accessible through  http://mysite.com/api/v1/EcommerceProductTag/
	 * @var array

	public static $api_access = array(
		'view' => array(
			"Title",
			"Explanation",
			"Products"
		)
	);
	 */

	public static $db = array(
		"Code" => "Varchar(30)",
		"Title" => "Varchar(100)",
		"Explanation" => "Varchar(255)",
		"Synonyms" => "Text"
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

	/**
	 * standard SS method
	 * @return Boolean
	 */
	public function canView($member = null) {
		return true;
	}

	//defaults
	public static $default_sort = "\"Title\" ASC";

	public function TinyIcon() {return $this->getTinyIcon();}
	public function getTinyIcon() {
		return $this->Icon()->CroppedImage(32,32);
	}

	public function Link() {return $this->getLink();}
	public function getLink() {
		$page = DataObject::get_one("ProductGroupWithTags", "\"LevelOfProductsToShow\" = -1");
		if(!$page) {
			$pages2 = $this->ProductGroupWithTagsPages();
			if($pages2) {
				$page = $pages2->First();
			}
			if(!$page) {
				$page = DataObject::get_one("ProductGroupWithTags");
			}
		}
		if($page) {
			return $page->Link()."#filter_".$this->Code;
		}
	}


	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->replaceField("Code", new ReadonlyField("Code", "Code"));
		$fields->replaceField("ExplanationPageID", new OptionalTreeDropdownField("ExplanationPageID", "Page explaining tag", "SiteTree"));
		//temporary hack, because image fields do not work in modeladmin
		$fields->replaceField("Icon", new OptionalTreeDropdownField("IconID", "Icon", "Images"));
		$fields->addFieldToTab("Root.Merge", new TextField("Synonyms", "Synonyms (seperate by comma)"));
		if($this->exists()) {
			$stage = '';
			if(Versioned::current_stage() == "Live") {
				$stage = "_Live";
			}
			$selectedProducts = $this->Products();
			$sortString = "";
			if($selectedProducts) {
				$selectedProductsArray = $selectedProducts->map("ID", "Title");
				$sortStringEnd = "";
				if(is_array($selectedProductsArray) && count($selectedProductsArray)) {
					foreach($selectedProductsArray as $ID => $Title) {
						$sortString .= "IF(Product$stage.ID = $ID, 1, ";
						$sortStringEnd .= ")";
					}
					$sortString .= " 0".$sortStringEnd." DESC, \"Title\"";
				}
			}
			if($dos = DataObject::get("Product", "", $sortString)) {
				$dosArray = $dos->toDropDownMap();
				$fields->replaceField("Products", new CheckboxSetField("Products", "Applies to ...", $dosArray));
			}
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
		if($this->Title) {
			$this->Code = strtolower(ereg_replace("[^A-Za-z0-9]", "", $this->Title));
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
		if(isset($_REQUEST["MergeID"]) && $_REQUEST["MergeID"]) {
			$this->mergeInto = null;
			$mergeID = intval($_REQUEST["MergeID"]);
			$_REQUEST["MergeID"] = null;
			unset($_REQUEST["MergeID"]);
			if($mergeID != $this->ID) {
				$this->mergeInto = DataObject::get_by_id("EcommerceProductTag", $mergeID);
			}
		}
	}



	function onAfterWrite(){
		parent::onAfterWrite();
		if($this->mergeInto) {
			if($this->mergeInto->Synonyms) {
				$this->mergeInto->Synonyms .= ", ";
			}
			$this->mergeInto->Synonyms .= str_replace(",", ";", $this->Title);
			$this->mergeInto->write();
			DB::query("UPDATE \"EcommerceProductTag_Products\" SET \"EcommerceProductTagID\" = ".$this->mergeInto->ID." WHERE \"EcommerceProductTagID\"  = ".$this->ID);
			$this->mergeInto = null;
			$this->delete();
		}
	}


	static function get_by_code($code) {
		$code = Convert::raw2sql($code);
		return DataObject::get_one("EcommerceProductTag", "\"Code\" = '$code'");
	}

}





