<?php

namespace Materia\Core;

/**
 * View class
 *
 * @package Materia.Core
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class View implements \ArrayAccess {

    protected $data  =  array();

    /**
     * Set the value of a property
     *
     * @param   string  $key    property name
     * @param   string  $value  property value
     **/
    public function __set( $key, $value ) {
        $this->offsetSet( $key, $value );
    }

    /**
     * Get the value of a property
     *
     * @param   string  $key    property name
     * @return  mixed           property value
     **/
    public function __get( $key ) {
        return $this->offsetGet( $key );
    }

    /**
     * Unset a property
     *
     * @param   string  $key    property name
     **/
    public function __unset( $key ) {
        $this->offsetUnset( $key );
    }

    /**
     * Checks if a property exists
     *
     * @param   string  $key    property name
     * @return  boolean
     **/
    public function __isset( $key ) {
        return $this->offsetExists( $key );
    }

    /**
     * @see ArrayAccess::offsetSet()
     **/
    public function offsetSet( $offset, $value ) {
        $this->data[$offset]     =  $value;
    }

    /**
     * @see ArrayAccess::offsetGet()
     **/
    public function offsetGet( $offset ) {
        if( isset( $this->data[$offset] ) ) {
            return $this->data[$offset];
        }

        return NULL;
    }

    /**
     * @see ArrayAccess::offsetExists()
     **/
    public function offsetExists( $offset ) {
        return isset( $this->data[$offset] );
    }

    /**
     * @see ArrayAccess::offsetUnset()
     **/
    public function offsetUnset( $offset ) {
        if( isset( $this->data[$offset] ) ) {
            unset( $this->data[$offset] );
        }
    }

    /**
     * Render a template
     *
     * @param   string  $markup
     * @return  string
     **/
    public function render( $markup ) {
    }
}
