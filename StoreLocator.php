<?php

/**
 * Store Locator
 * @author Galen Grover <galenjr@gmail.com>
 * @package StoreLocator
*/

/**
 * Store Locator
 *
 * Here is the minimum implementation:
 *
 * require( 'path/to/StoreLocator.php' );
 *
 * $locator = new StoreLocator;
 * $locator->setDbInfo( 'username', 'password', 'database' );
 * $locator->setPosition( $lat, $lng );
 *
 * try {
 *    $result =  $locator->getLocations();
 * }
 * catch( Exception $e ) {
 *    if ( $e->getCode() == StoreLocator::ERROR_DB_CONNECTION ) {
 *        $error_msg = 'Error Connecting to the database';
 *    }
 *    else {
 *        $error_msg = 'Error executing sql query';
 *        error_log( $e->getMessage );
 *    }
 * }
 * require( 'view.php' );
 *
 */

class StoreLocator {

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
     * PDO object
     * This is set via setDb()
     * It is an alternative to setting the db info with setDbInfo()
     *
     * @see setDb()
     *
     * @var PDO
     */
    protected $db;

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
     * Return class
     *
     * Class name of the returned locations
     * Defaults to StdClass
     *
     * @see setReturnClass()
     *
     * @var string
     */
    protected $return_class;

    /**
     * Constructor
     *
     * @param array $options array of initialization options
     * @return StoreLocator
     */
    public function __construct() {}

    /**
     * Set the locations table
     *
     * @param string $locations_table locations table name
     * @return
     */
    public function setLocationsTable( $locations_table ) {
        $this->locations_table = $locations_table;
    }

    /**
     * Set the lat/lng columns
     *
     * @param string $latitude_column latitude column
     * @param string $longitude_column longitude column
     * @return void
     */
    public function setLatLngColumns( $latitude_column, $longitude_column ) {
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
    public function setDbInfo( $db_username, $db_password, $db_name, $db_host = null, $db_type = null ) {
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
    * Set the DB object
    *
    * Alternative to setting the database info with setDbInfo()
    * You pass an already created PDO object to this function
    *
    * @param PDO $db PDO object
    * @return void
    */
    public function setDb( PDO $db ) {
        $this->db = $db;
    }

    /**
     * Set the search radius
     *
     * @param int $radius
     * @return void
     */
    public function setRadius( $radius ) {
        $this->radius = intval( $radius );
    }

    /**
     * Set the limit
     *
     * @param int $limit
     * @return void
     */
    public function setLimit( $limit_length, $limit_start = null ) {
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
    public function useMetricUnits() {
        $this->current_unit_system = 'metric';
    }

    /**
     * Use english units
     *
     * @return void
     */
    public function useEnglishUnits() {
        $this->current_unit_system = 'english';
    }

    /**
     * Get the units used
     *
     * Return the current units
     * 
     * @return string
     */
    public function getUnits() {
        $var = 'units_' . $this->current_unit_system;
        return $this->$var;
    }

    /**
     * Set the metric unit text
     *
     * @param string $units text to use as the metric units (km, kilo, kilomters)
     * @return void
     */
    public function setMetricUnits( $units ) {
        $this->units_metric = $units;
    }

    /**
     * Set the english unit text
     *
     * @param string $units text to use as the english units (mi, miles)
     * @return void
     */
    public function setEnglishUnits( $units ) {
        $this->units_english = $units;
    }

    /**
     * Set the distance adjustment
     *
     * @param float|int $distance_adjustment number to mutliply the haversine distance by to account
     *        for turns/elevation, etc…   
     * @return void
     */
    public function setDistanceAdjustment( $distance_adjustment ) {
        $this->distance_adjustment = floatval( $distance_adjustment );
    }

    /**
     * Set the class of the returned locations
     *
     * @param string $return_class class of the returned locations
     *
     * @return void
     */
    public function setReturnClass( $return_class ) {
        if ( class_exists( $return_class ) ) {
            $this->return_class = $return_class;
        }
        else {
            trigger_error( 'Invalid class passed to setReturnClass()', E_USER_ERROR );
        }
    }

    /**
     * Set distance decimals
     * 
     * @param int $distance_decimals decimal places to round to
     * @return void
     */
    public function setDistanceDecimals( $distance_decimals ) {
        $this->distance_decimals = abs( intval( $distance_decimals ) );
    }

    /**
     * Set the position coords to search with
     *
     * @return void
     */
    public function setPosition( $lat, $lng ) {
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
    public function addRule( $rule_format_string, $rule_value ) {
        $replace = function( $v ) { return preg_replace( '~"|\'|`~', '', $v ); };
        $this->rules[] = array( $replace( $rule_format_string ), $replace( $rule_value ) );
    }

    /**
     * Set the return columns
     *
     * @var string|array $columns columns to return
     * @return void
     */
    public function setReturnColumns( $return_columns ) {
        $this->return_columns = $return_columns;
    }

    /**
     * Get locations
     *
     * You can pass the lat/lng here or via setPosition()
     *
     * @param float|null $lat latitude of the search position
     * @param float|null $lng longitude of the search position
     * @return Stdlcass
     * @throws Exception Exception will have a code of 1 if conection related otherwise 2
     */
    public function getLocations( $lat = null, $lng = null) {

        if ( $lat && $lng ) {
            $this->setPosition( $lat, $lng );
        }

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

        if ( $this->db === null ) {
            try {
                $driver_options = array( PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8' );
                $this->db = new PDO( sprintf( '%s:dbname=%s;host=%s', $this->db_type, $this->db_name, $this->db_host ), $this->db_username, $this->db_password, $driver_options );
            }
            catch( PDOException $e ) {
                throw new Exception( 'Error connecting to the database', self::ERROR_DB_CONNECTION, $e );
            }
        }

        try {
            $this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
            $stmnt = $this->db->prepare( $sql );
            $stmnt->bindValue( ':lat', $this->position->lat );
            $stmnt->bindValue( ':lng', $this->position->lng );
            
            foreach( $this->rules as $k => $rule ) {
                $stmnt->bindValue( sprintf( ':rule%s', $k+1 ), $rule[1] );
            }
            
            $stmnt->execute();
            if ( $this->return_class ) {
                $locations = $stmnt->fetchAll( PDO::FETCH_CLASS, $this->return_class ); 
            }
            else {
                $locations = $stmnt->fetchAll( PDO::FETCH_OBJ );
            }
            $locations_slice = $this->limit_length ? array_slice( $locations, $this->limit_start, $this->limit_length ) : null;
            
            $result_data = array(
                'radius'            => $this->radius,
                'units'             => $this->getUnits(),
                'position'          => $this->position,
                'return_columns'    => $this->return_columns,
                'rules'             => count( $this->rules ) ? $this->rules : null,
                'locations'         => $locations_slice ? $locations_slice : $locations,
                'result_count'      => $locations_slice ? count( $locations_slice ) : count( $locations ),
                'total_locations'   => count( $locations ),
                'limit_start'       => $this->limit_start,
                'limit_length'      => $this->limit_length
            );
            
            if ( isset( $this->position ) ) {
                $result_data['position'] = $this->position;
            }
            
            return (object)$result_data;
        }
        catch ( PDOException $e ) {
            throw new Exception( $e->getMessage(), self::ERROR_QUERY, $e );
        }


    }

}
