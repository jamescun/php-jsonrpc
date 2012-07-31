<?php
/**
 * JSON-RPC Server
 *
 * @package     JSON-RPC
 * @subpackage  Server
 *
 * @author      James Cunningham <james@stackblaze.com>
 * @copyright   Copyright 2012 StackBlaze Inc
 */


/**
 * JSON-RPC Server
 */
class jsonrpc_server
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
		if (  jsonrpc_server::valid_jsonrpc_request() === false ) { return false; }
			
		// Get JSON-RPC Request Data
		$request = file_get_contents( 'php://input' );		// Get Input Stream Contents
		$request = json_decode( $request );					// Decode into Object
		
		// Check for JSON Decode Error
		if ( json_last_error() != JSON_ERROR_NONE ) { return false; }
		
		
		// Attempt to execute request against local object
		try
		{
			// Call Object Function (supress errors)
			$result = @call_user_func_array( array( $object, $request->method ), $request->params );
			
			$response = new stdClass;	// Initiate Response Class
			
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
	
	
	
	/* --- Internal Functions ---
	   ------------------------------------------------------------ */
	
	/**
	 * Valid JSON-RPC Request
	 *
	 * @return  boolean  True on valid, False on Invalid
	 */
	public static function valid_jsonrpc_request()
	{
		if ( $_SERVER['REQUEST_METHOD'] != 'POST' ) { return false; }	// Not POST Request
		if ( $_SERVER['HTTP_CONTENT_TYPE'] != 'application/json' ) { return false; }	// Not Corrent Content-Type
		
		return true;	// Checks Passed
	}

}

