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

    private $messages    =  [];

    /**
     * @see \Materia\Debug\Logger::logMessage()
     **/
    public function logMessage( $type, $severity = self::SEVERITY_INFO, $message = NULL ) {
        $this->messages[$type][$severity][]  =  $message;

        return $this;
    }

}