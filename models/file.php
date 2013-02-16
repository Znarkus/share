<?php

class File
{
	private $_path;
	private $_filename;
	private $_file;
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
			$key = key(filemtime($path) . $this->_size($path));
			$this->_file = $this->_di['db']->one('SELECT * FROM files WHERE `key` = ?', $key);
			
			if (!$this->_file) {
				$this->_di['db']->execute('INSERT INTO files (filename, `size`, `key`) VALUES (?, ?, ?)'
					, array($this->_filename, $this->_size($path), $key));
				$this->_file = $this->_di['db']->one('SELECT * FROM files WHERE id = ?', $this->_di['db']->last_insert_id());
			
			} else if ($this->_file['filename'] !== $this->_filename) {
				$this->_di['db']->execute('UPDATE files SET filename = ? WHERE id = ?', array($this->_filename, $this->_file['id']));
				$this->_file['filename'] = $this->_filename;
			}
		}
	}
	
	public function data($key = null)
	{
		return $key ? $this->_file[$key] : $this->_file;
	}
	
	private function _size($path) {
		if (PHP_OS == 'WINNT') {
			$size = filesize($path);
		} else {
			exec(sprintf('du -b %s', escapeshellarg(realpath($path))), $out);
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
			array($this->_file['id'], $data['ip_address'], $this->_di['db']->format_date_time($data['date'])));
	}
	
	private function _register_hit_prowl($data)
	{
		/** @var Prowl\Wrapper */
		$prowl = $this->_di['prowl'];
		$prowl->push('Download started', 'Download of ' . $this->_filename . ' was initiated by ' . $data['ip_address']);
	}
}