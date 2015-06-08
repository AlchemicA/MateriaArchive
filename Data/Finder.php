<?php

namespace Materia\Data;

/**
 * Data filter/finder class
 *
 * @package Materia.Data
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class Finder {

    private $filters;
    private $record;
    private $prefix;
    private $name;
    private $pk;

    /**
     * Constructor
     *
     * @param   string  $record     kind of record
     **/
    public function __construct( $record ) {
        if( !is_string( $record ) ) {
            throw new \InvalidArgumentException( 'Argument 1 passed to ' . __METHOD__ . ' must be a string, ' . gettype( $record ) . ' given' );
        }

        if( !is_subclass_of( $record, '\Materia\Data\Record' ) ) {
            throw new \InvalidArgumentException( 'Argument 1 passed to ' . __METHOD__ . ' must be a subclass of \Materia\Data\Record, ' . $record . ' given' );
        }

        // Store the record's information
        $this->record    =  $record;
        $this->pk        =  constant( "{$this->record}::PRIMARY_KEY" );
        $this->name      =  constant( "{$this->record}::NAME" );
        $this->prefix    =  constant( "{$this->record}::PREFIX" );

        // Initialize filters
        $this->resetFilters();
    }

    /**
     * Filter results
     *
     * @param   string  $field      name of the field
     * @param   string  $operator   operator
     * @param   mixed   $value      value
     * @return  $this
     **/
    public function filter( $field, $operator, $value ) {
        // Initialize a new filter (force to use AND/OR)
        $this->resetFilters();

        $this->setCondition( $field, $operator, $value );

        return $this;
    }

    /**
     * Filter results with AND logic
     *
     * @param   string  $field      name of the field
     * @param   string  $operator   operator
     * @param   mixed   $value      value
     * @return  $this
     **/
    public function filterAnd( $field, $operator, $value ) {
        $this->filters->conditions[]     =  '&';

        $this->setCondition( $field, $operator, $value );

        return $this;
    }

    /**
     * Filter results with OR logic
     *
     * @param   string  $field      name of the field
     * @param   string  $operator   operator
     * @param   mixed   $value      value
     * @return  $this
     **/
    public function filterOr( $field, $operator, $value ) {
        $this->filters->conditions[]     =  '|';

        $this->setCondition( $field, $operator, $value );

        return $this;
    }

    /**
     * Constrain the number of rows returned
     *
     * @param   integer $count      maximum number of rows
     * @param   integer $offset     offset of the first row
     * @return  $this
     **/
    public function page( $count, $offset = 0 ) {
        $this->filters->paging   =  [ intval( $count ), intval( $offset ) ];

        return $this;
    }

    /**
     * Sorting results
     *
     * @param   string  $field      field name
     * @param   integer $reverse    if TRUE, use reverse order
     * @return  $this
     **/
    public function sort( $field, $reverse = FALSE ) {
        // Remove prefix from field name
        if( $this->prefix && ( strpos( $field, $this->prefix ) === 0 ) ) {
            $field   =  substr( $field, strlen( $this->prefix ) );
        }

        $this->filters->sorting[$field]  =  $reverse ? TRUE : FALSE;

        return $this;
    }

    /**
     * Set a condition that rows must satisfy
     *
     * @param   string  $field      name of the field
     * @param   string  $operator   operator
     * @param   mixed   $value      value
     **/
    protected function setCondition( $field, $operator, $value ) {
        if( !is_scalar( $field ) || is_numeric( $field ) ) {
            throw new \InvalidArgumentException( 'Argument 1 passed to ' . __METHOD__ . ' must be a string, ' . gettype( $field ) . ' given' );
        }

        if( !is_scalar( $operator ) ) {
            throw new \InvalidArgumentException( 'Argument 2 passed to ' . __METHOD__ . ' must be a string, ' . gettype( $operator ) . ' given' );
        }

        switch( $operator ) {
            // Greater
            case '>':
            // Greater or equal
            case '>=':
            // Lower
            case '<':
            // Lower or equal
            case '<=':
                if( !is_numeric( $value ) ) {
                    throw new \InvalidArgumentException( 'Argument 3 passed to ' . __METHOD__ . ' must be a number, ' . gettype( $value ) . ' given' );
                }

                break;

            // Equal
            case '=':
                if( !is_scalar( $value ) && !is_null( $value ) && !is_array( $value ) ) {
                    throw new \InvalidArgumentException( 'Argument 3 passed to ' . __METHOD__ . ' must be scalar or an array, ' . gettype( $value ) . ' given' );
                }

                break;

            // Not equal
            case '!=':
                if( !is_scalar( $value ) && !is_null( $value ) && !is_array( $value ) ) {
                    throw new \InvalidArgumentException( 'Argument 3 passed to ' . __METHOD__ . ' must be scalar or an array, ' . gettype( $value ) . ' given' );
                }

                break;

            // Range
            case '<>':
                if( !is_array( $value ) || ( count( $value ) != 2 ) ) {
                    throw new \InvalidArgumentException( 'Argument 3 passed to ' . __METHOD__ . ' must be an array, ' . gettype( $value ) . ' given' );
                }

                break;

            default:
                throw new \InvalidArgumentException( 'Argument 2 passed to ' . __METHOD__ . ' must be a valid operator, ' . $value . ' given' );
                break;
        }

        // Remove prefix from field name
        if( $this->prefix && ( strpos( $field, $this->prefix ) === 0 ) ) {
            $field   =  substr( $field, strlen( $this->prefix ) );
        }

        $this->filters->conditions[]     =  [ $field, $operator, $value ];
    }

    /**
     * Reset filters
     **/
    public function resetFilters() {
        $filters     =  [
            'conditions'    =>  [],
            'paging'        =>  [],
            'sorting'       =>  [],
        ];

        $this->filters   =  new \ArrayObject( $filters, \ArrayObject::ARRAY_AS_PROPS );
    }

    /**
     * Returns filters
     *
     * @param   boolean $prefix     if TRUE, remove the prefix from fields' name
     * @return  ArrayObject
     **/
    public function getFilters( $prefix = FALSE ) {
        $filters     =  $this->filters;

        if( $this->prefix && $prefix ) {
            $prefix  =  $this->prefix;

            // Prepend prefix
            $filters->conditions     =  array_map(
                function( $v ) use ( $prefix ) {
                    if( is_array( $v ) && ( strpos( $v[0], $prefix ) !== 0 ) ) {
                        $v[0]    =  $prefix . $v[0];
                    }

                    return $v;
                },
                $filters->conditions
            );

            $filters->sorting        =  array_combine(
                array_map( function( $k ) use ( $prefix ) {
                        if( strpos( $k, $prefix ) !== 0 )
                            $k   =  $prefix . $k;

                        return $k;
                    },
                    array_keys( $filters->sorting )
                ),
                $filters->sorting
            );

        }

        return $filters;
    }

    /**
     * @see Record::getRecordName()
     **/
    public function getRecordName() {
        return $this->name;
    }

    /**
     * Returns the class name ofrecord
     *
     * @return  string
     **/
    public function getRecordClass() {
        return $this->record;
    }

    /**
     * @see Record::getFieldPrefix()
     **/
    public function getFieldPrefix() {
        return $this->prefix;
    }

    /**
     * @see Record::getPrimaryKey()
     **/
    public function getPrimaryKey( $prefix = FALSE ) {
        return $prefix ? $this->prefix . $this->pk : $this->pk;
    }

}