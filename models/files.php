<?php

class Files
{
	/** @var Lib\Database */
	private $_db;
	
	public function __construct($db)
	{
		$this->_db = $db;
	}
	
	public function all()
	{
		$rows = $this->_db->many('SELECT * FROM files ORDER BY filename');
		
		foreach ($rows as &$row) {
			$row['size_human'] = $this->_human_fs($row['size']);
		}
		
		return $rows;
	}
	
	private function _human_fs($size) {
		static $units = array('B', 'kB', 'MB', 'GB');
		
		if ($size <= 0) {
			return 'N/A';
		}
		
		foreach ($units as $unit) {
			if ($size / 1024 >= 1) {
				$size /= 1024;
			} else {
				break;
			}
		}
		
		return number_format($size, 1) . " {$unit}";
	}
}