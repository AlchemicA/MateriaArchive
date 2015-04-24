<?php

namespace Materia\Data\Formatters;

/**
 * JSON data formatter
 *
 * @package Materia.Data
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class JSON implements \Materia\Data\Formatter {

    /**
     * @see \Materia\Data\Formatter::encode()
     **/
    public function encode( array $data ) {
        $data    =  json_encode( $data, JSON_FORCE_OBJECT );

        if( JSON_ERROR_NONE !== ( $error = json_last_error() ) ) {
            $error   =  $this->getError( $error );

            throw new RuntimeException( sprintf( 'Invalid JSON: %s', $error ) );
        }

        return $data;
    }

    /**
     * @see \Materia\Data\Formatter::decode()
     **/
    public function decode( $data ) {
        $data    =  json_decode( $data, TRUE );

        if( JSON_ERROR_NONE !== ( $error = json_last_error() ) ) {
            $error   =  $this->getError( $error );

            throw new RuntimeException( sprintf( 'Invalid JSON: %s', $error ) );
        }

        return $data ? $data : array();
    }

    /**
     * @see \Materia\Data\Formatter::merge()
     **/
    public function merge( $one, $two ) {
        $encode  =  FALSE;

        if( is_string( $one ) ) {
            $one     =  $this->decode( $one );
            $encode  =  TRUE;
        }

        if( is_string( $two ) ) {
            $two     =  $this->decode( $two );
        }

        $data    =  array_merge_recursive( $one, $two );

        return $encode ? $this->encode( $data ) : $data;
    }

    /**
     * Translate error codes to human readable text
     *
     * @param   integer $code           error code
     **/
    private function getError( $code ) {
        if( !isset( $errors ) ) {
            static $errors    =  array(
                JSON_ERROR_DEPTH            =>  'The maximum stack depth has been exceeded',
                JSON_ERROR_STATE_MISMATCH   =>  'Invalid or malformed JSON',
                JSON_ERROR_CTRL_CHAR        =>  'Control character error, possibly incorrectly encoded',
                JSON_ERROR_SYNTAX           =>  'Syntax error',
                JSON_ERROR_UTF8             =>  'Malformed UTF-8 characters, possibly incorrectly encoded',
            );
        }

        return isset( $errors[$code]) ? $errors[$code] : 'Unknown error';
    }

}