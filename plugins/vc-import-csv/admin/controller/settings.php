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

namespace Vvveb\Plugins\VcImportCsv\Controller;

use \Vvveb\System\Db;
use function Vvveb\__;
use Vvveb\Controller\Base;
use function Vvveb\humanReadable;
use function Vvveb\sanitizeFileName;
use function Vvveb\url;

class Settings extends Base {
	private $namespaces = ['product', 'manufacturer', 'taxonomy', 'media', 'product_variant', 'product_meta'];

	private function readColumns($file, $name = '') {
		$this->view->columns = [];
		$this->view->values  = [];

		if (($handle = fopen($file, 'r')) !== FALSE) {
			if (($data = fgetcsv($handle,null,',','"','\\')) !== FALSE) {
				$this->view->columns = $data;
				$this->view->values = fgetcsv($handle,null,',','"','\\') ?: [];
			}

			fclose($handle);
		}
	}

	function next() {
		$position  = $this->request->get['position'] ?? 0;
		$count     = $this->request->get['count'] ?? 0;
		$file      = $this->request->get['file'] ?? '';
		$providerName  = $this->request->get['provider'] ?? 'woocommerce_full';
		$column    = $this->request->post['column'] ?? [];
		$data      = [];
		$results   = [];
		$maxrows   = 3;
		$i = 0;

		$path = DIR_BACKUP . $file;

		if (($handle = fopen($path, 'r')) !== FALSE) {
			fseek($handle, $position, SEEK_SET);

			$providerClass = 'Vvveb\Plugins\VcImportCsv\Provider\\' . $providerName;
			if (! class_exists($providerClass)) {
				$errorMessage         = __('Invalid provider!');
				$this->view->errors[] = $errorMessage;
				return;
			}

			$provider = new $providerClass();
			$provider->setColumns($column);
			$provider->setDefaults($this->global);
			$provider->setOptions(['overwrite_image' => true]);

			$this->tableOrder = include DIR_SYSTEM . 'import' . DS . 'table-order.php';
			$this->tableOrder = array_flip($this->tableOrder);

			while ((($data = fgetcsv($handle,null,',','"','\\')) !== FALSE) && $i < $maxrows) {
				$i++;
				if ($i > 1) {//skip header
					$provider->processRow($data);
				}
				$position = ftell($handle);
			}

			$count += $i;
			fclose($handle);
		}

		$last = ($data === false);
		$message = '';
		if ($last) {
			$message = __('Import finished!');
		}
		$url     = ['module'=> 'plugins/vc-import-csv/settings', 'action' => 'next', 'file' => $file, 'provider' => $providerName, 'success' => $message, 'position' => $position, 'count' => $count, 'last' => $last, 'i' => $i, 'data' => $results];

		if ($this->request->isAjax()) {
			$this->response->setType('json');
			$this->response->output($url + ['url' => url($url)]);
		} else {
			$this->redirect(url($url));
			die('Processing');
		}
	}

	function import() {
		$column   = $this->request->post['column'];
		$provider = $this->request->get['provider'];
		$file = $this->request->get['file'];
		$position = 0;
		$count = 0;

		$url = ['module'=> 'plugins/vc-import-csv/settings', 'action' => 'next', 'file' => $file, 'provider' => $provider, 'position' => $position, 'count' => $count];
		$this->view->provider = $provider;
		$this->view->column = $column;
		$this->view->nextUrl = url($url);
		$this->view->providers = $this->getProviders();
	}

	function dbColumns() {
		$db = Db::getInstance();
		$tables = $db->getTableNames();
		$group = [];

		foreach ($this->namespaces as $namespace) {
			$len = strlen($namespace);
			foreach ($tables as $table) {
				if (strncmp($table, $namespace, $len) === 0) {
					$columns = $db->getColumnsMeta($table);
					$group[$namespace][$table] = array_keys($columns);
				}
			}
		}

		return $group;
	}

	function flattenTableColumns($group) {
		$flat = [];
		foreach ($group as $namespace => $tables) {
			foreach ($tables as $table => $columns) {
				foreach ($columns as $column) {
					$flat[$namespace][$table . '.' . $column] = humanReadable($table) . ' - ' . humanReadable($column);
				}
			}
		}

		return $flat;
	}

	function config() {
		$file = $this->request->get['file'] ?? false;
		$providerName = $this->request->get['provider'] ?? 'woocommerce_full';
		$path = DIR_BACKUP . $file;

		if (file_exists($path)) {
			$this->readColumns($path, $file);
			$group = $this->dbColumns();
			$flat = $this->flattenTableColumns($group);
			$providerClass = 'Vvveb\Plugins\VcImportCsv\Provider\\' . $providerName;
			if (! class_exists($providerClass)) {
				$errorMessage         = __('Invalid provider!');
				$this->view->errors[] = $errorMessage;
				return;
			}

			$provider = new $providerClass();

			$transformers = ['categoriesNested' => 'Categories Nested', 'tagsList' => 'Tags List', '[Skip]' => 'Skip'];
			$this->view->tableColumns = ['' => ' - none - '] + $flat + $provider->transformers();
			$this->view->columnMap = $provider->columnMap();
			$this->view->providers = $this->getProviders();
		} else {
			$errorMessage         = __('File does not exist!');
			$this->view->errors[] = $errorMessage;
		}

		$this->view->formAction = url(['module'=> 'plugins/vc-import-csv/settings', 'file' => $file, 'provider' => $providerName]);
	}

	function upload() {
		$files = $this->request->files;
		$providerName = $this->request->post['provider'] ?? 'woocommerce_full';

		if ($files) {
			$file = $files['file'];
			$fileName = sanitizeFileName($file['name']);
			$extension = strtolower(substr($fileName, strrpos($fileName, '.') + 1));
			$type  = $file['type'];
			$mimeType  = mime_content_type($file['tmp_name']);

			if ($type != 'csv' && $mimeType != 'text/csv') {
				$errorMessage           = sprintf(__('Must upload .csv file!'), $fileName);
				$this->view->errors[]   = $errorMessage;
				return;
			}

			$dest = DIR_BACKUP . $fileName;
			if (move_uploaded_file($file['tmp_name'], $dest)) {
				$this->redirect(['module'=> 'plugins/vc-import-csv/settings', 'action' => 'config', 'file' => $fileName, 'provider' => $providerName]);
			}
		}

		$file = $this->request->post['file'] ?? false;

		if (is_array($file)) {
			foreach ($file as $f) {
				$this->importFile($f, basename($f));
			}
		} else {
			if ($file) {
				$this->importFile($file, basename($file));
			}
		}

		return $this->index();
	}

	private function getProviders() {
		return [
			'woocommerce'      => 'WooCommerce',
			'woocommerce_full' => 'WooCommerce Full',
			'custom'           => 'Custom VCenter',
		];
	}

	function index() {
		$this->view->columns = [];
		$this->view->providers = $this->getProviders();
	}
}
