<?php

namespace Materia\Network\Rest;

/**
 * RestFul client class
 *
 * @package Materia.Network
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class Client extends \Materia\Network\Request {

	protected $formatter;

	protected $headers	 =	array();

	/**
	 * Set headers
	 *
	 * @param	array	$headers
	 * @return	$this
	 **/
	public function setHeaders( array $headers ) {
		$this->headers	 =	$headers;

		return $this;
	}

	/**
	 * Performs the request
	 *
	 * @param	string	$type		the type of the request
	 * @param	string	$url		the URL for the request
	 * @param	array	$params		additional parameters
	 * @return	array
	 **/
	public function execute() {
		$url	 =	$this->buildURL();
		$curl	 =	curl_init();

		// Auth
		if( !is_null( $this->user ) ) {
		   curl_setopt( $curl, CURLOPT_USERPWD, $this->user . ':' . $this->pass );
		}

		switch( $this->method ) {
			// DELETE
			case 'DELETE':
				curl_setopt( $curl, CURLOPT_URL, $url );
				curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'DELETE' );
				break;

			// PUT & PATCH
			case 'PUT':
			case 'PATCH':
				curl_setopt( $curl, CURLOPT_URL, $url );
				curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, $this->method );

				if( isset( $this->data ) )
					curl_setopt( $curl, CURLOPT_POSTFIELDS, $this->data );

				break;

			// POST
			case 'POST':
				curl_setopt( $curl, CURLOPT_URL, $url );
				curl_setopt( $curl, CURLOPT_POST, TRUE );

				if( isset( $this->data ) )
					curl_setopt( $curl, CURLOPT_POSTFIELDS, $this->data );

				break;

			// GET
			case 'GET':
				curl_setopt( $curl, CURLOPT_URL, $url );
				break;
		}

		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, $this->headers );

		$body	 =	curl_exec( $curl );
		$status	 =	curl_getinfo( $curl, CURLINFO_HTTP_CODE );

		curl_close( $curl );

		if( !isset( $this->response ) )
			$this->response	 =	new \Materia\Network\Response();

		$this->response
			->setStatus( $status )
			->setBody( trim( $body ), FALSE );

		return $this->response;
	}

}