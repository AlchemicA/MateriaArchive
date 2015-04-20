<?php

namespace Materia\Debug\Loggers;

/**
 * Stack logger class
 *
 * @package Materia.Debug
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class Stack implements \IteratorAggregate, \Materia\Debug\Logger {

    private $loggers     =  array();

    /**
     * Append a new logger to the list of active loggers
     *
     * @param   \Materia\Debug\Logger   $logger
     * @return  $this
     **/
    public final function addLogger( \Materia\Debug\Logger $logger ) {
        if( !in_array( $logger, $this->loggers ) )
            $this->loggers[]     =  $logger;

        return $this;
    }

    /**
     * Remove a logger from the list of active loggers
     *
     * @param   \Materia\Debug\Logger   $logger
     * @return  $this
     **/
    public final function removeLogger( \Materia\Debug\Logger $logger ) {
        if( $key = array_search( $logger, $this->loggers ) )
            unset( $this->loggers[$key];

        return $this;
    }

    /**
     * @see \Materia\Debug\Logger::logMessage()
     **/
    public function logMessage( $level, $message = NULL ) {
        foreach( $this->loggers as $logger ) {
            call_user_func_array( array( $logger, 'logMessage' ), func_get_args() );
        }

        return $this;
    }

    /**
     * @see IteratorAggregate::getIterator()
     **/
    public function getIterator() {
        return new \ArrayIterator( $this->loggers );
    }

}