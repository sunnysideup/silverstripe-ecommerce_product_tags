<?php
 /**
 * @authors: Nicolaas
 *
 * @package: ecommerce
 * @sub-package: Products
 *
 **/

class ProductGroupWithTags extends Page {

	public static $db = array(
		"DefaultSortOrder" => "Varchar(50)",
	);

	public static $belongs_many_many = array(
		'Products' => 'Product'
	);

	public static $defaults = array(
		"DefaultSortOrder" => "title",
	);

	//public static $allowed_children = "none";

	public static $icon = 'ecommerce/ecommerce_product_tags/icons/ProductGroupWithTags';

	function canCreate($member = null) {
		return !DataObject::get_one("ProductGroupWithTags", "\"ClassName\" = 'ProductGroupWithTags'");
	}

	protected static $sort_options = array(
			'title' => array("Title" => 'Alphabetical', "SQL" => "\"Title\" ASC"),
			'price' => array("Title" => 'Lowest Price', "SQL" => "\"Price\" ASC, \"Title\" ASC"),
		);
		static function add_sort_option($key, $title, $sql){self::$sort_options[$key] = array("Title" => $title, "SQL" => $sql);}
		static function remove_sort_option($key){unset(self::$sort_options[$key]);}
		static function set_sort_options(array $a){self::$sort_options = $a;}
		static function get_sort_options(){return self::$sort_options;}
		//NON-STATIC
		protected function getSortOptionsForDropdown(){
			$array = array();
			if(is_array(self::$sort_options) && count(self::$sort_options)) {
				foreach(self::$sort_options as $key => $sort_option) {
					$array[$key] = $sort_option["Title"];
				}
			}
			return $array;
		}
		protected function getSortOptionSQL($key = ""){ // NOT STATIC
			if($key && isset(self::$sort_options[$key])) {
				return self::$sort_options[$key]["SQL"];
			}
			elseif(is_array(self::$sort_options) && count(self::$sort_options)) {
				$firstItem = array_shift(self::$sort_options);
				return $firstItem["SQL"];
			}
			else {
				return "\"Sort\" ASC";
			}
		}

	protected $standardFilter = " AND \"ShowInSearch\" = 1";
	public function getStandardFilter(){return $this->standardFilter;}

	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldToTab(
			'Root.Content',
			new Tab(
				'Products',
				new DropdownField("DefaultSortOrder", _t("ProductGroup.DEFAULTSORTORDER", "Default Sort Order"), $this->getSortOptionsForDropdown())
			)
		);
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
	function MyDefaultSortOrder() {
		$defaultSortOrder = "";
		if($this->DefaultSortOrder) {
			$defaultSortOrder = $this->DefaultSortOrder;
		}
		return $defaultSortOrder;
	}

}
class ProductGroupWithTags_Controller extends Page_Controller {

	protected $tag;

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
	 *@return DataObjectSet(Products)
	 **/
	public function Products(){
	//	return $this->ProductsShowable("\"FeaturedProduct\" = 1",$recursive);
		return $this->ProductsShowable($this->tag);
	}

	function show() {
		return array();
	}

	function Title() {
		$v = $this->Title;
		if($this->tag) {
			$v .= " - ".$this->tag->Title;
		}
		return $v;
	}

	function MetaTitle() {
		$v = $this->MetaTitle;
		if($this->tag) {
			$v .= " - ".$this->tag->Title;
		}
		return $v;
	}

	function Tags() {
		$dos = DataObject::get("EcommerceProductTag");
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



	function SortLinks(){
		if(count(ProductGroup::get_sort_options()) <= 0) return null;
		$sort = (isset($_GET['sortby'])) ? Convert::raw2sql($_GET['sortby']) : $this->MyDefaultSortOrder();
		$dos = new DataObjectSet();
		foreach(ProductGroup::get_sort_options() as $key => $array){
			$current = ($key == $sort) ? 'current' : false;
			$dos->push(new ArrayData(array(
				'Name' => _t('ProductGroup.SORTBY'.strtoupper(str_replace(' ','',$array['Title'])),$array['Title']),
				'Link' => $this->Link()."?sortby=$key",
				'Current' => $current,
				'LinkingMode' => $current ? "current" : "link"
			)));
		}
		return $dos;
	}

}
