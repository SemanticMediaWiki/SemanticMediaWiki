<?php

namespace SMW\Utils;

use SMW\Localizer\MessageLocalizerTrait;
use Html;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class JsonView {

	use MessageLocalizerTrait;

	/**
	 * @since 3.2
	 *
	 * @param string $id
	 * @param string $data
	 * @param int $level
	 *
	 * @return string
	 */
	public function create( string $id, string $data, int $level = 1 ) : string {

		$placeholder = Html::rawElement(
			'div',
			[
				'class' => 'smw-schema-placeholder-message',
			],
			$this->msg( 'smw-data-lookup-with-wait' ) .
			"\n\n\n" .$this->msg( 'smw-preparing' ) . "\n"
		) .	Html::rawElement(
			'span',
			[
				'class' => 'smw-overlay-spinner medium',
				'style' => 'transform: translate(-50%, -50%);'
			]
		);

		return Html::rawElement(
				'div',
				[
					'class' => '',
				],
				Html::rawElement(
					'div',
					[
						'class' => 'smw-jsonview-menu',
					]
				) . Html::rawElement(
					'pre',
					[
						'id' => "smw-json-container-$id",
						'class' => 'smw-json-container smw-json-placeholder',
						'data-level' => $level
					],
					$placeholder . Html::rawElement(
					'div',
					[
						'class' => 'smw-json-data'
					],
					$data
				)
			)
		);
	}

}
