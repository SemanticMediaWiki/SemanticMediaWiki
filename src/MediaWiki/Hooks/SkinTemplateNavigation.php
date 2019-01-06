<?php

namespace SMW\MediaWiki\Hooks;

use SkinTemplate;

/**
 * Alter the structured navigation links in SkinTemplates.
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinTemplateNavigation
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class SkinTemplateNavigation {

	/**
	 * @var SkinTemplate
	 */
	private $skinTemplate = null;

	/**
	 * @var array
	 */
	private $links;

	/**
	 * @since  2.0
	 *
	 * @param SkinTemplate $skinTemplate
	 * @param array $links
	 */
	public function __construct( SkinTemplate &$skinTemplate, array &$links ) {
		$this->skinTemplate = $skinTemplate;
		$this->links =& $links;
	}

	/**
	 * @since 2.0
	 *
	 * @return true
	 */
	public function process() {

		if ( $this->skinTemplate->getUser()->isAllowed( 'purge' ) ) {
			$this->skinTemplate->getOutput()->addModules( 'ext.smw.purge' );
			$this->links['actions']['purge'] = [
				'class' => 'is-disabled',
				'text' => $this->skinTemplate->msg( 'smw_purge' )->text(),
				'href' => $this->skinTemplate->getTitle()->getLocalUrl( [ 'action' => 'purge' ] )
			];
		}

		return true;
	}

}
