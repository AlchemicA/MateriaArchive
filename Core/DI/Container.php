<?php

namespace Materia\Core\DI;

/**
 * Simple implementation of the Dependency Injection pattern
 *
 * @package Materia.Core
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class Container implements \ArrayAccess {

	protected $storage	 =	[];

	/**
	 * Destructor
	 **/
	public function __destruct() {
		$this->storage	 =	[];
	}

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
		$this->storage[$offset]	 =	$value;
	}

	/**
	 * @see	ArrayAccess::offsetGet()
	 **/
	public function offsetGet( $offset ) {
		if( isset( $this->storage[$offset] ) ) {
			if( is_object( $this->storage[$offset] ) && ( $this->storage[$offset] instanceof \Closure ) ) {
				$this->storage[$offset]	 =	$this->storage[$offset]();
			}

			return $this->storage[$offset];
		}
	}

	/**
	 * @see	ArrayAccess::offsetExists()
	 **/
	public function offsetExists( $offset ) {
		return isset( $this->storage[$offset] );
	}

	/**
	 * @see	ArrayAccess::offsetUnset()
	 **/
	public function offsetUnset( $offset ) {
		if( isset( $this->storage[$offset] ) ) {
			unset( $this->storage[$offset] );
		}
	}

}