<?php

namespace Materia\Data\Superglobals;

/**
 * Filter GET
 *
 * @package Materia.Data
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class Get implements \Materia\Data\Superglobal {

	protected $data	 =	array();

	/**
	 * Set the value of a property
	 *
	 * @param	string	$key	property name
	 * @param	string	$value	property value
	 **/
	public function __set( $key, $value ) {
		$this->offsetSet( $key, $value );
	}

	/**
	 * Get the value of a property
	 *
	 * @param	string	$key	property name
	 * @return	mixed			property value
	 **/
	public function __get( $key ) {
		return $this->offsetGet( $key );
	}

	/**
	 * Unset a property
	 *
	 * @param	string	$key	property name
	 **/
	public function __unset( $key ) {
		$this->offsetUnset( $key );
	}

	/**
	 * Checks if a property exists
	 *
	 * @param	string	$key	property name
	 * @return	boolean
	 **/
	public function __isset( $key ) {
		return $this->offsetExists( $key );
	}

	/**
	 * @see	ArrayAccess::offsetSet()
	 **/
	public function offsetSet( $offset, $value ) {
		throw new \RuntimeException( 'Attempt to write a read-only object' );
	}

	/**
	 * @see	ArrayAccess::offsetGet()
	 **/
	public function offsetGet( $offset ) {
		if( isset( $this->data[$offset] ) ) {
			return filter_input( INPUT_GET, $offset );
		}

		return NULL;
	}

	/**
	 * @see	ArrayAccess::offsetExists()
	 **/
	public function offsetExists( $offset ) {
		return isset( $this->data[$offset] );
	}

	/**
	 * @see	ArrayAccess::offsetUnset()
	 **/
	public function offsetUnset( $offset ) {
		if( isset( $this->data[$offset] ) )
			unset( $this->data[$offset] );
	}

	/**
	 * @see \Materia\data\Global::setValidator()
	 **/
	public function setValidator( $offset, \Materia\Data\Validator $validator ) {
		if( is_array( $offset ) ) {
			foreach( $offset as $key ) {
				$this->setValidator( $key, $validator );
			}
		}
		else {
			if( filter_has_var( INPUT_GET, $offset ) && $validator->isValid( filter_input( INPUT_GET, $offset ) ) )
				$this->data[$offset]	 =	TRUE;
		}

		return $this;
	}

}