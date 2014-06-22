<?php

/*
 * PSR4PackageLoader (c) 2014 E. Duncan George <kanone@rogers.com>
 *
 */
namespace Kanone\Component\ClassLoader;

/*
 * A PSR-4 compatible class loader.  The namespace prefix is supplied for each package along with the package root directory.
 * to registration functions.  The directory sub-structure is recursively searched so as to re-create it in the loader, enabling
 * any package class file to be found using a single namespace.
 *
 * See http://www.php-fig.org/psr/psr-4/
 *
 * @author E. Duncan George <kanone@rogers.com>
 */
class PSR4PackageLoader {
	private $prefixes = array();

	/*
	 * Function to register a package's namespace prefix.  The root directory for the package ('$packageRoot')
	 * is installed in an array corresponding to the supplied prefix.  This directory is then searched recursively
	 * for sub-directories which are also added to the array.  In this way a package can be given a single
	 * namespace, thus simplifying its use as a library by the calling application.
	 *
	 */
	public function registerPrefix($prefix, $packageRoot) {
		$prefix = trim($prefix, '\\').'\\';
		$packageRoot = rtrim($packageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		/*
		 * If the array for this package has not been created, do so
		 */
		if (isset($this->prefixes[$prefix]) === false)
			$this->prefixes[$prefix] = [];
		else
			/*
			 * Trap to guard against inadvertent duplicate request (i.e. prefix supplied than once)
			 */
			foreach ($this->prefixes[$prefix] as $packageDir)
				if ($packageDir === $packageRoot)
					return;			
		 /*
		  * The package root has to be pushed onto the prefix directory array as the first entry here because
		  * the recursive directory iterator returns only its children.  The trap just above ensures that 
		  * the directory has not already been installed.
		  */
		$this->prefixes[$prefix][] = $packageRoot;
		
		/*
		 * File system directory iterator.  The code is a little dense, so go read the SPL man pages on iterators if you 
		 * you're new to iterators.  They take a bit of getting used to, but dramatically reduce the amount of code that
		 * needs to be written for common tasks such as this one.  The nested directory structure returned in $objects is
		 * amenable to being iterated by foreach(), from which much simplicity derives.
		 */
		$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($packageRoot, \RecursiveDirectoryIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
		foreach($objects as $value){
			$innerCurrent = $objects->getInnerIterator();
			if ($innerCurrent->isDir())
				$this->push_once($this->prefixes[$prefix], $innerCurrent->getPathname() . DIRECTORY_SEPARATOR);
		}
	}
	/*
	 * This is just a syntactic convenience which allows the calling application to avoid
	 * repeated calls to registerPrefix() above.  We do it here for him, he supplying
	 * just an array of prefix => package base directory pairs.
	 */
	public function registerPrefixes(array $classes) {
		foreach ($classes as $prefix => $packageRoot)
			$this->registerPrefix($prefix, $packageRoot);
		/*
		 * DEBUGGING: Constructed package directory structure.
		 */
		//$prefix_array = "\nPrefix array \n\n" . print_r($this->prefixes, true) . "\nEnd of array.";
		//echo "PSR-4_CL registerPrefixes2: <br />\n<pre>" . $prefix_array . "</pre>";
	}
	
	/*
	 * Install the supplied package directory once only in the prefix's directory array.
	 * NOTE: Prefix array $prefix is called in by reference for obvious reasons.
	 */
	private function push_once(&$prefix, $packageDir) {
		foreach ($prefix as $base_dir)
			if ($packageDir === $base_dir)
				return;
		$prefix[] = $packageDir;
	}
	
	/*
	 * Private class method to convert the relative classname and base directory corresponding to the supplied prefix
	 * to a fully qualified file system file pathname.  If the file exists and can be read, load it.
	 */
	private function loadClassFile($prefix, $relative_class) {
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

	/*
	 * Entry point to PHP's autoloader.  This is registered as such with PHP using method register() below.
	 * Takes as argument a single class namespace string which includes a mandatory, registered namespace
	 * prefix, an optional namespace and terminated by the name of the class to load.
	 */
	public function loadClass($class) {
		foreach ($this->prefixes as $prefix => $base_dirs) {
			/*
			 * Simple test to match registered namespace prefixes: first past the post.  Thus the
			 * order of prefix registration is significant, there being no other obvious discriminant.
			 */
			if (0 !== strpos($class, $prefix))
				continue;
			/*
			 * The relative class name, successor string to the (stripped out) namespace prefix, and terminated by the classname.
			 * Any text in it preceding the classname is interpreted as a namespace in the PSR-0 sense, and converted 
			 * to a relative path which will be suffixed by loadClassFile() to the base directory corresponding
			 * to the namespace prefix. (See the second argument in loadClassFile() ).
			 *
			 * loadClassFile() attempts to load the file corresponding to the namespace prefix and the relative class
			 */
			$class_file = $this->loadClassFile($prefix, substr($class, strlen($prefix)));
			if ($class_file)
				return $class_file;
		}
		return false;		// Class file not found or was unreadable
	}

	/*
	 * Register this instance of PSR4PackageLoader as an autoloader.  The entry point is given in the second argument.
	 */
	public function register($prepend = false) {
		spl_autoload_register(array($this, 'loadClass'), true, $prepend);
	}

    /*
     * Removes this instance from the registered autoloaders - utility method that may or may not be of use.
     */
	public function unregister() {
		spl_autoload_unregister(array($this, 'loadClass'));
	}
}

