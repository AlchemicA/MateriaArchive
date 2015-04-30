<?php

namespace Materia\Debug;

/**
 * Logger interface
 *
 * @package Materia.Debug
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

interface Logger extends \ArrayAccess {

    const EMERGENCY  =  10;
    const ALERT      =  20;
    const CRITICAL   =  30;
    const ERROR      =  40;
    const WARNING    =  50;
    const NOTICE     =  60;
    const INFO       =  70;
    const DEBUG      =  80;

    /**
     * Logs a message
     *
     * @param   integer $level      priority of the message
     * @param   string  $message    the message to log
     * @return  $this
     **/
    public function logMessage( $level, $message = NULL );

}
