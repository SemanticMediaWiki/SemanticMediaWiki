<?php

namespace SMW\MediaWiki\Specials;

use SMW\MediaWiki\Specials\SearchByProperty\PageBuilder;
use SMW\MediaWiki\Specials\SearchByProperty\PageRequestOptions;
use SMW\MediaWiki\Specials\SearchByProperty\QueryResultLookup;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMWInfolink as Infolink;
use SpecialPage;

/**
 * A special page to search for entities that have a certain property with
 * a certain value.
 *
 * This special page for Semantic MediaWiki implements a view on a
 * relation-object pair,i.e. a typed backlink. For example, it shows me all
 * persons born in Croatia, or all winners of the Academy Award for best actress.
 *
 * @license GPL-2.0-or-later
 * @since   2.1
 *
 * @author mwjames
 */
class SpecialSearchByProperty extends SpecialPage {

	/**
	 * @codeCoverageIgnore
	 */
	public function __construct() {
		parent::__construct( 'SearchByProperty' );
	}

	/**
	 * @see SpecialPage::execute
	 */
	public function execute( $query ) {
		$this->setHeaders();
		$output = $this->getOutput();
		$request = $this->getRequest();

		$output->setPageTitle( $this->msg( 'searchbyproperty' )->text() );
		$output->addModuleStyles( 'ext.smw.styles' );
		$output->addModules( 'ext.smw.tooltip' );
		$output->addModules( 'ext.smw.autocomplete.property' );

		[ $limit, $offset ] = $this->getLimitOffset();

		if ( $request->getText( 'cl', '' ) !== '' ) {
			$query = Infolink::decodeCompactLink( 'cl:' . $request->getText( 'cl' ) );
		} else {
			$query = Infolink::decodeCompactLink( $query );
		}

		// @see SMWInfolink::encodeParameters
		if ( $query === null && $this->getRequest()->getCheck( 'x' ) ) {
			$query = $this->getRequest()->getVal( 'x' );
		}

		$applicationFactory = ApplicationFactory::getInstance();

		$requestOptions = [
			'limit'    => $limit,
			'offset'   => $offset,
			'property' => $this->getRequest()->getVal( 'property' ),
			'value'    => $this->getRequest()->getVal( 'value' ),
			'nearbySearchForType' => $applicationFactory->getSettings()->get( 'smwgSearchByPropertyFuzzy' )
		];

		$htmlFormRenderer = $applicationFactory->newMwCollaboratorFactory()->newHtmlFormRenderer(
			$this->getContext()->getTitle(),
			$this->getLanguage()
		);

		$pageBuilder = new PageBuilder(
			$htmlFormRenderer,
			new PageRequestOptions( $query ?? '', $requestOptions ),
			new QueryResultLookup( $applicationFactory->getStore() )
		);

		$output->addHTML( $pageBuilder->getHtml() );
	}

	private function getLimitOffset() {
		$request = $this->getRequest();
		return $request->getLimitOffsetForUser( $this->getUser() );
	}

	/**
	 * @see SpecialPage::getGroupName
	 */
	protected function getGroupName() {
		return 'smw_group/search';
	}

}
