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
		$serializer = $applicationFactory->newSerializerFactory()->newSemanticDataSerializer();

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

		$semanticData = new SemanticData( $dataItem );
		$requestOptions = new RequestOptions();
		$requestOptions->sort = true;

		// Retrieve direct and incoming (inverse) properties from the store
		$directProperties = $this->store->getProperties( $dataItem, $requestOptions );
		$incomingProperties = $this->store->getInProperties( $dataItem, $requestOptions );

		$semanticDataDirect = new SemanticData( $dataItem );
		$semanticDataIncoming = new SemanticData( $dataItem );

		// Collect and structure direct property values into a separate SemanticData object
		// This separation allows clear handling before final serialization
		if ( !empty( $directProperties ) ) {
			foreach ( $directProperties as $property ) {
				$directSubjects = $this->store->getPropertyValues( $dataItem, $property );

				foreach ( $directSubjects as $subject ) {
					$semanticDataDirect->addPropertyObjectValue( $property, $subject );
				}
			}
		}

		$incomingData = [];
		// Collect inverse (incoming) property values into their own SemanticData object
		// This allows us to later customize their serialization format (e.g. "inverse property" instead of "property")
		if ( !empty( $incomingProperties ) ) {
			foreach ( $incomingProperties as $property ) {
				$incomingSubjects = $this->store->getPropertySubjects( $property, $dataItem );

				foreach ( $incomingSubjects as $subject ) {
					$semanticDataIncoming->addPropertyObjectValue( $property, $subject );
				}
			}

			// Serialize the inverse SemanticData separately to extract its entries
			$incomingDataSerialized = $serializer->serialize( $semanticDataIncoming );

			// Transform the entries so that the output clearly distinguishes inverse properties
			foreach ( $incomingDataSerialized['data'] as $entry ) {
				if ( isset( $entry['property'] ) ) {
					$incomingData[] = [
						'inverse property' => $entry['property'],
						'dataitem' => $entry['dataitem'] ?? [],
					];
				}
			}
		}

		// Merge the direct properties into the main SemanticData object for standard serialization
		$semanticData->importDataFrom( $semanticDataDirect );

		// Serialize the full direct data
		$semanticData = $serializer->serialize( $semanticData );

		// Append the previously processed inverse properties with custom "inverse property" label
		$semanticData['data'] = array_merge( $semanticData['data'], $incomingData );

		return $semanticData;
	}
}
