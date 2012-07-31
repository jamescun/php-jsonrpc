<?php
/**
 * JSON-RPC Client
 *
 * @package     JSON-RPC
 * @subpackage  Client
 *
 * @author      James Cunningham <james@stackblaze.com>
 * @copyright   Copyright 2012 StackBlaze Inc
 */


/**
 * JSON-RPC Client
 */
class jsonrpc_client
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
		
		
		// Intialise Request Object
		$request = new stdClass;
		
		
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
		
		// Build Custom HTTP Headers
		$headers = array( 'Content-Type: application/json' );
		
		// Execute HTTP Request
		$response = $this->http_request( $this->_url, 'POST', $headers, $request );
		
		
		// If Notification, return true
		if ( $this->_notification === true ) { return true; }
		
		// Decode JSON-RPC Reponse
		$object = json_decode( $response->response, true );
		
		
		// Verify Response ID
		if ( $object['id'] != $this->_id ) { throw new Exception( 'Unable to Confirm Response ID' ); }
		
		
		// Handle Error/Exception
		if ( !empty( $object['error'] ) ) { throw new Exception( $object['error'] ); }
		
		
		return $object['result'];
	}
	
	
	
	/* --- Request Functions ---
	   ------------------------------------------------------------ */
	
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
		if ( !filter_var( $url, FILTER_VALIDATE_URL ) ) { throw new Exception( 'Invalid URL Format' ); }
		
		// Custom Options
		$options['method']  = $method;
		$options['header']  = implode( PHP_EOL, $custom_headers );
		$options['content'] = $content;
		
		// Set HTTP Context
		$options = array( 'http' => $options );
		$options = stream_context_create( $options );
		
		
		// Execute Request
		$response = file_get_contents( $url, false, $options );
		
		
		// Build Return
		
		$output = new stdClass;
		$output->response = $response;
		$output->headers  = $this->http_headers( $http_response_header );
		
		return $output;
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
