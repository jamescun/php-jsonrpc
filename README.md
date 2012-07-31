# PHP JSON-RPC #

PHP JSON-RPC is a simple set of classes which implements the [JSON-RPC specification](http://json-rpc.org/) over HTTP(S).

### Requirements ###

  * PHP 5.3.0+


## Usage ##

## Server ##

### Function: Hook ###

#### jsonrpc_server::hook( object $object ) ####

This function parses the current HTTP request and calls the requested method from the object specified at `$object`.


#### Example ####

File `test-server.php`:

    <?php
    
        // Include JSON-RPC Server Class
        require_once( 'jsonrpc_server.class.php' );
        
        
        // Example Class
        class example
        {
            public function foo()
            {
                return 'bar';
            }
        }
        
        // Create an instance of Example() class
        $myclass = new example();
        
        
        // Create JSON-RPC Server
        jsonrpc_server::hook( $myclass )
            or echo 'Invalid Request';
        
    ?>




## Client ##

### Function: __construct ###

#### $client = new jsonrpc_client( string $url, boolean $notification = false ) ####

This function creates the new overloaded object which can access a JSON-RPC server.

`$url` is the HTTP URL of the JSON-RPC Server.
`$notification` enabled the notification mode of JSON-RPC.


#### Example ####

File `test-client.php`:

    <?php
    
        // Include JSON-RPC Client Class
        require_once( 'jsonrpc_client.class.php' );
        
        
        // Initiate JSON-RPC Class
        $myclass = new jsonrpc_client( 'http://127.0.0.1/test-server.php' );
        
        
        // Access Foo Function
        echo $myclass->foo();
        
        // Returns: bar
    
    ?>
