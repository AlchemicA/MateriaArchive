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

    protected $storage   =  [];

    /**
     * Attach a new observer
     *
     * @param   \SplObserver    $observer
     **/
    public function attach( \SplObserver $observer ) {
        $this->storage[]     =  $observer;
    }

    /**
     * Detach an observer
     *
     * @param   \SplObserver    $observer
     **/
    public function detach( \SplObserver $observer ) {
        if( $index = array_search( $observer, $this->storage ) ) {
            unset( $this->storage[$index] );
        }
    }

    /**
     * Notify observers
     **/
    public function notify() {
        foreach( $this->storage as &$observer ) {
            $observer->update( $this );
        }
    }
}
