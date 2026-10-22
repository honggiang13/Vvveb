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

class WoocommerceFull extends ExtendedProvider {
	protected $columnMap = [
		'Description'              => 'product_content.content',
		'Short description'        => 'product_content.excerpt',
		'Name'                     => 'product_content.name',
		'SKU'                      => 'product.sku',
		'Vendor'                   => 'manufacturer.name',
		'Product Category'         => 'categoriesNested',
		'Tags'                     => '[Tags]',
		'Stock'                    => 'product.stock_quantity',
		'Sale price'               => 'product.price',
		'Regular price'            => 'product.old_price',
		'Variant Barcode'          => 'product.barcode',
		'Image Src'                => 'product.image',
		'Images'                   => 'product.images',
		'Published'                => 'product.status',
		'Weight'                   => 'product.weight',
		'Length'                   => 'product.length',
		'Width'                    => 'product.width',
		'Height'                   => 'product.height',
		'Language'                 => 'product_content.language_id',
		'Site'                     => 'product_to_site.site_id',
	];

	protected $transformers = ['categoriesNested' => 'Categories Nested', 'tagsList' => 'Tags List'];
}
