#PHP Store Locator

PHP Store Locator API

##Features
 - English or metric units
 - Easily adapted to your database environment (Uses PDO and prepared statements)
 - Returns distances
 - Highly customizable
 - Ability to add extra where clauses to the SQL query

##Example

	$options = array(
		'db_info'	=> array(
			'db_username'	=> 'username',
			'db_password'	=> 'password',
			'db_name'		=> 'database'
		),
		'locations_table'	=> 'storelocations',
		'position'	=> array(
			'lat'	=> $lat,
			'lng'	=> $lng
		),
		'return_columns'	=> array(
			'name',
			'address',
			'lat',
			'lng'
		),
		'units'	=> 'metric',
		'distance_decimals'	=>	3,
		'rules'		=> array(
			array(
				'format_string' => 'other_column = %s',
				'value'			=> 'value'
			)
			array(
				'format_string' => 'another_column like %s',
				'value'			=> '%value%'
			)
		)
	);

	$locator = new PHPStoreLocator( $options );
	
	try {
		$result = $locator->getLocations();
	}
	catch( Exception $e ) {
		if ( $e->getCode() == PHPStoreLocator::ERROR_DB_CONNECTION ) {
			$error_msg = 'Error Connecting to the database';
		}
		else {
			$error_msg = 'Error executing sql query';
		}
	}
	
	require( 'view.php' );

This will return a `StdClass` object with all the info you need for a store locator.

	stdClass Object	(
	
	    [radius] => 50
	    [units] => mi
	    [position] => stdClass Object
	        (
	            [lat] => 42.3584308
	            [lng] => -71.0597732
	        )
	
	    [columns] => Array
	        (
	            [0] => name
	            [1] => address
	            [2] => lat
	            [3] => lng
	        )
	
	    [rules] => 
	    [locations] => Array
	        (
	            [0] => stdClass Object
	                (
	                    [name] => Bertucci's Brick Oven Rstrnt
	                    [address] => 22 Merchants Row
	                    [lat] => 42.359146
	                    [lng] => -71.055473
	                    [distance] => 0.3
	                )
	
	            [1] => stdClass Object
	                (
	                    [name] => Pizzeria Regina: Regina Pizza
	                    [address] => 11 1/2 Thacher St
	                    [lat] => 42.365337
	                    [lng] => -71.056831
	                    [distance] => 0.6
	                )
	
	            [2] => stdClass Object
	                (
	                    [name] => Upper Crust
	                    [address] => 20 Charles St
	                    [lat] => 42.356606
	                    [lng] => -71.069679
	                    [distance] => 0.6
	                )
	
	            [3] => stdClass Object
	                (
	                    [name] => Bertucci's Brick Oven Rstrnt
	                    [address] => 43 Stanhope St
	                    [lat] => 42.348297
	                    [lng] => -71.073250
	                    [distance] => 1.2
	                )
	
	            [4] => stdClass Object
	                (
	                    [name] => Aquitaine
	                    [address] => 569 Tremont St
	                    [lat] => 42.343636
	                    [lng] => -71.072266
	                    [distance] => 1.4
	                )
	
	            [5] => stdClass Object
	                (
	                    [name] => Bertucci's Brick Oven Rstrnt
	                    [address] => 799 Main St
	                    [lat] => 42.363258
	                    [lng] => -71.097214
	                    [distance] => 2.3
	                )
	
	            [6] => stdClass Object
	                (
	                    [name] => Upper Crust
	                    [address] => 286 Harvard St
	                    [lat] => 42.342857
	                    [lng] => -71.122314
	                    [distance] => 4.0
	                )
	
	            [7] => stdClass Object
	                (
	                    [name] => Bertucci's Brick Oven Rstrnt
	                    [address] => 4 Brookline Pl
	                    [lat] => 42.331917
	                    [lng] => -71.115311
	                    [distance] => 4.1
	                )
	
	        )
	
	    [result_count] => 8
	    [total_locations] => 8
	    [limit_start] => 0
	    [limit_length] => 0
	    [location] => Boston, MA
	)