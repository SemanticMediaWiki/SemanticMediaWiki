<?php

namespace SMW\Test;

use SMW\ParserParameterFormatter;
use SMW\RecurringEvents;

/**
 * @covers \SMW\RecurringEvents
 * @group semantic-mediawiki
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class RecurringEventsTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return RecurringEvents
	 */
	private function newInstance( array $params ) {

		$parameters = new ParserParameterFormatter( $params );

		$instance = new RecurringEvents( $parameters->toArray() );
		$instance->setDefaultNumRecurringEvents( 10 );
		$instance->setMaxNumRecurringEvents( 50 );

		return $instance;
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\RecurringEvents',
			new RecurringEvents( [] )
		);
	}

	/**
	 * @dataProvider getParametersDataProvider
	 *
	 * @since 1.9
	 */
	public function testGetErrors( array $params, array $expected ) {
		$this->assertCount( $expected['errors'], $this->newInstance( $params )->getErrors() );
	}

	/**
	 * @dataProvider getParametersDataProvider
	 *
	 * @since 1.9
	 */
	public function testGetProperty( array $params, array $expected ) {
		$this->assertEquals( $expected['property'], $this->newInstance( $params )->getProperty() );
	}

	/**
	 * @dataProvider getParametersDataProvider
	 *
	 * @since 1.9
	 */
	public function testGetParameters( array $params, array $expected ) {
		$this->assertEquals( $expected['parameters'], $this->newInstance( $params )->getParameters() );
	}

	/**
	 * @dataProvider getParametersDataProvider
	 *
	 * @since 1.9
	 */
	public function testGetDates( array $params, array $expected ) {
		$this->assertEquals( $expected['dates'], $this->newInstance( $params )->getDates() );
	}

	/**
	 * @return array
	 */
	public function getMassInsertDataProvider() {
		return [
			[
				[
					'property=Has birthday',
					'start=01 Feb 1970',
					'Has title=Birthday',
					'unit=month', 'period=12',
					'limit=500',
				],
				[
					'errors' => 0,
					'count' => 501,
					'property' => '',
					'parameters' => []
				]
			]
		];
	}

	/**
	 * @dataProvider getMassInsertDataProvider
	 *
	 * @since 1.
	 */
	public function testMassInsert( array $params, array $expected ) {
		$this->assertCount( $expected['count'], $this->newInstance( $params )->getDates() );
	}

	/**
	 * @test RecurringEvents::getJulianDay
	 *
	 * @since 1.9
	 */
	public function testGetJulianDay() {
		$instance = $this->newInstance( [] );

		// SMWDIWikiPage stub object
		$dataValue = $this->getMockBuilder( 'SMWTimeValue' )
			->disableOriginalConstructor()
			->getMock();

		$dataValue->expects( $this->any() )
			->method( 'getDataItem' )
			->will( $this->returnValue( null ) );

		$this->assertEquals( null, $instance->getJulianDay( $dataValue ) );
	}

	/**
	 * @return array
	 */
	public function getParametersDataProvider() {
		return [
			// {{#set_recurring_event:property=Has birthday
			// |start=01 Feb 1970
			// |has title= Birthday
			// |unit=year
			// |period=12
			// |limit=3
			// }}
			[
				[
					'property=Has birthday',
					'start=01 Feb 1970',
					'has title=Birthday',
					'unit=month',
					'period=12',
					'limit=3'
				],
				[
					'errors' => 0,
					'dates' => [ '1 February 1970', '1 February 1971', '1 February 1972', '1 February 1973' ],
					'property' => 'Has birthday',
					'parameters' => [ 'has title' => [ 'Birthday' ] ]
				]
			],

			// {{#set_recurring_event:property=Has birthday
			// |start=01 Feb 1970
			// |end=01 Feb 1972
			// |has title= Birthday
			// |unit=year
			// |period=12
			// |limit=3
			// }}
			[
				[
					'property=Has birthday',
					'start=01 Feb 1970',
					'end=01 Feb 1972',
					'has title=Birthday',
					'unit=month',
					'period=12',
					'limit=3'
				],
				[
					'errors' => 0,
					'dates' => [ '1 February 1970', '1 February 1971', '1 February 1972' ],
					'property' => 'Has birthday',
					'parameters' => [ 'has title' => [ 'Birthday' ] ]
				]
			],

			// {{#set_recurring_event:property=Has birthday
			// |start=01 Feb 1970
			// |end=01 Feb 1972
			// |has title= Birthday
			// |unit=year
			// |week number=2
			// |period=12
			// |limit=3
			// }}
			[
				[
					'property=Has birthday',
					'start=01 Feb 1970',
					'end=01 Feb 1972',
					'has title=Birthday',
					'unit=month',
					'week number=2',
					'period=12',
					'limit=3'
				],
				[
					'errors' => 0,
					'dates' => [ '1 February 1970', '14 February 1971' ],
					'property' => 'Has birthday',
					'parameters' => [ 'has title' => [ 'Birthday' ] ]
				]
			],

			// {{#set_recurring_event:property=Has birthday
			// |start=01 Feb 1972 02:00
			// |has title=Test 12
			// |unit=week
			// |period=4
			// |limit=3
			// }}
			[
				[
					'property=Has birthday',
					'start=01 Feb 1972 02:00',
					'has title=Test 2',
					'unit=week',
					'period=4',
					'limit=3'
				],
				[
					'errors' => 0,
					'dates' => [ '1 February 1972 02:00:00', '29 February 1972 02:00:00', '28 March 1972 02:00:00', '25 April 1972 02:00:00' ],
					'property' => 'Has birthday',
					'parameters' => [ 'has title' => [ 'Test 2' ] ]
				]
			],

			// {{#set_recurring_event:property=Has date
			// |start=January 4, 2010
			// |unit=week
			// |period=1
			// |limit=4
			// |include=March 16, 2010;March 23, 2010
			// |exclude=January 18, 2010;January 25, 2010
			// }}
			[
				[
					'property=Has date',
					'start=January 4, 2010',
					'unit=week',
					'period=1',
					'limit=4',
					'include=March 16, 2010;March 23, 2010',
					'exclude=January 18, 2010;January 25, 2010'
				],
				[
					'errors' => 0,
					'dates' => [ '4 January 2010', '11 January 2010', '1 February 2010', 'March 16, 2010', 'March 23, 2010' ],
					'property' => 'Has date',
					'parameters' => []
				]
			],

			// {{#set_recurring_event:property=Has date
			// |start=January 4, 2010
			// |unit=week
			// |period=1
			// |limit=4
			// |include=March 16, 2010;March 23, 2010|+sep=;
			// |exclude=January 18, 2010;January 25, 2010|+sep=;
			// }}
			[
				[
					'property=Has date',
					'start=January 4, 2010',
					'unit=week',
					'period=1',
					'limit=4',
					'include=March 16, 2010;March 23, 2010',
					'+sep=;',
					'exclude=January 18, 2010;January 25, 2010',
					'+sep=;'
				],
				[
					'errors' => 0,
					'dates' => [ '4 January 2010', '11 January 2010', '1 February 2010', 'March 16, 2010', 'March 23, 2010' ],
					'property' => 'Has date',
					'parameters' => []
				]
			],

			// Simulate start date has wrong type

			// {{#set_recurring_event:property=Has date
			// |start=???
			// |unit=week
			// |period=1
			// |limit=4
			// |include=March 16, 2010;March 23, 2010
			// |exclude=January 18, 2010;January 25, 2010
			// }}
			[
				[
					'property=Has date',
					'start=???',
					'unit=week',
					'period=1',
					'limit=4',
					'include=March 16, 2010;March 23, 2010',
					'exclude=January 18, 2010;January 25, 2010'
				],
				[
					'errors' => 1,
					'dates' => [],
					'property' => 'Has date',
					'parameters' => []
				]
			],

			// Simulate missing start date

			// {{#set_recurring_event:property=Has date
			// |start=
			// |unit=week
			// |period=1
			// |limit=4
			// |include=March 16, 2010;March 23, 2010
			// |exclude=January 18, 2010;January 25, 2010
			// }}
			[
				[
					'property=Has date',
					'start=',
					'unit=week',
					'period=1',
					'limit=4',
					'include=March 16, 2010;March 23, 2010',
					'exclude=January 18, 2010;January 25, 2010'
				],
				[
					'errors' => 1,
					'dates' => [],
					'property' => 'Has date',
					'parameters' => []
				]
			],

			// Simulate missing property

			// {{#set_recurring_event:property=
			// |start=January 4, 2010
			// |unit=week
			// |period=1
			// |limit=4
			// |include=March 16, 2010;March 23, 2010|+sep=;
			// |exclude=January 18, 2010;January 25, 2010|+sep=;
			// }}
			[
				[
					'property=',
					'start=January 4, 2010',
					'unit=week', 'period=1',
					'limit=4',
					'include=March 16, 2010;March 23, 2010',
					'+sep=;',
					'exclude=January 18, 2010;January 25, 2010',
					'+sep=;'
				],
				[
					'errors' => 1,
					'dates' => [],
					'property' => '',
					'parameters' => []
				]
			],
		];
	}
}
