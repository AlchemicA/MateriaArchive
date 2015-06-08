<?php

namespace Materia\Data;

/**
 * Formatter interface
 *
 * @package Materia.Data
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

interface Formatter {

    /**
     * Encode to string
     *
     * @param   array   $data       array to be encoded
     * @return  mixed               encoded data into string or FALSE on error
     **/
    public function encode( array $data );

    /**
     * Decode from string
     *
     * @param   string  $data       encoded string
     * @return  mixed               decoded string into array or FALSE on error
     **/
    public function decode( $data );

    /**
     * Merge two (or more) entities
     *
     * @param   mixed   $one        first entity to merge
     * @param   mixed   $two        second entity to merge
     * @return  mixed               merged entities or FALSE on error
     **/
    public function merge( $one, $two );

    /**
     * Returns latest error
     *
     * @return  mixed               latest error code of FALSE if none
     **/
    public function getError();

}
