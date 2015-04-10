<?php

namespace Materia\Data;

/**
 * Collection interface
 *
 * @package Materia.Data
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

interface Collection extends \Iterator {

    /**
     * Returns collection's type
     *
     * @return  string      class name or NULL if not defined (empty collection)
     **/
    public function getType();

    /**
     * Returns number of items
     *
     * @return  integer
     **/
    public function count();

    /**
     * Reverse the order of the elements
     **/
    public function reverse();

}