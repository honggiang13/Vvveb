<?php

/**
 * Vvveb
 *
 * Copyright (C) 2022  Ziadin Givan
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of
 * the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Vvveb\Plugins\VcImportCsv\Provider;

use function Vvveb\download;
use Vvveb\System\Db;

class ExtendedProvider extends Provider {
	protected $processors = [
		'product.image'        => 'downloadMedia',
		'product_variant.image'=> 'downloadMedia',
		'media.file'           => 'downloadMedia',
	];

	protected $fullTables = [
		'taxonomy'            => ['taxonomy_id', 'name', 'post_type', 'type', 'site_id'],
		'taxonomy_content'    => ['taxonomy_id', 'language_id', 'name', 'slug', 'content'],
		'taxonomy_item'       => ['taxonomy_item_id', 'taxonomy_id', 'image', 'template', 'parent_id', 'item_id', 'sort_order', 'status'],
		'taxonomy_item_content'=>['taxonomy_item_id', 'language_id', 'name', 'slug', 'content', 'meta_title', 'meta_description', 'meta_keywords'],
		'taxonomy_to_site'    => ['taxonomy_item_id', 'site_id'],
		'manufacturer'        => ['manufacturer_id', 'name', 'slug', 'image', 'sort_order'],
		'manufacturer_to_site'=> ['manufacturer_id', 'site_id'],
		'product'             => ['product_id', 'admin_id', 'model', 'sku', 'upc', 'ean', 'jan', 'isbn', 'mpn', 'barcode', 'hs_code', 'origin_country', 'mid_code', 'location', 'stock_quantity', 'stock_status_id', 'image', 'manufacturer_id', 'vendor_id', 'requires_shipping', 'price', 'old_price', 'points', 'tax_type_id', 'material', 'weight', 'weight_type_id', 'length', 'width', 'height', 'length_type_id', 'date_available', 'template', 'views', 'subtract_stock', 'minimum_quantity', 'subscription_onetime', 'type', 'status', 'sort_order', 'created_at', 'updated_at'],
		'product_content'     => ['product_id', 'language_id', 'name', 'slug', 'content', 'excerpt', 'meta_title', 'meta_description', 'meta_keywords'],
		'product_variant'     => ['product_variant_id', 'product_id', 'options', 'image', 'price', 'old_price', 'stock_quantity', 'weight', 'sku', 'barcode'],
		'product_image'       => ['product_image_id', 'product_id', 'image', 'sort_order'],
		'product_meta'        => ['product_id', 'namespace', 'key', 'value'],
		'product_to_taxonomy_item' => ['product_id', 'taxonomy_item_id'],
		'media'               => ['media_id', 'file', 'type', 'meta'],
		'media_content'       => ['media_id', 'language_id', 'name', 'caption', 'description'],
	];

	function processRow($data) {
		$tables = [];
		$transformers = [];

		// Parse columns into tables
		foreach ($data as $index => $value) {
			if (isset($this->columns[$index])) {
				$column = $this->columns[$index];

				if (strpos($column, '.') !== false) {
					if (isset($this->processors[$column])) {
						$processor = $this->processors[$column];
						$value = $this->$processor($value, $data);
					}

					list($table, $col) = explode('.' , $column);
					$tables[$table][$col] = $value;
				} else {
					$transformers[$column] = $value;
				}
			}
		}

		// Handle category transformer
		if (isset($transformers['categoriesNested'])) {
			$this->processCategories($transformers['categoriesNested'], $tables);
		}

		// Defaults for product
		if (isset($tables['product'])) {
			$tables['product']['status'] = $tables['product']['status'] ?? 1;
			$tables['product']['admin_id'] = $tables['product']['admin_id'] ?? $this->defaults['admin_id'] ?? 1;
			$tables['product']['site_id'] = $this->defaults['site_id'] ?? 1;
			$tables['product_to_site']['site_id'] = $this->defaults['site_id'] ?? 1;
			$tables['product_content']['language_id'] = $tables['product_content']['language_id'] ?? $this->defaults['language_id'] ?? 1;
		}

		// Defaults for manufacturer
		if (isset($tables['manufacturer'])) {
			$tables['manufacturer_to_site']['site_id'] = $this->defaults['site_id'] ?? 1;
		}

		// Defaults for taxonomy
		if (isset($tables['taxonomy'])) {
			$tables['taxonomy']['site_id'] = $this->defaults['site_id'] ?? 1;
			$tables['taxonomy']['post_type'] = $tables['taxonomy']['post_type'] ?? 'product';
			$tables['taxonomy']['type'] = $tables['taxonomy']['type'] ?? 'categories';
		}

		if (isset($tables['taxonomy_content'])) {
			$tables['taxonomy_content']['language_id'] = $tables['taxonomy_content']['language_id'] ?? $this->defaults['language_id'] ?? 1;
		}

		if (isset($tables['taxonomy_item'])) {
			$tables['taxonomy_item']['status'] = $tables['taxonomy_item']['status'] ?? 1;
		}

		if (isset($tables['taxonomy_item_content'])) {
			$tables['taxonomy_item_content']['language_id'] = $tables['taxonomy_item_content']['language_id'] ?? $this->defaults['language_id'] ?? 1;
		}

		if (isset($tables['media'])) {
			$tables['media_content']['language_id'] = $tables['media_content']['language_id'] ?? $this->defaults['language_id'] ?? 1;
		}

		if (isset($tables['product_variant'])) {
			$tables['product_variant']['product_id'] = $tables['product_variant']['product_id'] ?? (isset($tables['product']['product_id']) ? $tables['product']['product_id'] : null);
		}

		if (isset($tables['product_image'])) {
			$tables['product_image']['product_id'] = $tables['product_image']['product_id'] ?? (isset($tables['product']['product_id']) ? $tables['product']['product_id'] : null);
		}

		if (isset($tables['product_meta'])) {
			$tables['product_meta']['product_id'] = $tables['product_meta']['product_id'] ?? (isset($tables['product']['product_id']) ? $tables['product']['product_id'] : null);
		}

		if (isset($tables['product_to_taxonomy_item'])) {
			$tables['product_to_taxonomy_item']['product_id'] = $tables['product_to_taxonomy_item']['product_id'] ?? (isset($tables['product']['product_id']) ? $tables['product']['product_id'] : null);
		}

		// Order tables by dependency
		$tableOrder = $this->tableOrder;
		uksort($tables, function ($left, $right) use ($tableOrder) {
			return  ($tableOrder[$left] ?? 999) <=> ($tableOrder[$right] ?? 999);
		});

		$this->insertRow($tables);
	}

	protected function processCategories($categoryValue, &$tables) {
		// Parse nested category string like "Parent > Child > Grandchild"
		$categories = array_map('trim', explode('>', $categoryValue));
		if (empty($categories)) {
			return;
		}

		$siteId = $this->defaults['site_id'] ?? 1;
		$languageId = $this->defaults['language_id'] ?? 1;
		$postType = 'product';

		// Create taxonomy for each level if not exists
		$parentTaxonomyId = null;
		$parentItemId = null;

		foreach ($categories as $level => $catName) {
			if ($catName === '') continue;

			$slug = $this->slugify($catName);

			// taxonomy
			if (! isset($tables['taxonomy'][$level])) {
				$tables['taxonomy'][$level] = [
					'name'     => $catName,
					'post_type'=> $postType,
					'type'     => 'categories',
					'site_id'  => $siteId,
				];
			}

			// taxonomy_content
			$tables['taxonomy_content'][$level] = [
				'language_id' => $languageId,
				'name'        => $catName,
				'slug'        => $slug,
				'content'     => '',
			];

			// taxonomy_item
			$tables['taxonomy_item'][$level] = [
				'taxonomy_id' => null, // will be set after taxonomy insert
				'image'       => '',
				'template'    => '',
				'parent_id'   => $parentItemId ?? 0,
				'item_id'     => null,
				'sort_order'  => $level + 1,
				'status'      => 1,
			];

			// taxonomy_item_content
			$tables['taxonomy_item_content'][$level] = [
				'language_id'       => $languageId,
				'name'              => $catName,
				'slug'              => $slug,
				'content'           => '',
				'meta_title'        => $catName,
				'meta_description'  => '',
				'meta_keywords'     => $catName,
			];

			// taxonomy_to_site
			$tables['taxonomy_to_site'][$level] = [
				'site_id' => $siteId,
			];

			$parentTaxonomyId = $level; // reference for next level
			$parentItemId = $level;     // reference for next level
		}

		// Link product to last category
		if (isset($tables['product'])) {
			$lastLevel = array_key_last($categories);
			$tables['product_to_taxonomy_item'] = [
				'taxonomy_item_id' => $lastLevel,
			];
		}
	}

	protected function slugify($text) {
		$text = strip_tags($text);
		$text = strtolower($text);
		$text = preg_replace('/[^a-z0-9]+/', '-', $text);
		$text = trim($text, '-');
		return $text;
	}
}
