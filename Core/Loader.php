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
     * @see \Materia\Filesystem\Loader::load()
     **/
    public function load( $class ) {
        // Append the file's extension
        return parent::load( $class . '.php' );
    }

    /**
     * @see \Materia\Filesystem\Loader::loadFile()
     **/
    protected function loadFile( $file ) {
        if( file_exists( $file ) ) {
            require_once $file;

            return TRUE;
        }

        return FALSE;
    }

}
