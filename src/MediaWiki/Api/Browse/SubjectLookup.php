<?php

namespace SMW\MediaWiki\Api\Browse;

use Exception;
use SMW\DataItems\WikiPage;
use SMW\Exception\ParameterNotFoundException;
use SMW\Exception\RedirectTargetUnresolvableException;
use SMW\MediaWiki\Specials\Browse\HtmlBuilder;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class SubjectLookup extends Lookup {

	const VERSION = 1;

	/**
	 * @since 3.0
	 */
	public function __construct( private readonly Store $store ) {
	}

	/**
	 * @since 3.0
	 */
	public function getVersion(): string {
		return 'SubjectLookup:' . self::VERSION;
	}

	/**
	 * @since 3.0
	 */
	public function lookup( array $parameters ): array {
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

	private function buildHTML( array $params ): string {
		if ( !isset( $params['options'] ) ) {
			throw new ParameterNotFoundException( 'options' );
		}

		$subject = new WikiPage(
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

	private function doSerialize( array $params ) {
		$applicationFactory = ApplicationFactory::getInstance();
		$subobject = $params['subobject'] ?? '';

		$title = $applicationFactory->newTitleFactory()->newFromText(
			$params['subject'],
			$params['ns']
		);

		$deepRedirectTargetResolver = $applicationFactory->newMwCollaboratorFactory()->newDeepRedirectTargetResolver();
		$serializer = $applicationFactory->newSerializerFactory()->newSemanticDataSerializer();

		try {
			$title = $deepRedirectTargetResolver->findRedirectTargetFor( $title );
		} catch ( Exception $e ) {
			throw new RedirectTargetUnresolvableException( $e->getMessage() );
		}

		$dataItem = new WikiPage(
			$title->getDBkey(),
			$title->getNamespace(),
			$title->getInterwiki(),
			$subobject
		);

		$semanticData = $applicationFactory->getStore()->getSemanticData(
			$dataItem
		);
		$semanticDataSerializer = $applicationFactory->newSerializerFactory()->newSemanticDataSerializer();

		return $semanticDataSerializer->serialize( $semanticData, true );
	}
}
