<?php

namespace Materia\Filesystem;

/**
 * File locator class
 *
 * @package Materia.Filesystem
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class Locator {

    protected $chroot;
    protected $logger;

    protected $files     =  [];
    protected $paths     =  [];

    /**
     * Constructor
     *
     * @param   \SplFileInfo    $chroot     base path
     * @param   boolean         $register   register $chroot as path
     **/
    public function __construct( \SplFileInfo $chroot, $register = FALSE ) {
        if( !$chroot->isDir() ) {
            throw new \InvalidArgumentException( sprintf( 'Invalid base path %s', $chroot->getRealPath() ) );
        }

        if( !$chroot->isReadable() ) {
            throw new \InvalidArgumentException( sprintf( 'Path %s is not readable', $chroot->getRealPath() ) );
        }

        // Normalize path
        $this->chroot    =  rtrim( $chroot->getRealPath(), DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;

        // Register $chroot as path
        if( $register ) {
            $this->setPath( $chroot );
        }
    }

    /**
     * Adds a base directory for a namespace prefix
     *
     * @param   SplFileInfo $info       path for the given namespace or file
     * @param   string      $prefix     the namespace prefix
     * @return  self
     **/
    public function setPath( \SplFileInfo $info, $priority = 0, $prefix = '\\' ) {
        if( !$info->isReadable() ) {
            return $this;
        }

        if( !is_int( $priority ) ) {
            $priority    =  0;
        }

        // Normalize path
        if( $info->isDir() ) {
            $path    =  rtrim( $info->getRealPath(), DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
        }
        else {
            $path    =  $info->getRealPath();
        }

        // Disallowed path
        if( strpos( $path, $this->chroot ) !== 0 ) {
            return $this;
        }

        if( $info->isDir() ) {
            // Normalize the namespace prefix
            $prefix  =  trim( $prefix, '\\' ) . '\\';

            // Initialize the namespace prefix array if needed
            if( !isset( $this->paths[$prefix] ) ) {
                $this->paths[$prefix]    =  array();
            }

            // Add if not present
            if( !in_array( $path, $this->paths[$prefix] ) ) {
                $this->paths[$prefix][]  =   $path;
            }
        }
        else {
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
     * Load a specific file
     *
     * @param   string  $filename   file name
     * @return  string
     **/
    public function locate( $filename ) {
        if( !is_string( $filename ) )
            return FALSE;

        $this->logMessage( 'Locating %s', [ $filename ] );

        // Clean-up namespace prefix
        $filename    =  trim( $filename, '\\' );

        // Explicit file
        if( isset( $this->files[$filename] ) ) {
            if( is_readable( $this->files[$filename] ) ) {
                $this->logMessage( 'Located from explicit: %s', [ $this->files[$filename] ] );

                return $this->files[$filename];
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

                    // If the mapped file exists, return its path
                    if( is_readable( $file ) ) {
                        return $file;
                    }

                    // Not in the base directory
                    $this->logMessage( '%s: %s not found', array( $prefix, $file ) );
                }
            }
        }

        // Did not find any file
        $this->logMessage( '%s not loaded', array( $filename ) );

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
    protected function logMessage( $message, array $params = [] ) {
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
