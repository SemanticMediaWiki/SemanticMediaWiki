<?php

namespace SMW\Test;

use SMW\SpecialSemanticStatistics;

use Title;

/**
 * Tests for the SMW\SpecialSemanticStatistics class.
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
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * This class tests methods provided by the SMW\SpecialSemanticStatistics class
 *
 * @ingroup SMW
 * @ingroup Test
 */
class SpecialSemanticStatisticsTest extends \MediaWikiTestCase {

	/**
	 * Test SpecialSemanticStatistics::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$title = Title::newFromText( 'Special:SemanticStatistics' );
		$context = new \RequestContext;
		$context->setTitle( $title );

		$statistics = new SpecialSemanticStatistics();
		$statistics->setContext( $context );
		$statistics->execute( array() );

		$this->assertTrue( $title->equals( $statistics->getContext()->getTitle() ) );
		$this->assertNotEmpty( $statistics->getOutput()->getHtml() );
	}

}
