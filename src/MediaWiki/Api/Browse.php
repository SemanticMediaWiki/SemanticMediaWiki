<?php

namespace SMW\MediaWiki\Api;

use MediaWiki\Api\ApiBase;
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
use SMW\SQLStore\SQLStore;
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
		$applicationFactory = ApplicationFactory::getInstance();

		$cacheUsage = $applicationFactory->getSettings()->get(
			'smwgCacheUsage'
		);

		$cacheTTL = CachingLookup::CACHE_TTL;

		if ( isset( $cacheUsage['api.browse'] ) ) {
			$cacheTTL = $cacheUsage['api.browse'];
		}

		$store = $applicationFactory->getStore();

		// We explicitly want the SQLStore here to avoid
		// "Call to undefined method SMW\SPARQLStore\SPARQLStore::getSQLOptions() ..."
		// since we don't use those methods anywher else other than the SQLStore
		if ( !is_a( $store, SQLStore::class ) ) {
			$store = $applicationFactory->getStore( SQLStore::class );
		}

		$listLookup = new ListLookup(
			$store,
			new ListAugmentor( $store )
		);

		$cachingLookup = new CachingLookup(
			$applicationFactory->getCache(),
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
		$applicationFactory = ApplicationFactory::getInstance();

		$cacheUsage = $applicationFactory->getSettings()->get(
			'smwgCacheUsage'
		);

		$cacheTTL = CachingLookup::CACHE_TTL;

		if ( isset( $cacheUsage['api.browse.pvalue'] ) ) {
			$cacheTTL = $cacheUsage['api.browse.pvalue'];
		}

		$store = $applicationFactory->getStore();

		// We explicitly want the SQLStore here to avoid
		// "Call to undefined method SMW\SPARQLStore\SPARQLStore::getSQLOptions() ..."
		// since we don't use those methods anywher else other than the SQLStore
		if ( !is_a( $store, SQLStore::class ) ) {
			$store = $applicationFactory->getStore( SQLStore::class );
		}

		$listLookup = new PValueLookup(
			$store
		);

		$cachingLookup = new CachingLookup(
			$applicationFactory->getCache(),
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
		$applicationFactory = ApplicationFactory::getInstance();

		$cacheUsage = $applicationFactory->getSettings()->get(
			'smwgCacheUsage'
		);

		$cacheTTL = CachingLookup::CACHE_TTL;

		if ( isset( $cacheUsage['api.browse.psubject'] ) ) {
			$cacheTTL = $cacheUsage['api.browse.psubject'];
		}

		$store = $applicationFactory->getStore();

		// We explicitly want the SQLStore here to avoid
		// "Call to undefined method SMW\SPARQLStore\SPARQLStore::getSQLOptions() ..."
		// since we don't use those methods anywher else other than the SQLStore
		if ( !is_a( $store, SQLStore::class ) ) {
			$store = $applicationFactory->getStore( SQLStore::class );
		}

		$listLookup = new PSubjectLookup(
			$store
		);

		$cachingLookup = new CachingLookup(
			$applicationFactory->getCache(),
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

		$cacheUsage = $applicationFactory->getSettings()->get(
			'smwgCacheUsage'
		);

		$cacheTTL = CachingLookup::CACHE_TTL;

		if ( isset( $cacheUsage['api.browse'] ) ) {
			$cacheTTL = $cacheUsage['api.browse'];
		}

		$connection = $applicationFactory->getStore()->getConnection( 'mw.db' );

		$articleLookup = new ArticleLookup(
			$connection,
			new ArticleAugmentor(
				$applicationFactory->create( 'TitleFactory' )
			)
		);

		$cachingLookup = new CachingLookup(
			$applicationFactory->getCache(),
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
			ApplicationFactory::getInstance()->getStore()
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
