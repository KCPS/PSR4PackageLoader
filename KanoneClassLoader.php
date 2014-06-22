<?php

/*
 * This file is derived from the Symfony packages UniversalClassLoader and Psr4ClassLoader: (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that is distributed with the Symfony package (http://symfony.com/doc/current/components/class_loader/psr4_class_loader.html)
 *
 * KanoneClassLoader (c) 2014 E. Duncan George <kanone@rogers.com>
 *
 */
namespace Kanone\Component\ClassLoader;

/**
 * A PSR-4 compatible class loader.
 *
 * See http://www.php-fig.org/psr/psr-4/
 *
 * @author E. Duncan George <kanone@rogers.com>
 */
class KanoneClassLoader {
    /**
     * @var array
     */
    private $prefixes = array();

	/**
	* @param string $prefix
	* @param string $baseDir
	*/
	public function registerPrefix($prefix, $baseDir) {
		$prefix = trim($prefix, '\\').'\\';
		$baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		if (isset($this->prefixes[$prefix]) === false) {
			$this->prefixes[$prefix] = array();
		}
		array_push($this->prefixes[$prefix], $baseDir);
	}
	/*
	* @param array $classes: An array of classes (prefixes as keys, base directories as values)
	*
	* @api
	*/
	public function registerPrefixes(array $classes) {
		foreach ($classes as $prefix => $baseDirs) {
			$prefix = trim($prefix, '\\').'\\';
			foreach ($baseDirs as $baseDir) {
				$baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
				if (isset($this->prefixes[$prefix]) === false) {
					$this->prefixes[$prefix] = array();
				}
				array_push($this->prefixes[$prefix], $baseDir);
			}
		}
	}
    /**
     * @param string $class
     *
     * @return string|null
     */
	private function loadClassFile($prefix, $relative_class)
	{
		if (isset($this->prefixes[$prefix]) === false) {	// Block inadvertent null prefixes
			return false;
		}
		// Examine the array of base directories for the supplied namespace prefix
		foreach ($this->prefixes[$prefix] as $base_dir) {
			/*
			 * Map relative namespaced class to a relative pathname, and append to the base directory.
			 * The last entry in the relative class name is the classname.  Append with .php to name its file.
			 */
			$file = $base_dir
				. str_replace('\\', DIRECTORY_SEPARATOR, $relative_class)
				. '.php';
			/*
			 * If the resulting file exists and is readable, load it.
			 */
			if (is_readable($file)) {
				require $file;
				return $file;
			}
		}
		return false;		// File for the requested class was not found
	}

    /**
     * @param string $class
     *
     * @return string | bool
     */
	public function loadClass($class) {
		foreach ($this->prefixes as $prefix => $base_dirs) {	
			if (0 !== strpos($class, $prefix)) {
				continue;
			}
			// The relative class name is the string which follows the prefix
			$relative_class = substr($class, strlen($prefix));

			// try to load a file corresponding to the namespace prefix and relative class
			$class_file = $this->loadClassFile($prefix, $relative_class);
			if ($class_file) {
				return $class_file;
			}
		}
		return false;		// Class file not found or was unreadable
	}

     /**
     * Registers this instance as an autoloader.
     *
     * @param bool    $prepend
     */
	public function register($prepend = false) {
		spl_autoload_register(array($this, 'loadClass'), true, $prepend);
	}

    /**
     * Removes this instance from the registered autoloaders.
     */
	public function unregister() {
		spl_autoload_unregister(array($this, 'loadClass'));
	}
}
