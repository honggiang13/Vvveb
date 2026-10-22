<?php

/* 
 * Vvveb
 * Copyright (C) 2022  Ziadin Givan
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/* 
 * Name: VCenter Import CSV
 * Slug: vc-import-csv
 * Category: content
 * Url: https://www.vcenter.vn
 * Description: Import full products, categories, vendors, images, variants, meta, SEO from CSV (WooCommerce, Shopify, Magento, PrestaShop, OpenCart, custom).
 * Author: VCenter
 * Version: 1.0
 * Thumb: vc-import-csv.svg
 * Settings: /admin/index.php?module=plugins/vc-import-csv/settings
 */

use Vvveb\System\Event;
use function Vvveb\__;

if (! defined('V_VERSION')) {
	die('Invalid request!');
}

class VcImportCsvPlugin {
	function admin() {
		$admin_path = \Vvveb\adminPath();
		Event::on('Vvveb\Controller\Base', 'init-menu', __CLASS__, function ($menu) use ($admin_path) {
			$menu['plugins']['items']['vc-import-csv'] = [
				'name'     => __('VC Import CSV'),
				'url'      => $admin_path . 'index.php?module=plugins/vc-import-csv/settings',
				'icon-img' => PUBLIC_PATH . 'plugins/vc-import-csv/vc-import-csv.svg',
				'module'   => 'plugins/vc-import-csv/settings',
				'action'   => 'index',
			];

			return [$menu];
		});
	}

	function __construct() {
		if (APP == 'admin') {
			$this->admin();
		}
	}
}

$vcImportCsvPlugin = new VcImportCsvPlugin();