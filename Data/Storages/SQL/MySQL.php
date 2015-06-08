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

    protected $dsn;
    protected $username;
    protected $password;
    protected $prefix;

    protected $connection    =  FALSE;
    protected $error         =  FALSE;
    protected $latest        =  0;

    /**
     * Constructor
     *
     * @param   string  $username       user name to connect the server
     * @param   string  $password       password to connect the server
     * @param   string  $database       database's name
     * @param   string  $prefix         table prefix
     * @param   string  $host           server's host name or IP address
     * @param   integer $port           port number
     **/
    public function __construct( $database, $username, $password, $prefix = NULL, $host = 'localhost', $port = 3306 ) {
        $this->dsn       =  "mysql:dbname={$database};host={$host}";
        $this->username  =  $username;
        $this->password  =  $password;
        $this->prefix    =  $prefix;
    }

    /**
     * @see \Materia\Data\Storage::connect()
     **/
    public function connect() {
        $this->error     =  FALSE;

        try {
            $this->connection    =  new \PDO( $this->dsn, $this->username, $this->password );

            if( $this->connection ) {
                $this->connection->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
                $this->connection->setAttribute( \PDO::ATTR_AUTOCOMMIT, FALSE );
                // $this->connection->setAttribute( PDO::ATTR_STATEMENT_CLASS, array( 'Materia\\Data\\Collections\\Statement', array( $this->connection ) ) );

                // Set encoding
                $this->connection->setAttribute( \PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES utf8' );
                $this->connection->exec( 'SET NAMES utf8' );
            }
        }
        catch( \PDOException $exception ) {
            $this->connection    =  FALSE;
            $this->error         =  $exception->getCode();

            return FALSE;
        }

        return TRUE;
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
    public function find( \Materia\Data\Finder &$finder, array $fields = [] ) {
        $data        =  [];
        $where       =  [];
        $order       =  [];
        $limit       =  NULL;
        $filters     =  $finder->getFilters( TRUE );
        $record      =  $finder->getRecordClass();
        $table       =  $this->prefix . $finder->getRecordName();

        // Fields' list
        if( !empty( $fields ) ) {
            $prefix  =  $finder->getFieldPrefix();
            $fields  =  $prefix . implode( ", {$prefix}", $fields );
        }
        // If no specific field(s), get everything
        else {
            $fields  =  '*';
        }

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
                                        range( 1, count( $values ) )
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
        if( !empty( $where ) ) {
            $where   =  ' WHERE ' . implode( ' ', $where );
        }
        else {
            $where   =  NULL;
        }

        // Order by
        foreach( $filters->sorting as $field => $reverse ) {
            $order[]     =  $field . ' ' . ( $reverse ? 'DESC' : 'ASC' );
        }

        // Convert ro string
        if( !empty( $order ) ) {
            $order   =  ' ORDER BY ' . implode( ', ', $order );
        }
        else {
            $order   =  NULL;
        }

        // Limit
        if( !empty( $filters->paging ) ) {
            $limit   =  " LIMIT {$filters->paging[0]} OFFSET {$filters->paging[1]}";
        }

        // Build and execute the query
        $query   =  trim( sprintf( 'SELECT %s FROM %s%s%s%s', $fields, $table, $where, $order, $limit ) ) . ';';

        if( $result = $this->query( $query, $data, $record ) ) {
            // Limit count to 1, returns the Record
            if( reset( $filters->paging ) === 1 ) {
                $record  =  $result->fetch();

                $result->closeCursor();

                return $record;
            }
            else {
                return new Collection( $result );
            }
        }

        return FALSE;
    }

    /**
     * @see \Materia\Data\Storage::load()
     **/
    public function load( \Materia\Data\Record &$record, $relationships = FALSE ) {
        $table   =  $this->prefix . $record->getRecordName();
        $pk      =  $record->getPrimaryKey( TRUE );

        // Build and execute the query
        $query   =  trim( sprintf( 'SELECT * FROM %s WHERE %s = %s LIMIT 1', $table, $pk, ":{$pk}" ) ) . ';';

        if( $result = $this->query( $query, [ ":{$pk}" => $record->{$pk} ], get_class( $record ) ) ) {
            $record  =  $result->fetch();

            $result->closeCursor();

            // Resolve relationships
            if( $relationships ) {
                $fields      =  $record->getInfo();

                foreach( $fields as $field => $config ) {
                    if( isset( $config['relation'] ) && is_subclass_of( $config['relation'], '\Materia\Data\Record' ) && $record->{$field} ) {
                        $relation    =  new $config['relation']();
                        $pk          =  $relation->getPrimaryKey();

                        $relation->{$pk}     =  $record->{$field};

                        // Try to load the relationship
                        if( $this->load( $relation ) ) {
                            $record->{$field}    =  $relation;
                        }
                    }
                }
            }

            return TRUE;
        }

        return FALSE;
    }

    /**
     * @see \Materia\Data\Storage::save()
     **/
    public function save( \Materia\Data\Record &$record ) {
        $data    =  [];
        $values  =  [];
        $table   =  $this->prefix . $record->getRecordName();
        $pk      =  $record->getPrimaryKey( TRUE );

        // Update
        if( $record->isUpdated() ) {
            foreach( $record as $field => $value ) {
                $key         =  ":{$field}";
                $data[$key]  =  $value;

                if( $field != $pk ) {
                    $values[]    =  "{$field} = {$key}";
                }
                else {
                    $where       =  "{$field} = {$key}";
                }
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
                // Populate PK
                $record->{$pk}   =  $this->latest;

                $result->closeCursor();

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

            if( $result = $this->query( $query, [ ":{$pk}" => $record->$pk ], get_class( $record ) ) ) {
                // Remove PK
                unset( $record->{$pk} );

                $result->closeCursor();

                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * @see \Materia\Data\Storage::getError()
     **/
    public function getError() {
        return $this->error;
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
            $stmt    =  $this->connection->prepare( $query, [ \PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL ] );

            // Set the fetch mode
            $stmt->setFetchMode( \PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $record );

            // Execute query
            $stmt->execute( $data );

            // Store the latest inserted ID
            $this->latest    =  $this->connection->lastInsertId();

            $this->connection->commit();
        }
        catch( \PDOException $exception ) {
            $this->connection->rollBack();

            $this->error     =  $exception->getCode();

            echo $query;
            print_r( $data );
            print_r( $exception->getTrace() );
            die( $exception->getMessage() );

            return FALSE;
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
        $keys    =  [];
        $values  =  [];

        foreach( $data as $key => $value ) {
            // Check if named parameters (":param") or anonymous parameters ("?"") are used
            if( is_string( $key ) ) {
                $keys[]      =  '/' . ( ( $key{0} == ':' ) ? '' : ':' ) . $key . '/';
            }
            else {
                $keys[]      =  '/[?]/';
            }

            // Bring parameter into human-readable format
            if( is_string( $value ) ) {
                $values[]    =  "'" . str_replace( "'", "''", $value ) . "'";
            }
            else if( is_array( $value ) ) {
                $values[]    =  implode( ',', $value );
            }
            else if ( is_null( $value ) ) {
                $values[]    =  'NULL';
            }
            else {
                $values[]    =  (string) $value;
            }
        }

        $query   =  preg_replace( $keys, $values, $query, 1, $count );

        return $query;
    }

}
