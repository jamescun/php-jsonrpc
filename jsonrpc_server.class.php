<?php
/**
 * JSON-RPC Server
 *
 * @package     JSON-RPC
 * @subpackage  Server
 *
 * @author      James Cunningham <j@goscale.com>
 * @copyright   Copyright 2013 James Cunningham
 */

namespace jsonrpc;


/**
 * JSON-RPC Server
 */
class server
{
	/* --- Core Functions ---
	   ------------------------------------------------------------ */
	
	/**
	 * Hook
	 *
	 * Hooks a class into JSON-RPC
	 *
	 * @param   object   $object  Class or Object to expose in JSON-RPC request
	 * @return  boolean  True on Success, False on Failure
	 */
	public static function hook( $object )
	{
		// Check if valid JSON-RPC Request
		if (  !self::valid_jsonrpc_request() ) { throw new Exception( 'Invalid JSON-RPC Request' ); }
		
			
		// Get JSON-RPC Request Data
		if ( !$request = self::get_request() ) { throw new Exception( 'Invalid or incomplete request given' ); }
		
		
		// Attempt to execute request against local object
		try
		{
			// Call Object Function (supress errors)
			$result = call_user_func_array( array( $object, $request->method ), $request->params );
			
			$response = new \stdClass;	// Initiate Response Class
			
			if ( empty( $result ) )
			{
				// Function does not exist or Error Occurred
				
				$err = error_get_last();
				
				$response->id		= $request->id;		// Request ID
				$response->result	= null;				// Null on Error
				$response->error	= $err['message'];	// Last Error Array
			} else
			{
				// Success
				
				$response->id		= $request->id;		// Request ID
				$response->result	= $result;			// Function Result
				$response->error	= null;				// No Error
			}
		
		} catch (Exception $e)
		{
			// Exception Encountered
			
			$response->id		= $request->id;		// Request ID
			$response->result	= null;				// Null on Error
			$response->error	= $e->getMessage();	// Exception Object
		}
		
		
		// Return Response
		if ( !empty( $request->id ) )
		{
			// Respond if not Notification
			
			$json = json_encode( $response );				// Encode JSON Response
			
			header( 'Content-SHA1: ' . sha1( $json ) );		// Calculate Checksum
			
			header( 'Content-Type: application/json' );		// Set JSON Content-Type
			
			echo $json;
		}
		
		// Success
		return true;
	}
	
	
	
	/* --- Request Processing ---
	   ------------------------------------------------------------ */
	
	/**
	 * Get JSON-RPC Request
	 *
	 * @return  array|boolean  Array of request parameters, or false on error
	 */
	private static function get_request()
	{
		// Get Contents of JSON stream
		$request = file_get_contents( 'php://input' );
		
		if ( empty( $request ) ) { return false; }		// No Data Given
		
		
		// Decode JSON Request
		$request = json_decode( $request );		// Decode into Object
		
		if ( json_last_error() != JSON_ERROR_NONE ) { return false; }		// Invalid JSON
		
		
		return $request;
	}
	
	
	
	/* --- Validation Functions ---
	   ------------------------------------------------------------ */
	
	/**
	 * Validate JSON-RPC Request
	 *
	 * @return  boolean  True on valid, False on Invalid
	 */
	private static function valid_jsonrpc_request()
	{
		if ( $_SERVER[ 'REQUEST_METHOD' ] != 'POST' ) { return false; }	// Not POST Request
		if ( $_SERVER[ 'HTTP_CONTENT_TYPE' ] != 'application/json' ) { return false; }	// Not Corrent Content-Type
		
		return true;	// Checks Passed
	}
	
	
	
	/* --- Error Handling ---
	   ------------------------------------------------------------ */
	
	/**
	 * Error Handler
	 *
	 * @param   integer  $errno    Error Number
	 * @param   string   $errstr   Error Message
	 * @param   string   $errfile  Error File
	 * @param   integer  $errline  Error Line
	 *
	 * @return  boolean
	 */
	public static function handle_error( $errno, $errstr, $errfile, $errline )
	{
		var_dump( $errno, $errstr, $errfile, $errline );
	}
	
	
	/**
	 * Exception Handler
	 *
	 * @param   exception  $e  Exception to Format
	 *
	 * @return  boolean
	 */
	public static function handle_exception( $e )
	{
		$error[ 'type' ] = 'exception';				// Error Type: Exception
		
		$error[ 'number' ]	= $e->getCode();		// Error Number
		$error[ 'message' ]	= $e->getMessage();		// Error Message
		
		$error[ 'file' ][ 'filename' ]	= $e->getFile();		// Error File
		$error[ 'file' ][ 'line' ]		= $e->getLine();		// Error File Line
		
		$error[ 'trace' ]	= $e->getTrace();					// Stack Trace
		
		// Reply JSON Error Object
		echo json_encode( array( 'result' => null, 'error' => $error, 'id' => 0 ) );
		
		return true;
	}
	
	
	/**
	 * Register Exception and Error Handlers
	 *
	 * @return  boolean
	 */
	public static function register_handlers()
	{
		// Register Error Handler
		set_error_handler( array( 'jsonrpc\server', 'handle_error' ) );
		
		// Register Exception Handler
		set_exception_handler( array( 'jsonrpc\server', 'handle_exception' ) );
		
		return true;
	}

}

