<?php

namespace Materia\Mail;

/**
 * Sender interface
 *
 * @package Materia.Mail
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

interface Sender {

	/**
	 * Send message
	 *
	 * @param	Message	$message	instance of email message
	 * @return	boolean
	 **/
	public function send( Message &$message );

	/**
	 * Set additional parameters
	 *
	 * @param	array	$params		list of addition parameters
	 * @return	$this
	 **/
	public function setParameters( array $params );

	/**
	 * Set additional parameter
	 *
	 * @param	array	$param		name of the parameter
	 * @param	mixed	$value		value of the parameter
	 * @return	$this
	 **/
	public function setParameter( $param, $value );

	/**
	 * Get the list additional parameters
	 *
	 * @return	array
	 **/
	public function getParameters();

}