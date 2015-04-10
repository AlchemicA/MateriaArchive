<?php

namespace Materia\Data;

/**
 * Storage interface
 *
 * @package Materia.Data
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

interface Storage {

    /**
     * Establish a connection to storage engine
     **/
    public function connect();

    /**
     * Returns a single record
     *
     * @param   Record  $record         record with PK populated
     * @return  boolean                 TRUE on success or FALSE
     **/
    public function load( Record &$record );

    /**
     * Save a record
     *
     * @param   Record  $record         record to save
     * @return  boolean                 TRUE on success or FALSE
     **/
    public function save( Record &$record );

    /**
     * Delete a record
     *
     * @param   Record  $record         record with PK populated
     * @return  boolean                 TRUE on success or FALSE
     **/
    public function remove( Record &$record );

    /**
     * Returns a collection of records that match filter criteria
     *
     * @param   Finder      $finder         finder instance
     * @return  integer                     number or records
     **/
    public function find( Finder &$finder );

    /**
     * Setup a logger
     *
     * @param   Logger  $logger         logger instance
     **/
    // public function setLogger( Logger $logger ) {
    //     $this->logger    =  $logger;
    // }

    /**
     * Generate a random unique ID
     *
     * @return string
     **/
    // public function generateUID( $date = FALSE ) {
    //     if( $date ) {
    //         $string  =  uniqid( '', FALSE );

    //         return date( 'Ymd-' ) . base_convert( $string, 16, 36 );
    //     }
    //     else {
    //         $string  =  uniqid( '', TRUE );
    //         $hex     =  substr( $string, 0, 13 );
    //         $dec     =  $string[13] . substr( $string, 15 ); // skip the dot

    //         return base_convert( $hex, 16, 36 ) . base_convert( $dec, 10, 36 );
    //     }
    // }

}