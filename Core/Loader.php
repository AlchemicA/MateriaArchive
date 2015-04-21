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

    protected $loaded    =  [];

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
     * @see \Materia\Filesystem\Loader::load()
     **/
    public function load( $class ) {
        // Append the file's extension and try to load it
        if( $file = $this->locate( $class . '.php' ) ) {
            require_once( $file );

            $this->loaded[$class]    =  $file;

            return TRUE;
        }

        return FALSE;
    }

    /**
     * Returns the list of classes, interfaces, and traits loaded by the autoloader
     *
     * @return  array       an associative array of class or interface names and their file name
     **/
    public function getLoaded() {
        return $this->loaded;
    }

}
