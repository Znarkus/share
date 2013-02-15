<?php

//namespace Autoloader;

class Autoloader
{
	public static $map = array();
	
	public static function callback($class)
	{
		
	}
}

/*function map() {
	
}*/

spl_autoload_register(Autoloader::callback);