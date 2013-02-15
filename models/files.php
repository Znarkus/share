<?php

class Files
{
	/** @var Lib\Database */
	private $_db;
	private $_dir;
	
	public function __construct($db, $dir)
	{
		$this->_db = $db;
		$this->_dir = $dir;
	}
	
	public function all()
	{
		$files = array();
		$ids = array();
		
		foreach (glob($this->_dir . '*') as $path) {
			$file = new File($path, array('db' => $this->_db));
			$data = $file->data();
			$data['size_human'] = $this->_human_fs($data['size']);
			$data['hits'] = array();
			$data['last_hit'] = 0;
			$files[$data['id']] = $data;
			$ids[] = $data['id'];
		}
		
		$hits = $this->_db->many('SELECT *, UNIX_TIMESTAMP(created_at) AS created_at FROM `hits` WHERE file_id IN (' . implode(',', $ids) . ') ORDER BY created_at');
		
		foreach ($hits as $hit) {
			$files[$hit['file_id']]['hits'][] = $hit;
			$files[$hit['file_id']]['last_hit'] = $hit['created_at'];
		}
		
		return $files;
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