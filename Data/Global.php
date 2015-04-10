<?php

namespace Materia\Data;

/**
 * Globals interface
 *
 * @package Materia.Data
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

interface Global extends \ArrayAccess {

    /**
     * Set validation service
     *
     * @param   mixed       $offset     key or array of keys to validate
     * @param   Validator   $validator
     * @return  $this
     **/
    public function setValidator( $offset, Validator $validator );

}