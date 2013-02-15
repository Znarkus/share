<?php

class File
{
	private $_path;
	private $_filename;
	private $_file_id;
	private $_di;
	
	/**
	* @param string $filename
	* @param mixed $di DI container (db, prowl)
	* @return File
	*/
	public function __construct($path, array $di = array())
	{
		$this->_path = $path;
		$this->_filename = basename($path);
		$this->_di = $di;
		
		if (isset($this->_di['db'])) {
			$md5 = md5_file($path);
			$this->_file_id = $this->_di['db']->one('SELECT id FROM files WHERE `md5` = ? LIMIT 1', $md5, array('single_column' => true));
			
			if (!$this->_file_id) {
				$this->_di['db']->execute('INSERT INTO files (filename, `size`, `md5`) VALUES (?, ?, ?)',
					array($this->_filename, $this->_size($path), $md5));
				
				$this->_file_id = $this->_di['db']->last_insert_id();
			}
		}
	}
	
	private function _size($path) {
		$path = realpath($path);
		
		if (PHP_OS == 'WINNT') {
			$size = filesize($path);
		} else {
			exec(sprintf('du -b %s', escapeshellarg($path)), $out);
			preg_match('/^[0-9]+/', $out[0], $m);
			$size = $m[0];
		}
		
		if ($size < 0) {
			return null;
		} else {
			return $size;
		}
	}
	
	public function register_hit($data)
	{
		if (isset($this->_di['db'])) {
			$this->_register_hit_db($data);
		}
		
		if (isset($this->_di['prowl'])) {
			$this->_register_hit_prowl($data);
		}
	}
	
	private function _register_hit_db($data)
	{
		$this->_di['db']->execute('INSERT INTO hits (file_id, ip_address, created_at) VALUES (?, INET_ATON(?), ?)',
			array($this->_file_id, $data['ip_address'], $this->_di['db']->format_date_time($data['date'])));
	}
	
	private function _register_hit_prowl($data)
	{
		/** @var Prowl\Wrapper */
		$prowl = $this->_di['prowl'];
		$prowl->push('Download started', 'Download of ' . $this->_filename . ' was initiated by ' . $data['ip_address']);
	}
}