<?php

namespace Materia\Network;

/**
 * Basic routing class
 *
 * @package Materia.Network
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class Router {

    protected $routes        =  array();
    protected $closures      =  array();

    /**
     * Set a route
     *
     * @param   string      $pattern        base pattern
     * @param   \Closure    $closure        closure
     * @return  self
     **/
    public function setClosure( $pattern, \Closure $closure ) {
        if( !is_string( $pattern ) )
            throw new \InvalidArgumentException( sprintf( 'Argument 1 passed to %s must be a string, %s given', __METHOD__, gettype( $controller ) ) );

        // Parse allowed request methods
        if( strpos( $pattern, ' ' ) !== FALSE ) {
            list( $methods, $url ) = explode( ' ', trim( $pattern ), 2 );

            $methods     =  explode( '|', $methods );
            $patern      =  $this->compilePattern( trim( $url ) );
        }
        else {
            $methods     =  array( 'GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD' );
            $pattern     =  $this->compilePattern( trim( $pattern ) );
        }

        // Map methods to patterns
        $this->closures[]    =  $closure;

        $key     =  end( ( array_keys( $this->closures ) ) );

        // Register routes
        foreach( $methods as $method ) {
            $this->routes[$method][$key]     =  '/^\/' . $pattern . '$/i';
        }

        return $this;
    }

    /**
     * Compile a pattern to regular expression
     *
     * @param   string  $pattern
     * @return  string
     **/
    protected function compilePattern( $pattern ) {
        $pattern     =  explode( '/', $pattern );
        $pattern     =  array_map(
            function( $str ) {
                if( $str == '*' ) {
                    return '(.*)';
                }
                else if( $str && ( $str{0} == '@' ) ) {
                    if( preg_match( '/@([\w]+)(\:([^\/]*))?/', $str, $matches ) ) {
                        return '(?P<' . $matches[1] . '>' . ( isset( $matches[3] ) ? $matches[3] : '[^(\/|\?)]+' ) . ')';
                    }
                }

                return $str;
            },
            $pattern
        );

        $pattern     =  implode( '\/', $pattern );

        return $pattern;
    }

    /**
     * Try to match a request to a pattern
     *
     * @param   Request $request    the request object
     * @return  mixed
     **/
    public function processRequest( Request &$request ) {
        $params  =  array();
        $method  =  $request->getMethod();
        $path    =  $request->getPath();

        if( !isset( $this->routes[$method] ) )
            return FALSE;

        // Sort routes by length first, then alphabetically
        usort( $this->routes[$method], function( $a, $b ) {
            $la  =  strlen( $a );
            $lb  =  strlen( $b );

            if( $la == $lb ) {
                return strcmp( $a, $b );
            }

            return $la - $lb;
        });

        foreach( $this->routes[$method] as $key => $pattern ) {
            if( preg_match( $pattern, $path, $matches ) ) {
                // $reflection  =  new \ReflectionFunction( $this->closures[$key] );
                // $args        =  array();

                // foreach( $reflection->getParameters() as $parameter ) {
                //     if( $parameter->name && isset( $matches[$parameter->name] ) ) {
                //         $pos         =  $parameter->getPosition();
                //         $args[$pos]  =  $matches[$parameter->name];
                //     }
                // }

                // return $reflection[$key]->invokeArgs( $args );

                return call_user_func( $this->closures[$key], $matches );
            }
        }

        return FALSE;
    }

}