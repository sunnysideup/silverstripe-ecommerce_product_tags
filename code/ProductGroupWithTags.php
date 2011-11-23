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
		$fields->removeByName("LevelOfProductsToShow");
		$dos = DataObject::get("EcommerceProductTag");
		if($dos) {
			$dosArray = $dos->toDropDownMap();
			$fields->addFieldsToTab(
				"Root.Content.Tags",
				array(
					new CheckboxSetField("EcommerceProductTags", _t("ProductGroupWithTags.ECOMMERCEPRODUCTTAGS", "Select Tags to Show"), $dosArray)
				)
			);
		}
		return $fields;
	}


	/**
	 * Retrieve a set of products, based on the given parameters. Checks get query for sorting and pagination.
	 *
	 * @param string $extraFilter Additional SQL filters to apply to the Product retrieval
	 * @param boolean $recursive
	 * @return DataObjectSet | Null
	 */
	function ProductsShowable($tagOrTags, $extraFilter = ''){

		// STANDARD FILTER
		$filter = $this->getStandardFilter(); //

		// EXTRA FILTER
		if($extraFilter) {
			$filter.= " AND $extraFilter";
		}
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
		$idArray = array();
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

		if($idArray) {
			if(count($idArray)) {
				//SORT BY
				if(!isset($_GET['sortby'])) {
					$sortKey = $this->MyDefaultSortOrder();
				}
				else {
					$sortKey = Convert::raw2sqL($_GET['sortby']);
				}
				$sort = $this->getSortOptionSQL($sortKey);
				$stage = '';
				if(Versioned::current_stage() == "Live") {
					$stage = "_Live";
				}
				$whereForPageOnly = "\"Product$stage\".\"ID\" IN (".implode(",", $idArray).") $filter";
				$products = DataObject::get('Product',$whereForPageOnly,$sort);
				if($products) {
					return $products;
				}
			}
		}
		return null;
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
		if($tag = $this->request->param("ID")) {
			$this->tag = EcommerceProductTag::get_by_code($tag);
		}
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
