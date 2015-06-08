<?php

namespace Materia\Mail\Senders;

/**
 * PHP Mailer class
 *
 * @package Materia.Mail
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class Mail implements \Materia\Mail\Sender {

	protected $params;

	/**
	 * Constructor
	 **/
	public function __construct() {
	}

	/**
	 * @see	\Materia\Mail\Sender::send()
	 **/
	public function send( \Materia\Mail\Message &$message ) {
		$to        =  $message->getTo();
		$headers   =  $message->getHeaders();

		if( empty( $to ) ) {
			return FALSE;
		}

		return mail( join( ',', $to ), $message->getSubject(), $message->build(), NULL, $this->params );
	}

	/**
	 * @see	\Materia\Mailer\Sender::setParameters()
	 **/
	public function setParameters( array $params ) {
		$this->params	 =	$params;

		return $this;
	}

	/**
	 * @see	\Materia\Mailer\Sender::setParameter()
	 **/
	public function setParameter( $param , $value ) {
		if( is_string( $param ) )
			$this->params[$param]	 =	$value;

		return $this;
	}

	/**
	 * @see	\Materia\Mailer\Sender::getParameters()
	 **/
	public function getParameters() {
		return $this->params;
	}

}