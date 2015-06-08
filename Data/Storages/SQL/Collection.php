<?php

namespace Materia\Data\Storages\SQL;

/**
 * Collection class
 *
 * @package Materia.Data
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class Collection implements \Materia\Data\Collection {

    protected $reverse   =  FALSE;
    protected $count     =  0;

    protected $current;
    protected $type;

    /**
     * Constructor
     *
     * @param   PDOStatement    $records    initial mapping
     **/
    public function __construct( \PDOStatement $records, $count = 0 ) {
        $this->type      =  NULL;
        $this->storage   =  $records;
    }

    /**
     * Destructor
     **/
    public function __destruct() {
        if( $this->storage instanceof \PDOStatement ) {
            $this->storage->closeCursor();
        }
    }

    /**
     * Rewinds back to the first element of the Iterator
     *
     * @see Iterator::rewind()
     **/
    public function rewind() {
        if( $this->reverse ) {
            $this->current   =  $this->storage->fetch( \PDO::FETCH_CLASS, \PDO::FETCH_ORI_LAST );
        }
        else {
            $this->current   =  $this->storage->fetch( \PDO::FETCH_CLASS, \PDO::FETCH_ORI_FIRST );
        }
    }

    /**
     * Returns the current element
     *
     * @see Iterator::current()
     **/
    public function current() {
        return $this->current;
    }

    /**
     * Returns the key of the current element
     *
     * @see Iterator::key()
     **/
    public function key() {
        if( isset( $this->type ) && $this->current ) {
             // Cache the PK name
            if( !isset( $key ) ) {
                static $key;

                $key     =  $this->current->getPrimaryKey();
            }

            return $this->current->$key;
        }

        return NULL;
    }

    /**
     * Moves the current position to the next element
     *
     * @see Iterator::next()
     **/
    public function next() {
        if( isset( $this->current ) ) {
            if( $this->reverse ) {
                $this->current   =  $this->storage->fetch( \PDO::FETCH_CLASS, \PDO::FETCH_ORI_PRIOR );
            }
            else {
                $this->current   =  $this->storage->fetch( \PDO::FETCH_CLASS, \PDO::FETCH_ORI_NEXT );
            }
        }
        else {
            $this->rewind();
        }

        // Populate type
        if( !isset( $this->type ) && $this->current ) {
            if( !( $this->current instanceof \Materia\Data\Record ) ) {
                throw new \RuntimeException( sprintf( 'Elements of collection must be instances of \Materia\Data\Record, instances of %s given', get_class( $this->current ) ) );
            }

            $this->type  =  get_class( $this->current );
        }
    }

    /**
     * Checks if the current position is valid
     *
     * @see Iterator::valid()
     **/
    public function valid() {
        return ( $this->current !== FALSE );
    }

    /**
     * Returns collection's type
     *
     * @return  string      class name or NULL if not defined (empty collection)
     **/
    public function getType() {
        return $this->type;
    }

    /**
     * Reverse the order of the items
     *
     * @return  boolean     the value of $reverse (TRUE = reverse order)
     **/
    public function reverse() {
        return $this->reverse;
    }

    /**
     * Returns number of items
     *
     * @return integer
     **/
    public function count() {
    }

}
