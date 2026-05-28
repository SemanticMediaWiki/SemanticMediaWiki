<?php

namespace SMW\MediaWiki\Api;

use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiMain;
use MediaWiki\Title\TitleFactory;
use Onoi\Cache\Cache;
use SMW\Exception\JSONParseException;
use SMW\Exception\ParameterNotFoundException;
use SMW\Exception\RedirectTargetUnresolvableException;
use SMW\MediaWiki\Api\Browse\ArticleAugmentor;
use SMW\MediaWiki\Api\Browse\ArticleLookup;
use SMW\MediaWiki\Api\Browse\CachingLookup;
use SMW\MediaWiki\Api\Browse\ListAugmentor;
use SMW\MediaWiki\Api\Browse\ListLookup;
use SMW\MediaWiki\Api\Browse\PSubjectLookup;
use SMW\MediaWiki\Api\Browse\PValueLookup;
use SMW\MediaWiki\Api\Browse\SubjectLookup;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Settings;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * Module to support selected browse activties including:
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class Browse extends ApiBase {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		ApiMain $main,
		string $action,
		private readonly Store $store,
		private readonly Settings $settings,
		private readonly Cache $cache,
		private readonly TitleFactory $titleFactory
	) {
		parent::__construct( $main, $action );
	}

	/**
	 * @see ApiBase::execute
	 */
	public function execute(): void {
		$params = $this->extractRequestParams();

		$parameters = json_decode( $params['params'], true );
		$res = [];

		if ( json_last_error() !== JSON_ERROR_NONE || !is_array( $parameters ) ) {
			$error = new JSONParseException( $params['params'] );

			$this->dieWithError( [ 'smw-api-invalid-parameters', 'JSON: ' . $error->getMessage() ] );
		}

		if ( $params['browse'] === 'category' ) {
			$res = $this->callListLookup( NS_CATEGORY, $parameters );
		}

		if ( $params['browse'] === 'property' ) {
			$res = $this->callListLookup( SMW_NS_PROPERTY, $parameters );
		}

		if ( $params['browse'] === 'concept' ) {
			$res = $this->callListLookup( SMW_NS_CONCEPT, $parameters );
		}

		if ( $params['browse'] === 'pvalue' ) {
			$res = $this->callPValueLookup( $parameters );
		}

		if ( $params['browse'] === 'psubject' ) {
			$res = $this->callPSubjectLookup( $parameters );
		}

		if ( $params['browse'] === 'subject' ) {
			$res = $this->callSubjectLookup( $parameters );
		}

		if ( $params['browse'] === 'page' ) {
			$res = $this->callPageLookup( $parameters );
		}

		$result = $this->getResult();

		foreach ( $res as $key => $value ) {

			if ( $key === 'query' && is_array( $value ) ) {

				// For those items that start with _xyz as in _MDAT
				// https://www.mediawiki.org/wiki/API:JSON_version_2
				// " ... can indicate that a property beginning with an underscore ..."
				foreach ( $value as $k => $v ) {
					if ( is_string( $k ) && $k[0] === '_' ) {
						$result->addPreserveKeysList( 'query', $k );
					}
				}
			}

			$result->addValue( null, $key, $value );
		}
	}

	private function callListLookup( $ns, array $parameters ) {
		$cacheUsage = $this->settings->get(
			'smwgCacheUsage'
		);

		$cacheTTL = CachingLookup::CACHE_TTL;

		if ( isset( $cacheUsage['api.browse'] ) ) {
			$cacheTTL = $cacheUsage['api.browse'];
		}

		$store = $this->getSQLStore();

		$listLookup = new ListLookup(
			$store,
			new ListAugmentor( $store )
		);

		$cachingLookup = new CachingLookup(
			$this->cache,
			$listLookup
		);

		$cachingLookup->setCacheTTL(
			$cacheTTL
		);

		$parameters['ns'] = $ns;

		return $cachingLookup->lookup(
			$parameters
		);
	}

	private function callPValueLookup( array $parameters ) {
		$cacheUsage = $this->settings->get(
			'smwgCacheUsage'
		);

		$cacheTTL = CachingLookup::CACHE_TTL;

		if ( isset( $cacheUsage['api.browse.pvalue'] ) ) {
			$cacheTTL = $cacheUsage['api.browse.pvalue'];
		}

		$store = $this->getSQLStore();

		$listLookup = new PValueLookup(
			$store
		);

		$cachingLookup = new CachingLookup(
			$this->cache,
			$listLookup
		);

		$cachingLookup->setCacheTTL(
			$cacheTTL
		);

		return $cachingLookup->lookup(
			$parameters
		);
	}

	private function callPSubjectLookup( array $parameters ) {
		$cacheUsage = $this->settings->get(
			'smwgCacheUsage'
		);

		$cacheTTL = CachingLookup::CACHE_TTL;

		if ( isset( $cacheUsage['api.browse.psubject'] ) ) {
			$cacheTTL = $cacheUsage['api.browse.psubject'];
		}

		$store = $this->getSQLStore();

		$listLookup = new PSubjectLookup(
			$store
		);

		$cachingLookup = new CachingLookup(
			$this->cache,
			$listLookup
		);

		$cachingLookup->setCacheTTL(
			$cacheTTL
		);

		return $cachingLookup->lookup(
			$parameters
		);
	}

	private function callPageLookup( array $parameters ) {
		$applicationFactory = ApplicationFactory::getInstance();

		$cacheUsage = $this->settings->get(
			'smwgCacheUsage'
		);

		$cacheTTL = CachingLookup::CACHE_TTL;

		if ( isset( $cacheUsage['api.browse'] ) ) {
			$cacheTTL = $cacheUsage['api.browse'];
		}

		$connection = $this->store->getConnection( 'mw.db' );

		$articleLookup = new ArticleLookup(
			$connection,
			new ArticleAugmentor(
				$this->titleFactory
			)
		);

		$cachingLookup = new CachingLookup(
			$this->cache,
			$articleLookup
		);

		$cachingLookup->setCacheTTL(
			$cacheTTL
		);

		return $cachingLookup->lookup(
			$parameters
		);
	}

	private function callSubjectLookup( array $parameters ): array {
		$subjectLookup = new SubjectLookup(
			$this->store
		);

		try {
			$res = $subjectLookup->lookup( $parameters );
		} catch ( RedirectTargetUnresolvableException $e ) {
			$this->dieWithError( [ 'smw-redirect-target-unresolvable', $e->getMessage() ] );
		} catch ( ParameterNotFoundException $e ) {
			$this->dieWithError( [ 'smw-parameter-missing', $e->getName() ] );
		}

		return $res;
	}

	/**
	 * The list lookups need the SQLStore-typed surface (e.g. `getSQLOptions`).
	 * When the default store is a non-SQL backend (notably SPARQLStore) we
	 * fall back to the registered SQLStore via `ApplicationFactory` because
	 * it is not exposed as a separate global service. This mirrors the
	 * partial-DI pattern used by slice 6's UpdateJob `$store !== null` branch.
	 */
	private function getSQLStore(): Store {
		if ( $this->store instanceof SQLStore ) {
			return $this->store;
		}

		return ApplicationFactory::getInstance()->getStore( SQLStore::class );
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getAllowedParams
	 *
	 * @return array
	 */
	public function getAllowedParams(): array {
		return [
			'browse' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => [

					// List, browse of properties
					'property',

					// List, browse of categories
					'category',

					// List, browse of concepts
					'concept',

					// List, browse of articles, pages (mediawiki)
					'page',

					// Equivalent to Store::getPropertyValues
					'pvalue',

					// Equivalent to Store::getPropertySubjects
					'psubject',

					// Equivalent to Special:Browse
					'subject',
				],
				ApiBase::PARAM_HELP_MSG => 'apihelp-smwbrowse-param-browse',
			],
			'params' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
				ApiBase::PARAM_HELP_MSG => 'apihelp-smwbrowse-param-params',
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages(): array {
		return [
			'action=smwbrowse&browse=property&params={ "limit": 10, "offset": 0, "search": "*" }'
				=> 'apihelp-smwbrowse-example-1',
			'action=smwbrowse&browse=property&params={ "limit": 10, "offset": 10, "search": "*", "sort": "desc" }'
				=> 'apihelp-smwbrowse-example-2',
			'action=smwbrowse&browse=property&params={ "limit": 10, "offset": 0, "search": "Date" }'
				=> 'apihelp-smwbrowse-example-3',
			'action=smwbrowse&browse=property&params={ "limit": 10, "offset": 0, "search": "Date", "description": true }'
				=> 'apihelp-smwbrowse-example-4',
			'action=smwbrowse&browse=property&params={ "limit": 10, "offset": 0, "search": "Date", "description": true, "prefLabel": true }'
				=> 'apihelp-smwbrowse-example-5',
			'action=smwbrowse&browse=property&params={ "limit": 10, "offset": 0, "search": "Date", "description": true, "prefLabel": true, "usageCount": true }'
				=> 'apihelp-smwbrowse-example-6',
			'action=smwbrowse&browse=pvalue&params={ "limit": 10, "offset": 0, "property" : "Foo", "search": "Bar" }'
				=> 'apihelp-smwbrowse-example-7',
			'action=smwbrowse&browse=psubject&params={ "limit": 10, "offset": 0, "property" : "Foo", "value" : "Bar", "search": "foo" }'
				=> 'apihelp-smwbrowse-example-8',
			'action=smwbrowse&browse=category&params={ "limit": 10, "offset": 0, "search": "" }'
				=> 'apihelp-smwbrowse-example-9',
			'action=smwbrowse&browse=category&params={ "limit": 10, "offset": 0, "search": "Date" }'
				=> 'apihelp-smwbrowse-example-10',
			'action=smwbrowse&browse=concept&params={ "limit": 10, "offset": 0, "search": "" }'
				=> 'apihelp-smwbrowse-example-11',
			'action=smwbrowse&browse=concept&params={ "limit": 10, "offset": 0, "search": "Date" }'
				=> 'apihelp-smwbrowse-example-12',
			'action=smwbrowse&browse=page&params={ "limit": 10, "offset": 0, "search": "Main" }'
				=> 'apihelp-smwbrowse-example-13',
			'action=smwbrowse&browse=page&params={ "limit": 10, "offset": 0, "search": "Main", "fullText": true, "fullURL": true }'
				=> 'apihelp-smwbrowse-example-14',
			'action=smwbrowse&browse=subject&params={ "subject": "Main page", "ns" :0, "iw": "", "subobject": "" }'
				=> 'apihelp-smwbrowse-example-15',
		];
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getVersion
	 *
	 * @return string
	 */
	public function getHelpUrls(): string {
		return 'https://www.semantic-mediawiki.org/wiki/Help:API';
	}

}
