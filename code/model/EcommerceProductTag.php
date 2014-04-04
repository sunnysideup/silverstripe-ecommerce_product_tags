<?php

class EcommerceProductTag extends DataObject {

	/**
	 * what variables are accessible through  http://mysite.com/api/v1/EcommerceProductTag/
	 * @var array
	 */
	private static  $api_access = array(
		'view' => array(
			"Title",
			"Explanation",
			"Products"
		)
	);


	private static $db = array(
		"Code" => "Varchar(30)",
		"Title" => "Varchar(100)",
		"Explanation" => "Varchar(255)",
		"Synonyms" => "Text"
	);

	private static $has_one = array(
		"Icon" => "Image",
		"ExplanationPage" => "SiteTree"
	);

	private static $many_many = array(
		"Products" => "Product"
	);

	private static $belongs_many_many = array(
		"ProductGroupWithTagsPages" => "ProductGroupWithTags"
	);

	private static $casting = array(
		"TinyIcon" => "HTMLText",
		"Link" => "Varchar"
	); //adds computed fields that can also have a type (e.g.

	private static $searchable_fields = array(
		"Title" => "PartialMatchFilter",
		"Code" => "PartialMatchFilter"
	);

	private static $field_labels = array();

	private static $summary_fields = array("Title" => "Name", "TinyIcon" => "Icon");

	private static $singular_name = "Product Tag";

	private static $plural_name = "Product Tags";

	//CRUD settings

	/**
	 * standard SS method
	 * @return Boolean
	 */
	public function canView($member = null) {
		return true;
	}

	//defaults
	private static $default_sort = "\"Title\" ASC";

	public function TinyIcon() {return $this->getTinyIcon();}
	public function getTinyIcon() {
		return $this->Icon()->CroppedImage(32,32);
	}

	public function Link() {return $this->getLink();}
	public function getLink() {
		$page = ProductGroupWithTags::get()
			->filter(array("LevelOfProductsToShow" => 2))
			->first();
		if(!$page) {
			$pages2 = $this->ProductGroupWithTagsPages();
			if($pages2) {
				$page = $pages2->First();
			}
			if(!$page) {
				$page = ProductGroupWithTags::get()->first();
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
				$selectedProductsArray = $selectedProducts->map("ID", "Title")->toArray();
				$sortStringEnd = "";
				if(is_array($selectedProductsArray) && count($selectedProductsArray)) {
					foreach($selectedProductsArray as $ID => $Title) {
						$sortString .= "IF(Product$stage.ID = $ID, 1, ";
						$sortStringEnd .= ")";
					}
					$sortString .= " 0".$sortStringEnd." DESC, \"Title\"";
				}
			}
			if($dos = Product::get()) {
				$dosArray = $dos->map()->toArray();
				$fields->replaceField("Products", new CheckboxSetField("Products", "Applies to ...", $dosArray));
			}
			$dos = EcommerceProductTag::get()
				->exclude(array("ID" => $this->ID));
			if($dos->count()) {
				$dosArray = array("" => "DoNotMerge");
				$dosArray += $dos->map()->toArray();
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
		$className = $this->ClassName;

		while($className::get()
			->filter(array("Code" => $this->Code) )
			->exclude(array("ID" => $id))->count()
			&& $i < 10
		) {
			$i++;
			$this->Code = $startCode."_".$i;
		}
		if(isset($_REQUEST["MergeID"]) && $_REQUEST["MergeID"]) {
			$this->mergeInto = null;
			$mergeID = intval($_REQUEST["MergeID"]);
			$_REQUEST["MergeID"] = null;
			unset($_REQUEST["MergeID"]);
			if($mergeID != $this->ID) {
				$this->mergeInto = EcommerceProductTag::get()->byID($mergeID);
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
		return EcommerceProductTag::get()
			->filter(array("Code" => $code))
			->first();
	}

}





