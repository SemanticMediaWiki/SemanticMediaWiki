<?php

namespace SMW;

use OutputPage;
use Skin;
use Title;

/**
 * BeforePageDisplay hook
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * BeforePageDisplay hook which allows last minute changes to the
 * output page, e.g. adding of CSS or JavaScript
 *
 * @see http://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
 *
 * @ingroup Hook
 */
class BeforePageDisplay extends MediaWikiHook {

	/** @var OutputPage */
	protected $outputPage = null;

	/** @var Skin */
	protected $skin = null;

	/**
	 * @since  1.9
	 *
	 * @param OutputPage &$outputPage
	 * @param Skin &$skin
	 */
	public function __construct( OutputPage &$outputPage, Skin &$skin ) {
		$this->outputPage = $outputPage;
		$this->skin = $skin;
	}

	/**
	 * @see HookBase::process
	 *
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process() {

		$title = $this->outputPage->getTitle();

		// Add style resources to avoid unstyled content
		$this->outputPage->addModules( array( 'ext.smw.style' ) );

		// Add export link to the head
		if ( $title instanceof Title && !$title->isSpecialPage() ) {
			$linkarr['rel']   = 'ExportRDF';
			$linkarr['type']  = 'application/rdf+xml';
			$linkarr['title'] = $title->getPrefixedText();
			$linkarr['href']  = \SpecialPage::getTitleFor( 'ExportRDF', $title->getPrefixedText() )->getLocalUrl( 'xmlmime=rdf' );
			$this->outputPage->addLink( $linkarr );
		}

		return true;
	}

}
