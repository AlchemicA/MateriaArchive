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
    protected $loader;

    protected $files     =  array();
    protected $paths     =  array();
    protected $loaded    =  array();

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

            // Outside chroot: not allowed
            if( strpos( $path, $this->chroot ) !== 0 )
                return $this;

            // Normalize the namespace prefix
            $prefix  =  trim( $prefix, '\\' ) . '\\';

            // Initialize the namespace prefix array if needed
            if( !isset( $this->paths[$prefix] ) ) {
                $this->paths[$prefix]    =  array();
            }

            // Add if not present
            if( !in_array( $path, $this->paths[$prefix] ) )
                $this->paths[$prefix][]  =   $path;
        }
        else {
            $path    =  rtrim( $info->getRealPath(), DIRECTORY_SEPARATOR );

            // Outside chroot: not allowed
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
     * @param   string  $filename   file name
     * @return  string
     **/
    public function load( $filename ) {
        if( !is_string( $filename ) )
            return;

        $this->logMessage( "Loading {$filename}" );

        // Clean-up Namespace prefix
        $filename    =  trim( $filename, '\\' );

        // Load explicit file
        if( isset( $this->files[$filename] ) ) {
            if( FALSE !== ( $data = $this->loadFile( $this->files[$filename] ) ) ) {
                $this->logMessage( "Loaded from explicit: {$this->files[$filename]}" );

                $this->loaded[]  =  $this->files[$filename];

                return $data;
            }
        }

        $chunks      =  explode( '\\', $filename );
        $filename    =  array_pop( $chunks );
        $count       =  count( $chunks );

        // Work backwards through the namespace names
        for( $i = 0; $i <= $count; $i++ ) {
            // Retain the trailing namespace separator in the prefix
            $prefix  =  implode( '\\', array_slice( $chunks, ( 0 - $count ), ( $count - $i ) ) ) . '\\';

            // Try to load a mapped file for the prefix
            if( isset( $this->paths[$prefix] ) ) {
                foreach( $this->paths[$prefix] as $path ) {
                    $file    =  $path . DIRECTORY_SEPARATOR . implode( DIRECTORY_SEPARATOR, $chunks ) . DIRECTORY_SEPARATOR . $filename;

                    // If the mapped file exists, load it
                    if( FALSE !== ( $data = $this->loadFile( $file ) ) ) {
                        // Yes, we're done
                        return $data;
                    }

                    // Not in the base directory
                    $this->logMessage( "{$prefix}: {$file} not found" );
                }
            }
        }

        // Did not find a file for the class
        $this->logMessage( "{$filename} not loaded" );

        return FALSE;
    }

    /**
     * Load the mapped file
     *
     * @param   string  $file       the full path to file
     * @return  mixed               FALSE if the file can't be loaded, or the file content
     **/
    protected function loadFile( $file ) {
        if( file_exists( $file ) && is_readable( $file ) )
            return file_get_contents( $file );

        // Not found
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
     * Log a message onto logger
     *
     * @see \Materia\Debug\Logger::setMessage()
     **/
    public function logMessage( $message, array $params = array() ) {
        if( isset( $this->logger ) )
            $this->logger->logMessage( \Materia\Debug\Logger::INFO, $message, $params );
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
