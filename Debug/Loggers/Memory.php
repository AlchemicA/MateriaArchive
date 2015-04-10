<?php

namespace Materia\Debug\Loggers;

/**
 * Memory logger class
 *
 * @package Materia.Debug
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class Memory implements \Materia\Debug\Logger {

    private $queue;

    /**
     * Constructor
     */
    public function __construct() {
        $this->queue     =  new \SplPriorityQueue();
    }

    /**
     * @see \Materia\Debug\Logger::logMessage()
     **/
    public function logMessage( $level, $message = NULL ) {
        $this->queue->insert( $message, $level );

        return $this;
    }

}