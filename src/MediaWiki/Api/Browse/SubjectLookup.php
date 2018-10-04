<?php

namespace SMW\MediaWiki\Api\Browse;

use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\MediaWiki\Specials\Browse\HtmlBuilder;
use SMW\Store;
use SMW\Exception\RedirectTargetUnresolvableException;
use SMW\Exception\ParameterNotFoundException;

/**
 * @license GNU GPL v2+
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
	 * @return string|integer
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

		$semanticDataSerializer = $applicationFactory->newSerializerFactory()->newSemanticDataSerializer();

		return $semanticDataSerializer->serialize( $semanticData );
	}

}
