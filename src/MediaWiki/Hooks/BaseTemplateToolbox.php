<?php

namespace SMW\MediaWiki\Hooks;

use SMW\ApplicationFactory;
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
 * @see http://www.mediawiki.org/wiki/Manual:Hooks/BaseTemplateToolbox
 *
 * @ingroup FunctionHook
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class BaseTemplateToolbox {

	/**
	 * @var SkinTemplate
	 */
	private $skinTemplate;

	/**
	 * @var array
	 */
	private $toolbox;

	/**
	 * @since 1.9
	 *
	 * @param $skinTemplate
	 * @param &$toolbox
	 */
	public function __construct( $skinTemplate, &$toolbox ) {
		$this->skinTemplate = $skinTemplate;
		$this->toolbox =& $toolbox;
	}

	/**
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function process() {
		return $this->canPerformUpdate( $this->skinTemplate->getSkin()->getTitle() ) ? $this->performUpdate() : true;
	}

	protected function canPerformUpdate( Title $title ) {
		return !$title->isSpecialPage() &&
			ApplicationFactory::getInstance()->getSettings()->get( 'smwgToolboxBrowseLink' ) &&
			$this->isEnabledNamespace( $title ) &&
			$this->skinTemplate->data['isarticle'];
	}

	protected function performUpdate() {

		$this->toolbox['smw-browse'] = array(
			'text' => wfMessage( 'smw_browselink' )->text(),
			'href' => SpecialPage::getTitleFor( 'Browse', $this->encodePrefixedDBkey() )->getLocalUrl(),
			'id'   => 't-smwbrowselink',
			'rel'  => 'smw-browse'
		);

		return true;
	}

	private function encodePrefixedDBkey() {
		return Infolink::encodeParameters( array( $this->skinTemplate->getSkin()->getTitle()->getPrefixedDBkey() ), true );
	}

	private function isEnabledNamespace( Title $title ) {
		return NamespaceExaminer::newFromArray(
			ApplicationFactory::getInstance()->getSettings()->get( 'smwgNamespacesWithSemanticLinks' ) )->isSemanticEnabled( $title->getNamespace() );
	}

}
