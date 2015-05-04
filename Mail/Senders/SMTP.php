<?php

namespace Materia\Mail\Senders;

/**
 * SMTP sender class
 *
 * @package Materia.Mail
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

use \Exception;

class SMTP implements \Materia\Mail\Sender {

    const SECURE_TLS     =  'tls';
    const SECURE_SSL     =  'ssl';

    protected $smtp;
    protected $host;
    protected $port;
    protected $secure;
    protected $username;
    protected $password;
    protected $logger;

    /**
     * Constructor
     *
     * @param   string  $host           server host name or IP address
     * @param   string  $username       username
     * @param   string  $password       password
     * @param   int     $port           port number
     * @param   string  $secure         SSL/TLS
     * @todo    validate host
     **/
    public function __construct( $host, $username, $password, $port = 25, $secure = NULL ) {
        $this->host      =  (string) $host;
        $this->username  =  (string) $username;
        $this->password  =  (string) $password;
        $this->port      =  (int) $port;
        $this->secure    =  strtolower( (string) $secure );
    }

    /**
     * @see \Materia\Mail\Sender::send()
     **/
    public function send( \Materia\Mail\Message &$message ) {
        // Try to connect
        if( !$this->connect() ) {
            return FALSE;
        }

        // Send "EHLO"
        if( !$this->doSend( 'EHLO ' . $this->host, 250 ) ) {
            return FALSE;
        }

        // StartTLS
        if( $this->secure == self::SECURE_TLS ) {
            if( $this->doSend( 'STARTTLS' ) != 220 ) {
                return FALSE;
            }

            if( !stream_socket_enable_crypto( $this->smtp, TRUE, STREAM_CRYPTO_METHOD_TLS_CLIENT ) ) {
                return FALSE;
            }

            if( $this->doSend( 'EHLO ' . $this->host ) != 250 ) {
                return FALSE;
            }
        }

        // Log-in
        if( ( $this->doSend( 'AUTH LOGIN' ) != 334 ) || ( $this->doSend( base64_encode( $this->username ) ) != 334 ) || ( $this->doSend( base64_encode( $this->password ) ) != 235 ) ) {
            return FALSE;
        }

        // From
        if( $this->doSend( 'MAIL FROM:<' . key( $message->getFrom() ) . '>' ) != 250 ) {
            return FALSE;
        }

        // To
        foreach( $message->getTo() as $email => $to ) {
            if( $this->doSend( 'RCPT TO:<' . $email . '>' ) != 250 ) {
                return FALSE;
            }
        }

        // Data
        if( $this->doSend( 'DATA' ) != 354 ) {
            return FALSE;
        }

        $mail    =  $message->build();
        $mail   .=  "\r\n";
        $mail   .=  "\r\n" . '.' . "\r\n";

        fputs( $this->smtp, $mail, strlen( $mail ) );

        if( $this->getCode() != 250 ){
            return FALSE;
        }

        return TRUE;

        if( $this->doSend( 'QUIT' ) != 221 ) {
            return FALSE;
        }

        return fclose( $this->smtp ) ? TRUE : FALSE;
    }

    /**
     * @see \Materia\Mailer\Sender::setParameters()
     **/
    public function setParameters( array $params ) {
        $this->params    =  $params;

        return $this;
    }

    /**
     * @see \Materia\Mailer\Sender::setParameter()
     **/
    public function setParameter( $param , $value ) {
        if( is_string( $param ) ) {
            $this->params[$param]    =  $value;
        }

        return $this;
    }

    /**
     * @see \Materia\Mailer\Sender::getParameters()
     **/
    public function getParameters() {
        return $this->params;
    }

    /**
     * Set a logger
     *
     * @param   \Materia\Debug\Logger   $logger     logger instance
     **/
    public function setLogger( \Materia\Debug\Logger $logger ) {
        $this->logger    =  $logger;
    }

    /**
     * Send a command
     *
     * @param   string  $command    command to send
     * @return  mixed               returned code or FALSE if it fails
     */
    protected function doSend( $command ) {
        fputs( $this->smtp, $command . "\r\n" );

        return $this->getCode();
    }

    /**
     * Connect the server
     *
     * @return  boolean
     **/
    protected function connect() {
        $this->smtp  =  fsockopen( ( ( $this->secure == self::SECURE_SSL ) ? 'ssl://' . $this->host : $this->host ), $this->port );
        // set block mode
        // stream_set_blocking( $this->smtp, 1 );
        if( !$this->smtp ) {
            return FALSE;
        }

        // Success: 220
        if( $this->getCode() != 220 ) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Get SMTP response code
     *
     * @return  mixed       returned code or FALSE if it fails
     **/
    protected function getCode() {
        while( $str = @fgets( $this->smtp, 515 ) ) {
            if( isset( $logger ) )
                $this->logger->setMesssage( \Materia\Debug\Logger::INFO, $str );

            if( substr( $str, 3, 1 ) == " " ) {
                return substr( $str, 0, 3 );
            }
        }

        return FALSE;
    }
}