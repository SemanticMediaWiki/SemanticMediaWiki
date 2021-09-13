<?php

namespace SMW\MediaWiki\Hooks;

use SMW\MediaWiki\HookListener;
use SMW\NamespaceExaminer;
use SMWInfolink as Infolink;
use Skin;
use SpecialPage;
use Title;
use SMW\OptionsAwareTrait;
/**
 * Called at the end of Skin::buildSidebar().
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SidebarBeforeOutput
 *
 * @license GNU GPL v2+
 *
 * @author StarHeartHunt
 */
class SidebarBeforeOutput implements HookListener {

    use OptionsAwareTrait;

	/**
	 * @var NamespaceExaminer
	 */
	private $namespaceExaminer;

	/**
	 *
	 * @param NamespaceExaminer $namespaceExaminer
	 */
	public function __construct( NamespaceExaminer $namespaceExaminer ) {
		$this->namespaceExaminer = $namespaceExaminer;
	}

	/**
	 *
	 * @param $skin
	 * @param &$sidebar
	 *
	 * @return boolean
	 */
	public function process( $skin, &$sidebar ) {

		$title = $skin->getTitle();

		if ( $this->canProcess( $title, $skin ) ) {
			$this->performUpdate( $title, $skin, $sidebar );
		}

		return true;
	}

	private function canProcess( Title $title, Skin $skin ) {
		if ( $title->isSpecialPage() || !$this->namespaceExaminer->isSemanticEnabled( $title->getNamespace() ) ) {
			return false;
		}

		if ( !$skin->getOutput()->isArticle() || !$this->isFlagSet( 'smwgBrowseFeatures', SMW_BROWSE_TLINK ) ) {
			return false;
		}

		return true;
	}

	private function performUpdate( Title $title, Skin $skin, &$sidebar ) {

		$link = Infolink::encodeParameters(
			[
				$title->getPrefixedDBkey()
			],
			true
		);

		$sidebar["TOOLBOX"][] = [
			'text' => $skin->msg( 'smw_browselink' )->text(),
			'href' => SpecialPage::getTitleFor( 'Browse', ':' . $link )->getLocalUrl(),
			'id'   => 't-smwbrowselink',
			'rel'  => 'search'
		];
	}

}
