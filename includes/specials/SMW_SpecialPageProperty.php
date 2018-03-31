<?php

use SMWInfolink as Infolink;
use SMW\Encoder;
use SMW\DataValueFactory;
use SMW\MediaWiki\Specials\PageProperty\PageBuilder;
use SMW\ApplicationFactory;
use SMW\Options;
use SMW\RequestOptions;

/**
 * This special page implements a view on a object-relation pair, i.e. a page that
 * shows all the values of a property for a certain page.
 *
 * This is typically used for overflow results from other dynamic output pages.
 *
 * @license GNU GPL v2+
 * @since 1.4
 *
 * @author Denny Vrandecic
 * @author mwjames
 */
class SMWPageProperty extends SpecialPage {

	/**
	 * @codeCoverageIgnore
	 */
	public function __construct() {
		parent::__construct( 'PageProperty', '', false );
	}

	/**
	 * @see SpecialPage::execute
	 */
	public function execute( $query ) {

		$output = $this->getOutput();
		$request = $this->getRequest();

		$this->setHeaders();
		$output->setPagetitle( wfMessage( 'pageproperty' )->text() );

		$output->addModuleStyles( 'ext.smw.special.style' );
		$output->addModules( 'ext.smw.tooltip' );

		$output->addModules( 'ext.smw.autocomplete.property' );
		$output->addModules( 'ext.smw.autocomplete.article' );

		if ( $request->getText( 'cl', '' ) !== '' ) {
			$query = Infolink::decodeCompactLink( 'cl:'. $request->getText( 'cl' ) );
		} else {
			$query = Infolink::decodeCompactLink( $query );
		}

		if ( $query !== '' ) {
			$query = Encoder::unescape( $query );
		}

		// Get parameters
		$pagename = $request->getVal( 'from' );
		$propname = $request->getVal( 'type' );

		// No GET parameters? Try the URL with the convention `PageName::PropertyName`
		if ( $propname == '' ) {
			$queryparts = explode( '::', $query );
			$propname = $query;
			if ( count( $queryparts ) > 1 ) {
				$pagename = $queryparts[0];
				$propname = implode( '::', array_slice( $queryparts, 1 ) );
			}
		}

		$options = new Options(
			[
				'from' => $pagename,
				'type' => $propname,
				'property' => $propname,
				'limit' => $request->getVal( 'limit', 20 ),
				'offset' => $request->getVal( 'offset', 0 ),
			]
		);

		$this->addHelpLink(
			wfMessage( 'smw-special-pageproperty-helplink' )->escaped(),
			true
		);

		$output->addHTML( $this->createHtml( $options ) );
	}

	/**
	 * @see SpecialPage::getGroupName
	 */
	protected function getGroupName() {
		return 'smw_group';
	}

	private function createHtml( $options ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$dataValueFactory = DataValueFactory::getInstance();

		$subject = $dataValueFactory->newTypeIDValue(
			'_wpg',
			$options->get( 'from' )
		);

		$pagename = $subject->isValid() ? $subject->getPrefixedText() : '';

		$property = $dataValueFactory->newPropertyValueByLabel(
			$options->get( 'property' )
		);

		$propname = $property->isValid() ? $property->getWikiValue() : '';

		$options->set( 'from', $pagename );
		$options->set( 'property', $propname );
		$options->set( 'type', $propname );

		$htmlFormRenderer = $applicationFactory->newMwCollaboratorFactory()->newHtmlFormRenderer(
			$this->getContext()->getTitle(),
			$this->getLanguage()
		);

		$pageBuilder = new PageBuilder(
			$htmlFormRenderer,
			$options
		);

		$html = '';

		// No property given, no results
		if ( $propname === '' ) {
			$html .= $pageBuilder->getForm();
			$html .= wfMessage( 'smw_result_noresults' )->text();
		} else {

			$requestOptions = new RequestOptions();
			$requestOptions->setLimit( $options->get( 'limit' ) + 1 );
			$requestOptions->setOffset( $options->get( 'offset' ) );
			$requestOptions->sort = true;

			$results = $applicationFactory->getStore()->getPropertyValues(
				$pagename !== '' ? $subject->getDataItem() : null,
				$property->getDataItem(),
				$requestOptions
			);

			$html .= $pageBuilder->getForm( count( $results ) );
			$html .= $pageBuilder->getHtml( $results );
		}

		return $html;
	}

}
