<?php

namespace Materia\Filesystem;

/**
 * An SPL autoloader adhering to PSR-4
 *
 * @package Materia.Filesystem
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class Loader {

    protected $chroot;

    protected $files     =  array();
    protected $debug     =  array();
    protected $loaded    =  array();
    protected $paths     =  array();

    /**
     * Constructor
     *
     * @param   \SplFileInfo    $chroot     base path (absolute)
     **/
    public function __construct( \SplFileInfo $chroot ) {
        if( !$chroot->isDir() )
            throw new \InvalidArgumentException( sprintf( 'Invalid base path %s', $chroot->getRealPath() ) );

        if( !$chroot->isReadable() )
            throw new \InvalidArgumentException( sprintf( 'Path %s is not readable', $chroot->getRealPath() ) );

        $this->chroot    =  rtrim( $chroot->getRealPath(), DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
    }

    /**
     * Registers this autoloader with SPL
     *
     * @param   bool    $prepend        TRUE to prepend to the autoload stack
     **/
    public function register( $prepend = FALSE ) {
        spl_autoload_register(
            array( $this, 'load' ),
            TRUE,
            $prepend ? TRUE : FALSE
        );
    }

    /**
     * Unregisters this autoloader from SPL
     **/
    public function unregister() {
        spl_autoload_unregister( array( $this, 'load' ) );
    }

    /**
     * Returns the debugging information array from the last load() attempt
     *
     * @return  array
     **/
    public function getDebug() {
        return $this->debug;
    }

    /**
     * Adds a base directory for a namespace prefix
     *
     * @param   string  $path       base directory for the namespace prefix
     * @param   string  $prefix     the namespace prefix
     * @return          $this
     **/
    public function setPath( $path, $prefix = '\\' ) {
        $path    =  rtrim( $path, DIRECTORY_SEPARATOR );

        // prepend the base path if necessary
        if( strpos( $path, $this->chroot ) !== 0 )
            $path    =  $this->chroot . ltrim( $path, DIRECTORY_SEPARATOR );

        // normalize the namespace prefix
        $prefix  =  trim( $prefix, '\\' ) . '\\';

        // initialize the namespace prefix array if needed
        if( !isset( $this->paths[$prefix] ) ) {
            $this->paths[$prefix]    =  array();
        }

        // add if not present
        if( !in_array( $path, $this->paths[$prefix] ) )
            $this->paths[$prefix][]  =   $path;

        return $this;
    }

    /**
     * Sets all namespace prefixes and their base directories
     *
     * @param   array   $paths      an associative array of namespace prefixes and their base directories
     **/
    public function setPaths( array $paths ) {
        foreach( $paths as $prefix => $path ) {
            $this->addPrefix( $path, $prefix );
        }
    }

    /**
     * Returns the list of all class name prefixes and their base directories
     *
     * @return  array
     **/
    public function getPaths() {
        return $this->paths;
    }

    /**
     * Sets the explicit file path for an explicit class name
     *
     * @param   string  $class      the explicit class name
     * @param   string  $file       the file path to that class
     * @return          $this
     */
    public function setFile( $class, $file ) {
        // check path
        if( strpos( $file, $this->chroot ) !== 0 )
            throw new \Exception( "Error Processing Request", 1 );

        $this->files[$class]     =  $file;
    }

    /**
     * Sets all file paths for all class names
     *
     * @param   array   $files      an associative array of class names and their file path
     **/
    public function setFiles( array $files ) {
        foreach( $files as $class => $file ) {
            $this->setFile( $class, $file );
        }
    }

    /**
     * Returns the list of explicit class names and their file paths
     *
     * @return  array
     **/
    public function getFiles() {
        return $this->files;
    }

    /**
     * Returns the list of classes, interfaces, and traits loaded by the autoloader
     *
     * @return  array       An associative array of class or interface names and their file name.
     **/
    public function getLoaded() {
        return $this->loaded;
    }

    /**
     * Loads the class file for a given class name
     *
     * @param string $class The fully-qualified class name.
     *
     * @return mixed The mapped file name on success, or boolean false on failure.
     **/
    public function load( $class ) {
        // reset debug info
        $this->debug     =  array( "Loading {$class}" );

        // is an explicit class file noted?
        if( isset( $this->files[$class] ) ) {
            $file    =  $this->files[$class];

            if( $this->requireFile( $file ) ) {
                $this->debug[]           =  "Loaded from explicit: {$file}";
                $this->loaded[$class]    =  $file;

                return TRUE;
            }
        }

        // no explicit class file
        $this->debug[]   =  "No explicit class file";

        // the current namespace prefix
        $prefix  =  '\\' . trim( $class, '\\' );

        // work backwards through the namespace names of the fully-qualified
        // class name to find a mapped file name
        while( FALSE !== ( $pos = strrpos( $prefix, '\\' ) ) ) {
            // retain the trailing namespace separator in the prefix
            $prefix  =  substr( $prefix, 0, ( $pos + 1 ) );

            // try to load a mapped file for the prefix and relative class
            if( $file = $this->loadFile( $prefix, substr( $class, $pos ) ) ) {
                $this->debug[]           =  "Loaded from {$prefix}: {$file}";
                $this->loaded[$class]    =  $file;

                return TRUE;
            }

            // remove the trailing namespace separator for the next iteration of strrpos()
            $prefix  =  rtrim( $prefix, '\\' );
        }

        // did not find a file for the class
        $this->debug[]   =  "{$class} not loaded";

        throw new \Exception( "{$class} not found" );

        return FALSE;
    }

    /**
     * Load the mapped file for a namespace prefix and relative class
     *
     * @param   string  $prefix     the namespace prefix
     * @param   string  $class      the relative class name
     * @return  mixed               FALSE if no mapped file can be loaded, or the file name that was loaded
     */
    protected function loadFile( $prefix, $class ) {
        // are there any base directories for this namespace prefix?
        if( !isset( $this->paths[$prefix] ) ) {
            $this->debug[]   =  "{$prefix}: no base dirs";

            return FALSE;
        }

        // look through base directories for this namespace prefix
        foreach( $this->paths[$prefix] as $dir ) {
            // replace the namespace prefix with the base directory,
            // replace namespace separators with directory separators
            // in the relative class name, append with .php
            $file    =  $dir . DIRECTORY_SEPARATOR . str_replace( '\\', DIRECTORY_SEPARATOR, $class ) . '.php';

            // if the mapped file exists, require it
            if( $this->requireFile( $file ) ) {
                // yes, we're done
                return $file;
            }

            // not in the base directory
            $this->debug[]   =  "{$prefix}: {$file} not found";
        }

        // never found it
        return FALSE;
    }

    /**
     * If a file exists, require it from the file system
     *
     * @param   string  $file   the file to require
     * @return  bool            TRUE if the file exists, FALSE if not
     **/
    protected function requireFile( $file ) {
        if( file_exists( $file ) ) {
            require_once $file;

            return TRUE;
        }

        return FALSE;
    }

}
