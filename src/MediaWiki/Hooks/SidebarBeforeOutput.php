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
			$this->performUpdate( $title, $sidebar );
		}

		return true;
	}

	private function canProcess( Title $title, $skin ) {
		if ( $title->isSpecialPage() || !$this->namespaceExaminer->isSemanticEnabled( $title->getNamespace() ) ) {
			return false;
		}
		
		# || !$skin->data['isarticle'] 
		if ( !$this->isFlagSet( 'smwgBrowseFeatures', SMW_BROWSE_TLINK )) {
			return false;
		}

		return true;
	}

	private function performUpdate( $title, &$sidebar ) {

		$link = Infolink::encodeParameters(
			[
				$title->getPrefixedDBkey()
			],
			true
		);

		$sidebar["TOOLBOX"][] = [
			'text' => wfMessage( 'smw_browselink' )->text(),
			'href' => SpecialPage::getTitleFor( 'Browse', ':' . $link )->getLocalUrl(),
			'id'   => 't-smwbrowselink',
			'rel'  => 'search'
		];
	}

}
