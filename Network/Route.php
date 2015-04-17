<?php

namespace Materia\Network;

/**
 * Basic routing class
 *
 * @package Materia.Network
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class Route {

    protected $routes        =  array();
    protected $resources     =  array();

    /**
     * Set a route to a Controller
     *
     * @param   string  $pattern        base pattern
     * @param   string  $controller     controller class
     * @return  $this
     **/
    public function setController( $pattern, $controller ) {
        if( !is_string( $pattern ) )
            throw new \InvalidArgumentException( sprintf( 'Argument 1 passed to %s must be a string, %s given', __METHOD__, gettype( $controller ) ) );

        if( !is_string( $controller ) )
            throw new \InvalidArgumentException( sprintf( 'Argument 2 passed to %s must be a string, %s given', __METHOD__, gettype( $controller ) ) );

        $controller  =  new \ReflectionClass( $controller );
        $resources   =  $controller->getMethods();

        if( !$controller->implementsInterface( '\Materia\Core\Controller' ) )
            throw new \InvalidArgumentException( sprintf( 'Argument 2 passed to %s must implements \Materia\Core\Controller interface', __METHOD__ ) );

        // Parse allowed request methods
        if( strpos( $pattern, ' ' ) !== FALSE ) {
            list( $methods, $url ) = explode( ' ', trim( $pattern ), 2 );

            $methods     =  explode( '|', $methods );
            $patern      =  $this->compile( trim( $url ) );
        }
        else {
            $methods     =  array( 'GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD' );
            $pattern     =  $this->compile( trim( $pattern ) );
        }

        // Map methods to patterns
        foreach( $resources as $resource ) {
            $args   =  array( rtrim( $pattern, '/' ) );

            if( $resource->isPublic() && !$resource->isStatic() && !$resource->isConstructor() && !$resource->isDestructor() ) {
                if( strtolower( $resource->name ) != 'index' ) {
                    $args[]  =  $resource->name;
                    $params  =  $resource->getParameters();

                    foreach( $params as $param ) {
                        if( $param->isOptional() )
                            $this->setResource( $methods, '/^\/' . implode( '\/', $args ) . '$/i', $resource );

                        $args[]  =  "(?P<{$param->name}>[^(\/|\?)]+)";
                    }
                }

                $this->setResource( $methods, '/^\/' . implode( '\/', $args ) . '$/i', $resource );
            }
        }

        return $this;
    }

    /**
     * Set a route to a callback
     *
     * @param   array               $methods    allowed methods
     * @param   string              $pattern    pattern
     * @param   ReflectionMethod    $resource   ReflectionMethod instance
     * @return  $this
     **/
    protected function setResource( $methods, $pattern, \ReflectionMethod $resource ) {
        $this->resources[]   =  $resource;

        end( $this->resources );

        $key     =  key( $this->resources );

        // Register routes
        foreach( $methods as $method ) {
            $this->routes[$method][$key]     =  $pattern;
        }
    }

    /**
     * Compile a pattern to regular expression
     *
     * @param   string  $pattern
     * @return  string
     **/
    protected function compile( $pattern ) {
        $pattern     =  explode( '/', $pattern );
        $pattern     =  array_map(
            function( $str ) {
                if( $str == '*' ) {
                    $str     =  '(.*)';
                }
                else if( ( $str != NULL ) && ( $str{0} == '@' ) ) {
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
     * @param   Request $request
     * @return  mixed
     **/
    public function process( Request &$request, \Materia\Core\Container &$container ) {
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
                $class   =  new \ReflectionClass( $this->resources[$key]->class );

                if( $constructor = $class->getConstructor() ) {
                    $args    =  array();

                    foreach( $constructor->getParameters() as $parameter ) {
                        if( $parameter->getClass()->name ) {
                            $args[]  =& $container->{$parameter->name};
                        }
                    }

                    $instance    =  $class->newInstanceArgs( $args );
                }
                else {
                    $instance    =  $class->newInstance();
                }

                $args    =  array();

                foreach( $this->resources[$key]->getParameters() as $parameter ) {
                    if( $parameter->name && isset( $matches[$parameter->name] ) ) {
                        $pos         =  $parameter->getPosition();
                        $args[$pos]  =  $matches[$parameter->name];
                    }
                }

                return $this->resources[$key]->invokeArgs( $instance, $args );
            }
        }

        return FALSE;
    }

}