<?php

namespace Materia\Data\Storages;

/**
 * Abstract storage class
 *
 * @package Materia.Data
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

use \SplFileObject, \SplTempFileObject, \Exception, \RuntimeException, \InvalidArgument, \ExceptionOutOfBoundsException;

// ---

class FlatFile extends Storage {

    const FILE_READ      =  1;
    const FILE_WRITE     =  2;
    const FILE_APPEND    =  3;

    private $gzip    =  FALSE;
    private $swap    =  1048576;

    private $formatter;
    private $path;

    /**
     * Constructor
     *
     * @param   string  $database   the database name
     * @param   array   $options    an array of options
     **/
    public function __construct( $path, \Materia\Data\Formatter $formatter, array $options = array() ) {
        $path    =  (string) $path;

        if( !is_dir( $path ) || !is_writable( $path ) )
            throw new Exception( $path . ' is not a valid directory' );

        if( isset( $options['gzip'] ) )
            $this->gzip  =  $options['gzip'] ? TRUE : FALSE;

        if( isset( $options['swap'] ) ) {
            $this->swap  =  filter_var(
                $options['swap'],
                FILTER_VALIDATE_INT,
                array(
                    'options'   =>  array(
                        'min_range' =>  0,
                        'default'   =>  $this->swap
                    )
                )
            );
        }

        $this->path          =  rtrim( $path, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
        $this->formatter     =  $formatter;
    }

    /**
     * @see \Materia\Data\Storage::connect()
     **/
    public function connect() {}

    /**
     * @see \Materia\Data\Storage::find()
     **/
    public function find( \Materia\Data\Finder &$finder, array $fields = array() ) {
        $data        =  array();
        $filters     =  $finder->getFilters();
        $record      =  $finder->getType();
        $table       =  $finder->getRecordName();
        $pk          =  $finder->getPrimaryKey();

        // Where clause
        foreach( $filters->conditions as $index => &$condition ) {
            $callback    =  NULL;

            // Clause
            if( is_array( $condition ) ) {
                list( $field, $operator, $value ) = $condition;

                $results     =  $this->getKey( $table . DIRECTORY_SEPARATOR . $field, $value, $operator );

                if( $results == FALSE )
                    $results     =  array();

                $data        =  $callback ? call_user_func( $callback, $data, $results ) : $results;
            }
            // AND/OR operator
            else if( is_string( $condition ) ) {
                if( $condition == '|' )
                    $callback    =  'array_merge';
                else
                    $callback    =  'array_intersect';
            }
        }

        // Sort by PK by default
        if( empty( $filters->sorting ) )
            natsort( $data );

        // Populate collection
        foreach( $data as &$row ) {
            $row     =  new $record( array( $pk => $row ) );

            if( FALSE == $this->load( $row ) )
                $row     =  FALSE;
        }

        // Remove empty records
        $data    =  array_filter( $data );

        // Sorting
        if( !empty( $filters->sorting ) ) {
            $sorting     =  $filters->sorting;

            usort( $data, function( $a, $b ) use ( $sorting ) {
                foreach( $sorting as $field => $reverse ) {
                    // DESC
                    if( $reverse ) {
                        if( $a[$field] > $b[$field] )
                            return -1;
                        else if( $a[$field] < $b[$field] )
                            return 1;
                        else
                            return 0;
                    }
                    // ASC
                    else {
                        if( $a[$field] < $b[$field] )
                            return -1;
                        else if( $a[$field] > $b[$field] )
                            return 1;
                        else
                            return 0;
                    }
                }
            });
        }

        // Limit
        if( !empty( $filters->paging ) )
            $data    =  array_slice( $results, $filters->paging[0], $filters->paging[1] );

        // Populate collection
        array_walk( $data, array( &$finder, 'push' ) );

        return $finder->count();
    }

    /**
     * @see \Materia\Data\Storage::remove()
     **/
    public function remove( \Materia\Data\Record &$record ) {
        $table   =  $record->getRecordName();
        $pk      =  $record->getPrimaryKey();
        $schema  =  $record->getInfo();

        if( $record->isUpdated() ) {
            // Remove all the indexes
            foreach( $schema as $field => $info ) {
                if( $info['index'] )
                    $this->removeKey( $table . DIRECTORY_SEPARATOR . $field, $record->$field, $record->$pk );
            }

            // Remove the record
            if( @unlink( $this->path . $table . DIRECTORY_SEPARATOR . $record->$pk . '.dat' ) ) {
                // Remove PK from record
                unset( $record->$pk );

                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * @see \Materia\Data\Storage::load()
     **/
    public function load( \Materia\Data\Record &$record, $dependacies = FALSE ) {
        $table   =  $record->getRecordName();
        $pk      =  $record->getPrimaryKey();

        if( !isset( $record->$pk ) )
            throw new RuntimeException( "Missing value for primary key {$pk}" );

        $file    =  $this->openFile( $table . DIRECTORY_SEPARATOR . $record->$pk . '.dat', self::FILE_READ );
        $path    =  $file->getRealPath();

        $this->closeFile( $file );

        $data    =  file_get_contents( $path );

        if( $data ) {
            $data    =  $this->formatter->decode( $data );

            foreach( $data as $key => $value ) {
                $record->$key    =  $value;
            }

            return TRUE;
        }

        return FALSE;
    }

    /**
     * @see \Materia\Data\Storage::save()
     **/
    public function save( \Materia\Data\Record &$record ) {
        $table   =  $record->getRecordName();
        $pk      =  $record->getPrimaryKey();
        $schema  =  $record->getSchemaInfo();
        $keys    =  array();

        // New record
        if( !isset( $record->$pk ) ) {
            // Set new PK
            $record->$pk     =  $this->generateFileUID( $table );

            foreach( $schema as $field => $info ) {
                if( $info['index'] )
                    $this->setKey( $table . DIRECTORY_SEPARATOR . $field, $record->$field, $record->$pk, $info['unique'] );
            }

            $file    =  $this->openFile( $table . DIRECTORY_SEPARATOR . $record->$pk . '.dat', self::FILE_WRITE );
            $path    =  $file->getRealPath();

            $this->closeFile( $file );

            file_put_contents( $path, $this->formatter->encode( $record->getArrayCopy( TRUE ) ), LOCK_EX );
        }
        // Old record
        else {
            // Retrieve stored data to compare
            $old         =  clone $record;

            $this->load( $old );

            $updates     =  $old->compare( $record );
        }

        return TRUE;
    }

    /**
     * Get a key data from the database
     *
     * @param   string  $file   name of index file
     * @param   string  $key    the key
     * @return  mixed
     */
    private function getKey( $file, $key, $operator = '=' ) {
        $data    =  FALSE;
        $key     =  $this->normalizeKey( $key );
        $file    =  $this->openFile( $file . '.key', self::FILE_READ );

        // Itereate through lines
        foreach( $file as $line ) {
            $data    =  $this->getDataFromLine( $line, $key, $operator );

            // Found it
            if( FALSE !== $data ) {
                $data    =  $this->formatter->decode( $data );

                break;
            }
        }

        $this->closeFile( $file );

        return $data;
    }

    /**
     * Store a key into the database
     *
     * @param   string  $file       storage file name (witout extension)
     * @param   string  $key        the key
     * @param   mixed   $value      the value to store
     * @param   boolean $unique     unique key
     * @return  boolean
     **/
    private function setKey( $file, $key, $value, $unique = FALSE ) {
        // Validate data
        if( !is_scalar( $value ) )
            throw new InvalidArgumentException( 'Argument 3 passed to ' . __METHOD__ . ' must be an integer or a string, ' . gettype( $value ) . ' given' );

        $match       =  0;
        $file        =  $file . '.key';
        $key         =  $this->normalizeKey( $key );
        $temp        =  new SplTempFileObject( $this->swap );

        if( file_exists( $this->path . ltrim( $file, DIRECTORY_SEPARATOR ) ) ) {
            $pointer     =  $this->openFile( $file, self::FILE_READ );

            // Itereate through lines
            foreach( $pointer as $line ) {
                $data        =  $this->getDataFromLine( $line, $key, '=' );

                // We have a match
                if( FALSE !== $data ) {
                    if( $unique )
                        throw new RuntimeException( "Duplicate entry {$value} for key UNIQUE" );

                    $data    =  $this->formatter->decode( $data );

                    // Update if different
                    if( !in_array( $value, $data ) ) {
                        $data[]  =  $value;

                        $temp->fwrite( $key . '=' . $this->formatter->encode( $data ) . PHP_EOL );

                        $match++;
                    }
                    else {
                        $temp->fwrite( $line . PHP_EOL );
                    }
                }
                else {
                    $temp->fwrite( $line . PHP_EOL );
                }
            }

            $this->closeFile( $pointer );
        }

        // Key not found need a new line
        if( !$match ) {
            $temp->fwrite( $key . '=' . $this->formatter->encode( array( $value ) ) . PHP_EOL );

            $match   =  1;
        }

        // Update the file
        if( $match ) {
            $temp->rewind();

            $pointer     =  $this->openFile( $file, self::FILE_WRITE );

            foreach( $temp as $line ) {
                $pointer->fwrite( $line );
            }

            $this->closeFile( $pointer );
        }

        // Close
        $temp    =  NULL;

        return $match;
    }

    /**
     * Delete a key from the database
     *
     * @param   string  $file       storage file name
     * @param   string  $key        the key
     * @param   string  $value      the value to remove
     * @return  boolean
     */
    private function removeKey( $file, $key, $value ) {
        // Validate data
        if( !is_scalar( $value ) )
            throw new InvalidArgumentException( 'Argument 3 passed to ' . __METHOD__ . ' must be an integer or a string, ' . gettype( $value ) . ' given' );

        $match       =  0;
        $key         =  $this->normalizeKey( $key );
        $temp        =  new SplTempFileObject( $this->swap );
        $pointer     =  $this->openFile( $file . '.key', self::FILE_READ );

        // Itereate through lines
        foreach( $pointer as $line ) {
            $data        =  $this->getDataFromLine( $line, $key, '=' );

            // We have a match
            if( FALSE !== $data ) {
                $data    =  $this->formatter->decode( $data );

                // Remove the index
                if( $pos = array_search( $value, $data ) ) {
                    unset( $data[$pos] );

                    // Write the new line if not empty
                    if( !empty( $data ) )
                        $temp->fwrite( $key . '=' . $this->formatter->encode( $data ) . PHP_EOL );

                    $match++;
                }
                else {
                    $temp->fwrite( $line . PHP_EOL );
                }
            }
            else {
                $temp->fwrite( $line . PHP_EOL );
            }
        }

        $this->closeFile( $pointer );

        if( $match ) {
            $temp->rewind();

            $pointer     =  $this->openFile( $file, self::FILE_WRITE );

            foreach( $temp as $line ) {
                $pointer->fwrite( $line );
            }

            $this->closeFile( $pointer );
        }

        $this->closeFile( $temp );

        return $match;
    }

    /**
     * Open the database file
     *
     * @param   string  $file       file to open
     * @param   integer $mode       file mode
     * @return  SplFileObject
     **/
    private function openFile( $file, $mode ) {
        if( !isset( $access ) ) {
            static $access  =  array(
                self::FILE_READ     =>  array(
                    'mode'      =>  'rb',
                    'operation' =>  LOCK_SH
                ),
                self::FILE_WRITE    =>  array(
                    'mode'      =>  'wb',
                    'operation' =>  LOCK_EX,
                ),
                self::FILE_APPEND   =>  array(
                    'mode'      =>  'ab',
                    'operation' =>  LOCK_EX,
                ),
            );
        }

        // Build path
        $path    =  $this->path . ltrim( $file, DIRECTORY_SEPARATOR );

        if( !file_exists( $path ) && ( ( $mode != self::FILE_READ ) && !@touch( $path ) ) )
            throw new Exception( 'Could not create file ' . $path );
        else if( ( $mode == self::FILE_READ ) && !is_readable( $path ) )
            throw new Exception( 'Could not read file ' . $path );
        else if( ( $mode != self::FILE_READ ) && !is_writable( $path ) )
            throw new Exception( 'Could not write to file ' . $path );

        // GZip data
        if( $this->gzip )
            $path    =  'compress.zlib://' . $path;

        $file    =  new SplFileObject( $path, $access[$mode]['mode'] );

        // Set flags
        if( self::FILE_READ == $mode )
            $file->setFlags( SplFileObject::DROP_NEW_LINE | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD );

        // Set LOCK
        if( !$this->gzip && !$file->flock( $access[$mode]['operation'] ) )
            throw new Exception( 'Could not lock file ' . $path );

        return $file;
    }

    /**
     * Close the database file
     *
     * @param   SplFileObject   $file       file pointer
     **/
    private function closeFile( SplFileObject &$file ) {
        if( !$this->gzip && !$file->flock( LOCK_UN ) ) {
            $file    =  NULL;

            throw new Exception( 'Could not unlock file' );
        }

        $file    =  NULL;
    }

    /**
     * Check the database has been loaded and valid key
     *
     * @param   mixed   $key        the key
     */
    private function normalizeKey( $key ) {
        if( !is_string( $key ) && !is_int( $key ) )
            throw new InvalidArgumentException( 'Argument 1 passed to ' . __METHOD__ . ' must be an integer or a string,  ' . gettype( $key ) . ' given' );
        else if( strlen( $key ) > 256 )
            throw new InvalidArgumentException( 'Maximum key length is 256 characters' );
        else if( strpos( $key, '=' ) !== FALSE )
            throw new InvalidArgumentException( 'Key may not contain the equals character' );

        return $key;
    }

    /**
     * Retrieve data from a given line
     *
     * @param   string  $line       file line
     * @param   mixed   $key        valid key(s)
     * @param   string  $operator   operator
     * @return  mixed               the data if the key matches, FALSE otherwise
     **/
    private function getDataFromLine( $line, $key, $operator ) {
        $chunks  =  explode( '=', $line, 2 );

        switch( $operator ) {
            // Equal
            case '=':
                if( !is_array( $key ) )
                    $key     =  array( $key );

                if( in_array( $chunks[0], $key ) )
                    return $chunks[1];

                break;

            // Greater
            case '>':
                if( $chunks[0] > $key )
                    return $chunks[1];

                break;

            // Greater or equal
            case '>=':
                if( $chunks[0] >= $key )
                    return $chunks[1];

                break;

            // Less
            case '<':
                if( $chunks[0] < $key )
                    return $chunks[1];

                break;

            // Less or equal
            case '<=':
                if( $chunks[0] <= $key )
                    return $chunks[1];

                break;

            // Range
            case '<>':
                if( in_array( $chunks, $key ) )
                    return $chunks[1];

                break;

        }

        return FALSE;
    }

    /**
     * Generate ID for file naming
     *
     * @param   string  $table      folder of data
     * @return  string
     **/
    private function generateFileUID( $table ) {
        $today   =  date( 'Ymd' );
        $array   =  glob( $this->path . trim( $table, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . $today . ( $this->gzip ? '*.dat.gz' : '*.dat' ) );

        if( !empty( $array ) ) {
            natsort( $array );

            $last    =  basename( end( $array ), ( $this->gzip ? '.dat.gz' : '.dat' ) );
            $date    =  substr( $last, 0, 8 );
            $dec     =  base_convert( substr( $last, 8 ), 36, 10 );

            // Overflow
            if( $dec >= 2821109907455 )
                throw new OverflowException( "File UID ($last) overflow" );

            $id      =  sprintf( "%8s%08s", $today, base_convert( ( $dec + 1 ), 10, 36 ) );
        }
        else {
            $id      =  sprintf( "%8s%08s", $today, base_convert( 1, 10, 36 ) );
        }

        return $id;
    }
}