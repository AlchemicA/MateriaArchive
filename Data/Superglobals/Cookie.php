<?php

namespace Materia\Data\Superglobals;

/**
 * Filter cookies
 *
 * @package Materia.Data
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class Cookie implements \Materia\Data\Superglobal {

	protected $data			 =	array();

	protected $expire;
	protected $path;
	protected $domain;
	protected $secure;
	protected $httponly;
	protected $formatter;

	/**
	 * Constructor
	 *
	 * @param	integer	$expire		the time the cookie expires
	 * @param	string	$path		the path on the server in which the cookie will be available on
	 * @param	string	$domain		the domain that the cookie is available to
	 * @param	boolean	$secure		indicates that the cookie should only be transmitted over a secure HTTPS connection from the client
	 * @param	boolean	$httponly	makes the cookie accessible only through the HTTP protocol
	 **/
	public function __construct( $expire = 0, $path = NULL, $domain = NULL, $secure = FALSE, $httponly = FALSE ) {
		$this->expire		 =	!empty( $expire ) ? time() + $expire : 0;
		$this->path			 =	$path;
		$this->domain		 =	$domain;
		$this->secure		 =	$secure ? TRUE : FALSE;
		$this->httponly		 =	$httponly ? TRUE : FALSE;
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
		if( isset( $this->formatter ) )
			$value	 =	$this->formatter->encode( $value );

		@setcookie( $offset, $value, $this->expire, $this->path, $this->domain, $this->secure, $this->httponly );
	}

	/**
	 * @see	ArrayAccess::offsetGet()
	 **/
	public function offsetGet( $offset ) {
		if( isset( $this->data[$offset] ) ) {
			if( isset( $this->formatter ) )
				return $this->formatter->decode( filter_input( INPUT_COOKIE, $offset ) );
			else
				return filter_input( INPUT_COOKIE, $offset );
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
		if( isset( $this->data[$offset] ) ) {
			@setcookie( $offset, NULL, ( time() - 3600 ), $this->path, $this->domain, $this->secure, $this->httponly );

			unset( $this->data[$offset] );
		}
	}

	/**
	 * @see \Materia\Data\Global::setValidator()
	 **/
	public function setValidator( $offset, \Materia\Data\Validator $validator ) {
		if( is_array( $offset ) ) {
			foreach( $offset as $key ) {
				$this->setValidator( $key, $validator );
			}
		}
		else {
			if( filter_has_var( INPUT_COOKIE, $offset ) && $validator->isValid( filter_input( INPUT_COOKIE, $offset ) ) ) {
				if( isset( $this->formatter ) )
					$this->data[$offset]	 =	$this->formatter->decode( filter_input( INPUT_COOKIE, $offset ) );
				else
					$this->data[$offset]	 =	filter_input( INPUT_COOKIE, $offset );
			}
		}

		return $this;
	}

	/**
	 * Set data encoder/decoder (encryption)
	 *
	 * @param	\Materia\Data\Formatter		$formatter
	 * @return	$this
	 **/
	public function setFormatter( \Materia\Data\Formatter $formatter ) {
		$this->formatter	 =	$formatter;

		return $this;
	}

}