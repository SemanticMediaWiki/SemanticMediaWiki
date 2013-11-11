<?php

namespace SMW;

use SMWInfolink as Infolink;
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
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class BaseTemplateToolbox extends FunctionHook {

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
	 * @see FunctionHook::process
	 *
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process() {
		return $this->isValid( $this->skinTemplate->getSkin()->getTitle() ) ? $this->performUpdate() : true;
	}

	/**
	 * @since 1.9
	 *
	 * @return boolean
	 */
	protected function isValid( Title $title ) {
		return !$title->isSpecialPage() &&
			$this->withContext()->getDependencyBuilder()->newObject( 'Settings' )->get( 'smwgToolboxBrowseLink' ) &&
			$this->withContext()->getDependencyBuilder()->newObject( 'NamespaceExaminer' )->isSemanticEnabled( $title->getNamespace() ) &&
			$this->skinTemplate->data['isarticle'];
	}

	/**
	 * @since 1.9
	 *
	 * @return true
	 */
	protected function performUpdate() {

		$this->toolbox['smw-browse'] = array(
			'text' => $this->skinTemplate->getSkin()->getContext()->msg( 'smw_browselink' )->text(),
			'href' => \SpecialPage::getTitleFor( 'Browse', $this->encodeTitle() )->getLocalUrl(),
			'id'   => 't-smwbrowselink',
			'rel'  => 'smw-browse'
		);

		return true;
	}

	/**
	 * @since 1.9
	 */
	private function encodeTitle() {
		return Infolink::encodeParameters( array( $this->skinTemplate->getSkin()->getTitle()->getPrefixedDBkey() ), true );
	}

}
