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

class Custom extends Provider {
	protected $columnMap = [
		'product_name'       => 'product_content.name',
		'product_slug'       => 'product_content.slug',
		'product_description'=> 'product_content.content',
		'product_excerpt'    => 'product_content.excerpt',
		'meta_title'         => 'product_content.meta_title',
		'meta_description'   => 'product_content.meta_description',
		'meta_keywords'      => 'product_content.meta_keywords',
		'sku'                => 'product.sku',
		'model'              => 'product.model',
		'barcode'            => 'product.barcode',
		'upc'                => 'product.upc',
		'ean'                => 'product.ean',
		'jan'                => 'product.jan',
		'isbn'               => 'product.isbn',
		'mpn'                => 'product.mpn',
		'hs_code'            => 'product.hs_code',
		'origin_country'     => 'product.origin_country',
		'mid_code'           => 'product.mid_code',
		'location'           => 'product.location',
		'stock_quantity'     => 'product.stock_quantity',
		'stock_status_id'    => 'product.stock_status_id',
		'price'              => 'product.price',
		'old_price'          => 'product.old_price',
		'points'             => 'product.points',
		'tax_type_id'        => 'product.tax_type_id',
		'material'           => 'product.material',
		'weight'             => 'product.weight',
		'weight_type_id'     => 'product.weight_type_id',
		'length'             => 'product.length',
		'width'              => 'product.width',
		'height'             => 'product.height',
		'length_type_id'     => 'product.length_type_id',
		'date_available'     => 'product.date_available',
		'template'           => 'product.template',
		'subtract_stock'     => 'product.subtract_stock',
		'minimum_quantity'   => 'product.minimum_quantity',
		'type'               => 'product.type',
		'status'             => 'product.status',
		'sort_order'         => 'product.sort_order',
		'vendor_name'        => 'manufacturer.name',
		'vendor_slug'        => 'manufacturer.slug',
		'vendor_image'       => 'manufacturer.image',
		'vendor_sort_order'  => 'manufacturer.sort_order',
		'category_name'      => 'categoriesNested',
		'category_slug'      => 'taxonomy_content.slug',
		'image_url'          => 'product.image',
		'variant_options'    => 'product_variant.options',
		'variant_price'      => 'product_variant.price',
		'variant_stock'      => 'product_variant.stock_quantity',
		'variant_sku'        => 'product_variant.sku',
		'variant_weight'     => 'product_variant.weight',
		'image_caption'      => 'media_content.caption',
		'image_description'  => 'media_content.description',
		'language_id'        => 'product_content.language_id',
		'site_id'            => 'product_to_site.site_id',
	];

	protected $transformers = [
		'categoriesNested' => 'Categories Nested',
		'tagsList'         => 'Tags List',
		'[Skip]'           => 'Skip',
	];
}
