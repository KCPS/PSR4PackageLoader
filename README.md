PSR4PackageLoader
=================

Two standalone, PSR-4 compliant class loaders for PHP libraries and packages.  KanoneClassLoader is a straightforward implementation that requires each package directory, including the root, to be registered manually.  This can be done with repeated calls to registerPrefix() or with a call to registerPrefixes() and supplying an array of prefix - directory pairs.  (An example of the latter is given below.)

Loader PSR4PackageLoader features a recursive re-construction of a package's directory structure.  This allows package creators to use a single namespace for their packages; the namespace is supplied as a prefix to the loader's registry, along with the package base directory.  The loader then searches under the root and maps any found directory onto the namespace prefix.  By keeping namespace proliferation to an absolute minimum, it becomes unnecessary to add namespaces to classes, static functions and constants referenced anywhere in the package.  External namespace referencing is typically only required in application code which might, for example, want to instantiate some class in a loaded package.  The following example is from an application which uses James Heinrich's getID3() media file parsing library (http://www.getid3.org/):

    use getid3 as ID3;
    $MPX_ID3 = new ID3\getID3;

Because the application's namespace is different from getid3, an explicit namespace reference is applied to the class being sought, getID3.  To use the loader, at the top of your application top level .php file add code similar to this example:

    require_once __DIR__.'\Libs\Kanone\Components\ClassLoader\PSR4PackageLoader.php';
    use Kanone\Component\ClassLoader\PSR4PackageLoader;
    
    $loader = new PSR4PackageLoader();
    $loader->register();	// Internally, the loader's registration functions submits the name of the load method to be used.
    
    /*
     * Register project namespace prefixes with the loader.  Note the PSR-4 structure where a prefix is associated with an array of base directories.
     * Only the package root directory is supplied, however, as the directory array is created recursively downward from the root.
     */
    $loader->registerPrefixes([
            'MPX'	=> __DIR__ . '\\Libs\\MPX',
            'getid3'	=> __DIR__ . '\\Libs\\GETID3\\getid3'
    ]);
    /*
     * Application code begins here.  The getid3 alias must precede the instantiation of class getID3.
     */
The loader was developed from scratch after trials with Symfony's Psr4ClassLoader (http://symfony.com/doc/current/components/class_loader/psr4_class_loader.html) and the Framework Interoperability Group's example (https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader-examples.md).  Symfony's loader has a serious design flaw, and the FIG sample loader is coded somewhat awkwardly.  Neither implements recursive descent filesystem traversal.  By automatically registering all directories under the package root, the loader much reduces code brittleness by not having to hard-code a package's directory structure in strings.


    

    
