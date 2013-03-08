# PHP JSON-RPC #

PHP JSON-RPC is a simple set of classes which implements the [JSON-RPC specification](http://json-rpc.org/) over HTTP(S).

### Requirements ###

  * PHP 5.3.0+


## Usage ##

## Server ##

### jsonrpc\server::hook( object $object ) ###

This hooks the class given at `$object` and makes it available over JSON-RPC.

It is important to note that only one class may be hooked into JSON-RPC per request.


#### Example ####

File `test-server.php`:

    <?php
    
        // Include JSON-RPC Server Class
        require_once( 'jsonrpc_server.class.php' );
        
        
        // Example Class
        class example
        {
            public function ping()
            {
                return 'pong';
            }
        }
        
        // Create an instance of Example() class
        $myclass = new example();
        
        
        // Create JSON-RPC Server
        jsonrpc\server::hook( $myclass )
            or echo 'Invalid Request';
        
    ?>


### jsonrpc\server::register_handlers() ###

This is a custom exception and error handler provided to JSON-RPC which should trigger a `RemoteException` or `RemoteError` on the client side when presented with an exception or error on the server side. It is intended to be invoked automatically upon exception or error rather than by the user.

#### Example ####

    <?php
    
        // Include JSON-RPC Server Class
        require_once( 'jsonrpc_server.class.php' );
        
        // Register Exception and Error Handlers
        jsonrpc\server::register_handlers();
        
        // Or to register them manually
        set_error_handler( array( 'jsonrpc\server', 'handle_error' ) );
        set_exception_handler( array( 'jsonrpc\server', 'handle_exception' ) );
        
        
        // Hook Class
        jsonrpc\server::hook( $myclass );
    
    ?>



## Client ##

### jsonrpc_client( string $url, boolean $notification = false ) ###

This function creates the new overloaded object which can access a JSON-RPC server.

`$url` is the HTTP URL of the JSON-RPC Server.

`$notification` enabled the notification mode of JSON-RPC.


#### Example ####

File `test-client.php`:

    <?php
    
        // Include JSON-RPC Client Class
        require_once( 'jsonrpc_client.class.php' );
        
        
        // Initiate JSON-RPC Class
        $myclass = new jsonrpc\client( 'http://127.0.0.1/test-server.php' );
        
        
        // Access Foo Function
        echo $myclass->foo();
        
        // Returns: bar
    
    ?>


### RemoteException ###

`RemoteException` extends the built in exception system of PHP to allow for server-side generated exceptions and errors given by the remote object to be treated as if they were issued locally.

#### Example ####

    <?php
    
        // Include JSON-RPC Client Class
        require_once( 'jsonrpc_client.class.php' );
        
        
        // Initiate JSON-RPC Class
        $myclass = new jsonrpc\client( 'http://127.0.0.1/test-server.php' );
        
        try
        {
            // Access Foo Function
            echo $myclass->foo();
        }
        
        catch ( jsonrpc\RemoteException $e )
        {
            echo 'Exception Given: ' . $e->getMessage();
        }
    
    ?>