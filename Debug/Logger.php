<?php

namespace Materia\Debug;

/**
 * Logger interface
 *
 * @package Materia.Debug
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

interface Logger {

    const SEVERITY_EMERGENCY     =  10;
    const SEVERITY_ALERT         =  20;
    const SEVERITY_CRITICAL      =  30;
    const SEVERITY_ERROR         =  40;
    const SEVERITY_WARNING       =  50;
    const SEVERITY_NOTICE        =  60;
    const SEVERITY_INFO          =  70;
    const SEVERITY_DEBUG         =  80;

    /**
     * Logs a message
     *
     * @param   string  $type       the type of log
     * @param   integer $severity   the severity of the message
     * @param   string  $message    the message to log
     * @return  $this
     **/
    public function logMessage( $type, $severity = self::SEVERITY_INFO, $message = NULL );

    /**
     * Get the latest logged message
     *
     * @param   string  $type       the type of log
     * @param   integer $severity   the severity of the message
     * @return  string
     **/
    // public function getLatestMessage( $type, $severity = NULL );

}
