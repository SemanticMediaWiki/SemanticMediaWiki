<?php

namespace SMW\MediaWiki\Api;

use Exception;
use MediaWiki\Api\ApiBase;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Specials\Browse\HtmlBuilder;
use SMW\Services\ServicesFactory as ApplicationFactory;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * Browse a subject api module
 *
 * @note To browse a particular subobject use the 'subobject' parameter because
 * MW's WebRequest (responsible for handling request data sent by a browser) will
 * eliminate any fragments (marked by "#") therefore using something like
 * '"Lorem_ipsum#Foo' is not going to work but '&subject=Lorem_ipsum&subobject=Foo'
 * will return results for the selected subobject
 *
 * @ingroup Api
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class BrowseBySubject extends ApiBase {

	/**
	 * @deprecated since 3.0, use the smwbrowse API module
	 */
	public function isDeprecated(): bool {
		return true;
	}

	/**
	 * @see ApiBase::execute
	 */
	public function execute(): void {
		$params = $this->extractRequestParams();

		if ( isset( $params['type'] ) && $params['type'] === 'html' ) {
			$data = $this->buildHTML( $params );
		} else {
			$data = $this->doSerialize( $params );
		}

		$this->getResult()->addValue(
			null,
			'query',
			$data
		);
	}

	protected function buildHTML( array $params ): string {
		$subject = new WikiPage(
			$params['subject'],
			$params['ns'],
			$params['iw'],
			$params['subobject']
		);

		$htmlBuilder = new HtmlBuilder(
			ApplicationFactory::getInstance()->getStore(),
			$subject
		);

		$htmlBuilder->setOptions(
			(array)$params['options']
		);

		return $htmlBuilder->buildHTML();
	}

	protected function doSerialize( array $params ): array {
		$applicationFactory = ApplicationFactory::getInstance();

		$title = $applicationFactory->newTitleFactory()->newFromText(
			$params['subject'],
			$params['ns']
		);

		$deepRedirectTargetResolver = $applicationFactory->newMwCollaboratorFactory()->newDeepRedirectTargetResolver();

		try {
			$title = $deepRedirectTargetResolver->findRedirectTargetFor( $title );
		} catch ( Exception $e ) {
			$this->dieWithError( [ 'smw-redirect-target-unresolvable', $e->getMessage() ] );
		}

		$dataItem = new WikiPage(
			$title->getDBkey(),
			$title->getNamespace(),
			$title->getInterwiki(),
			$params['subobject']
		);

		$semanticData = $applicationFactory->getStore()->getSemanticData(
			$dataItem
		);

		$semanticDataSerializer = $applicationFactory->newSerializerFactory()->newSemanticDataSerializer();

		return $this->doFormat( $semanticDataSerializer->serialize( $semanticData ) );
	}

	protected function doFormat( array $serialized ): array {
		$this->addIndexTags( $serialized );

		if ( isset( $serialized['sobj'] ) ) {

			$this->getResult()->setIndexedTagName( $serialized['sobj'], 'subobject' );

			foreach ( $serialized['sobj'] as $key => &$value ) {
				$this->addIndexTags( $value );
			}
		}

		return $serialized;
	}

	protected function addIndexTags( array|string &$serialized ): void {
		if ( isset( $serialized['data'] ) && is_array( $serialized['data'] ) ) {

			$this->getResult()->setIndexedTagName( $serialized['data'], 'property' );

			foreach ( $serialized['data'] as $key => $value ) {
				if ( isset( $serialized['data'][$key]['dataitem'] ) && is_array( $serialized['data'][$key]['dataitem'] ) ) {
					$this->getResult()->setIndexedTagName( $serialized['data'][$key]['dataitem'], 'value' );
				}
			}
		}
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getAllowedParams
	 *
	 * @return array
	 */
	public function getAllowedParams(): array {
		return [
			'subject' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_ISMULTI => false,
				ParamValidator::PARAM_REQUIRED => true,
				ApiBase::PARAM_HELP_MSG => 'apihelp-browsebysubject-param-subject',
			],
			'ns' => [
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_ISMULTI => false,
				ParamValidator::PARAM_DEFAULT => 0,
				ParamValidator::PARAM_REQUIRED => false,
			],
			'iw' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_ISMULTI => false,
				ParamValidator::PARAM_DEFAULT => '',
				ParamValidator::PARAM_REQUIRED => false,
			],
			'subobject' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_ISMULTI => false,
				ParamValidator::PARAM_DEFAULT => '',
				ParamValidator::PARAM_REQUIRED => false,
				ApiBase::PARAM_HELP_MSG => 'apihelp-browsebysubject-param-subobject',
			],
			'type' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_ISMULTI => false,
				ParamValidator::PARAM_DEFAULT => '',
				ParamValidator::PARAM_REQUIRED => false,
			],
			'options' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_ISMULTI => false,
				ParamValidator::PARAM_DEFAULT => '',
				ParamValidator::PARAM_REQUIRED => false,
			]
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages(): array {
		return [
			'action=browsebysubject&subject=Main_Page'
				=> 'apihelp-browsebysubject-example-1',
		];
	}

}
