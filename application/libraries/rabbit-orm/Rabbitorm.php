<?php

 use Doctrine\Common\Annotations\AnnotationReader;
 use RabbitRobot\AnnotationParser\AnnotationParser;

// In CI 3 the EXT constant doesn't exist!
if(!defined('EXT'))
{
	define('EXT', '.php');
}


class RabbitORM {



	function __construct() {
		$entity_path = APPPATH . 'entities' . DIRECTORY_SEPARATOR;
		if(file_exists($entity_path)) { $this->_read_entity_dir($entity_path); }
	}


	/**
	 * @param $dirpath
     */
	private function _read_entity_dir($dirpath)
	{
		$ci =& get_instance();

		$handle = opendir($dirpath);
		if(!$handle) return;

		while (false !== ($filename = readdir($handle)))
		{
			if($filename == "." or $filename == "..") {
				continue;
			}

			$filepath = $dirpath.$filename;
			if(is_dir($filepath)) {
				$this->_read_entity_dir($filepath);

			} elseif(strpos(strtolower($filename), '.php') !== false)
			{
				require_once $filepath;
			}

			else { continue; }
		}

		closedir($handle);
	}

}

spl_autoload_register(function($class){


	if(strpos($class, "RabbitORM\\") === 0)
	{
		$classname = str_replace("RabbitORM\\", "", $class);
		$path = 'RabbitORM/' . ucfirst(strtolower(str_replace("\\", DIRECTORY_SEPARATOR, $classname)) ) . EXT;
		require_once $path;
	}

	if(strpos($class, "RabbitRobot\\") === 0)
	{
		$path =  str_replace("\\", DIRECTORY_SEPARATOR, $class) . EXT;
		require_once $path;
	}


});
