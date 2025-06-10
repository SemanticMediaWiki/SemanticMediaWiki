<?php

namespace SMW\MediaWiki\Api\Browse;

use SMW\DIWikiPage;
use SMW\Exception\ParameterNotFoundException;
use SMW\Exception\RedirectTargetUnresolvableException;
use SMW\MediaWiki\Specials\Browse\HtmlBuilder;
use SMW\RequestOptions;
use SMW\SemanticData;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class SubjectLookup extends Lookup {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.0
	 *
	 * @return string|int
	 */
	public function getVersion() {
		return 'SubjectLookup:' . self::VERSION;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $parameters
	 *
	 * @return array
	 */
	public function lookup( array $parameters ) {
		if ( !isset( $parameters['subject'] ) ) {
			throw new ParameterNotFoundException( 'subject' );
		}

		if ( !isset( $parameters['ns'] ) ) {
			throw new ParameterNotFoundException( 'ns' );
		}

		if ( !isset( $parameters['iw'] ) ) {
			$parameters['iw'] = '';
		}

		if ( !isset( $parameters['subobject'] ) ) {
			$parameters['subobject'] = '';
		}

		if ( isset( $parameters['type'] ) && $parameters['type'] === 'html' ) {
			$data = $this->buildHTML( $parameters );
		} else {
			$data = $this->doSerialize( $parameters );
		}

		// Changing this output format requires to set a new version
		$res = [
			'query' => $data,
			'meta' => [
				'type'  => 'subject'
			]
		];

		return $res;
	}

	private function buildHTML( $params ) {
		if ( !isset( $params['options'] ) ) {
			throw new ParameterNotFoundException( 'options' );
		}

		$subject = new DIWikiPage(
			$params['subject'],
			$params['ns'],
			$params['iw'],
			$params['subobject']
		);

		$htmlBuilder = new HtmlBuilder(
			$this->store,
			$subject
		);

		$htmlBuilder->setOptions(
			$params['options']
		);

		return $htmlBuilder->buildHTML();
	}

	private function doSerialize( $params ) {
		$applicationFactory = ApplicationFactory::getInstance();
		$subobject = isset( $params['subobject'] ) ? $params['subobject'] : '';

		$title = $applicationFactory->newTitleFactory()->newFromText(
			$params['subject'],
			$params['ns']
		);

		$deepRedirectTargetResolver = $applicationFactory->newMwCollaboratorFactory()->newDeepRedirectTargetResolver();

		try {
			$title = $deepRedirectTargetResolver->findRedirectTargetFor( $title );
		} catch ( \Exception $e ) {
			throw new RedirectTargetUnresolvableException( $e->getMessage() );
		}

		$dataItem = new DIWikiPage(
			$title->getDBkey(),
			$title->getNamespace(),
			$title->getInterwiki(),
			$subobject
		);

		$semanticData = $applicationFactory->getStore()->getSemanticData(
			$dataItem
		);
		$inverseEntries = [];

		// check inverse properties if available
		$requestOptions = new RequestOptions();
		$requestOptions->sort = true;
		$requestOptions->setLimit( 100 );

		$incomingProperties = $this->store->getInProperties( $dataItem, $requestOptions );

		if ( !empty( $incomingProperties ) ) {
			$inverseSemanticData = new SemanticData( $dataItem );

			foreach ( $incomingProperties as $property ) {
				$subjects = $this->store->getPropertySubjects( $property, $dataItem );

				foreach ( $subjects as $subject ) {
					$inverseSemanticData->addPropertyObjectValue( $property, $subject );
				}
			}

			$serializer = $applicationFactory->newSerializerFactory()->newSemanticDataSerializer();
			$inverseDataSerialized = $serializer->serialize( $inverseSemanticData );

			foreach ( $inverseDataSerialized['data'] as $entry ) {
				if ( isset( $entry['property'] ) ) {
					$inverseEntries[] = [
						'inverse property' => $entry['property'],
						'dataitem' => $entry['dataitem'] ?? [],
					];
				}
			}
		} else {
			$serializer = $applicationFactory->newSerializerFactory()->newSemanticDataSerializer();
		}

		$data = $serializer->serialize( $semanticData );
		$data['data'] = array_merge( $data['data'], $inverseEntries );

		return $data;
	}
}
