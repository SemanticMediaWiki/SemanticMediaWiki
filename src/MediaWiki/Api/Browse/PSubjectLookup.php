<?php

namespace SMW\MediaWiki\Api\Browse;

use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataValueFactory;
use SMW\RequestOptions;
use SMW\Store;
use SMW\StringCondition;
use Traversable;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class PSubjectLookup extends Lookup {

	const VERSION = 1;

	/**
	 * @since 3.0
	 */
	public function __construct( private readonly Store $store ) {
	}

	/**
	 * @since 3.0
	 */
	public function getVersion(): string {
		return __METHOD__ . self::VERSION;
	}

	/**
	 * @since 3.0
	 */
	public function lookup( array $parameters ): array {
		$limit = 20;
		$offset = 0;

		if ( isset( $parameters['limit'] ) ) {
			$limit = (int)$parameters['limit'];
		}

		if ( isset( $parameters['offset'] ) ) {
			$offset = (int)$parameters['offset'];
		}

		$list = [];
		$continueOffset = 0;
		$property = null;
		$value = null;

		if ( isset( $parameters['property'] ) ) {
			$property = $parameters['property'];

			// Get the last which represents the final output
			// Foo.Bar.Foobar.Baz
			if ( strpos( $property, '.' ) !== false ) {
				$chain = explode( '.', $property );
				$property = array_pop( $chain );
			}
		}

		if ( isset( $parameters['value'] ) ) {
			$value = $parameters['value'];
		}

		if ( $property === '' || $property === null ) {
			return [];
		}

		[ $list, $continueOffset, $continueCursor ] = $this->findPropertySubjects(
			$property,
			$value,
			$limit,
			$offset,
			$parameters
		);

		// Changing this output format requires to set a new version. The
		// `query-continue-cursor` field is byte-additive: it is only emitted
		// when the caller opted into cursor mode (by sending `cursor` in
		// the request payload). Legacy clients that follow
		// `query-continue-offset` see exactly the pre-cursor response shape.
		$res = [
			'query' => $list,
			'query-continue-offset' => $continueOffset,
			'version' => self::VERSION,
			'meta' => [
				'type'  => 'psubject',
				'limit' => $limit,
				'count' => count( $list )
			]
		];

		if ( self::shouldUseCursorMode( $parameters ) ) {
			$res['query-continue-cursor'] = $continueCursor;
		}

		return $res;
	}

	private function findPropertySubjects( $property, $value, int $limit, int $offset, array $parameters ): array {
		$list = [];
		$dataItem = null;

		$property = Property::newFromUserLabel( $property );

		if ( $value !== '' && $value !== null ) {
			$dataItem = DataValueFactory::getInstance()->newDataValueByProperty( $property, $value )->getDataItem();
		}

		$continueOffset = 0;
		$continueCursor = 0;
		$count = 0;
		$requestOptions = $this->newRequestOptions( $parameters );
		$cursorMode = (bool)$requestOptions->getOption( RequestOptions::CURSOR_MODE );

		$res = $this->store->getPropertySubjects(
			$property,
			$dataItem,
			$requestOptions
		);

		foreach ( $res as $dataItem ) {

			if ( !$dataItem instanceof WikiPage ) {
				continue;
			}

			if ( isset( $parameters['title-prefix'] ) && !( (bool)( $parameters['title-prefix'] ) ) ) {
				$list[] = $dataItem->getTitle()->getText();
			} else {
				$list[] = $dataItem->getTitle()->getPrefixedText();
			}
		}

		if ( $this->is_iterable( $res ) ) {
			$count = count( $res );
		}

		if ( $cursorMode ) {
			// `PropertySubjectsLookup::postProcessCursorResult()` already
			// trimmed the lookahead row and wrote the cursor metadata back
			// onto `$requestOptions`. The trimmed list arrives ready to
			// display; we just surface the cursor anchor for the next page.
			if ( $requestOptions->getCursorHasMore() ) {
				$continueCursor = $requestOptions->getLastCursor() ?? 0;
			}
		} elseif ( $count > $limit ) {
			$continueOffset = $offset + $count;
			array_pop( $list );
		}

		return [ $list, $continueOffset, $continueCursor ];
	}

	private function newRequestOptions( array $parameters ): RequestOptions {
		$limit = 20;
		$offset = 0;
		$search = '';

		if ( isset( $parameters['limit'] ) ) {
			$limit = (int)$parameters['limit'];
		}

		if ( isset( $parameters['offset'] ) ) {
			$offset = (int)$parameters['offset'];
		}

		$requestOptions = new RequestOptions();
		$requestOptions->sort = true;

		if ( self::shouldUseCursorMode( $parameters ) ) {
			// Cursor mode is authoritative: any `offset` co-sent with
			// `cursor` is ignored so the response doesn't accidentally seek
			// past the cursor by the legacy offset amount.
			// `PropertySubjectsLookup` adds its own `LIMIT + 1` lookahead in
			// cursor mode, so the caller passes the plain page size.
			$requestOptions->setLimit( $limit );
			$requestOptions->setOption( RequestOptions::CURSOR_MODE, true );
			$cursor = (int)$parameters['cursor'];
			if ( $cursor > 0 ) {
				$requestOptions->setCursorAfter( $cursor );
			}
		} else {
			// Legacy offset path: `+1` lookahead is manually trimmed in
			// `findPropertySubjects()`.
			$requestOptions->setLimit( $limit + 1 );
			$requestOptions->setOffset( $offset );
		}

		if ( isset( $parameters['search'] ) && $parameters['search'] !== '' ) {
			$search = (string)$parameters['search'];

			if ( $search !== '' && $search[0] !== '_' ) {
				$search = str_replace( "_", " ", $search );
			}

			$requestOptions->addStringCondition(
				$search,
				StringCondition::STRCOND_MID
			);

			// Disjunctive condition to allow for auto searches to match foaf OR Foaf
			$requestOptions->addStringCondition(
				ucfirst( $search ),
				StringCondition::STRCOND_MID,
				true
			);

			// Allow something like FOO to match the search string `foo`
			$requestOptions->addStringCondition(
				strtoupper( $search ),
				StringCondition::STRCOND_MID,
				true
			);

			$requestOptions->addStringCondition(
				strtolower( $search ),
				StringCondition::STRCOND_MID,
				true
			);
		}

		return $requestOptions;
	}

	private function is_iterable( $obj ): bool {
		return is_array( $obj ) || ( is_object( $obj ) && ( $obj instanceof Traversable ) );
	}

}
