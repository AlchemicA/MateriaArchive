<?php

namespace Materia\Network;

/**
 * Request class
 *
 * @package Materia.Network
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class Request {

	protected $method;
	protected $scheme;
	protected $host;
	protected $port;
	protected $user;
	protected $pass;
	protected $path;
	protected $data;
	protected $fragment;
	protected $agent;
	protected $ajax;
	protected $response;

	/**
	 * Constructor
	 **/
	public function __construct( $globals = FALSE ) {
		// Set defaults
		$this->scheme	 =	'http';
		$this->method	 =	'GET';
		$this->ajax		 =	isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ? ( $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' ) : FALSE;

		if( $globals ) {
			if( isset( $_SERVER['SERVER_PROTOCOL'] )  && ( strpos( $_SERVER['SERVER_PROTOCOL'], 'HTTPS' ) !== FALSE ) )
				$this->setScheme( 'https' );

			if( isset( $_SERVER['HTTP_HOST'] ) )
				$this->setHost( $_SERVER['HTTP_HOST'] );

			if( isset( $_SERVER['REQUEST_METHOD'] ) )
				$this->setMethod( $_SERVER['REQUEST_METHOD'] );

			if( isset( $_SERVER['SERVER_PORT'] ) )
				$this->setPort( $_SERVER['SERVER_PORT'] );

			if( isset( $_SERVER['REQUEST_URI'] ) ) {
				$script	 =	str_replace( '\\', '/', $_SERVER['SCRIPT_NAME'] );
				$pos	 =	$script ? strpos( $_SERVER['REQUEST_URI'], $script ) : FALSE;

				if( $pos === 0 )
					$this->setPath( substr( $_SERVER['REQUEST_URI'], strlen( $script ) ) );
				else
					$this->setPath( $_SERVER['REQUEST_URI'] );
			}

			if( isset( $_SERVER['QUERY_STRING'] ) )
				$this->setQuery( $_SERVER['QUERY_STRING'] );

			if( isset( $_SERVER['HTTP_USER_AGENT'] ) )
				$this->setUserAgent( $_SERVER['HTTP_USER_AGENT'] );
		}
	}

	/**
	 * Set request host
	 *
	 * @param	string	$host		host name
	 * @return	$this
	 **/
	public function setHost( $host ) {
		$this->host	 =	(string) $host;

		return $this;
	}

	/**
	 * Get request host
	 *
	 * @return	string
	 **/
	public function getHost() {
		return $this->host;
	}

	/**
	 * Set port numbner
	 *
	 * @param	integer	$port		port number
	 * @return	$this
	 **/
	public function setPort( $port ) {
		$this->port	 =	intval( $port );

		return $this;
	}

	/**
	 * Get port number
	 *
	 * @return	integer
	 **/
	public function getPort() {
		return $this->port;
	}

	/**
	 * Set scheme
	 *
	 * @param	string	$scheme		scheme
	 * @return	$this
	 **/
	public function setScheme( $scheme ) {
		if( in_array( $scheme, array( 'http', 'https' ) ) )
			$this->scheme	 =	$scheme;

		return $this;
	}

	/**
	 * Get scheme
	 *
	 * @return	string
	 **/
	public function getScheme() {
		return $this->scheme;
	}

	/**
	 * Set username and password
	 *
	 * @param	string	$user	user name
	 * @param	string	$pass	password
	 * @return	$this
	 **/
	public function setAuth( $user, $pass ) {
		$this->user	 =	(string) $user;
		$this->pass	 =	(string) $pass;

		return $this;
	}

	/**
	 * Set request method
	 *
	 * @param	string	$method		request method
	 * @return	$this
	 **/
	public function setMethod( $method ) {
		if( in_array( $method, array( 'GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS' ) ) )
			$this->method	 =	$method;

		return $this;
	}

	/**
	 * Get request method
	 *
	 * @return	string
	 **/
	public function getMethod() {
		return $this->method;
	}


	/**
	 * Set request path
	 *
	 * @param	string	$path		request path
	 * @return	$this
	 **/
	public function setPath( $path ) {
		$this->path	 =	$path;

		return $this;
	}

	/**
	 * Get request path
	 *
	 * @return	string
	 **/
	public function getPath() {
		return $this->path;
	}

	/**
	 * Set request data
	 *
	 * @param	array	$data		request data
	 * @return	$this
	 **/
	public function setData( array $data ) {
		$this->data	 =	$data;

		return $this;
	}

	/**
	 * Get data
	 *
	 * @return	array
	 **/
	public function getData() {
		return $this->data;
	}

	/**
	 * Set query fragment
	 *
	 * @param	string	$fragment	fragment
	 * @return	$this
	 **/
	public function setFragment( $fragment ) {
		$this->fragment	 =	ltrim( $fragment, '#' );

		return $this;
	}

	/**
	 * Get query fragment
	 *
	 * @return	string
	 **/
	public function getFragment() {
		return $this->fragment;
	}

	/**
	 * Set User Agent
	 *
	 * @param	string	$agent	User Agent string
	 * @return	$this
	 **/
	public function setUserAgent( $agent ) {
		$this->agent	 =	filter_var( $agent, FILTER_SANITIZE_STRING, ( FILTER_FLAG_NO_ENCODE_QUOTES | FILTER_FLAG_STRIP_LOW ) );

		return $this;
	}

	/**
	 * Get User Agent
	 *
	 * @return	string
	 **/
	public function getUserAgent() {
		return $this->agent;
	}

	/**
	 * Set Response instance
	 *
	 * @param	Response	$response	Response instance
	 * @return	$this
	 **/
	public function setResponse( Response $response ) {
		$this->response	 =	$response;

		return $this;
	}

	/**
	 * Get Response instance
	 *
	 * @return	string
	 **/
	public function &getResponse() {
		return $this->response;
	}

	/**
	 * Build request URL
	 *
	 * @return	string
	 **/
	public function buildURL() {
		$url	 =	NULL;

		if( isset( $this->scheme ) )
			$url	.=	$this->scheme . '://';

		if( isset( $this->user ) ) {
			$url	.=	$this->user;

			if( isset( $this->pass ) && $this->pass )
				$url	.=	':' . $this->pass;

			$url	.=	'@';
		}

		if( isset( $this->host ) )
			$url	.=	rtrim( $this->host, '/' );

		if( isset( $this->port ) && $this->port )
			$url	.=	':' . $this->port;

		if( isset( $this->path ) && $this->path )
			$url	.=	'/' . ltrim( $this->path, '/' );

		if( !in_array( $this->method, array( 'POST', 'PUT' ) ) && isset( $this->data ) && $this->data )
			$url	.=	'?' . http_build_query( $this->data );

		if( isset( $this->fragment ) && $this->fragment )
			$url	.=	'#' . $this->fragment;

		return $url;
	}

}