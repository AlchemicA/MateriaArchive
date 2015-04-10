<?php

namespace Materia\Data\Globals;

/**
 * Filter SERVER global
 *
 * @package Materia.Data
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class Server implements \Materia\Data\Global {

	protected $data;
	protected $type;

	/**
	 * Constructor
	 *
	 * @param	boolean	$server		use INPUT_SERVER instead of INPUT_ENV
	 **/
	public function __construct( $server ) {
		$this->data	 =	array();
		$this->type	 =	$server ? INPUT_SERVER : INPUT_ENV;
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
		throw new \RuntimeException( 'Attempt to write a read-only object' );
	}

	/**
	 * @see	ArrayAccess::offsetGet()
	 **/
	public function offsetGet( $offset ) {
		if( isset( $this->data[$offset] ) ) {
			return filter_input( $this->type, $offset );
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
			if( filter_has_var( $this->type, $offset ) && $validator->isValid( filter_input( $this->type, $offset ) ) )
				$this->data[$offset]	 =	TRUE;
		}

		return $this;
	}

}
