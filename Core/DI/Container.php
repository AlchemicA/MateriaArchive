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

	protected $settings		 =	[];
	protected $instances	 =	[];

	/**
	 * Destructor
	 **/
	public function __destruct() {
		$this->instances	 =	[];
		$this->settings		 =	[];
	}

	/**
	 * Set the value of a property
	 *
	 * @param	string	$key	property name
	 * @param	string	$value	property value
	 **/
	public function __set( $key, $value ) {
		if( is_object( $value ) ) {
			$this->instances[$key]	 =	$value;
		}
		else {
			throw new \InvalidArgumentException( sprintf( '%s accepts only objects as properties, %s given', array( __CLASS__, gettype( $value ) ) ) );
		}
	}

	/**
	 * Get the value of a property
	 *
	 * @param	string	$key	property name
	 * @return	mixed			property value
	 **/
	public function __get( $key ) {
		if( isset( $this->instances[$key] ) ) {
			if( is_object( $this->instances[$key] ) && ( $this->instances[$key] instanceof \Closure ) ) {
				return $this->instances[$key]();
			}
			else {
				return $this->instances[$key];
			}
		}
	}

	/**
	 * Unset a property
	 *
	 * @param	string	$key	property name
	 **/
	public function __unset( $key ) {
		if( isset( $this->instances[$key] ) ) {
			unset( $this->instances[$key] );
		}
	}

	/**
	 * Checks if a property exists
	 *
	 * @param	string	$key	property name
	 * @return	boolean
	 **/
	public function __isset( $key ) {
		return isset( $this->instances[$key] );
	}

	/**
	 * @see	ArrayAccess::offsetSet()
	 **/
	public function offsetSet( $offset, $value ) {
		if( is_scalar( $value ) || is_null( $value ) ) {
			$this->settings[$offset]	 =	$value;
		}
	}

	/**
	 * @see	ArrayAccess::offsetGet()
	 **/
	public function offsetGet( $offset ) {
		if( isset( $this->settings[$offset] ) ) {
			return $this->settings[$offset];
		}
	}

	/**
	 * @see	ArrayAccess::offsetExists()
	 **/
	public function offsetExists( $offset ) {
		return isset( $this->settings[$offset] );
	}

	/**
	 * @see	ArrayAccess::offsetUnset()
	 **/
	public function offsetUnset( $offset ) {
		if( isset( $this->settings[$offset] ) ) {
			unset( $this->settings[$offset] );
		}
	}

}