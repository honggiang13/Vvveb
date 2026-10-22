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

class Provider {
	protected $columnMap = [];

	protected $transformers = [
		'categoriesNested' => 'Categories Nested',
		'tagsList'         => 'Tags List',
		'[Skip]'           => 'Skip',
	];

	protected $processors = [
		'product.image' => 'downloadMedia',
	];

	protected $db;
	protected $columns = [];
	protected $tableOrder = [];
	protected $idsMap = [];
	protected $defaults = [];
	protected $options = [];
	protected $tableColumns = [];

	function __construct() {
		$this->tableOrder = include DIR_SYSTEM . 'import' . DS . 'table-order.php';
		$this->tableOrder = array_flip($this->tableOrder);
		$this->db = Db::getInstance();
	}

	function downloadMedia($url, &$data) {
		if ($image = download($url)) {
			$name = basename(parse_url($url)['path'] ?? '');
			$path = DIR_MEDIA . $name;
			if ($name && file_put_contents($path, $image)) {
				return $name;
			}
		}

		return $url;
	}

	function columnMap() {
		return $this->columnMap;
	}

	function transformers() {
		return $this->transformers;
	}

	function setColumns($columns) {
		$this->columns = $columns;
	}

	function setDefaults($defaults) {
		$this->defaults = $defaults;
	}

	function setOptions($options) {
		$this->options = $options;
	}

	function insertRow($row) {
		$q = '`';

		if (DB_ENGINE == 'pgsql') {
			$q = '"';
		}

		foreach ($row as $tableName => $data) {
			foreach ($data as $column => &$value) {
				if ($value === '' || $value === null) {
					unset($data[$column]);
					continue;
				}

				$ivalue = strtolower($value);

				if ($ivalue == 'true') {
					$value = 1;
				} else
					if ($ivalue == 'false') {
						$value = 0;
					}
			}

			if (! isset($this->tableColumns[$tableName])) {
				$this->tableColumns[$tableName] = $this->db->getColumnsMeta($tableName);
			}

			$idColumn = $tableName . '_id';

			$data = $data + $this->idsMap + $this->defaults;
			$data = $this->db->filter($data, $this->tableColumns[$tableName]);

			unset($data[$idColumn]);

			if (! $data) {
				continue;
			}

			$cols   = implode("$q, $q", array_keys($data));
			$values = implode(', :', array_keys($data));

			$duplicate = '';
			$sql = "INSERT INTO $q$tableName$q ($q$cols$q) VALUES (:$values)" . $duplicate;

			$stmt   = $this->db->execute($sql, $data + $this->idsMap + $this->defaults);
			$lastId = $this->db->insert_id;

			$this->idsMap[$idColumn] = $lastId;
		}
	}

	function processRow($data) {
		$result = [];
		$tables = [];
		$transformers = [];
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

		if (isset($tables['product'])) {
			$tables['product_to_site']['site_id'] = $this->defaults['site_id'];
			$tables['product']['status'] = $tables['product']['status'] ?? 1;
		}

		$tableOrder = $this->tableOrder;
		uksort($tables, function ($left, $right) use ($tableOrder) {
			return  $tableOrder[$left] <=> $tableOrder[$right];
		});

		$this->insertRow($tables);
	}
}
