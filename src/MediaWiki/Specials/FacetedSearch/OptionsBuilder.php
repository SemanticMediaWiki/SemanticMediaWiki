<?php

namespace SMW\MediaWiki\Specials\FacetedSearch;

use Html;
use SMW\Localizer\MessageLocalizerTrait;

/**
 * @license GPL-2.0-or-later
 * @since   3.2
 *
 * @author mwjames
 */
class OptionsBuilder {

	use MessageLocalizerTrait;

	/**
	 * @var Profile
	 */
	private $profile;

	/**
	 * @since 3.2
	 *
	 * @param Profile $profile
	 */
	public function __construct( Profile $profile ) {
		$this->profile = $profile;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $profile
	 *
	 * @return string
	 */
	public function profiles( string $profile = '' ): string {
		$html = [];
		$list = $this->profile->getProfileList();

		if ( $profile == '' ) {
			$profile = $this->profile->getProfileName();
		}

		ksort( $list );

		foreach ( $list as $key => $val ) {

			$message_key = $val;
			$key = str_replace( '_profile', '', $key );

			$opt = $profile === $key ? ' selected' : '';
			$val = $this->msg( $val );

			if ( strpos( $val, $message_key ) ) {
				$val = $key;
			}

			$html[] = "<option value='$key'$opt>$val</option>";
		}

		return implode( '', $html );
	}

	/**
	 * @since 3.2
	 *
	 * @param string $format
	 */
	public function format( string $format ) {
		$html = [];
		$list = [
			'table' => $this->msg( "smw-facetedsearch-format-table" )
		];

		if ( $format == '' ) {
			$format = $this->profile->get( 'result.default_format' );
		}

		foreach ( $list as $key => $val ) {

			$opt = '';

			if ( $format === $key ) {
				$opt = ' selected';
			}

			$html[] = "<option value='$key'$opt>$val</option>";
		}

		return implode( '', $html );
	}

	/**
	 * @since 3.2
	 *
	 * @param int $size
	 */
	public function size( int $size ) {
		$html = [];
		$list = $this->profile->get( 'result.paging_limit' );

		if ( $size == 0 ) {
			$size = $this->profile->get( 'result.default_limit' );
		}

		foreach ( $list as $val ) {

			$opt = '';
			$key = $val;

			if ( $size === $key ) {
				$opt = ' selected';
			}

			$html[] = "<option value='$key'$opt>$val</option>";
		}

		return implode( '', $html );
	}

	/**
	 * @since 3.2
	 *
	 * @param string $order
	 */
	public function order( string $order ) {
		$html = [];

		$list = [
			'asc' => "Title (A-Z)",
			'desc' => "Title (Z-A)",
			'recent' => "Most recent",
		// 'score' => "Relevance"
		];

		foreach ( $list as $key => $val ) {

			$opt = '';

			if ( $order === $key ) {
				$opt = ' selected';
			}

			$html[] = "<option value='$key'$opt>$val</option>";
		}

		return implode( '', $html );
	}

	/**
	 * @since 3.2
	 *
	 * @param int $size
	 * @param int $offset
	 *
	 * @return string
	 */
	public function previous( int $size, int $offset ): string {
		if ( $offset < 1 ) {
			return $this->msg( 'smw_result_prev' );
		}

		return Html::rawElement(
			'button',
			[
				'class' => 'button-link',
				'name' => 'offset',
				'value' => max( $offset - $size, 0 ),
				'onchange' => 'this.form.submit()',
				'form' => 'search-input-form'
			],
			$this->msg( 'smw_result_prev' )
		);
	}

	/**
	 * @since 3.2
	 *
	 * @param int $size
	 * @param int $offset
	 * @param bool $hasFurtherResults
	 *
	 * @return string
	 */
	public function next( int $size, int $offset, bool $hasFurtherResults ): string {
		if ( $hasFurtherResults === false ) {
			return $this->msg( 'smw_result_next' );
		}

		return Html::rawElement(
			'button',
			[
				'class' => 'button-link',
				'name' => 'offset',
				'value' => $offset + $size,
				'onchange' => 'this.form.submit()',
				'form' => 'search-input-form'
			],
			$this->msg( 'smw_result_next' )
		);
	}

}
