<?php

namespace SMW\MediaWiki\Specials\Admin;

use Html;
use SMW\Message;
use WebRequest;

/**
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 */
class DeprecationNoticeTaskHandler extends TaskHandler {

	/**
	 * @var OutputFormatter
	 */
	private $outputFormatter;

	/**
	 * @var array
	 */
	private $deprecationNoticeList = [];

	/**
	 * @since 3.0
	 *
	 * @param OutputFormatter $outputFormatter
	 * @param array $deprecationNoticeList
	 */
	public function __construct( OutputFormatter $outputFormatter, array $deprecationNoticeList = [] ) {
		$this->outputFormatter = $outputFormatter;
		$this->deprecationNoticeList = $deprecationNoticeList;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getSection() {
		return self::SECTION_DEPRECATION;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function hasAction() {
		return false;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$html = '';

		// Push `smw` to the top
		uksort( $this->deprecationNoticeList, function( $a, $b ) {
			return $b === 'smw';
		} );

		foreach ( $this->deprecationNoticeList as $section => $deprecationNoticeList ) {
			$html .= $this->buildSection( $section, $deprecationNoticeList );
		}

		if ( $html === '' ) {
			return '';
		}

		return Html::rawElement(
			'div',
			[
				'class' => 'smw-admin-deprecation'
			],
			Html::rawElement(
				'p',
				[
					'class' => 'plainlinks'
				],
				$this->msg( 'smw-admin-deprecation-notice-docu' )
			) . $html
		);
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function isTaskFor( $task ) {}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function handleRequest( WebRequest $webRequest ) {}

	private function buildSection( $section, $deprecationNoticeList ) {

		$noticeConfigList = [];
		$replacementConfigList = [];
		$removedConfigList = [];
		$html = '';

		if ( isset( $deprecationNoticeList['notice'] ) ) {
			$noticeConfigList = $deprecationNoticeList['notice'];
		}

		if ( isset( $deprecationNoticeList['replacement'] ) ) {
			$replacementConfigList = $deprecationNoticeList['replacement'];
		}

		if ( isset( $deprecationNoticeList['removal'] ) ) {
			$removedConfigList = $deprecationNoticeList['removal'];
		}

		$sectionList = $this->buildList(
			$section,
			$noticeConfigList,
			$replacementConfigList,
			$removedConfigList
		);

		if ( $sectionList === [] ) {
			return '';
		}

		$html .= Html::rawElement(
			'legend',
			[
				'class' => "$section-admin-deprecation-notice-section"
			],
			$this->msg( "$section-admin-deprecation-notice-section" )
		);

		return Html::rawElement(
			'fieldset',
			[
				'class' => "$section-admin-deprecation-section"
			],
			$html . implode( '', $sectionList )
		);
	}

	private function buildList( $section, $noticeConfigList, $replacementConfigList, $removedConfigList ) {

		$noticeList = [];
		$list = [];

		// Replacements
		foreach ( $replacementConfigList as $setting => $value ) {
			if ( $setting === 'options' ) {
				$list[] = $this->createItems( "$section-admin-deprecation-notice-config-replacement", $value );
			} elseif ( isset( $GLOBALS[$setting] ) ) {
				$list[] = $this->createItem( [ "$section-admin-deprecation-notice-config-replacement", '$' . $setting, '$' . $value ] );
			}
		}

		if ( $list !== [] && ( $mList = $this->mergeList( "$section-admin-deprecation-notice-title-replacement", $section, $list ) ) !== null ) {
			$noticeList[] = $mList;
		}

		// Changes
		foreach ( $noticeConfigList as $setting => $value ) {
			if ( $setting === 'options' ) {
				$list[] = $this->createItems( "$section-admin-deprecation-notice-config-notice", $value );
			} elseif ( isset( $GLOBALS[$setting] ) ) {
				$list[] = $this->createItem( [ "$section-admin-deprecation-notice-config-notice", '$' . $setting, $value ] );
			}
		}

		if ( $list !== [] && ( $mList = $this->mergeList( "$section-admin-deprecation-notice-title-notice", $section, $list ) ) !== null ) {
			$noticeList[] = $mList;
		}

		// Removals
		foreach ( $removedConfigList as $setting => $msg ) {
			if ( isset( $GLOBALS[$setting] ) ) {
				$list[] = $this->createItem( [ "$section-admin-deprecation-notice-config-removal", '$' . $setting, $msg ] );
			}
		}

		if ( $list !== [] && ( $mList = $this->mergeList( "$section-admin-deprecation-notice-title-removal", $section, $list ) ) !== null ) {
			$noticeList[] = $mList;
		}

		return $noticeList;
	}

	private function mergeList( $title, $section, &$list ) {

		if ( $list === [] || ( $items = implode( '', $list ) ) === '' ) {
			return;
		}

		$html = Html::rawElement(
			'h4',
			[],
			$this->msg( $title )
		) . Html::rawElement(
			'p',
			[
				'class' => "$section-admin-deprecation-notice-section-explanation",
				'style' => 'margin-bottom:10px;'
			],
			$this->msg( $title . '-explanation' )
		) . Html::rawElement(
			'ul',
			[
				'style' => 'margin-bottom:10px;'
			],
			$items
		);

		$list = [];

		return $html;
	}

	private function createItems( $message, $values ) {

		$list = [];

		if ( !is_array( $values ) ) {
			return '';
		}

		foreach ( $values as $setting => $options ) {

			if ( !is_array( $options ) ) {
				continue;
			}

			$opt = [];

			foreach ( $options as $option => $v ) {
				if ( $this->hasOption( $setting, $option ) ) {
					$opt[] = $this->createItem(
						[
							$message . '-option-list',
							$option,
							$v
						]
					);
				}
			}

			if ( $opt !== [] ) {
				$list[] = $this->createItem(
					[
						$message . '-option',
						'$' . $setting,
						count( $opt )
					]
				) . '<ul>' . implode( '', $opt ) . '</ul>';
			}
		}

		return implode( '', $list );
	}

	private function hasOption( $setting, $option ) {
		return isset( $GLOBALS[$setting][$option] ) || ( is_array( $GLOBALS[$setting] ) && array_search( $option, $GLOBALS[$setting] ) );
	}

	private function createItem( $message ) {
		return Html::rawElement( 'li', [], $this->msg( $message, Message::PARSE ) );
	}

}
