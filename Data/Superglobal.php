<?php

namespace Materia\Data;

/**
 * Superglobal interface
 *
 * @package Materia.Data
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

interface Superglobal extends \ArrayAccess {

    /**
     * Set validation service
     *
     * @param   mixed       $offset     key or array of keys to validate
     * @param   Validator   $validator
     * @return  $this
     **/
    public function setValidator( $offset, Validator $validator );

}