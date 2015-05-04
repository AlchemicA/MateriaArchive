<?php

namespace Materia\Data\Validators;

/**
 * Number validation class
 *
 * @package Materia.Data
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class Number implements \Materia\Data\Validator {

    const INTEGER        =  'integer';

    protected $conditions    =  [];
    protected $precision;

    /**
     * Constructor
     *
     * @param   integer $precision      number of decimal digits, default PHP limits
     **/
    public function __construct( $precision = NULL ) {
        if( is_integer( $precision ) ) {
            $this->precision     =  $precision;
        }
    }

    /**
     * @see \Materia\Data\Validator::isValid()
     **/
    public function isValid( $value, $default = FALSE ) {
        // Must be numeric
        if( !is_numeric( $value ) ) {
            return FALSE;
        }

        // Out of bounds
        if( isset( $this->precision ) ) {
            $digits  =  strlen( substr( strrchr( "{$value}", '.' ), 1 ) );

            if( $digits > $this->precision ) {
                return FALSE;
            }
        }

        // Process others
        foreach( $this->conditions as $key => $condition ) {
            switch( $key ) {
                case 'range':
                    list( $min, $max ) = $condition;

                    if( !( ( $value >= $min ) && ( $value <= $max ) ) ) {
                        return FALSE;
                    }

                    break;

                case 'integer':
                    if( $condition && !is_integer( $value ) ) {
                        return FALSE;
                    }
                    else if( !$condition && is_integer( $value ) ) {
                        return FALSE;
                    }

                    break;
            }
        }

        return TRUE;
    }

    /**
     * Set range limit
     *
     * @param   integer $min    minimum allowed value
     * @param   integer $max    maximum allowed value
     **/
    public function range( $min, $max ) {
        $this->conditions['range']   =  [ $min, $max ];
    }

    /**
     * Set "must be" condition(s)
     *
     * @param   integer $what   condition's constant(s)
     **/
    public function is( $what ) {
        if( $what & self::INTEGER ) {
            $this->conditions['integer']     =  TRUE;
        }
    }

    /**
     * Set "must NOT be" condition(s)
     *
     * @param   integer $what   condition's constant(s)
     **/
    public function not( $what ) {
        if( $what & self::INTEGER ) {
            $this->conditions['integer']     =  FALSE;
        }
    }

}