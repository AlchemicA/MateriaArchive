<?php

namespace Materia\Core;

/**
 * An SPL autoloader adhering to PSR-4
 *
 * @package Materia.Core
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class Loader extends \Materia\Filesystem\Loader {

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
     * Loads the class file for a given class name
     *
     * @param   string  $class      the fully-qualified class name
     * @return  mixed               the mapped file name on success or FALSE on failure
     **/
    public function load( $class ) {
        if( isset( $this->logger ) )
            $this->logger->logMessage( \Materia\Debug\Logger::INFO, "Loading {$class}" );

        // is an explicit class file noted?
        if( isset( $this->files[$class] ) ) {
            $file    =  $this->files[$class];

            if( $this->requireFile( $file ) ) {
                if( isset( $this->logger ) )
                    $this->logger->logMessage( \Materia\Debug\Logger::INFO, "Loaded from explicit: {$file}" );

                $this->loaded[$class]    =  $file;

                return TRUE;
            }
        }

        // the current namespace prefix
        $prefix  =  '\\' . trim( $class, '\\' );

        // work backwards through the namespace names of the fully-qualified
        // class name to find a mapped file name
        while( FALSE !== ( $pos = strrpos( $prefix, '\\' ) ) ) {
            // retain the trailing namespace separator in the prefix
            $prefix  =  substr( $prefix, 0, ( $pos + 1 ) );

            // try to load a mapped file for the prefix and relative class
            if( $file = $this->loadFile( $prefix, substr( $class, $pos ) ) ) {
                if( isset( $this->logger ) )
                    $this->logger->logMessage( \Materia\Debug\Logger::INFO, "Loaded from {$prefix}: {$file}" );

                $this->loaded[$class]    =  $file;

                return TRUE;
            }

            // remove the trailing namespace separator for the next iteration of strrpos()
            $prefix  =  rtrim( $prefix, '\\' );
        }

        // did not find a file for the class
        if( isset( $this->logger ) )
            $this->logger->logMessage( \Materia\Debug\Logger::INFO, "{$class} not loaded" );

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
            if( isset( $this->logger ) )
                $this->logger->logMessage( \Materia\Debug\Logger::INFO, "{$prefix}: no base dirs" );

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
            if( isset( $this->logger ) )
                $this->logger->logMessage( \Materia\Debug\Logger::INFO, "{$prefix}: {$file} not found" );
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
