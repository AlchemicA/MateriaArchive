<?php

namespace Materia\Network;

/**
 * Response class
 *
 * @package Materia.Network
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class Response {

	protected $headers;
	protected $status;
	protected $body;
	protected $formatter;

	protected static $codes	 =	[
		200  =>  'OK',
		201  =>  'Created',
		202  =>  'Accepted',
		203  =>  'Non-Authoritative Information',
		204  =>  'No Content',
		205  =>  'Reset Content',
		206  =>  'Partial Content',

		300  =>  'Multiple Choices',
		301  =>  'Moved Permanently',
		302  =>  'Found',
		303  =>  'See Other',
		304  =>  'Not Modified',
		305  =>  'Use Proxy',
		307  =>  'Temporary Redirect',

		400  =>  'Bad Request',
		401  =>  'Unauthorized',
		403  =>  'Forbidden',
		404  =>  'Not Found',
		405  =>  'Method Not Allowed',
		406  =>  'Not Acceptable',
		407  =>  'Proxy Authentication Required',
		408  =>  'Request Timeout',
		409  =>  'Conflict',
		410  =>  'Gone',
		411  =>  'Length Required',
		412  =>  'Precondition Failed',
		413  =>  'Request Entity Too Large',
		414  =>  'Request-URI Too Long',
		415  =>  'Unsupported Media Type',
		416  =>  'Requested Range Not Satisfiable',
		417  =>  'Expectation Failed',

		500  =>  'Internal Server Error',
		501  =>  'Not Implemented',
		502  =>  'Bad Gateway',
		503  =>  'Service Unavailable',
		504  =>  'Gateway Timeout',
		505  =>  'HTTP Version Not Supported'
	];

	/**
	 * Constructor
	 *
	 * @param	integer	$code		status code
	 **/
	public function __construct( $status = 200 ) {
		$this->reset();
		$this->setStatus( $status );
	}

	/**
	 * Sets the HTTP status of the response
	 *
	 * @param	integer	$code	HTTP status code
	 * @return	$this
	 **/
	public function setStatus( $status ) {
		if( array_key_exists( $status, self::$codes ) ) {
			$this->status	 =	$status;

			// if( strpos( php_sapi_name(), 'cgi' ) !== FALSE )
			// 	header( 'Status: ' . $code . ' ' . static::$codes[$code], TRUE );
			// else
			// 	header( ( $_SERVER['SERVER_PROTOCOL'] ? '' : 'HTTP/1.1 ' ) . static::$codes[$code], TRUE, $code );
		}
		else {
			throw new \InvalidArgumentException( 'Invalid status code' );
		}

		return $this;
	}

	/**
	 * Set a response header
	 *
	 * @param	mixed	$name	header name or array of names and values
	 * @param	string	$value	header value
	 * @return	$this
	 **/
	public function setHeader( $name, $value = NULL ) {
		$this->headers[$name]  =  $value;

		return $this;
	}

	/**
	 * Set multiple response headers
	 *
	 * @param	array	$headers	associative array of headers
	 * @return	$this
	 **/
	public function setHeaders( array $headers ) {
		foreach( $headers as $key => $value ) {
			$this->setHeader( $key, $value );
		}

		return $this;
	}

	/**
	 * Set formatter
	 *
	 * @param	\Materia\Data\Formatter	$formatter		data formatter instance
	 * @return	$this
	 **/
	public function setFormatter( \Materia\Data\Formatter $formatter ) {
		$this->formatter	 =	$formatter;

		return $this;
	}

	/**
	 * Writes content on the response body
	 *
	 * @param	mixed	$body		response content
	 * @param	boolean	$append		whatever append or not
	 * @return	$this
	 **/
	public function setBody( $body, $append = TRUE ) {
		// Decode if necessary
		if( isset( $this->formatter ) && is_scalar( $body ) ) {
			$body	 =	$this->formatter->decode( $body );
		}

		if( $this->body && $append ) {
			if( is_null( $body ) || is_scalar( $body ) ) {
				$this->body	.=  $body;
			}
			else if( isset( $this->formatter ) ) {
				$this->body	 =	$this->formatter->merge( $this->body, $body );
			}
		}
		else {
			$this->body	 =	$body;
		}

		return $this;
	}

	/**
	 * Get response body
	 *
	 * @return	mixed
	 **/
	public function getBody( $encode = FALSE ) {
		return ( $encode && isset( $this->formatter ) ) ? $this->formatter->encode( $this->body ) : $this->body;
	}

	/**
	 * Reset the response
	 *
	 * @return	$this
	 **/
	public function reset() {
		$this->headers   =  [];
		$this->status    =  200;
		$this->body      =  NULL;

		return $this;
	}

	/**
	 * Sets caching headers for the response
	 *
	 * @param	mixed	$expires	expiration time
	 * @return	$this
	 **/
	public function setCache( $expires ) {
		if( $expires === FALSE ) {
			$this->headers['Expires']        =  'Mon, 26 Jul 1997 05:00:00 GMT';
			$this->headers['Cache-Control']  =  [
				'no-store, no-cache, must-revalidate',
				'post-check=0, pre-check=0',
				'max-age=0'
			];
			$this->headers['Pragma']         =  'no-cache';
		}
		else {
			$expires   =  is_int( $expires ) ? $expires : strtotime( $expires );

			$this->headers['Expires']        =  gmdate( 'D, d M Y H:i:s', $expires ) . ' GMT';
			$this->headers['Cache-Control']  =  'max-age=' . ( $expires - time() );
		}

		return $this;
	}

	/**
	 * Sends the response to output and exit
	 */
	public function send() {
		if( ob_get_length() > 0 ) {
			ob_end_clean();
		}

		if( !headers_sent() ) {
			foreach( $this->headers as $key => $value ) {
				if( is_array( $value ) ) {
					foreach( $value as $val ) {
						header( $key . ': ' . $val );
					}
				}
				else {
					header( $key . ': ' . $value );
				}
			}
		}

		exit( $this->body );
	}

	/**
	 * Stops and outputs the current response
	 */
	public function stop() {
		$this->write( ob_get_clean() )->send();
	}

	/**
	 * Stops processing and returns a given response.
	 *
	 * @param int $code HTTP status code
	 * @param int $message Response message
	 */
	public function halt( $code = 200, $message = NULL ) {
		$this
			->clear()
			->status( $code )
			->write( $message )
			->cache( FALSE )
			->send();
	}

	/**
	 * Redirects the current request to specific URL
	 *
	 * @param string $url URL
	 **/
	public function redirect( $url, $code = 303 ) {
		$this
			->clear()
			->status( $code )
			->header( 'Location', $url )
			->write( $url )
			->send();
	}

}
