<?php

namespace Materia\Data;

/**
 * Data validation interface
 *
 * @package Materia.Data
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

interface Validator {

    /**
     * Returns whatever a value is valid or not
     *
     * @param   mixed   $value
     * @return  boolean
     **/
    public function isValid( $value );

}
