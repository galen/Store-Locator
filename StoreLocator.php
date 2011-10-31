<?php

/**
 * PHP Store Locator
 * @author Galen Grover <galenjr@gmail.com>
 * @package PHPStoreLocator
*/

/**
 * PHP Store Locator
 *
 * Here is the minimum implementation:
 *
 * require( 'path/to/Store-Locator/Store-Locator.php' );
 *
 * $locator = new PHPStoreLocator;
 * $locator->setDbInfo( 'username', 'password', 'database' );
 * $locator->setPosition( $lat, $lng );
 *
 * try {
 * 	  $result =  $locator->getLocations();
 * }
 * catch( Exception $e ) {
 * 	  if ( $e->getCode() == PHPStoreLocator::ERROR_DB_CONNECTION ) {
 * 		  $error_msg = 'Error Connecting to the database';
 * 	  }
 * 	  else {
 * 		  $error_msg = 'Error executing sql query';
 *		  error_log( $e->getMessage );
 * 	  }
 * }
 * require( 'view.php' );
 *
 */

class PHPStoreLocator {

	/**
	 * Miles to Kilometers conversion
	 *
	 * @var float
	 */
	const MILES_TO_KILOMETERS = 1.6093;

	/**
	 * Radius of the earth in miles
	 *
	 * @var float
	 */
	const EARTH_RADIUS = 3963.1676;

	/**
	 * Error code for connection
	 *
	 * getLocations() throws this exception code for a database connection related error
	 *
	 * @var int
	 */
	const ERROR_DB_CONNECTION = 1;

	/**
	 * Error code for an invalid query
	 * 
	 * getLocations() throws this exception code for any database errors except connection related errors.
	 * This is usually because invalid parameters were passed and therefore the sql query was invalid
	 *
	 * @var int
	 */
	const ERROR_QUERY = 2;

	/**
	 * Name of the table that contains the locations
	 * 
	 * @see setLocationsTable()
	 *
	 * @var string
	 */
	protected $locations_table = 'locations';

	/**
	 * Name of the latitude column in the table
	 *
	 * @see setLalLngColumns()
	 *
	 * @var string
	 */
	protected $latitude_column = 'lat';

	/**
	 * Name of the longitude column in the table
	 *
	 * @see setLalLngColumns()
	 *
	 * @var string
	 */
	protected $longitude_column = 'lng';

	/**
	 * Databse username
	 *
	 * @see setDbInfo()
	 *
	 * @var string
	 */
	protected $db_username;

	/**
	 * Databse password
	 *
	 * @see setDbInfo()
	 *
	 * @var string
	 */
	protected $db_password;

	/**
	 * Databse name
	 *
	 * @see setDbInfo()
	 *
	 * @var string
	 */
	protected $db_name;

	/**
	 * Databse host
	 *
	 * @see setDbInfo()
	 *
	 * @var string
	 */
	protected $db_host = 'localhost';

	/**
	 * Databse type
	 *
	 * @see setDbInfo()
	 *
	 * @var string
	 */
	protected $db_type = 'mysql';

	/**
	 * Return columns
	 *
	 * The table columns to return
	 *
	 * @see setReturnColumns()
	 *
	 * @var mixed
	 */
	 protected $return_columns = '*';

	/**
	 * Search radius
	 *
	 * @see setRadius()
	 *
	 * @var int
	 */
	protected $radius = 50;

	/**
	 * Limit start
	 *
	 * Where in the result to start the return from
	 *
	 * @see setLimit()
	 *
	 * @var int
	 */
	protected $limit_start = 0;

	/**
	 * Limit length
	 *
	 * Maximum results to return
	 *
	 * @see setLimit()
	 *
	 * @var int
	 */
	protected $limit_length = 0;

	/**
	 * Unit system to use
	 * 
	 * @see useEnglishUnits()
	 * @see useMetricUnits()
	 *
	 * @var string
	 */
	protected $current_unit_system = 'english';

	/**
	 * English units
	 *
	 * @see setEnglishunits()
	 *
	 * @var string
	 */
	protected $units_english = 'mi';

	/**
	 * Metric units
	 *
	 * @see setMetricUnits()
	 *
	 * @var string
	 */
	protected $units_metric = 'km';

	/**
	 * Distance adjustment
	 *
	 * The distance returned from the haversine formula is a perfect arc
	 * This accounts for turns and elevation changes
	 *
	 * @see setDistanceAdjustment()
	 *
	 * @var float
	 */
	protected $distance_adjustment = 1.2;

	/**
	 * Distance decimals
	 *
	 * The amount of digits after the decimal in the returned distances
	 *
	 * @see setDistanceDecimals()
	 *
	 * @var int
	 */
	protected $distance_decimals = 1;

	/**
	 * The ungeocoded position
	 *
	 * @see setPosition()
	 *
	 * @var StdClass
	 */
	protected $position;

	/**
	 * Rules
	 *
	 * Extra rules to pass to the locator SQL statement
	 *
	 * @see addRule()
	 *
	 * @var Array
	 */
	protected $rules = array();

	/**
	 * Constructor
	 *
	 * @param array $options array of initialization options
	 * @return PHPStoreLocator
	 */
	function __construct( array $options = null ) {
	
		$this->setOptions( $options );
	
	}

	function setOptions( array $options = null ) {

		if ( $options === null ) {
			return;
		}

		foreach( $options as $o => $v ) {
			switch( $o ) {
				case 'locations_table':
					$this->setLocationsTable( $v );
					break;
				case 'latlng_columns':
					if ( !isset( $v['lat'], $v['lng'] ) ) {
						break;
					}
					$this->setLatLngColumns( $v['lat'], $v['lng'] );
					break;
				case 'db_info':
					if ( !isset( $v['db_username'], $v['db_password'], $v['db_name'] ) ) {
						break;
					}
					$this->setDbInfo(
						$v['db_username'],
						$v['db_password'],
						$v['db_name'],
						isset( $v['db_host'] ) ? $v['db_host'] : null,
						isset( $v['db_type'] ) ? $v['db_type'] : null
					);
					break;
				case 'radius':
					$this->setRadius( $v );
					break;
				case 'limit':
					$limit = explode( ',', $v );
					$this->setLimit( $limit[0], isset( $limit[1] ) ? $limit[1] : null );
					break;
				case 'units':
					if ( in_array( strtolower( $v ), array( 'english', 'metric' ) ) ) {
						$m = sprintf( 'use%sUnits', ucwords( strtolower( $v ) ) );
					}
					$this->$m();
					break;
				case 'metric_units':
					$this->setMetricUnits( $v );
					break;
				case 'english_units':
					$this->setEnglishUnits( $v );
					break;
				case 'distance_adjustment':
					$this->setDistanceAdjustment( $v );
					break;
				case 'distance_decimals':
					$this->setDistanceDecimals( $v );
					break;
				case 'position':
					if ( isset( $v['lat'], $v['lng'] ) ) {
						$this->setPosition( $v['lat'], $v['lng'] );
					}
					break;
				case 'return_columns':
					$this->setReturnColumns( $v );
					break;
				case 'rules':
					foreach( $v as $rule ) {
						if ( isset( $rule['format_string'], $rule['value'] ) ) {
							$this->addRule( $rule['format_string'], $rule['value'] );
						}
					}
					break;
			}
		}

	}

	/**
	 * Set the locations table
	 *
	 * @param string $locations_table locations table name
	 * @return
	 */
	function setLocationsTable( $locations_table ) {
		$this->locations_table = $locations_table;
	}

	/**
	 * Set the lat/lng columns
	 *
	 * @param string $latitude_column latitude column
	 * @param string $longitude_column longitude column
	 * @return void
	 */
	function setLatLngColumns( $latitude_column, $longitude_column ) {
		$this->latitude_column = $latitude_column;
		$this->longitude_column = $longitude_column;
	}

	/**
	 * Set the DB info
	 *
	 * @param string $db_username database username
	 * @param string $db_password database password
	 * @param string $db_name database name
	 * @param string $db_type database type (mysql)
	 * @param string $db_host database host (localhost)
	 * @return void
	 */
	function setDbInfo( $db_username, $db_password, $db_name, $db_host = null, $db_type = null ) {
		$this->db_username = $db_username;
		$this->db_password = $db_password;
		$this->db_name = $db_name;
		if ( $db_host ) {
			$this->db_host = $db_host;
		}
		if ( $db_type ) {
			$this->db_type = $db_type;
		}
	}

	/**
	 * Set the search radius
	 *
	 * @param int $radius
	 * @return void
	 */
	function setRadius( $radius ) {
		$this->radius = intval( $radius );
	}

	/**
	 * Set the limit
	 *
	 * @param int $limit
	 * @return void
	 */
	function setLimit( $limit_length, $limit_start = null ) {
		$this->limit_length = abs( intval( $limit_length ) );
		if ( $limit_start ) {
			$this->limit_start = abs( intval( $limit_start ) );
		}
	}

	/**
	 * Use metric units
	 *
	 * @return void
	 */
	function useMetricUnits() {
		$this->current_unit_system = 'metric';
	}

	/**
	 * Use english units
	 *
	 * @return void
	 */
	function useEnglishUnits() {
		$this->current_unit_system = 'english';
	}

	/**
	 * Get the units used
	 *
	 * Return the current units
	 * 
	 * @return string
	 */
	function getUnits() {
		$var = 'units_' . $this->current_unit_system;
		return $this->$var;
	}

	/**
	 * Set the metric unit text
	 *
	 * @param string $units text to use as the metric units (km, kilo, kilomters)
	 * @return void
	 */
	function setMetricUnits( $units ) {
		$this->units_metric = $units;
	}

	/**
	 * Set the english unit text
	 *
	 * @param string $units text to use as the english units (mi, miles)
	 * @return void
	 */
	function setEnglishUnits( $units ) {
		$this->units_english = $units;
	}

	/**
	 * Set the distance adjustment
	 *
	 * @param float|int $distance_adjustment number to mutliply the haversine distance by to account
	 *                                       for turns/elevation, etc…   
	 * @return void
	 */
	function setDistanceAdjustment( $distance_adjustment ) {
		$this->distance_adjustment = floatval( $distance_adjustment );
	}

	/**
	 * Set distance decimals
	 * 
	 * @param int $distance_decimals decimal places to round to
	 * @return void
	 */
	function setDistanceDecimals( $distance_decimals ) {
		$this->distance_decimals = abs( intval( $distance_decimals ) );
	}

	/**
	 * Set the position coords to search with
	 *
	 * @return void
	 */
	function setPosition( $lat, $lng ) {
		$this->position = new StdClass;
		$this->position->lat = floatval( $lat );
		$this->position->lng = floatval( $lng );
	}

	/**
	 * Add rule
	 *
	 * Add a where clause to the sql statement
	 *
	 * @param string $rule_format_string e.g. "other_column = %s"
	 * @param string $rule_value e.g. "value"
	 * @return void
	 */
	function addRule( $rule_format_string, $rule_value ) {
		$replace = function( $v ) { return preg_replace( '~"|\'|`~', '', $v ); };
		$this->rules[] = array( $replace( $rule_format_string ), $replace( $rule_value ) );
	}

	/**
	 * Set the return columns
	 *
	 * @var string|array $columns columns to return
	 * @return void
	 */
	function setReturnColumns( $return_columns ) {
		$this->return_columns = $return_columns;
	}

	/**
	 * Get locations
	 *
	 * @param array $fields aray of fields to return, if null * will be used
	 * @return Stdlcass
	 * @throws Exception Exception will have a code of 1 if conection related otherwise 2
	 */
	function getLocations() {
	
		if ( !$this->position instanceof StdClass ) {
			trigger_error( 'You must set a position with setPosition() before locating', E_USER_ERROR );
		}
	
		$rule_sql = '';
		$rule_number=0;
		foreach( $this->rules as $rule ) {
			$rule_sql .= sprintf( ' and ' . $rule[0], ':rule'.++$rule_number );
		}

		$sql = sprintf(
			'SELECT
				%s,
				( round( %s * %s * acos( cos( radians(:lat) ) * cos( radians( %s ) ) * cos( radians( %s ) - radians(:lng) ) + sin( radians(:lat) ) * sin( radians( %s ) ) ), %s )  ) AS distance
			FROM
				%s
			where
				1=1
			and
				lat is not NULL
			and
				lng is not NULL
			%s
			HAVING
				distance < %d
			ORDER BY
				distance asc
			',
			is_array( $this->return_columns ) ? implode( ',', $this->return_columns ) : $this->return_columns,
			self::EARTH_RADIUS * ( $this->current_unit_system == 'english' ? 1 : self::MILES_TO_KILOMETERS ),
			$this->distance_adjustment,
			$this->latitude_column,
			$this->longitude_column,
			$this->latitude_column,
			$this->distance_decimals,		
			$this->locations_table,
			count( $this->rules ) ? $rule_sql : '',
			$this->radius
		);

		try {
			$driver_options = array( PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8' );
			$pdo = new PDO( sprintf( '%s:dbname=%s;host=%s', $this->db_type, $this->db_name, $this->db_host ), $this->db_username, $this->db_password, $driver_options );
		}
		catch( PDOException $e ) {
			throw new Exception( 'Error connecting to the database', self::ERROR_DB_CONNECTION, $e );
		}

		try {
			$pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$stmnt = $pdo->prepare( $sql );
			$stmnt->bindValue( ':lat', $this->position->lat );
			$stmnt->bindValue( ':lng', $this->position->lng );
			
			foreach( $this->rules as $k => $rule ) {
				$stmnt->bindValue( sprintf( ':rule%s', $k+1 ), $rule[1] );
			}
			
			$stmnt->execute();
			$locations = $stmnt->fetchAll( PDO::FETCH_OBJ );
			$locations_slice = $this->limit_length ? array_slice( $locations, $this->limit_start, $this->limit_length ) : null;
			
			$result_data = array(
				'radius'			=> $this->radius,
				'units'				=> $this->getUnits(),
				'position'			=> $this->position,
				'return_columns'	=> $this->return_columns,
				'rules'				=> count( $this->rules ) ? $this->rules : null,
				'locations'			=> $locations_slice ? $locations_slice : $locations,
				'result_count'		=> $locations_slice ? count( $locations_slice ) : count( $locations ),
				'total_locations'	=> count( $locations ),
				'limit_start'		=> $this->limit_start,
				'limit_length'		=> $this->limit_length
			);
			
			if ( isset( $this->position ) ) {
				$result_data['position'] = $this->position;
			}
			
			$result = (object)$result_data;
			return $result;
		}
		catch ( PDOException $e ) {
			throw new Exception( $e->getMessage(), self::ERROR_QUERY, $e );
		}


	}

}
