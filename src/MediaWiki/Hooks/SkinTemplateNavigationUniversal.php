<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;

/**
 * Alter the structured navigation links in SkinTemplates.
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinTemplateNavigation::Universal
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class SkinTemplateNavigationUniversal implements SkinTemplateNavigation__UniversalHook {

	/**
	 * @since 7.0.0
	 */
	public function __construct( private readonly PersonalUrls $personalUrls ) {
	}

	/**
	 * @since 7.0.0
	 */
	// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
	public function onSkinTemplateNavigation__Universal( $sktemplate, &$links ): void {
		if ( isset( $links['user-interface-preferences'] ) ) {
			$this->personalUrls->onPersonalUrls(
				$links['user-interface-preferences'],
				$sktemplate->getTitle(),
				$sktemplate
			);
		}

		if ( $sktemplate->getUser()->isAllowed( 'purge' ) ) {
			$sktemplate->getOutput()->addModules( 'ext.smw.purge' );
			$links['actions']['purge'] = [
				'class' => 'is-disabled',
				'text' => $sktemplate->msg( 'smw_purge' )->text(),
				'href' => $sktemplate->getTitle()->getLocalUrl( [ 'action' => 'purge' ] )
			];
		}
	}

}
