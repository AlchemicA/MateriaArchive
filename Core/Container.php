<?php

namespace Materia\Core;

/**
 * A simple Dependency Injection container
 *
 * @package Materia.Core
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class Container extends \ArrayObject {

	/**
	 * override ArrayObject::offsetGet()
	 *
	 * @see ArrayObject::offsetGet()
	 **/
	public function offsetGet( $offset ) {
		$value = parent::offsetGet( $offset );

		if( is_object( $value ) && ( $value instanceof \Closure ) )
			return $value();
		else
			return $value;
	}

}