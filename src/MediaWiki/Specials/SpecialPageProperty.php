<?php

namespace SMW\MediaWiki\Specials;

use MediaWiki\SpecialPage\SpecialPage;
use SMW\DataModel\SequenceMap;
use SMW\DataValueFactory;
use SMW\Encoder;
use SMW\Formatters\Infolink;
use SMW\MediaWiki\Specials\PageProperty\PageBuilder;
use SMW\Options;
use SMW\RequestOptions;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Store;

/**
 * This special page implements a view on a object-relation pair, i.e. a page that
 * shows all the values of a property for a certain page.
 *
 * This is typically used for overflow results from other dynamic output pages.
 *
 * @license GPL-2.0-or-later
 * @since 1.4
 *
 * @author Denny Vrandecic
 * @author mwjames
 */
class SpecialPageProperty extends SpecialPage {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly Store $store
	) {
		// MediaWiki 1.46 deprecated the SpecialPage constructor flags; the
		// page stays unlisted via the isListed() override below.
		parent::__construct( 'PageProperty' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function isListed(): bool {
		return false;
	}

	/**
	 * @see SpecialPage::execute
	 */
	public function execute( $query ): void {
		$request = $this->getRequest();

		if ( $request->getText( 'cl', '' ) !== '' ) {
			$query = Infolink::decodeCompactLink( 'cl:' . $request->getText( 'cl' ) );
		} else {
			$query = Infolink::decodeCompactLink( $query );
		}

		if ( $query !== '' ) {
			$query = Encoder::unescape( (string)$query );
		}

		// Get parameters
		$pagename = $request->getVal( 'from', '' );
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
			$this->msg( 'smw-special-pageproperty-helplink' )->text(),
			true
		);

		$this->load( $options );
	}

	/**
	 * @see SpecialPage::getGroupName
	 */
	protected function getGroupName(): string {
		return 'smw_group/search';
	}

	private function load( Options $options ): void {
		// Partial DI: MwCollaboratorFactory is still resolved through
		// ApplicationFactory because it is not registered as a global SMW.X
		// service.
		$applicationFactory = ApplicationFactory::getInstance();
		$dataValueFactory = DataValueFactory::getInstance();

		$subject = $dataValueFactory->newDataValueByType(
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
			$html .= $this->msg( 'smw_result_noresults' )->escaped();
		} else {

			$requestOptions = new RequestOptions();
			$requestOptions->setLimit( (int)$options->get( 'limit' ) + 1 );
			$requestOptions->setOffset( (int)$options->get( 'offset' ) );
			$requestOptions->sort = !SequenceMap::canMap( $propertyValue->getDataItem() );

			// Restrict the request otherwise the entire SemanticData record
			// is fetched which can in case of a subject with a large
			// subobject/subpage pool create excessive DB queries that are not
			// used for the display
			$requestOptions->conditionConstraint = true;

			$dataItem = $pagename !== '' ? $subject->getDataItem() : null;

			$results = $this->store->getPropertyValues(
				$dataItem,
				$propertyValue->getDataItem(),
				$requestOptions
			);

			$html .= $pageBuilder->buildForm( count( $results ) );
			$html .= $pageBuilder->buildHtml( $results );
		}

		$output = $this->getOutput();
		$output->setPagetitle( $this->msg( 'pageproperty' )->text() );

		$output->addModuleStyles( 'ext.smw.special.styles' );
		$output->addModules( 'ext.smw.tooltip' );

		$output->addModules( 'ext.smw.autocomplete.property' );
		$output->addModules( 'ext.smw.autocomplete.page' );

		$output->addHTML( $html );
	}

}
