<?php

namespace SMW\Utils;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class AbortMessage {

	/**
	 * @since 3.1
	 *
	 * @param string $text
	 *
	 * @return $text
	 */
	public static function abortMsg( $title, $text, $indicator = '' ) {

		$indicator = self::indicator( $indicator );

		$html = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"  \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
		$html .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\" dir=\"ltr\">\n";
		$html .= self::head( $title, $indicator );
		$html .= "<body><h2 style='color:#222;margin-left:8px;font-family: sans-serif;margin-top:12px;margin-bottom:12px;'>{$title}</h2></div>";
		$html .= "<div style='font-family: sans-serif;margin-top:20px;'><p>{$text}</p><div></body></html>";

		return $html;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $text
	 *
	 * @return $text
	 */
	public static function progress( $msg, $value ) {
		return "<div class='progress-bar-section'><div style='height: 16px;'></div>" .
			"<div class='progress-bar progress-bar-striped progress-bar-animated' style='background-color:#FFC107;height:16px;padding:6px;width:$value'>" .
			"<span style='width:100%;'>$msg</span></div></div>";
	}

	private static function head( $title, $indicator = '' ) {

		return "<head>" .
			"<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />" . self::style() .
			"<title>{$title}</title>" .
			"<div style='background-color:#eee;padding-top:5px;padding-bottom:5px; margin-top: -8px;margin-left: -8px;margin-right: -8px'>" .
			"$indicator" .
			"</head>";
	}

	private static function indicator( $key ) {

		$indicator = '';

		if ( $key === 'error' ) {
			$indicator = '<span class="circle-error"></span>';
		} elseif ( $key === 'maintenance' ) {
			$indicator = '<span class="circle-yellow"></span>';
		}

		return "<div style='float:right;margin-top: 14px;'>$indicator</div>";
	}

	private static function style() {
		return "<style>" .
			".circle-yellow {height: 25px;width: 25px;background-color:#FFC107;border-radius: 50%;display: inline-block;margin-right:10px;}" .
			".circle-error {height: 25px;width: 25px;background-color:#F44336;border-radius: 50%;display: inline-block;margin-right:10px;}" .
			".progress-bar-animated {animation: progress-bar-stripes 2s linear infinite;}" .
			".progress-bar-striped {background-image: linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%," .
			"rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent);background-size: 1rem 1rem;}" .
			".progress-bar-section {margin-left: -8px; margin-right: -8px;margin-bottom:10px;margin-top:-10px;display: flex;white-space: nowrap;border-top: 1px solid #eee;border-bottom: 1px solid #eee;}" .
			".progress-bar {background-color: #eee;transition: width .6s ease;justify-content: center;display: flex;white-space: nowrap;}" .
			".section {background-color: #eee; padding: 6px; margin-left: -8px; margin-right: -8px;}" .
			"@keyframes progress-bar-stripes {from { background-position: 28px 0; } to { background-position: 0 0; }" .
			"</style>";
	}

}
