<?php
/**
 * JSON-RPC Client
 *
 * @package     JSON-RPC
 * @subpackage  Client
 *
 * @author      James Cunningham <j@goscale.com>
 * @copyright   Copyright 2013 James Cunningham
 */

namespace jsonrpc;


/**
 * JSON-RPC Client
 */
class client
{

	/* --- Private Variables ---
	   ------------------------------------------------------------ */
	
	/**
	 * JSON-RPC HTTP URL
	 *
	 * @var     string
	 * @access  private
	 */
	private $_url = '';
	
	/**
	 * Current Request ID
	 *
	 * @var     integer
	 * @access  private
	 */
	private $_id = 0;
	
	/**
	 * Notification State
	 *
	 * @var     boolean
	 * @access  private
	 */
	private $_notification = false;
	
	
	
	/* --- Constructs ---
	   ------------------------------------------------------------ */
	
	/**
	 * Create JSON-RCP Object
	 *
	 * @param   string   $url           JSON-RPC Server HTTP URL
	 * @param   boolean  $notification  Notification State
	 * @return  boolean
	 */
	public function __construct( $url, $notification = false )
	{
		// Validate Server URL
		if ( filter_var( $url, FILTER_VALIDATE_URL ) )
		{
			// Set Internal URL
			$this->_url = $url;
		} else
		{
			// Invalid URL Given
			throw new Exception( 'Invalid URL Format' );
		}
		
		
		// Set Notification State
		if ( is_bool( $notification ) )
		{
			$this->_notification = $notification;
		}
	}
	
	
	
	/* --- Catch-All Functions ---
	   ------------------------------------------------------------ */
	
	/**
	 * Catch All Function
	 *
	 * Catch All Function calls to object and send to JSON-RPC Server
	 *
	 * @param   string  $method  Method Name
	 * @param   array   $params  Parameters
	 * @return  mixed
	 */
	public function __call( $method, $params )
	{
		// Validate Method Name ("wont" ever be an issue)
		if ( !is_scalar( $method ) ) { throw new Exception( 'Invalid Method Name' ); }
		
		// Validate Params
		if ( is_array( $params ) )
		{
			// Remove Keys from Array (not required)
			$params = array_values( $params );
		} else
		{
			throw new Exception( 'Parameters must be given as array' );
		}
		
		
		// Make JSON-RPC Request
		$response = $this->jsonrpc_request( $method, $params );
		
		
		// Handle Exception
		if ( $response->error->type == 'exception' )
		{
			// Get Error
			$error = $response->error;
			
			throw new RemoteException( $error->message, $error->number, $error->file, $error->trace, $this->_url );
		}
		
		
		// Verify Response ID
		if ( $response->id != $this->_id ) { throw new \Exception( 'Unable to Confirm Response ID' ); }
		
		return $response->result;
	}
	
	
	
	/* --- Request Functions ---
	   ------------------------------------------------------------ */
	
	/**
	 * JSON-RPC Request
	 *
	 * @param   string  $method  Function Name
	 * @param   array   $params  Function Paramaters
	 *
	 * @return  array|boolean
	 */
	private function jsonrpc_request( $method, $params )
	{
		// Intialise Request Object
		$request = new \stdClass;
		
		
		// Set Request ID or Notification State
		if ( $this->_notification === true )
		{
			// Notification has no ID
			$request->id = null;
		} else
		{
			$this->_id++;					// Increment ID Number
			$request->id = $this->_id;		// Set Request ID
		}
		
		
		// Create Request
		$request->method = $method;
		$request->params = $params;
		
		// Encode JSON Request
		$request = json_encode( $request );
		
		// Execute HTTP Request
		$response = $this->http_request( $this->_url, 'POST', array( 'Content-Type: application/json' ), $request );
		
		// If Notification, return true
		if ( $this->_notification === true ) { return true; }
		
		// Decode JSON-RPC Reponse
		$object = json_decode( $response->response );
		
		if ( json_last_error() != JSON_ERROR_NONE ) { throw new \Exception( 'Invalid JSON Response' ); }
		
		return $object;
	}
	
	
	/**
	 * HTTP Request
	 *
	 * @param   string  $url             URL
	 * @param   string  $method          HTTP Method
	 * @param   array   $custom_headers  Custom Headers
	 * @param   string  $content         Content for POST/PUT
	 * @return  string
	 */
	private function http_request( $url, $method = 'GET', $custom_headers = null, $content = null )
	{
		// Validate URL
		if ( !filter_var( $url, FILTER_VALIDATE_URL ) ) { throw new \Exception( 'Invalid URL Format' ); }
		
		// Custom Options
		$options['method']  = $method;
		$options['header']  = implode( PHP_EOL, $custom_headers );
		$options['content'] = $content;
		
		// Set HTTP Context
		$options = array( 'http' => $options );
		$options = stream_context_create( $options );
		
		
		// Execute Request
		if ( $response = file_get_contents( $url, false, $options ) )
		{
			// Build Return
			$output = new \stdClass;
			$output->response = $response;
			$output->headers  = $this->http_headers( $http_response_header );
			
			return $output;	
		} else
		{
			// Failed To Connect
			throw new Exception( 'Could Not Connect To Server' );
		}
	}
	
	
	
	/* --- Supporting Functions ---
	   ------------------------------------------------------------ */
	
	/**
	 * HTTP Headers
	 *
	 * Parse HTTP Headers into a Key:Value array
	 *
	 * @param   array  $response  HTTP Response
	 * @return  array
	 */
	private function http_headers( $response )
	{
		// Remove First Item
		$output['method'] = array_shift( $response );
		
		// Iterate Array
		foreach( $response as $line )
		{
			// Split
			list( $name, $value ) = explode( ': ', $line, 2 );
			
			// Append to Output
			$output[$name] = trim( $value );
		}
		
		// Return Output
		return $output;
	}

}


/**
 * Exception
 */
class RemoteException extends \Exception
{
	protected	$_message;
	protected	$_code = 0;
	protected	$_file;
	private		$trace;
	private		$url;
	
	/**
	 * Construct
	 *
	 * @param   string   $message  Exception Message
	 * @param   integer  $code     Exception Number
	 * @param   array    $file     Filename and Line
	 * @param   array    $trace    Stack Trace
	 * @param   string   $url      Server URL
	 */
	public function __construct( $message, $code, $file, $trace, $url = '' )
	{
		$this->_message	= $message;
		$this->_code	= $code;
		$this->_file	= (array)$file;
		$this->trace	= $trace;
		$this->url		= $url;
		
		parent::__construct( $message, $code, null );
	}
	
	
	/**
	 * Exception To String
	 *
	 * @return  string
	 */
	public function __toString()
	{
		$output  = 'Remote Exception from ' . $this->url . ': ';		// Begin Exception String
		$output .= "'" . $this->_message . "' ";						// Exception Message
		
		if ( $this->_code != 0 ) { $output .= '( Code: ' . $this->_code . ' ) '; }	// Exception Number
		
		$output .= 'on line ' . $this->_file[ 'line' ] . ' of ' . $this->_file[ 'filename' ] . ' ';		// File Position
		
		return $output;
	}
}
