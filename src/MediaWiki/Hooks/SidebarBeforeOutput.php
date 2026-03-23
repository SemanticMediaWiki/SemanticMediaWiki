<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Skin\SkinComponentUtils;
use MediaWiki\Title\Title;
use Skin;
use SMW\Formatters\Infolink;
use SMW\MediaWiki\HookListener;
use SMW\NamespaceExaminer;
use SMW\OptionsAwareTrait;

/**
 * Called at the end of Skin::buildSidebar().
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SidebarBeforeOutput
 *
 * @license GPL-2.0-or-later
 *
 * @author StarHeartHunt
 */
class SidebarBeforeOutput implements HookListener {

	use OptionsAwareTrait;

	public function __construct( private NamespaceExaminer $namespaceExaminer ) {
	}

	/**
	 * @param $skin
	 * @param &$sidebar
	 *
	 * @return bool
	 */
	public function process( $skin, array &$sidebar ): bool {
		$title = $skin->getTitle();

		if ( $this->canProcess( $title, $skin ) ) {
			$this->performUpdate( $title, $skin, $sidebar );
		}

		return true;
	}

	private function canProcess( Title $title, Skin $skin ): bool {
		if ( $title->isSpecialPage() || !$this->namespaceExaminer->isSemanticEnabled( $title->getNamespace() ) ) {
			return false;
		}

		if ( !$skin->getOutput()->isArticle() || !$this->isFlagSet( 'smwgBrowseFeatures', SMW_BROWSE_TLINK ) ) {
			return false;
		}

		return true;
	}

	private function performUpdate( Title $title, Skin $skin, array &$sidebar ): void {
		$link = Infolink::encodeParameters(
			[
				$title->getPrefixedDBkey()
			],
			true
		);

		$sidebar["TOOLBOX"]['smwbrowselink'] = [
			'text' => $skin->msg( 'smw_browselink' )->text(),
			'href' => SkinComponentUtils::makeSpecialUrl( "Browse" ) . "/:$link",
			'icon' => 'database',
			'id'   => 't-smwbrowselink',
			'rel'  => 'search'
		];
	}

}
