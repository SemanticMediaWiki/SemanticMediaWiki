<?php

namespace SMW\MediaWiki\Hooks;

use SMW\NamespaceExaminer;
use SMWInfolink as Infolink;
use SpecialPage;
use Title;

/**
 * Hook: Called by BaseTemplate when building the toolbox array and
 * returning it for the skin to output.
 *
 * Add a link to the toolbox to view the properties of the current page in
 * Special:Browse. The links has the CSS id "t-smwbrowselink" so that it can be
 * skinned or hidden with all standard mechanisms (also by individual users
 * with custom CSS).
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BaseTemplateToolbox
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class BaseTemplateToolbox extends HookHandler {

	/**
	 * @var NamespaceExaminer
	 */
	private $namespaceExaminer;

	/**
	 * @since 1.9
	 *
	 * @param NamespaceExaminer $namespaceExaminer
	 */
	public function __construct( NamespaceExaminer $namespaceExaminer ) {
		$this->namespaceExaminer = $namespaceExaminer;
	}

	/**
	 * @since 1.9
	 *
	 * @param $skinTemplate
	 * @param &$toolbox
	 *
	 * @return boolean
	 */
	public function process( $skinTemplate, &$toolbox ) {

		$title = $skinTemplate->getSkin()->getTitle();

		if ( $this->canProcess( $title, $skinTemplate ) ) {
			$this->performUpdate( $title, $toolbox );
		}

		return true;
	}

	private function canProcess( Title $title, $skinTemplate ) {

		if ( $title->isSpecialPage() || !$this->namespaceExaminer->isSemanticEnabled( $title->getNamespace() ) ) {
			return false;
		}

		if ( !$this->isFlagSet( 'smwgBrowseFeatures', SMW_BROWSE_TLINK ) || !$skinTemplate->data['isarticle'] ) {
			return false;
		}

		return true;
	}

	private function performUpdate( $title, &$toolbox ) {

		$link = Infolink::encodeParameters(
			[
				$title->getPrefixedDBkey()
			],
			true
		);

		$toolbox['smw-browse'] = [
			'text' => wfMessage( 'smw_browselink' )->text(),
			'href' => SpecialPage::getTitleFor( 'Browse', ':' . $link )->getLocalUrl(),
			'id'   => 't-smwbrowselink',
			'rel'  => 'search'
		];
	}

}
