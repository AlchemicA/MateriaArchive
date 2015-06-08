<?php

namespace Materia\Mail;

/**
 * Message class
 *
 * @package Materia.Mail
 * @author  Filippo "Pirosauro" Bovo
 * @link    http://lab.alchemica.it/materia/
 **/

class Message implements \ArrayAccess {

	protected $from;
	protected $wrap;
	protected $to;
	protected $cc;
	protected $subject;
	protected $text;
	protected $html;
	protected $headers;
	protected $attachments;
	protected $boundaries;
	protected $sent;
	protected $charset;

	private $eol;

	/**
	 * Constructor
	 **/
	public function __construct( $eol = "\r\n" ) {
		$this->reset();

		$this->eol	 =	$eol;
	}

	/**
	 * magic __toString
	 *
	 * @return	string
	 **/
	public function __toString() {
		return $this->build();
	}

	/**
	 * @see	ArrayAccess::offsetSet()
	 **/
	public function offsetSet( $offset, $value ) {
		throw new \RuntimeException( 'Attempt to write a read-only object, use set*() methods instead' );
	}

	/**
	 * @see	ArrayAccess::offsetExists()
	 **/
	public function offsetExists( $offset ) {
		$offset	 =	strtolower( $offset );

		return isset( $this->$offset );
	}

	/**
	 * @see	ArrayAccess::offsetUnset()
	 **/
	public function offsetUnset( $offset ) {

	}

	/**
	 * @see	ArrayAccess::offsetGet()
	 **/
	public function offsetGet( $offset ) {
		$offset	 =	strtolower( $offset );

		return isset( $this->$offset ) ? $this->$offset : NULL;
	}

	/**
	 * Reset/initialize all vars
	 *
	 * @return  $this
	 **/
	public function reset() {
		if( !isset( $this->sent ) ) {
			$this->from			 =	[];
			$this->to			 =	[];
			$this->cc			 =	[];
			$this->headers		 =	[];
			$this->subject		 =	NULL;
			$this->text			 =	NULL;
			$this->html			 =	NULL;
			$this->wrap			 =	96;
			$this->attachments	 =	[];
			$this->boundaries	 =	$this->generateBoundaries();
			$this->sent			 =	FALSE;
			$this->charset		 =	'UTF-8';
		}

		return $this;
	}

	/**
	 * Set wrap
	 *
	 * @param	integer	$wrap	the number of characters at which the message will wrap
	 * @return	$this
	 **/
	public function setWrap( $wrap = 96 ) {
		$this->wrap  =  ( $wrap < 1 ) ? 96 : $wrap;

		return $this;
	}

	/**
	 * Get wrap
	 *
	 * @return	integer
	 **/
	public function getWrap() {
		return $this->wrap;
	}

	/**
	 * Set sender
	 *
	 * @param	string	$email		email to send as from
	 * @param	string	$name		name to send as from
	 * @return	$this
	 **/
	public function setFrom( $email, $name = NULL ) {
		// From recipient should not be encoded to UTF-8, likely an obfuscation technique
		$this->from	 =	[ $email => $this->formatMailHeader( $email, $name ) ];

		return $this;
	}

	/**
	 * Get sender
	 *
	 * @return	array
	 **/
	public function getFrom() {
		return $this->from;
	}

	/**
	 * Set a "To" recipient
	 *
	 * @param	string	$email    the email address to send to
	 * @param   string	$name     the name of the person to send to
	 * @return  $this
	 **/
	public function setTo( $email, $name = NULL ) {
		$this->to[$email]	 =  $this->formatMailHeader( $email, $name );

		return $this;
	}

	/**
	 * Returns the list of formatted "To" recipients
	 *
	 * @return	array
	 **/
	public function getTo() {
		return $this->to;
	}

	/**
	 * Set a "Cc" recipient
	 *
	 * @param	string	$email		the email address to send to
	 * @param	string	$name 		the name of the person to send to
	 * @return	$this
	 **/
	public function setCc( $email, $name = NULL ) {
		$this->cc[$email]	 =	$this->formatMailHeader( $email, $name );

		return $this;
	}

	/**
	 * Return the list of formatted "Cc" recipients
	 *
	 * @return	array
	 **/
	public function getCc() {
		return $this->cc;
	}

	/**
	 * Set message subject
	 *
	 * @param	string	$subject		the email subject
	 * @return  $this
	 **/
	public function setSubject( $subject ) {
		$this->subject	 =	$this->encodeUFT8( $this->filterOther( $subject ) );

		return $this;
	}

	/**
	 * Returns message subject
	 *
	 * @return	string
	 **/
	public function getSubject() {
		return $this->subject;
	}

	/**
	 * Set body message
	 *
	 * @param	string	$message		the message to send
	 * @return  $this
	 **/
	public function setBody( $body ) {
		// contains HTML
		if( $body != strip_tags( $body ) ) {
			$this->html	 =	$body;
		}
		else {
			$this->text	 =	str_replace( "\n.", "\n..", (string) $body );
		}

		return $this;
	}

	/**
	 * Returns body message
	 *
	 * @return	string
	 **/
	public function getBody( $html = FALSE ) {
		return $html ? $this->html : $this->text;
	}

	/**
	 * Add an attachment to the message
	 *
	 * @param	string	$path    		the file path to the attachment
	 * @param	string	$filename		the filename of the attachment when emailed
	 * @return  $this
	 **/
	public function setAttachment( \SplFileInfo $file ) {
		if( file_exists( $file->getRealPath() ) && $file->isFile() && !in_array( $file, $this->attachments ) ) {
			// $info	 =	array(
			// 	'size'	=>	$file->getSize(),
			// 	'path'	=>	$file->getRealPath(),
			// 	'file'	=>	$file->getFilename(),
			// 	'data'	=>	$this->getAttachmentData( $file ),
			// );
			$this->attachments[]	 =	$file;
		}

		return $this;
	}

	/**
	 * Returns if the email has any registered attachment
	 *
	 * @return	boolean
	 **/
	public function hasAttachments() {
		return !empty( $this->attachments );
	}

	/**
	 * Get attachment data
	 *
	 * @param	string	$file	attachment file
	 * @return	string
	 */
	protected function getAttachmentData( \SplFileInfo $file ) {
		if( file_exists( $file->getRealPath() ) && $file->isFile() && $file->isReadable() ) {
			$attachment  =  file_get_contents( $file->getRealPath(), LOCK_EX );

			return chunk_split( base64_encode( $attachment ) );
		}

		return NULL;
	}

	/**
	 * Add mail header
	 *
	 * @param	string	$header		the header to add
	 * @param	string	$email		the email to add
	 * @param	string	$name		the name to add
	 **/
	protected function setMailHeader( $header, $email, $name = NULL ) {
		$address   =  $this->formatMailHeader( $email, $name );

		$this->setHeader( $header, $address );
		// $this->headers[$header]	 =	sprintf( '%s: %s', (string) $header, $address );
	}

	/**
	 * Formats a display address for emails according to RFC2822
	 *
	 * @param	string	$email	the email address
	 * @param	string	$name	the displayed name
	 * @return	string
	 **/
	protected function formatMailHeader( $email, $name = NULL ) {
		$email	 =	$this->filterEmail( (string) $email );

		// Just email address, nothing to do
		if( empty( $name ) ) {
			return $email;
		}

		$name	 =	$this->encodeUFT8( $this->filterName( $name ) );

		return sprintf( '%s <%s>', $name, $email );
	}

	/**
	 * Add generic header
	 *
	 * @param	string	$header		the header to add
	 * @param	mixed	$value		the value of the header
	 * @return	$this
	 **/
	public function setHeader( $header, $value ) {
		// $this->headers[]   =  sprintf( '%s: %s', (string) $header, (string) $value );
		if( is_string( $header ) ) {
			$this->headers[$header]	 =	(string) $value;
		}

		return $this;
	}

	/**
	 * Return the headers registered so far as an array or as string ready to send
	 *
	 * @param	boolean	$send	if TRUE returns headers formatted for sending
	 * @return	mixed
	 **/
	public function getHeaders() {
		return $this->headers;
	}

	/**
	 * Encode a string to UTF-8
	 *
	 * @param	string	$value	the value to encode
	 * @return	string
	 **/
	public function encodeUFT8( $value ) {
		$value   =  trim( $value );

		if( preg_match( '/(\s)/', $value ) ) {
			return $this->encodeUFT8Words( $value );
		}

		return $this->encodeUFT8Word( $value );
	}

	/**
	 * Encode a single word to UTF-8
	 *
	 * @param	string	$value	the word to encode
	 * @return	string
	 **/
	protected function encodeUFT8Word( $value ) {
		return sprintf( '=?UTF-8?B?%s?=', base64_encode( $value ) );
	}

	/**
	 * Encode words to UTF-8
	 *
	 * @param	string	$value	the words to encode
	 * @return	string
	 **/
	protected function encodeUFT8Words( $value ) {
		$words		 =	preg_split( '/[\s]+/', $value, -1, PREG_SPLIT_NO_EMPTY ); // explode( ' ', $value );
		$encoded	 =	[];

		foreach( $words as $word ) {
			$encoded[]	 =	$this->encodeUFT8Word( $word );
		}

		return join( $this->encodeUFT8Word( ' ' ), $encoded );
	}

	/**
	 * Removes any unwanted characters from email address
	 *
	 * @param	string	$email	the email to filter
	 * @return	string
	 **/
	public function filterEmail( $email ) {
		$rule	 =	[
			"\r"	=>	'',
			"\n"	=>  '',
			"\t"	=>  '',
			'"'		=>  '',
			','		=>  '',
			'<'		=>  '',
			'>'		=>  '',
		];

		$email	 =	strtr( $email, $rule );

		return filter_var( $email, FILTER_SANITIZE_EMAIL );
	}

	/**
	 * Removes any unwanted characters from name
	 *
	 * @param	string	$name	the name to filter
	 * @return	string
	 **/
	public function filterName( $name ) {
		$rule	 =	[
			"\r"	=>	'',
			"\n"	=>	'',
			"\t"	=>	'',
			'"'		=>	"'",
			'<'		=>	'[',
			'>'		=>	']',
		];

		$name	 =	filter_var( $name, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES );
		$name	 =	trim( strtr( $name, $rule ) );

		return $name;
	}

	/**
	 * Removes any carriage return, line feed or tab characters
	 *
	 * @param	string	$data	the data to filter
	 * @return	string
	 **/
	public function filterOther( $data ) {
		$rule	 =	[
			"\r"	=>	'',
			"\n"	=>	'',
			"\t"	=>	'',
		];

		return strtr( filter_var( $data, FILTER_SANITIZE_STRING ), $rule );
	}

	/**
	 * Generate boundary values
	 *
	 * @param	integer	$count		number of values
	 * @return	array
	 **/
	protected function generateBoundaries( $count = 3 ) {
		$values	 =	[];

		for( $c = 0; $c < $count; $c++ ) {
			$values[]	 =	md5( uniqid( time() ) );
		}

		return $values;
	}

	/**
	 * getWrapMessage()
	 *
	 * @return string
	 */
	protected function getWrapMessage( $break = PHP_EOL, $cut = FALSE ) {
		$lines	 =	explode( $break, $this->message );
		$width	 =	$this->wrap;

		foreach( $lines as &$line ) {
			$line	 =	rtrim( $line );

			if( mb_strlen( $line ) <= $width ) {
				continue;
			}

			$words	 =	explode( ' ', $line );
			$line	 =	NULL;
			$actual	 =	NULL;

			foreach( $words as $word ) {
				if( mb_strlen( $actual . $word ) <= $width ) {
					$actual	.=	$word . ' ';
				}
				else {
					if( $actual != '' )
						$line	.=	rtrim( $actual ) . $break;

					$actual	 =	$word;

					if( $cut ) {
						while( mb_strlen( $actual ) > $width ) {
							$line	.=	mb_substr( $actual, 0, $width ) . $break;
							$actual	 =	mb_substr( $actual, $width );
						}
					}

					$actual	.=	' ';
				}
			}

			$line	.=	trim( $actual );
		}

		return implode( $break, $lines );
	}

	/**
	 * Build the message
	 *
	 * @return	string
	 **/
	public function build() {
		$message	 =	NULL;
		$message	.=	'MIME-Version: 1.0' . $this->eol;
		$message	.=	'Date: ' . date( 'r' ) . $this->eol;
		$message	.=	'Message-ID: <' . md5( 'TX' . md5( time() ) . uniqid() ) . '@' . current( explode( '@', key( $this->from ) ) ) . '>' . $this->eol;

		if( !isset( $this->headers['Return-Path'] ) ) {
			$message	.=	'Return-Path: ' . $this->formatMailHeader( key( $this->from ), current( $this->from ) ) . $this->eol;
		}

		if( !isset( $this->headers['X-Priority'] ) ) {
			$message	.=	'X-Priority: 3' . $this->eol;
		}

		if( !isset( $this->headers['X-Mailer'] ) ) {
			$message	.=	'X-Mailer: Materia (https://github.com/AlchemicA/Materia)' . $this->eol;
		}

		foreach( $this->headers as $key => $value ) {
			if( !in_array( $key, [ 'MIME-Version', 'Date', 'Message-ID' ] ) )
           		$message	.=	$key . ': ' . $value . $this->eol;
        }

		$message	.=	'From: ' . $this->formatMailHeader( key( $this->from ), current( $this->from ) ) . $this->eol;
		$message	.=	'To: '. join( ', ', $this->to ) . $this->eol;
		$message	.=	'Subject: ' . $this->subject . $this->eol;

		if( $this->hasAttachments() ) {
			$message	.=	$this->buildBodyWithAttachments( $this->boundaries[0], $this->boundaries[1] );
		}
		else {
			$message	.=	$this->buildBody( $this->boundaries[0] );
		}

		return $message;
	}

	public function buildHeaders() {
		$message	 =	NULL;
		$message	.=	'MIME-Version: 1.0' . $this->eol;
		$message	.=	'Date: ' . date( 'r' ) . $this->eol;
		$message	.=	'Message-ID: <' . md5( 'TX' . md5( time() ) . uniqid() ) . '@' . current( explode( '@', key( $this->from ) ) ) . '>' . $this->eol;

		if( !isset( $this->headers['Return-Path'] ) ) {
			$message	.=	'Return-Path: ' . $this->formatMailHeader( key( $this->from ), current( $this->from ) ) . $this->eol;
		}

		if( !isset( $this->headers['X-Priority'] ) ) {
			$message	.=	'X-Priority: 3' . $this->eol;
		}

		if( !isset( $this->headers['X-Mailer'] ) ) {
			$message	.=	'X-Mailer: Materia (https://github.com/AlchemicA/Materia)' . $this->eol;
		}

		foreach( $this->headers as $key => $value ) {
			if( !in_array( $key, [ 'MIME-Version', 'Date', 'Message-ID' ] ) )
           		$message	.=	$key . ': ' . $value . $this->eol;
        }

		$message	.=	'From: ' . $this->formatMailHeader( key( $this->from ), current( $this->from ) ) . $this->eol;
		$message	.=	'To: '. join( ', ', $this->to ) . $this->eol;
		$message	.=	'Subject: ' . $this->subject . $this->eol;

		return $message;
	}

	/**
	 * Create email body
	 *
	 * @param	string	$boundary		boundary
	 * @return	string
	 **/
	protected function buildBody( $boundary ) {
		$body	 =  NULL;
		$body	.=  "Content-Type: multipart/alternative; boundary=\"{$boundary}\"" . $this->eol;
		$body	.=  $this->eol;
		$body	.=  "--{$boundary}" . $this->eol;
		$body	.=  "Content-Type: text/plain; charset=\"{$this->charset}\"" . $this->eol;
		$body	.=  "Content-Transfer-Encoding: quoted-printable" . $this->eol;
		$body	.=  $this->eol;
		$body	.=  ( empty( $this->text ) ? $this->formatPlainText( $this->html ) : $this->text ) . $this->eol;
		$body	.=  $this->eol;
		$body	.=  "--{$boundary}" . $this->eol;
		$body	.=  "Content-Type: text/html; charset=\"{$this->charset}\"" . $this->eol;
		$body	.=  "Content-Transfer-Encoding: quoted-printable" . $this->eol;
		$body	.=  $this->eol;
		$body	.=  ( empty( $this->html ) ? nl2br( $this->text ) : $this->html ) . $this->eol;
		$body	.=  $this->eol;
		$body	.=  "--{$boundary}--" . $this->eol;

		return $body;
	}

	/**
	 * Create email body with attachments
	 *
	 * @param	string	$boundary		boundary
	 * @param	string	$alternative	alternative boundary
	 * @return	string
	 **/
	protected function buildBodyWithAttachments( $boundary, $alternative ) {
		$body	 =	NULL;
		$body	.=	"Content-Type: multipart/related; boundary=\"{$boundary}\"" . $this->eol;
		$body	.=	$this->eol;
		$body	.=	"--{$boundary}" . $this->eol;
		// $body	.=	$this->eol;
		$body	.=	"Content-Type: multipart/alternative; boundary=\"{$alternative}\"" . $this->eol;
		$body	.=	$this->eol;
		$body	.=	"--{$alternative}" . $this->eol;
		$body	.=	"Content-Type: text/plain; charset=\"{$this->charset}\"" . $this->eol;
		// $body	.=	"Content-Transfer-Encoding: base64" . $this->eol;
		$body	.=	$this->eol;
		$body	.=	( empty( $this->text ) ? $this->formatPlainText( $this->html ) : $this->text ) . $this->eol;
		$body	.=	$this->eol;
		$body	.=	"--{$alternative}" . $this->eol;
		$body	.=	"Content-Type: text/html; charset=\"{$this->charset}\"" . $this->eol;
		// $body	.=	"Content-Transfer-Encoding: base64" . $this->eol;
		$body	.=	$this->eol;
		$body	.=	( empty( $this->html ) ? nl2br( $this->text ) : $this->html ) . $this->eol;
		$body	.=	$this->eol;
		$body	.=	"--{$alternative}--" . $this->eol;

		// Attachments
		foreach( $this->attachments as $attachment ){
			$body	.=	$this->eol;
			$body	.=	'--' . $boundary . $this->eol;
			$body	.=	'Content-Type: application/octet-stream; name="' . $attachment->getFilename() . '"' . $this->eol;
			$body	.=	'Content-Transfer-Encoding: base64' . $this->eol;
			$body	.=	'Content-Disposition: attachment; filename="' . $attachment->getFilename() . '"' . $this->eol;
			$body	.=	$this->eol;
			$body	.=	$this->getAttachmentData( $attachment ) . $this->eol;
			// $body	.=	$this->eol;
		}

		$body	.=	$this->eol;
		$body	.=	"--{$boundary}--" . $this->eol;

		return $body;
	}

	/**
	 * Convert HTML message into plain text
	 *
	 * @param	string	$message	HTML message
	 * @return	string
	 **/
	protected function formatPlainText( $message ) {
		$message	 =	str_replace( [ "\r", "\n", "\t" ], '', $message );
		$message	 =	str_ireplace( $message, '</p>', '</p>' . $this->eol . $this->eol );
		$message	 =	preg_replace( '/\<br(\s*)?\/?\>/i', $this->eol, $message );

		return strip_tags( $message );
	}

}