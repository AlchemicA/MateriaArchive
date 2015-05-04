<?php

namespace Materia\Debug;

/**
 * A class to help in gauging page load time of PHP applications (*NIX only)
 *
 * @package Materia.Debug
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class Benchmark {

	protected $data	 =	[];

	private $formater;

	/**
	 * Set the format for the result to be returned
	 *
	 * @param	\Materia\Data\Formatter	$formater
	 * @return	$this
	 **/
	public function setFormatter( \Materia\Data\Formatter $formatter ) {
		$this->formatter	 =	$formatter;

		return $this;
	}

	/**
	 * Set a step for benchmarking
	 *
	 * @param	string	$label			step's label
	 * @return	$this
	 **/
	public function step( $label ) {
		$this->data[$label]	 =	[
			'time'		=>	microtime( TRUE ),
			'memory'	=>	memory_get_usage(),
			'peak'		=>	memory_get_peak_usage(),
			'usage'		=>	getrusage(),
		];

		return $this;
	}

	/**
	 * Get stats
	 *
	 * @param	string	$primary	primary label
	 * @param	string	$secondary	secondary label
	 * @return	mixed
	 **/
	public function getReport( $primary, $secondary ) {
		// Get server load in last minute
		$load		 =	sys_getloadavg();
		// Get memory limit
		$mlimit		 =	ini_get( 'memory_limit' );

		if( strpos( $mlimit, 'M' ) ) {
			$mlimit		 =	str_replace( 'M', '', $mlimit ) * 1024 * 1024;
		}
		else if( strpos( $mlimit, 'G' ) ) {
			$mlimit		 =	str_replace( 'G', '', $mlimit ) * 1024 * 1024 * 1024;
		}

		$primary	 =	$this->data[$primary];
		$secondary	 =	$this->data[$secondary];
		$usage		 =	$this->getUsageDifference( $primary['usage'], $secondary['usage'] );

		$result	 =	[
			'time'			=>	$secondary['time'] - $primary['time'],	// Clock time in seconds (with miscoseconds)
			'utime'			=>	$usage['ru_utime.tv'], // Time taken in User Mode in seconds (with miscoseconds)
			'stime'			=>	$usage['ru_stime.tv'],	//Time taken in System Mode in seconds (with miscoseconds)
			'ktime'			=>	$usage['ru_stime.tv'] + $usage['ru_utime.tv'],	// Total time taken in Kernel in seconds (with miscoseconds)
			'maxrss'		=>	$usage['ru_maxrss'] * 1024,	// Maximum resident shared size
			'ixrss'			=>	$usage['ru_ixrss'],	// Integral shared memory size
			'idrss'			=>	$usage['ru_idrss'],	// Integral unshared data size
			'mlimit'		=>	$mlimit,	// Memory limit
			'musage'		=>	$secondary['memory'] - $primary['memory'],	// Memory usage
			'mpeak'			=>	$secondary['peak'] - $primary['peak'],	// Peak memory usage
			'load'			=>	$load['0'],	// Average server load in last minute
		];

		if( isset( $this->formatter ) ) {
			return $this->formatter->encode( $result );
		}
		else {
			return $result;
		}
	}

	/**
	 * Get difference of arrays with keys intact
	 *
	 * @param	array	$primary		minuend
	 * @param	array	$secondary		subtrahend
	 * @return	array
	 **/
	private function getUsageDifference( array $primary, array $secondary ) {
		$array	 =	[];

		// Add user mode time
		$primary['ru_utime.tv']		 =	( $primary['ru_utime.tv_usec'] / 1000000 ) + $primary['ru_utime.tv_sec'];
		$secondary['ru_utime.tv']	 =	( $secondary['ru_utime.tv_usec'] / 1000000 ) + $secondary['ru_utime.tv_sec'];
		// Add system mode time
		$primary['ru_stime.tv']		 =	( $primary['ru_stime.tv_usec'] / 1000000 ) + $primary['ru_stime.tv_sec'];
		$secondary['ru_stime.tv']	 =	( $secondary['ru_stime.tv_usec'] / 1000000 ) + $secondary['ru_stime.tv_sec'];

		// Unset time splits
		unset( $primary['ru_utime.tv_usec'] );
		unset( $primary['ru_utime.tv_sec'] );
		unset( $secondary['ru_utime.tv_usec'] );
		unset( $secondary['ru_utime.tv_sec'] );
		unset( $primary['ru_stime.tv_usec'] );
		unset( $primary['ru_stime.tv_sec'] );
		unset( $secondary['ru_stime.tv_usec'] );
		unset( $secondary['ru_stime.tv_sec'] );

		// Iterate over values
		foreach( $primary as $key => $value ) {
			$array[$key]	 =	$secondary[$key] - $primary[$key];
		}

		return $array;
	}

}