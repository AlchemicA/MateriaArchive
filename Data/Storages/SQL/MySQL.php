<?php

namespace Materia\Data\Storages\SQL;

/**
 * MySQL storage class
 *
 * @package Materia.Data
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class MySQL implements \Materia\Data\Storage {

    private $dsn;
    private $username;
    private $password;
    private $prefix;

    private $connection  =  FALSE;

    /**
     * Constructor
     *
     * @param   string  $dsn
     * @param   string  $username       user name to connect MySQL server
     * @param   string  $password       password to connect MySQL server
     * @param   string  $prefix         table prefix
     **/
    public function __construct( $dsn, $username, $password, $prefix = '' ) {
        $this->dsn       =  (string) $dsn;
        $this->username  =  (string) $username;
        $this->password  =  (string) $password;
        $this->prefix    =  (string) $prefix;
    }

    /**
     * @see \Materia\Data\Storage::connect()
     **/
    public function connect() {
        try {
            $this->connection    =  new \PDO( $this->dsn, $this->username, $this->password );

            if( $this->connection ) {
                $this->connection->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
                $this->connection->setAttribute( \PDO::ATTR_AUTOCOMMIT, FALSE );
                // $this->connection->setAttribute( PDO::ATTR_STATEMENT_CLASS, array( 'Materia\\Data\\Collections\\Statement', array( $this->connection ) ) );
            }
        } catch( \PDOException $exception ) {
            $this->connection    =  FALSE;

            throw new \Exception( $exception->getMessage() , (int) $exception->getCode() );
        }
    }

    /**
     * Destructor
     **/
    public function __destruct() {
        $this->connection    =  FALSE;
    }

    /**
     * @see \Materia\Data\Storage::find()
     **/
    public function find( \Materia\Data\Finder &$finder, array $fields = array() ) {
        $data        =  array();
        $where       =  array();
        $order       =  array();
        $limit       =  NULL;
        $filters     =  $finder->getFilters( TRUE );
        $record      =  $finder->getRecordClass();
        $table       =  $this->prefix . $finder->getRecordName();

        // Fields' list
        if( !empty( $fields ) )
            $fields  =  implode( ', ', $fields );
        // If no specific field(s), get everything
        else
            $fields  =  '*';

        // Where clause
        foreach( $filters->conditions as $index => $condition ) {
            // Clause
            if( is_array( $condition ) ) {
                list( $field, $operator, $value ) = $condition;

                // IN operator
                if( is_array( $value ) ) {
                    $values      =  array_values( $value );
                    $keys        =  array_map(
                                        function( $k ) {
                                            return ':in' . $k;
                                        },
                                        range( 0, count( $values ) )
                                    );
                    $data        =  array_merge( $data, array_combine( $keys, $values ) );
                    $where[]     =  "{$field} IN (" . implode( ', ', $keys ) . ")";
                }
                // Other operator
                else {
                    $key         =  ':where' . $index;
                    $data[$key]  =  $value;
                    $where[]     =  "{$field} {$operator} {$key}";
                }
            }
            // AND/OR operator
            else if( is_string( $condition ) ) {
                $where[]     =  ( $condition == '|' ) ? 'OR' : 'AND';
            }
        }

        // Convert to string
        if( !empty( $where ) )
            $where   =  ' WHERE ' . implode( ' ', $where );
        else
            $where   =  NULL;

        // Order by
        foreach( $filters->sorting as $field => $reverse ) {
            $order[]     =  $field . ' ' . ( $reverse ? 'DESC' : 'ASC' );
        }

        // Convert ro string
        if( !empty( $order ) )
            $order   =  ' ORDER BY ' . implode( ', ', $order );
        else
            $order   =  NULL;

        // Limit
        if( !empty( $filters->paging ) )
            $limit   =  ' LIMIT ' . implode( ', ', $filters->paging );

        // Build and execute the query
        $query   =  trim( sprintf( 'SELECT %s FROM %s%s%s%s', $fields, $table, $where, $order, $limit ) ) . ';';

        if( $result = $this->query( $query, $data, $record ) ) {
            $result->closeCursor();

            return new Collection( $result );
        }

        return FALSE;
    }

    /**
     * @see \Materia\Data\Storage::load()
     **/
    public function load( \Materia\Data\Record &$record, $relationships = FALSE ) {
        $table   =  $this->prefix . $record->getRecordName();
        $pk      =  $record->getPrimaryKey();

        if( !$record->isUpdated() ) {
            // Build and execute the query
            $query   =  trim( sprintf( 'SELECT * FROM %s WHERE %s = %s LIMIT 1', $table, $pk, ":{$pk}" ) ) . ';';

            if( $result = $this->query( $query, array( ":{$pk}" => $record->$pk ), get_class( $record ) ) ) {
                $record  =  $result->fetch( \PDO::FETCH_CLASS );

                $result->closeCursor();

                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * @see \Materia\Data\Storage::save()
     **/
    public function save( \Materia\Data\Record &$record ) {
        $data    =  array();
        $values  =  array();
        $table   =  $record->getRecordName();
        $pk      =  $record->getPrimaryKey();

/*
$values  =  array_combine(
    array_map(
        function( $k ) {
            return ':' . $k;
        },
        array_keys( $record->getArrayCopy( TRUE ) )
    )
    , $array
);
*/
        // Update
        if( $record->isUpdated() ) {
            foreach( $record as $field => $value ) {
                $key         =  ":{$field}";
                $data[$key]  =  $value;

                if( $field == $pk )
                    $values[]    =  "{$field} = {$key}";
                else
                    $where       =  "{$field} = {$key}";
            }

            // Build and execute the query
            $query   =  trim( sprintf( 'UPDATE %s SET  %s WHERE %s', $table, implode( ', ', $values ), $where ) ) . ';';

            if( $result = $this->query( $query, $data, get_class( $record ) ) ) {
                $result->closeCursor();

                return TRUE;
            }
        }
        // Insert
        else {
            foreach( $record as $field => $value ) {
                $key         =  ":{$field}";
                $values[]    =  "{$field} = {$key}";
                $data[$key]  =  $value;
            }

            // Built and execute the query
            $query   =  trim( sprintf( 'INSERT INTO %s SET %s', $table, implode( ', ', $values ) ) ) . ';';

            if( $result = $this->query( $query, $data, get_class( $record ) ) ) {
                $result->closeCursor();
                // Populate PK
                $record->$pk     =  $this->connection->lastInsertId();

                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * @see \Materia\Data\Storage::remove()
     **/
    public function remove( \Materia\Data\Record &$record ) {
        $table   =  $this->prefix . $record->getRecordName();
        $pk      =  $record->getPrimaryKey();

        if( $record->isUpdated() ) {
            // Built and execute the query
            $query   =  trim( sprintf( 'DELETE FROM %s WHERE {$pk} = :{$pk}', $table ) ) . ';';

            if( $result = $this->query( $query, array( ":{pk}" => $record->$pk ), get_class( $record ) ) ) {
                // Remove PK
                unset( $record->$pk );

                $result->closeCursor();

                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * Execute a query
     *
     * @param   string  $query          query to execute
     * @param   array   $data           data
     * @param   string  $record         name of Record class
     * @return  mixed                   PDO statement or FALSE on faillure
     **/
    protected function query( $query, array $data, $record = NULL ) {
        $this->connection->beginTransaction();

        try {
            $stmt    =  $this->connection->prepare( $query, array( \PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL ) );

            $stmt->setFetchMode( \PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $record );

            $stmt->execute( $data );

            $this->connection->commit();
        } catch( \PDOException $exception ) {
            $this->connection->rollBack();

            throw new \Exception( $exception->getMessage() , (int) $exception->getCode() );
        }

        return $stmt;
    }

    /**
     * Returns the emulated SQL string
     *
     * @deprecated                      use MySQL server logs instead
     * @param       string  $query      raw query
     * @param       array   $data       binded parameters
     * @return      string
     **/
    static protected function showQuery( $query, $data ) {
        $keys    =  array();
        $values  =  array();

        foreach( $data as $key => $value ) {
            // Check if named parameters (":param") or anonymous parameters ("?"") are used
            if( is_string( $key ) )
                $keys[]      =  '/' . ( ( $key{0} == ':' ) ? '' : ':' ) . $key . '/';
            else
                $keys[]      =  '/[?]/';

            // Bring parameter into human-readable format
            if( is_string( $value ) )
                $values[]    =  "'" . str_replace( "'", "''", $value ) . "'";
            else if( is_array( $value ) )
                $values[]    =  implode( ',', $value );
            else if ( is_null( $value ) )
                $values[]    =  'NULL';
            else
                $values[]    =  (string) $value;
        }

        $query   =  preg_replace( $keys, $values, $query, 1, $count );

        return $query;
    }

}