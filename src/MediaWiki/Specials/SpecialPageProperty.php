<?php

namespace SMW\MediaWiki\Specials;

use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\DataModel\SequenceMap;
use SMW\Encoder;
use SMW\MediaWiki\Specials\PageProperty\PageBuilder;
use SMW\Options;
use SMW\RequestOptions;
use SMWInfolink as Infolink;
use SpecialPage;

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
class SpecialPageProperty extends SpecialPage {

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

		$request = $this->getRequest();

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

		$this->load( $options );
	}

	/**
	 * @see SpecialPage::getGroupName
	 */
	protected function getGroupName() {

		if ( version_compare( MW_VERSION, '1.33', '<' ) ) {
			return 'smw_group';
		}

		// #3711, MW 1.33+
		return 'smw_group/search';
	}

	private function load( $options ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$dataValueFactory = DataValueFactory::getInstance();

		$subject = $dataValueFactory->newTypeIDValue(
			'_wpg',
			$options->get( 'from' )
		);

		$propertyValue = $dataValueFactory->newPropertyValueByLabel(
			$options->get( 'property' )
		);

		$pagename = '';
		$propname = '';

		if ( $subject->isValid() ) {
			$pagename = $subject->getPrefixedText();
		}

		if ( $propertyValue->isValid() ) {
			$propname = $propertyValue->getWikiValue();
		}

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
			$html .= $pageBuilder->buildForm();
			$html .= wfMessage( 'smw_result_noresults' )->text();
		} else {

			$requestOptions = new RequestOptions();
			$requestOptions->setLimit( $options->get( 'limit' ) + 1 );
			$requestOptions->setOffset( $options->get( 'offset' ) );
			$requestOptions->sort = !SequenceMap::canMap( $propertyValue->getDataItem() );

			// Restrict the request otherwise the entire SemanticData record
			// is fetched which can in case of a subject with a large
			// subobject/subpage pool create excessive DB queries that are not
			// used for the display
			$requestOptions->conditionConstraint = true;

			$dataItem = $pagename !== '' ? $subject->getDataItem() : null;

			$results = $applicationFactory->getStore()->getPropertyValues(
				$dataItem,
				$propertyValue->getDataItem(),
				$requestOptions
			);

			$html .= $pageBuilder->buildForm( count( $results ) );
			$html .= $pageBuilder->buildHtml( $results );
		}

		$output = $this->getOutput();
		$output->setPagetitle( wfMessage( 'pageproperty' )->text() );

		$output->addModuleStyles( 'ext.smw.special.style' );
		$output->addModules( 'ext.smw.tooltip' );

		$output->addModules( 'ext.smw.autocomplete.property' );
		$output->addModules( 'ext.smw.autocomplete.article' );

		$output->addHTML( $html );
	}

}
