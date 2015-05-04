<?php

namespace Materia\Data\Validators;

/**
 * String validation class
 *
 * @package Materia.Data
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class String implements \Materia\Data\Validator {

    const ALPHANUMERIC   =  'alphanumeric';
    const EMAIL          =  'email';
    const URL            =  'url';
    const IP_ADDRESS     =  'ip';

    protected $conditions    =  [];
    protected $strict        =  FALSE;

    /**
     * Constructor
     *
     * @param   boolean $strict     strict type check
     **/
    public function __construct( $strict = FALSE ) {
        $this->strict    =  $strict ? TRUE : FALSE;
    }

    /**
     * @see \Materia\Data\Validator::isValid()
     **/
    public function isValid( $value, $default = FALSE ) {
        if( $this->strict ) {
            if( !is_string( $value ) && !is_null( $value ) ) {
                return FALSE;
            }
        }
        else {
            if( !is_scalar( $value ) && !is_null( $value ) ) {
                return FALSE;
            }
        }

       foreach( $this->conditions as $key => $condition ) {
            switch( $key ) {
                case 'length':
                    list( $min, $max ) = $condition;

                    // Convert numeric, boolean and NULL to string
                    $length  =  mb_strlen( "{$value}" );

                    if( !( ( $length >= $min ) && ( $length <= $max ) ) ) {
                        return FALSE;
                    }

                    break;

                case 'regex':
                    if( !preg_match( $condition, $value ) ) {
                        return FALSE;
                    }

                    break;

                case 'is':
                    switch( $condition ) {
                        case self::ALPHANUMERIC:
                            if( preg_match( '#[^a-z0-9]#is', $value ) ) {
                                return FALSE;
                            }

                            break;

                        case self::EMAIL:
                            if( !filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
                                return FALSE;
                            }

                            break;

                        case self::URL:
                            if( !filter_var( $value, FILTER_VALIDATE_URL ) ) {
                                return FALSE;
                            }

                            break;

                        case self::IP_ADDRESS:
                            if( !filter_var( $value, FILTER_VALIDATE_IP, ( FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 ) ) ) {
                                return FALSE;
                            }

                            break;
                    }

                    break;
            }
        }

        return TRUE;
    }

    /**
     * Set length limit
     *
     * @param   integer $min    minimum allowed value
     * @param   integer $max    maximum allowed value
     **/
    public function length( $min, $max ) {
        $this->conditions['length']  =  [ intval( $min ), intval( $max ) ];
    }

    /**
     * Set regular expression pattern
     *
     * @param   string  $regex  regular expression
     **/
    public function regex( $regex ) {
        $this->conditions['regex']   =  $regex;
    }

    /**
     * Set "must be" condition
     *
     * @param   string  $what   condition's constant
     **/
    public function is( $what ) {
        $this->conditions['is']  =  $what;
    }

    /**
     * Set "must NOT be" condition
     *
     * @param   string  $what   condition's constant
     **/
    public function not( $what ) {
    }

}