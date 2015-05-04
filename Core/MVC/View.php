<?php

namespace Materia\Core\MVC;

/**
 * Simple implementation of the MVC pattern
 *
 * @package Materia.Core
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class View {

    protected $locator;
    protected $template;

    protected $output    =  FALSE;

    /**
     * Constructor
     *
     * @param   \Materia\Filesystem\Locator     $locator    file locator
     **/
    public function __construct( \Materia\Filesystem\Locator $locator ) {
        $this->locator   =  $locator;
    }

    /**
     * Render a template
     *
     * @param   string  $template
     * @param   mixed   $data
     * @return  string
     **/
    public function render( $template, array $data = [] ) {
        if( $this->template = $this->locator->locate( $template . '.html' ) ) {
            // Extract the data
            extract( $data );

            if( !$this->output ) {
                $this->output    =  TRUE;

                // Start the output buffer
                ob_start();

                // Require the file
                require( $this->template );

                $this->output    =  FALSE;

                // Flush the content and turn off the  output buffer
                return ob_get_flush();
            }
            else {
                require( $this->template );
            }
        }
    }

}
