<?php

namespace Materia\Core;

/**
 * Cron
 *
 * @package Materia.Core
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class Cron {

	const STATUS_INACTIVE    =  0;
	const STATUS_ACTIVE      =  1;
	const STATUS_RUNNING     =  2;
	const STATUS_LOCKED      =  4;

	const TIME_MINUTE        =  'm';
	const TIME_HOUR          =  'h';
	const TIME_DAY           =  'd';
	const TIME_WEEK          =  'w';
	const TIME_MONTH         =  'j';

	protected $path;
	protected $tasks;

	/**
	 * Constructor
	 *
	 * @param   string  $path               local path where to store .lock files
	 **/
	public function __construct( \SplFileInfo $path ) {
		if( !$path->isDir() ) {
			throw new \InvalidArgumentException( 'Argument 1 passed to ' . __METHOD__ . ' must be a valid path' );
		}

		$this->path      =  rtrim( $path->getRealPath(), DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
		$this->tasks     =  new \ArrayObject( array(), \ArrayObject::ARRAY_AS_PROPS );
	}

	/**
	 * Register a new job
	 *
	 * @param   string      $name           job name
	 * @param   callable    $callback       callback to execute
	 * @param   mixed       $expression     cron expression
	 * @return  self
	 **/
	public function registerTask( $name, callable $callback, $expression ) {
		$this->tasks[$name]  =  new \ArrayObject(
			array(
				'callback'      =>  $callback,
				'expression'    =>  $expression,
				'status'        =>  self::STATUS_ACTIVE,
				'started'       =>  0,
				'ended'         =>  0,
			),
			\ArrayObject::ARRAY_AS_PROPS
		);

		return $this;
	}

	/**
	 * Disable the execution of a job
	 *
	 * @param   string  $name       name of the task to disable
	 * @return  self
	 **/
	public function disableTask( $name ) {
		if( isset( $this->tasks[$name] ) && ( $this->tasks[$name]['status'] & self::STATUS_ACTIVE ) ) {
			$this->tasks[$name]['status']   ^=  self::STATUS_ACTIVE;
		}

		return $this;
	}

	/**
	 * Enable the execution of a job
	 *
	 * @param   string  $name       name of the job to enable
	 * @return  self
	 **/
	public function enableTask( $name ) {
		if( isset( $this->tasks[$name] ) ) {
			$this->tasks[$name]['status']   |=  self::STATUS_ACTIVE;
		}

		return $this;
	}

	/**
	 * Get list of tasks
	 *
	 * @return  \ArrayObject	list of registered tasks
	 **/
	public function getTasks() {
		return $this->tasks;
	}

	/**
	 * Executes cron tasks
	 *
	 * @param integer $sleep      sleep time (microseconds)
	 **/
	public function run( $sleep = FALSE ) {
		$now     =  time();

		foreach( $this->tasks as $name => $task ) {
			$file    =  $this->path . $name . '.lock';

			// Execute only if is ACTIVE, not executed, not RUNNING and not LOCKED
			if( ( $task['status'] == self::STATUS_ACTIVE ) && !$task->started ) {
				// Not running yet
				if( !file_exists( $file ) ) {
					// Should be executed ?
					if( $this->matchTime( $now, $task['expression'] ) ) {
						$task->status   |=  self::STATUS_RUNNING;
						$task->started   =  microtime( TRUE );

						// Lock the process
						file_put_contents( $file, getmypid() );
						// Execute task
						call_user_func( $task['callback'] );

						// Release the lock
						@unlink( $file );

						$task->status    =  self::STATUS_INACTIVE;
						$task->ended     =  microtime( TRUE );

						// Job finished, have a rest?
						if( $sleep && is_numeric( $sleep ) ) {
							usleep( $sleep );
						}
					}
				}
				// Already running
				else {
					$pid     =  file_get_contents( $file );

					if( $this->isRunning( $pid ) ) {
						$delta   =  time() - @filectime( $file );

						$task->status   |=  self::STATUS_RUNNING;

						// Takes too long, trigger a notice
						if( $delta > ( 60 * 60 ) ) {
							trigger_error( "{$name} (#{$pid}) takes more then " . sprintf( '%02d:%02d:%02d', floor( $delta / 3600 ), ( $delta / 60 ) % 60, $delta % 60 ) . " to be executed!" );
						}
					}
					// Release the lock
					else {
						@unlink( $file );
					}
				}
			}
		}
	}

	/**
	 * Check if a process is still running
	 *
	 * @param   integer $pid    process ID
	 * @return  bool            TRUE if the process is still active, FALSE otherwise
	 **/
	private function isRunning( $pid ) {
		$pids  =  explode( PHP_EOL, `ps -e | awk '{print $1}'` );

		if( in_array( $pid, $pids ) ) {
			return TRUE;
		}
		else {
			return FALSE;
		}
	}

	/**
	 * Check if the current time match the expression
	 *
	 * @param   mixed   $time           *NIX timestamp or strtodate() compatible time
	 * @param   string  $expression     *NIX crontab compatible expression
	 * @return  boolean                 TRUE if the time match the expression, FALSE otherwise
	 **/
	private function matchTime( $time, $expression ) {
		$cron    =  preg_split( '/\s+/', $expression, NULL, PREG_SPLIT_NO_EMPTY );

		// Syntax error
		if( count( $cron ) !== 5 ) {
			throw new \InvalidArgumentException( sprintf( 'Cron expression should have exactly 5 arguments, "%s" given', $expression ) );
		}

		// Convert string to *NIX timestamp
		if( is_string( $time ) ) {
			$time    =  strtotime( $time );
		}

		$date    =  getdate( $time );

		return $this->matchTimeComponent( $cron[0], $date['minutes'], self::TIME_MINUTE ) && $this->matchTimeComponent( $cron[1], $date['hours'], self::TIME_HOUR ) && $this->matchTimeComponent( $cron[2], $date['mday'], self::TIME_DAY ) && $this->matchTimeComponent( $cron[3], $date['mon'], self::TIME_MONTH ) && $this->matchTimeComponent( $cron[4], $date['wday'], self::TIME_WEEK );
	}

	/**
	 * Check if the task should be executed
	 *
	 * @param   string  $expression     *NIX crontab compatible expression
	 * @param   numeric $value          time component value
	 * @param   string  $type           time component key
	 * @return  bool                    TRUE if the element match the expression, FALSE otherwise
	 */
	private function matchTimeComponent( $expression, $value, $type ) {
		// Handle all match
		if( $expression === '*' ) {
			return TRUE;
		}

		// Handle multiple options
		if( strpos( $expression, ',' ) !== FALSE ) {
			$args  =  explode( ',', $expression );

			foreach( $args as $arg ) {
				if( $this->matchTimeComponent( $arg, $value, $type ) ) {
					return TRUE;
				}
			}

			return FALSE;
		}

		// Handle modulus
		if( strpos( $expression, '/' ) !== FALSE ) {
			$args  =  explode( '/', $expression );

			if( count( $args ) !== 2 ) {
				throw new \RuntimeException( sprintf( 'Invalid cron expression component: expecting match/modulus, "%s" given', $expression ) );
			}

			if( !is_numeric( $args[1] ) ) {
				throw new \RuntimeException( sprintf( 'Invalid cron expression component: expecting numeric modulus, "%s" given', $expression ) );
			}

			$expression  =  $args[0];
			$modulus     =  $args[1];
		}
		else {
			$modulus     =  1;
		}

		// Handle all match by modulus
		if( $expression === '*' ) {
			$from  =  0;
			$to    =  60;
		}
		// Handle range
		elseif( strpos( $expression, '-' ) !== FALSE ) {
			$args  =  explode( '-', $expression );

			if( count( $args ) !== 2 ) {
				throw new \RuntimeException( sprintf( 'Invalid cron expression component: expecting from-to structure, "%s" given', $expression ) );
			}

			$from  =  $this->toNumeric( $args[0], $type );
			$to    =  $this->toNumeric( $args[1], $type );
		}
		// Handle regular tokens
		else {
			$from  =  $this->toNumeric( $expression, $type );
			$to    =  $from;
		}

		// Final check
		if( ( $from === FALSE ) || ( $to === FALSE ) ) {
			throw new \RuntimeException( sprintf( 'Invalid cron expression component: expecting numeric or valid string, "%s" given', $expression ) );
		}

		return ( $value >= $from ) && ( $value <= $to ) && ( ( $value % $modulus ) === 0 );
	}

	/**
	 * toNumeric
	 *
	 * @param   mixed   $value
	 * @return  mixed
	 */
	private function toNumeric( $value, $type ) {
		$data    =  array();

		switch( $type ) {
			// Minutes
			case self::TIME_MINUTE:
				$data    =  range( 0, 59 );
				break;

			// Hours
			case self::TIME_HOUR:
				$data    =  range( 0, 23 );
				break;

			// Days
			case self::TIME_DAY:
				$data    =  range( 1, 31 );
				break;

			// Months
			case self::TIME_MONTH:
				$data    =  array(
					'jan'   =>  1,
					'feb'   =>  2,
					'mar'   =>  3,
					'apr'   =>  4,
					'may'   =>  5,
					'jun'   =>  6,
					'jul'   =>  7,
					'aug'   =>  8,
					'sep'   =>  9,
					'oct'   =>  10,
					'nov'   =>  11,
					'dec'   =>  12,
				);
				break;

			// Weekdays
			case self::TIME_WEEK:
				$data    =  array(
					'sun'   =>  0,
					'mon'   =>  1,
					'tue'   =>  2,
					'wed'   =>  3,
					'thu'   =>  4,
					'fri'   =>  5,
					'sat'   =>  6,
				);
				break;
		}

		// Numeric format
		if( is_numeric( $value ) ) {
			if( in_array( (int) $value, $data, TRUE ) ) {
				return $value;
			}
			else {
				return FALSE;
			}
		}

		// String format
		if( is_string( $value ) ) {
			$value   =  strtolower( substr( $value, 0, 3 ) );

			if( isset( $data[$value] ) ) {
				return $data[$value];
			}
		}

		return FALSE;
	}

}
