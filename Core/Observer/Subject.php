<?php

namespace Materia\Core\Observer;

/**
 * Simple implementation of the Observer pattern
 *
 * @package Materia.Core
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class Subject implements \SplSubject {

    protected $observers     =  array();

    /**
     * Attach a new observer
     *
     * @param   \SplObserver    $observer
     **/
    public function attach( \SplObserver $observer ) {
        $this->observers[]   =  $observer;
    }

    /**
     * Detach an observer
     *
     * @param   \SplObserver    $observer
     **/
    public function detach( \SplObserver $observer ) {
        if( $index = array_search( $observer, $this->observers ) ) {
            unset( $this->observers[$index] );
        }
    }

    /**
     * Notify observers
     **/
    public function notify() {
        foreach( $this->observers as &$observer ) {
            $observer->update( $this );
        }
    }
}
