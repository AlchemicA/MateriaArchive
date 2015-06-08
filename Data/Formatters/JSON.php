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

    const ERROR_DEPTH            =  JSON_ERROR_DEPTH;           // The maximum stack depth has been exceeded
    const ERROR_STATE_MISMATCH   =  JSON_ERROR_STATE_MISMATCH;  // Invalid or malformed JSON
    const ERROR_CTRL_CHAR        =  JSON_ERROR_CTRL_CHAR;       // Control character error, possibly incorrectly encoded
    const ERROR_SYNTAX           =  JSON_ERROR_SYNTAX;          // Syntax error
    const ERROR_UTH8             =  JSON_ERROR_UTF8;            // Malformed UTF-8 characters, possibly incorrectly encoded

    protected $error     =  FALSE;

    /**
     * @see \Materia\Data\Formatter::encode()
     **/
    public function encode( array $data ) {
        // Reset errors
        $this->error     =  FALSE;

        // Encode
        $data    =  json_encode( $data, JSON_FORCE_OBJECT );

        // Something wrong ?
        if( JSON_ERROR_NONE !== ( $error = json_last_error() ) ) {
            $this->error     =  $error;

            return FALSE;
        }

        return $data;
    }

    /**
     * @see \Materia\Data\Formatter::decode()
     **/
    public function decode( $data ) {
        // Reset errors
        $this->error     =  FALSE;

        // Decode
        $data    =  json_decode( $data, TRUE );

        // Something wrong ?
        if( JSON_ERROR_NONE !== ( $error = json_last_error() ) ) {
            $this->error     =  $error;

            return FALSE;
        }

        return $data ? $data : [];
    }

    /**
     * @see \Materia\Data\Formatter::merge()
     **/
    public function merge( $one, $two ) {
        $encode  =  FALSE;

        // Decode if necessary
        if( is_string( $one ) ) {
            $one     =  $this->decode( $one );
            $encode  =  TRUE;

            // Failed
            if( $this->error ) {
                return FALSE;
            }
        }

        // The same as above
        if( is_string( $two ) ) {
            $two     =  $this->decode( $two );

            // Failed
            if( $this->error ) {
                return FALSE;
            }
        }

        $data    =  array_merge_recursive( $one, $two );

        return $encode ? $this->encode( $data ) : $data;
    }

    /**
     * @see \Materia\Data\Formatter::getError()
     **/
    public function getError() {
        return $this->error;
    }

}