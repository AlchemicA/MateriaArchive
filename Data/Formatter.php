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
     * @return  string
     **/
    public function encode( array $data );

    /**
     * Decode from string
     *
     * @param   string  $data       encoded string
     * @return  array
     **/
    public function decode( $data );

    /**
     * Merge two (or more) entities
     *
     * @param   mixed   $one        first entity to merge
     * @param   mixed   $two        second entity to merge
     * @return  mixed
     **/
    public function merge( $one, $two );

}
