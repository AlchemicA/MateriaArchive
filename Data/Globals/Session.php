<?php

namespace Materia\Data\Globals;

/**
 * Session handler
 *
 * @package Materia.Data
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class Session extends \Materia\Data\Record implements \Materia\Security\Globals, \SessionHandlerInterface {

	protected $data		 =	array();

	protected $storage;

	public function __construct( \Materia\Data\Storage &$storage ) {
		if( !is_array( $_SESSION ) )
			throw new \RuntimeException( 'Unable to initialize session' );

		$this->storage	 =	$storage;
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
		throw new RuntimeException( 'Attempt to write a read-only object' );
	}

	/**
	 * @see	ArrayAccess::offsetGet()
	 **/
	public function offsetGet( $offset ) {
		if( isset( $this->data[$offset] ) ) {
			return filter_input( INPUT_POST, $offset );
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
	 * @see	\Materia\Data\Record::getInfo()
	 **/
	public function getInfo( $offset = NULL ) {
		$schema	 =	array(
			'id'	=>	array(
				'type'		=>	'varchar',
				'size'		=>	32,
				'index'		=>	TRUE,
				'unique'	=>	TRUE,
			),
			'data'	=>	array(
				'type'		=>	'text',
			),
			'created'	=>	array(
				'type'		=>	'integer',
				'index'		=>	TRUE,
			),
			'updated'	=>	array(
				'type'		=>	'integer',
				'index'		=>	TRUE,
			),
		);

		if( !empty( $offset ) )
			return isset( $schema[$offset] ) ? $schema[$offset] : NULL;
		else
			return $schema;
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
			if( filter_has_var( INPUT_POST, $offset ) && $validator->isValid( filter_input( INPUT_POST, $offset ) ) )
				$this->values[$offset]	 =	TRUE;
		}

		return $this;
	}

	/**
	 * @see \SessionHandlerInterface::open()
	 **/
	public open( $path, $name ) {
		return TRUE;
	}

	/**
	 * @see \SessionHandlerInterface::read()
	 **/
	public read( $session ) {
	}

	/**
	 * @see \SessionHandlerInterface::write()
	 **/
	public write( $session, $data ) {
		return TRUE;
	}

	/**
	 * @see \SessionHandlerInterface::close()
	 **/
	public close() {
		return TRUE;
	}

	/**
	 * @see \SessionHandlerInterface::destroy()
	 **/
	public destroy( $session ) {
		$record	 =	$this->storage->load( $session );

		$this->storage->remove( $record );

		return TRUE;
	}

	/**
	 * @see \SessionHandlerInterface::gc()
	 **/
	public gc( $life ) {
		$finder	 =	new \Materia\Data\Finder( __CLASS__ );
		$finder->filter( 'updated', '<', time() - 900 );

		return TRUE;
	}

}