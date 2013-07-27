<?php

namespace SMW;

use LinksUpdate;

/**
 * LinksUpdateConstructed hook
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
 * LinksUpdateConstructed hook is called at the end of LinksUpdate() is contruction
 *
 * @see http://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdateConstructed
 *
 * @ingroup Hook
 */
class LinksUpdateConstructed extends MediaWikiHook {

	/** @var LinksUpdate */
	protected $linksUpdate = null;

	/**
	 * @since  1.9
	 *
	 * @param LinksUpdate $linksUpdate
	 */
	public function __construct( LinksUpdate $linksUpdate ) {
		$this->linksUpdate = $linksUpdate;
	}

	/**
	 * @see HookBase::process
	 *
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process() {

		$parserData = new ParserData( $this->linksUpdate->getTitle(), $this->linksUpdate->getParserOutput() );
		$parserData->updateStore();

		return true;
	}

}
