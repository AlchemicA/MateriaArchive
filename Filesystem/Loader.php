<?php

namespace Materia\Filesystem;

/**
 * A simple file loader
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
     * Adds a base directory for a namespace prefix
     *
     * @param   SplFileInfo $info       path for the given namespace or file
     * @param   string      $prefix     the namespace prefix
     * @return  self
     **/
    public function setPath( \SplFileInfo $info, $prefix = '\\' ) {
        if( !$info->isReadable() )
            return $this;

        if( $info->isDir() ) {
            $path    =  rtrim( $info->getRealPath(), DIRECTORY_SEPARATOR );

            // out of bounds
            if( strpos( $path, $this->chroot ) !== 0 )
                return $this;

            // normalize the namespace prefix
            $prefix  =  trim( $prefix, '\\' ) . '\\';

            // initialize the namespace prefix array if needed
            if( !isset( $this->paths[$prefix] ) ) {
                $this->paths[$prefix]    =  array();
            }

            // add if not present
            if( !in_array( $path, $this->paths[$prefix] ) )
                $this->paths[$prefix][]  =   $path;
        }
        else {
            $path    =  rtrim( $info->getRealPath(), DIRECTORY_SEPARATOR );

            // out of bounds
            if( strpos( $path, $this->chroot ) !== 0 )
                return $this;

            $this->files[$prefix]    =  $path;
        }

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
     * @param   boolean $files      TRUE returns registered files
     * @return  array
     **/
    public function getPaths( $files = FALSE ) {
        return $files ? $this->files : $this->paths;
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
     * Load a specific file
     *
     * @param   string  $filename   filen name
     * @return  string
     **/
    public function load( $filename ) {
        if( !is_string( $filename ) )
            return;

        if( isset( $this->logger ) )
            $this->logger->logMessage( \Materia\Debug\Logger::INFO, "Loading {$filename}" );

        // is an explicit file noted?
        if( isset( $this->files[$filename] ) ) {
            $file    =  $this->files[$filename];

            if( $this->requireFile( $file ) ) {
                if( isset( $this->logger ) )
                    $this->logger->logMessage( \Materia\Debug\Logger::INFO, "Loaded from explicit: {$file}" );

                $this->loaded[$filename]     =  $file;

                return TRUE;
            }
        }

        // the current namespace prefix
        $prefix  =  '\\' . trim( $filename, '\\' );

        // work backwards through the namespace names of the fully-qualified
        // class name to find a mapped file name
        while( FALSE !== ( $pos = strrpos( $prefix, '\\' ) ) ) {
            // retain the trailing namespace separator in the prefix
            $prefix  =  substr( $prefix, 0, ( $pos + 1 ) );

            // try to load a mapped file for the prefix and relative class
            if( $file = $this->loadFile( $prefix, substr( $filename, $pos ) ) ) {
                if( isset( $this->logger ) )
                    $this->logger->logMessage( \Materia\Debug\Logger::INFO, "Loaded from {$prefix}: {$file}" );

                $this->loaded[$filename]     =  $file;

                return TRUE;
            }

            // remove the trailing namespace separator for the next iteration of strrpos()
            $prefix  =  rtrim( $prefix, '\\' );
        }

        // did not find a file for the class
        if( isset( $this->logger ) )
            $this->logger->logMessage( \Materia\Debug\Logger::INFO, "{$filename} not loaded" );

        throw new \Exception( "{$filename} not found" );

        return FALSE;
    }

    /**
     * Set logger
     *
     * @param   \Materia\Debug\Logger   $logger     logger instance
     * @return  self
     **/
    public function setLogger( \Materia\Debug\Logger $logger ) {
        $this->logger    =  $logger;

        return $this;
    }

    /**
     * Set validator
     *
     * @param   \Materia\Data\Validator $validator  validator instance
     * @return  self
     **/
    public function setValidator( \Materia\Data\Validator $validator ) {
        $this->validator     =  $validator;

        return $this;
    }

}
