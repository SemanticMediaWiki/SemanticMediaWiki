<?php

namespace SMW\Elastic\Connection;

use Elasticsearch\Client as ElasticClient;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Exception;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use SMW\Elastic\Exception\InvalidJSONException;
use SMW\Elastic\Exception\ReplicationException;
use SMW\Elastic\Config;
use SMW\Options;
use SMW\Site;

/**
 * Reduced interface to the Elasticsearch client class.
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Client {

	use LoggerAwareTrait;

	/**
	 * Type for main data storage.
	 */
	const TYPE_DATA = 'data';

	/**
	 * Index, type to temporary store index lookups during the execution
	 * of subqueries.
	 */
	const TYPE_LOOKUP = 'lookup';

	/**
	 * @var ElasticClient
	 */
	protected $client;

	/**
	 * @var boolean
	 */
	private static $ping;

	/**
	 * @var LockManager
	 */
	private $lockManager;

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @var string
	 */
	private $wikiid;

	/**
	 * @var boolean
	 */
	private static $hasIndex = [];

	/**
	 * @since 3.0
	 *
	 * @param ElasticClient $client
	 * @param LockManager $lockManager
	 * @param Options|null $options
	 */
	public function __construct( ElasticClient $client, LockManager $lockManager, Config $options = null ) {
		$this->client = $client;
		$this->lockManager = $lockManager;
		$this->options = $options;

		if ( $this->options === null ) {
			$this->options = new Options();
		}

		$this->logger = new NullLogger();

		// #3938
		$this->wikiid = strtolower( Site::id() );
	}

	/**
	 * @since 3.0
	 *
	 * @return Options
	 */
	public function getConfig() {
		return $this->options;
	}

	/**
	 * @since 3.0
	 */
	public function clear() {
		self::$ping = null;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public function getIndexName( string $type ): string {
		return "smw-$type-" . $this->wikiid;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public function getIndexDefinition( string $type ): string {
		static $indexDef = [];

		if ( isset( $indexDef[$type] ) ) {
			return $indexDef[$type];
		}

		$indexDef[$type] = file_get_contents( $this->options->dotGet( "index_def.$type" ) );

		// Modify settings on-the-fly
		if ( $this->options->dotGet( "settings.$type", [] ) !== [] ) {
			$definition = json_decode( $indexDef[$type], true );

			if ( ( $error = json_last_error() ) !== JSON_ERROR_NONE ) {
				throw new InvalidJSONException( $error, $this->options->dotGet( "index_def.$type" ) );
			}

			$definition['settings'] = $this->options->dotGet( "settings.$type" ) + $definition['settings'];
			$indexDef[$type] = json_encode( $definition );
		}

		return $indexDef[$type];
	}

	/**
	 * @since 3.0
	 *
	 * @return integer
	 */
	public function getIndexDefFileModificationTimeByType( $type ) {

		static $filemtime = [];

		if ( !isset( $filemtime[$type] ) ) {
			$filemtime[$type] = filemtime( $this->options->dotGet( "index_def.$type" ) );
		}

		return $filemtime[$type];
	}

	/**
	 * @since 3.0
	 *
	 * @return integer
	 */
	public function getVersion() {

		$info = $this->info();

		if (
			$this->options->isDefaultStore() &&
			isset( $info['version']['number'] ) ) {
			return $info['version']['number'];
		}

		return null;
	}

	/**
	 * @since 3.0
	 *
	 * @return array
	 */
	public function getSoftwareInfo() {
		return [
			'component' => "[https://www.elastic.co/elasticsearch/ Elasticsearch]",
			'version' => $this->getVersion()
		];
	}

	/**
	 * @since 3.0
	 *
	 * @param array
	 */
	public function info(): array {

		if ( !$this->ping() ) {
			return [];
		}

		return $this->client->info( [] );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
     * @param array $params
	 */
	public function stats( string $type = 'indices', array $params = [] ): array {

		$indices = [
			$this->getIndexName( self::TYPE_DATA ),
			$this->getIndexName( self::TYPE_LOOKUP )
		];

        switch ( $type ) {
            case 'indices':
                $res = $this->client->indices()->stats( [ 'index' => $indices ] + $params );
                break;
            case 'nodes':
                $res = $this->client->nodes()->stats( $params );
                break;
            default:
                return [];
        }

		if ( $type === 'indices' && isset( $res['indices'] ) ) {
			unset( $res['_all'] );
			ksort( $res['indices'] );
		}

		if ( $type === 'nodes' && isset( $res['nodes'] ) ) {
			foreach ( $res['nodes'] as $key => &$value ) {
				// Remove privacy info
				unset( $value['transport_address'] );
				unset( $value['host'] );
				unset( $value['ip'] );
			}
		}

		return $res;
	}

	/**
	 * @since 3.0
	 *
	 * @param array
	 */
	public function cat( $type, $params = [] ) {

		$res = [];

		if ( $type === 'indices' ) {
			$params += [ 'format' => 'json' ];

            $indices = $this->client->cat()->indices( $params );

			foreach ( $indices as $value ) {
				$res[$value['index']] = $value;
				unset( $res[$value['index']]['index'] );
			}
		}

		return $res;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 *
	 * @param boolean
	 */
	public function hasIndex( $type ) {

		if ( isset( self::$hasIndex[$type] ) && self::$hasIndex[$type] ) {
			return true;
		}

		$index = $this->getIndexName( $type );
		$result = $this->indexExists( $index );

		return self::$hasIndex[$type] = $result;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 */
	public function createIndex( $type ) {

		$index = $this->getIndexName( $type );
		$version = 'v1';

		if ( $this->indexExists( "$index-$version" ) ) {
			$version = 'v2';

			if ( $this->indexExists( "$index-$version" ) ) {
				$this->deleteIndex( "$index-$version" );
			}
		}

		$params = [
			'index' => "$index-$version",
			'body'  => $this->getIndexDefinition( $type )
		];

        $context = [
            'method' => __METHOD__,
            'role' => 'user',
            'index' => $index
        ];

        $context['response'] = $this->client->indices()->create( $params );
        $this->logger->info( 'Created index {index} with: {response}', $context );

		return $version;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $index
	 */
	public function deleteIndex( string $index ) {

		$params = [
			'index' => $index,
		];

        $context = [
            'method' => __METHOD__,
            'role' => 'user',
            'index' => $index
        ];

        $context['response'] = $this->client->indices()->delete( $params );
        $this->logger->info( 'Deleted index {index} with: {response}', $context );
	}

	/**
	 * @since 3.0
	 *
	 * @param array $params
	 */
	public function putSettings( array $params ) {
		$this->client->indices()->putSettings( $params );
	}

	/**
	 * @since 3.0
	 *
	 * @param array $params
	 */
	public function putMapping( array $params ) {
		$this->client->indices()->putMapping( $params );
	}

	/**
	 * @since 3.0
	 *
	 * @param array $params
	 */
	public function getMapping( array $params ) {
		return $this->client->indices()->getMapping( $params );
	}

	/**
	 * @since 3.0
	 *
	 * @param array $params
	 */
	public function getSettings( array $params ) {
		return $this->client->indices()->getSettings( $params );
	}

	/**
	 * @since 3.0
	 *
	 * @param array $params
	 */
	public function refresh( array $params ) {
		$this->client->indices()->refresh( [ 'index' => $params['index'] ] );
	}

	/**
	 * @since 3.0
	 *
	 * @param array $params
	 */
	public function validate( array $params ) {

		if ( $params === [] ) {
			return [];
		}

		$results = [];
		$context = [
			'method' => __METHOD__,
			'role' => 'production',
			'index' => $params['index']
		];

		unset( $params['body']['sort'] );
		unset( $params['body']['_source'] );
		unset( $params['body']['profile'] );
		unset( $params['body']['from'] );
		unset( $params['body']['size'] );

		try {
			$results = $this->client->indices()->validateQuery( $params );
		} catch ( Exception $e ) {
			$context['exception'] = $e->getMessage();
			$this->logger->info( 'Failed the validate with: {exception}', $context );
		}

		return $results;
	}

	/**
	 * @see Client::ping
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function ping() {

		if ( self::$ping !== null ) {
			return self::$ping;
		}

		if ( $this->options->dotGet( 'connection.quick_ping' ) ) {
			return self::$ping = $this->quick_ping();
		}

		return self::$ping = $this->client->ping( [] );
	}

	/**
	 * Check is faster than the standard Client::ping
	 *
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function quick_ping( $timeout = 2 ) {

		$hosts = $this->options->get( Config::ELASTIC_ENDPOINTS );

		foreach ( $hosts as $host ) {

			if ( is_string( $host ) ) {
				$host = parse_url( $host );
			}

			$fsock = @fsockopen( $host['host'], $host['port'], $errno, $errstr, $timeout );

			if ( $fsock ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @see Client::exists
	 * @since 3.0
	 *
	 * @param array $params
	 *
	 * @return boolean
	 */
	public function exists( array $params ) {
		return $this->client->exists( $params );
	}

	/**
	 * @see Client::get
	 * @since 3.0
	 *
	 * @param array $params
	 *
	 * @return mixed
	 */
	public function get( array $params ) {
		return $this->client->get( $params );
	}

	/**
	 * @see Client::delete
	 * @since 3.0
	 *
	 * @param array $params
	 *
	 * @return mixed
	 */
	public function delete( array $params ) {
		return $this->client->delete( $params );
	}

	/**
	 * @see Client::update
	 * @since 3.0
	 *
	 * @param array $params
	 *
	 * @return array|string
	 */
	public function update( array $params ) {

		$context = [
			'method' => __METHOD__,
			'role' => 'production',
			'index' => $params['index'],
			'id' => $params['id']
		];

		try {
			return $this->client->update( $params );
		} catch( Exception $e ) {
			$context['exception'] = $e->getMessage();
			$this->logger->info( 'Updated failed for document {id} with: {exception}, DOC: {doc}', $context );

            return $context['exception'];
		}
	}

	/**
	 * @see Client::index
	 * @since 3.0
	 *
	 * @param array $params
	 *
	 * @return array|string
	 */
	public function index( array $params ) {

		$context = [
			'method' => __METHOD__,
			'role' => 'production',
			'index' => $params['index'],
			'id' => $params['id']
		];

		try {
			return $this->client->index( $params );
		} catch( Exception $e ) {
			$context['exception'] = $e->getMessage();
			$this->logger->info( 'Index failed for document {id} with: {exception}', $context );

            return $context['exception'];
		}
	}

	/**
	 * @see Client::index
	 * @since 3.0
	 *
	 * @param array $params
	 */
	public function bulk( array $params ) {

		if ( $params === [] ) {
			return [];
		}

		$context = [
			'method' => __METHOD__,
			'role' => 'production'
		];

		try {
			$response = $this->client->bulk( $params );

			// No errors, just log the head otherwise show the entire
			// response
			if ( $response['errors'] === false ) {
				unset( $response['items'] );
			} else {

				$throw = $this->options->dotGet(
					'replication.throw.exception.on.illegal.argument.error'
				);

				foreach ( $response['items'] as $value ) {

					if ( !isset( $value['index'] ) ) {
						continue;
					}

					if ( $throw && $value['index']['error']['type'] === 'illegal_argument_exception' ) {
						throw new ReplicationException( $value['index']['error']['reason'] );
					}
				}
			}

			return $response;
		} catch( ReplicationException $e ) {
			throw new ReplicationException( $e->getMessage() );
		} catch( Exception $e ) {
			$context['exception'] = $e->getMessage();
			$this->logger->info( 'Bulk update failed with {exception}', $context );

            return $context['exception'];
		}
	}

	/**
	 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-count.html
	 * @see Client::count
	 * @since 3.0
	 *
	 * @param array $params
	 *
	 * @return mixed
	 */
	public function count( array $params ) {

		if ( $params === [] ) {
			return [];
		}

		$results = [];
		$time = -microtime( true );

		// ... "_source", "from", "profile", "query", "size", "sort" are not valid parameters.
		unset( $params['body']['sort'] );
		unset( $params['body']['_source'] );
		unset( $params['body']['profile'] );
		unset( $params['body']['from'] );
		unset( $params['body']['size'] );

		try {
			$results = $this->client->count( $params );
		} catch ( Exception $e ) {
			$context['exception'] = $e->getMessage();
			$this->logger->info( 'Failed the count with: {exception}', $context );
		}

		$context = [
			'method' => __METHOD__,
			'role' => 'production',
			'index' => $params['index'],
			'query' => json_encode( $params ),
			'procTime' => microtime( true ) + $time
		];

		$this->logger->info( 'COUNT: {query}, queryTime: {procTime}', $context );

		return $results;
	}

	/**
	 * @see Client::search
	 * @since 3.0
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public function search( array $params ) {

		if ( $params === [] ) {
			return [];
		}

		$results = [];
		$errors = [];

		$time = -microtime( true );

		try {
			$results = $this->client->search( $params );
		} catch ( NoNodesAvailableException $e ) {
			$errors[] = 'Elasticsearch endpoint returned with "' . $e->getMessage() . '" .';
		} catch ( Exception $e ) {
			$context['exception'] = $e->getMessage();
			$this->logger->info( 'Failed the search with: {exception}', $context );
		}

		$this->logger->info(
			[
				'Search',
				'{query}, queryTime: {procTime}'
			],
			[
				'method' => __METHOD__,
				'role' => 'user',
				'index' => $params['index'],
				'query' => $params,
				'procTime' => microtime( true ) + $time
			]
		);

		return [ $results, $errors ];
	}

	/**
	 * @see Client::explain
	 * @since 3.0
	 *
	 * @param array $params
	 *
	 * @return mixed
	 */
	public function explain( array $params ) {

		if ( $params === [] ) {
			return [];
		}

		return $this->client->explain( $params );
	}

	/**
	 * @see Indices::updateAliases
	 * @since 4.2.0
	 *
	 * @param array $params
	 */
	public function updateAliases( array $params ) {
		$this->client->indices()->updateAliases( $params );
	}

	/**
	 * @since 4.2.0
	 *
	 * @param string $index
	 *
	 * @return bool
	 */
	public function indexExists( string $index ): bool {
		return $this->client->indices()->exists( [ 'index' => $index ] );
	}

	/**
	 * @since 4.2.0
	 *
	 * @param string $index
	 *
	 * @return bool
	 */
	public function aliasExists( string $index ): bool {
		return $this->client->indices()->existsAlias( [ 'name' => $index ] );
	}

	/**
	 * @since 4.2.0
	 *
	 * @param string $index
	 */
	public function openIndex( string $index ) {
		$this->client->indices()->open( [ 'index' => $index ] );
	}

	/**
	 * @since 4.2.0
	 *
	 * @param string $index
	 */
	public function closeIndex( string $index ) {
		$this->client->indices()->close( [ 'index' => $index ] );
	}

    /**
     * @since 4.2.0
     *
     * @param array $params
     */
    public function ingestPutPipeline( array $params ) {
        $this->client->ingest()->putPipeline( $params );
    }

	/**
	 * @since 3.1
	 *
	 * @return boolean
	 */
	public function hasMaintenanceLock() {
		return $this->lockManager->hasMaintenanceLock();
	}

	/**
	 * @since 3.1
	 */
	public function setMaintenanceLock() {
		$this->lockManager->setMaintenanceLock();
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 * @param string $version
	 */
	public function setLock( $type, $version ) {
		$this->lockManager->setLock( $type, $version );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 *
	 * @return boolean
	 */
	public function hasLock( $type ) {
		return $this->lockManager->hasLock( $type );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 *
	 * @return mixed
	 */
	public function getLock( $type ) {
		return $this->lockManager->getLock( $type );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 */
	public function releaseLock( $type ) {
		$this->lockManager->releaseLock( $type );
	}

}
