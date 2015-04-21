<?php

namespace Materia\Filesystem;

/**
 * A simple file loader
 *
 * @package Materia.Filesystem
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class Loader extends Locator {

     /**
     * Load the mapped file
     *
     * @param   string  $file       the file to load
     * @return  mixed               FALSE if the file can't be loaded, or the file content
     **/
    public function load( $filename ) {
        if( $file = $this->locate( $filename ) ) {
            return file_get_contents( $file );
        }

        // Not found
        return FALSE;
    }

}
