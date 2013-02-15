<?php

/**
* Copyright 2012, Snowfire AB, snowfireit.com
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

namespace Lib;

interface Database_Interface
{
	public function one($sql, $parameters = array(), $option = null);
	public function many($sql, $parameters = array(), $option = null);
	public function execute($sql, $parameters = array());
	public function last_insert_id();
}