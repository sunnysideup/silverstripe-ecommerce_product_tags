<?php
 /**
 * @authors: Nicolaas
 *
 * @package: ecommerce
 * @sub-package: Products
 *
 **/

class ProductGroupWithTags extends ProductGroup {

	/**
	 * standard SS variable
	 */
	public static $many_many = array(
		"EcommerceProductTags" => "EcommerceProductTag"
	);

	/**
	 * standard SS variable - not used for now, but under consideration...
	 */
	//public static $allowed_children = "none";

	/**
	 * standard SS variable
	 * we set this variable, becase we dont want it to be Product (in the parent class it is product)
	 */
	public static $default_child = 'Page';

	/**
	 * standard SS variable
	 */
	public static $icon = 'ecommerce_product_tags/images/icons/ProductGroupWithTags';

	/**
	 * standard SS method
	 */
	function getCMSFields() {
		$fields = parent::getCMSFields();
		$dos = DataObject::get("EcommerceProductTag");
		if($dos) {
			$dosArray = $dos->toDropDownMap();
			$field = new CheckboxSetField("EcommerceProductTags", _t("ProductGroupWithTags.ECOMMERCEPRODUCTTAGS", "Select Tags to Show"), $dosArray);
		}
		else {
			$field = new LiteralField("EcommerceProductTags_Explanation", _t("ProductGroupWithTags.ECOMMERCEPRODUCTTAGSEXPLANANTION", "Create some tags first (see Shop) before you can select what tags to show on this page."));
		}
		$fields->addFieldsToTab(
			"Root.Content.Tags",
			array(
				$field
			)
		);
		return $fields;
	}



	/**
	 * returns the inital (all) products, based on the all the eligile products
	 * for the page.
	 *
	 * This is THE pivotal method that probably changes for classes that
	 * extend ProductGroup as here you can determine what products or other buyables are shown.
	 *
	 * The return from this method will then be sorted and filtered to product the final product list
	 *
	 * @param string $extraFilter Additional SQL filters to apply to the Product retrieval
	 * @param mixed $tagOrTags - can be almost any variable referring to tags
	 * @return DataObjectSet | Null
	 **/
	protected function currentInitialProducts($extraFilter = '', $tagOrTags = null){

		$stage = '';
		if(Versioned::current_stage() == "Live") {
			$stage = "_Live";
		}

		// STANDARD FILTER
		$filter = $this->getStandardFilter(); //
		// EXTRA FILTER
		if($extraFilter && is_string($extraFilter)) {
			$filter.= " AND $extraFilter";
		}
		$groupFilter = $this->getGroupFilter();
		$filter .= " AND $groupFilter";
		$dos = null;
		if(!$tagOrTags) {
			return null;
		}
		elseif($tagOrTags instanceOf DataObjectSet) {
			$dos = $tagOrTags;
			//do nothing
		}
		elseif($tagOrTags instanceOf DataObject) {
			$dos = new DataObjectSet(array($tagOrTags));
		}
		elseif(is_array($tagOrTags)) {
			$dos = DataObject::get("EcommerceProductTag", "\"EcommerceProductTag\".\"ID\" IN(".implode(",", $tagOrTags).")");
		}
		elseif(intval($tagOrTags) == $tagOrTags) {
			$dos = DataObject::get("EcommerceProductTag", "\"EcommerceProductTag\".\"ID\" IN(".$tagOrTags.")");
		}
		else {
			return null;
		}
		//add at least one product - albeit a fake one...
		$idArray = array(0 => 0);
		if($dos) {
			if($dos->count()) {
				foreach($dos as $do) {
					$products = $do->getManyManyComponents('Products');
					if($products && $products->count()) {
						$addedArray = $products->column("ID");
						if(is_array($addedArray) && count($addedArray)) {
							$idArray = array_merge($idArray, $addedArray);
						}
					}
				}
			}
		}
		$allProducts = DataObject::get('Product',"\"Product$stage\".\"ID\" IN (".implode(",", $idArray).") $filter");
		return $allProducts;
	}


	function ChildGroups() {
		return null;
	}



}

class ProductGroupWithTags_Controller extends Page_Controller {

	/**
	 * currently selected tag
	 * @var Object
	 */
	protected $tag = null;

	/**
	 * standard SS method
	 */
	function init() {
		parent::init();
		Requirements::themedCSS('Products');
		Requirements::themedCSS('ProductGroup');
		Requirements::themedCSS('ProductGroupWithTags');
	}

	/**
	 * Return the products for this group.
	 *
	 * @return DataObjectSet(Products)
	 **/
	public function Products(){
		if($this->tag) {
			$toShow = $this->tag;
		}
		else {
			$toShow = $this->EcommerceProductTags();
		}
		return $this->ProductsShowable($toShow);
	}

	/**
	 * just a placeholder that is required
	 */
	function show() {
		if($tag = $this->request->param("ID")) {
			$this->tag = EcommerceProductTag::get_by_code($tag);
		}
		return array();
	}

	/**
	 * change title in case of a selected tag
	 */
	function Title() {
		$v = $this->Title;
		if($this->tag) {
			$v .= " - ".$this->tag->Title;
		}
		return $v;
	}

	/**
	 * change MetaTitle in case of a selected tag
	 * TODO: this method does not seem to be working
	 */
	function MetaTitle() {
		$v = $this->MetaTitle;
		if($this->tag) {
			$v .= " - ".$this->tag->Title;
		}
		return $v;
	}

	/**
	 * Tags available in the template
	 */
	function Tags() {
		$dos = $this->EcommerceProductTags();
		if($dos) {
			foreach($dos as $do) {
				if($do->Code == $this->tag) {
					$do->LinkingMode = "current";
				}
				else {
					$do->LinkingMode = "link";
				}
			}
		}
		return $dos;
	}



}
