<?php

namespace Materia\Data;

/**
 * Abstract record class
 *
 * @package Materia.Data
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

abstract class Record implements \ArrayAccess, \Iterator {

	const NAME			 =	NULL;
	const PREFIX		 =	NULL;
	const PRIMARY_KEY	 =	NULL;

	protected $data	 =	[];

	/**
	 * Constructor
	 *
	 * @param	array	$data	initial data
	 **/
	public final function __construct( array $data = [] ) {
		foreach( $data as $offset => $value ) {
			$this->offsetSet( $offset, $value );
		}

		reset( $this->data );
	}

	/**
	 * Object cloning
	 **/
	public function __clone() {
		$this->offsetUnset( static::PRIMARY_KEY );
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
	 * Return the string representation of the record
	 **/
	public function __toString() {
		return isset( $this->data[static::PRIMARY_KEY] ) ? $this->data[static::PRIMARY_KEY] : NULL;
	}

	/**
	 * @see	ArrayAccess::offsetGet()
	 **/
	public function offsetGet( $offset ) {
		// Remove the prefix
		if( static::PREFIX && ( strpos( $offset, static::PREFIX ) === 0 ) ) {
			$offset	 =	substr( $offset, strlen( static::PREFIX ) );
		}

		return isset( $this->data[$offset] ) ? $this->data[$offset] : NULL;
	}

	/**
	 * @see	ArrayAccess::offsetSet()
	 **/
	public function offsetSet( $offset, $value ) {
		// Only strings please
		if( !is_string( $offset ) ) {
			throw new \InvalidArgumentException( 'Argument 1 passed to ' . __METHOD__ . ' must be a string, ' . gettype( $offset ) . ' given' );
		}

		// Non-scalar values are not allowed
		if( !is_scalar( $value ) && !is_null( $value ) && !( $value instanceof Record ) ) {
			throw new \InvalidArgumentException( 'Argument 2 passed to ' . __METHOD__ . ' must be scalar or NULL, ' . gettype( $value ) . ' given' );
		}

		// Remove the prefix
		if( static::PREFIX && ( strpos( $offset, static::PREFIX ) === 0 ) ) {
			$offset	 =	substr( $offset, strlen( static::PREFIX ) );
		}

		// Out-of-bound
		if( ( $offset != static::PRIMARY_KEY ) && !$this->getInfo( $offset ) ) {
        	throw new \OutOfBoundsException( "The offset {$offset} is out of bounds" );
        }

		 $this->data[$offset]	 =	$value;
	}

	/**
	 * @see	ArrayAccess::offsetUnset()
	 **/
	public function offsetUnset( $offset ) {
		// Remove the prefix
		if( static::PREFIX && ( strpos( $offset, static::PREFIX ) === 0 ) ) {
			$offset	 =	substr( $offset, strlen( static::PREFIX ) );
		}

		if( isset( $this->data[$offset] ) ) {
			unset( $this->data[$offset] );
		}
	}

	/**
	 * @see	ArrayAccess::offsetExists()
	 **/
	public function offsetExists( $offset ) {
		// Remove the prefix
		if( static::PREFIX && ( strpos( $offset, static::PREFIX ) === 0 ) ) {
			$offset	 =	substr( $offset, strlen( static::PREFIX ) );
		}

		return isset( $this->data[$offset] );
	}

	/**
	 * @see	Iterator::current()
	 **/
	public function current() {
		return current( $this->data );
	}

	/**
	 * @see	Iterator::key()
	 **/
	public function key() {
		return static::PREFIX . key( $this->data );
	}

	/**
	 * @see	Iterator::next()
	 **/
	public function next() {
		return next( $this->data );
	}

	/**
	 * @see	Iterator::rewind()
	 **/
	public function rewind() {
		return reset( $this->data );
	}

	/**
	 * @see	Iterator::valid()
	 **/
	public function valid() {
		return key( $this->data ) !== NULL;
	}

	/**
	 * Returns schema or field information
	 *
	 * @param	string	$offset			field name (optional)
	 * @return	array
	 **/
	abstract public function getInfo( $offset = NULL );

	/**
	 * Returns whatever the record is a old record or not
	 *
	 * @return  boolean
	 **/
	public function isUpdated() {
		if( static::PRIMARY_KEY ) {
			return $this->offsetExists( static::PRIMARY_KEY );
		}

		return FALSE;
	}

	/**
	 * Compare two records and returns the difference
	 *
	 * @param	Record	$record		record to compare
	 * @return	array
	 **/
	public function compare( Record $record ) {
		// We can only compare the same Records
		if( get_class( $this ) != get_class( $record ) ) {
			throw new \InvalidArgumentException( 'Argument 1 passed to ' . __METHOD__ . ' must be an instance of ' . get_class( $this ) . ', ' . get_class( $record ) . ' given' );
		}

		$a	 =	$this->data;
		$b	 =	$record->data;

		// Remove the PKs
		if( isset( $a[static::PRIMARY_KEY] ) ) {
			unset( $a[static::PRIMARY_KEY] );
		}

		if( isset( $b[static::PRIMARY_KEY] ) ) {
			unset( $b[static::PRIMARY_KEY] );
		}

		$difference	 =	array_diff_assoc( $a, $b );

		return $difference;
	}

	/**
	 * Returns the name of the record
	 *
	 * @return  string
	 **/
	public function getRecordName() {
		return static::NAME;
	}

	/**
	 * Returns the name of the Primary Key
	 *
	 * @param	boolean	$prefix		if TRUE, prepend the field prefix
	 * @return  string
	 **/
	public function getPrimaryKey( $prefix = FALSE ) {
		return $prefix ? static::PREFIX . static::PRIMARY_KEY : static::PRIMARY_KEY;
	}

	/**
	 * Returns the prefix for fields
	 *
	 * @return  string
	 **/
	public function getFieldPrefix() {
		return static::PREFIX;
	}

}