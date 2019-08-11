<?php

namespace SMW\Utils;

/**
 * @see https://www.semantic-mediawiki.org/wiki/SMW_logo
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class Logo {

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public static function get( $key ) {

		if ( $key === 'small' || $key === '100x90' ) {
			return self::small();
		}

		if ( $key === 'footer' ) {
			return self::footer();
		}
	}

	private static function small() {
		return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAGQAAABaCAMAAAB5TAO7AAACvlBMVE' .
		'VHcEz5+fpGUZL6/P37/v/z8PHLyNH49vf6+v36+/xjY4/1+fz6/P+knq/EvMovSnH5+/2Hf57z8PCmorb4+' .
		'v37/P5SV40+UnFeZJj7/P38/f7JwclWXZVscYs7R4n8/v74+/xlZI+Mh6iela1ETIn6/P/9/v+6nqqblrN7' .
		'e5cuPYNlapVjao9HUnF5fZ10cJf5+/309ff3+/33+f33+fuqpLkjMWJGTXZ5d55vc6H8/v5BH0BSW4k7PXr' .
		'Pz9pmZpQyS3G3scH5+/2Fh51/fp2amrPz9fmppr+6t8olMHY0PnErQmgZMlmQlKpKXn7u7fCyq76tqr4WTq' .
		'//sw4WTrD8rg/+sQ//sg////8UUbb2pRL4qBEaUrX6qxA+b+H+sg/njhkcU7cwZNEYULMtYc1FdekWT7EnX' .
		'cYqX8rokRjxnxUVULM4atkQS7HwnBU7bd0gV70eVbr0ohPqlBfslxYzZtbhhxsiWL8YT7EkWsNBcuXfgxvu' .
		'mhb//vsMP6Q0Zt0ORqwRTrTlixnkhxoTOG/dgBsPQ6gTP50uQYtMWpL47+ccLXo5UZnafx4hNYIZSacNM5A' .
		'JJVAaQ6DprGcbOIz59vQnRJcWSqzy4M/ut3bz5tkVMYYeUqwqT6LihyQXPnjcgyYNOZosNnPv18LKfi87So' .
		'/tjhaVlbEaP5j21bAlRHPhjTQ3QoHomz9xXl9ESX7wzKPypSHmrnTtqEVzaXMOLFoCG0bkm1Lrv5Hqp1hZQ' .
		'VI/Nl+fdVCgbURBV3ztnibr7fPGx9btxJnj4eYXJnH/+eg4aNI9Y7ujYS7DcCFUS2JMWYOQc2+5eTeDbV5+' .
		'hatjbKDT0dztv4gTKGbwoyb+6c1/U0PSgigHHkwfRoCIeYfHwsolWsAzXKwgOFyygFvAt7vawKuPi6FdWn9' .
		'zcpnXoXDmtIOwn6HY2ucDKF4AIRaGAAAA33RSTlMAKf4ZFDNHCwT+tTlSR2T9jHUgdGj06rr+Sukx/Uf4gJ' .
		'mgrmHtocYYwWDv7MtHwMSpRbJ434ivdeX82hXPxt3Z54TXNY6kvv77zpPKyZLd9J7x/////////////////' .
		'///////////////////////////////////////////////////////////////////////////////////' .
		'//////////////////////////////////////////////////////////////7////////////////////' .
		'////+ZUdwegAAC29JREFUaN7tmPdXU2kax4GhBSkCwgAi9u7YdyzT+8yZsruXoPdC0LRJApcriZBAwMQkQH' .
		'qBEHoHaQOKXbGLesYy9jIzyvS6/8U+770J4CIIOZs5+8M+v3gOeb2f87Tv87yvn98ktiEOx1kXqt5/wc9nN' .
		'mMJjl+8ndzZGbBlQZCvGHH4n3dcei2h1airTdEhvmCEvo9f7NRLhEKJUsYj1NYF06cEzt369twZk514gXWp' .
		'U8uX5+TIhWKZQKCpXjZdxoYk3GzG8XmTHJmJ31EL5bk7duTmCAs4bFIfEDw9hj9uHqqsvFePh098JumuS8n' .
		'P2QGWyxfzCjHSsGZ6kMWqk+fTMw7VmZMCJzyztrtNzM/dUQQQOYKINItipuUIq3YwPTU17UEva+IOmHdHUy' .
		'B0e6IECEZZI0Z+jPH3fx4kCO84kpGauu3BwaqJIZEBlEzMz8nNzZVLpBw2hpHVL7l/Wjfzw9t3XguLnrR5Y' .
		'uLqK7PTtmUc6cAnhoSuEHCkYr5czpcU8AppyBzml1l3E1xqfY2jOmGh36SVc3Dw/PkjQ1UbJy7+pYs0Ao5s' .
		'WCxWSnkCYGCkfVFYbHBgYOJFu0aJ/kqojcsn64EkVcfQyV5V3CSRDXnDQBYKeGAC5AfGrrHrnU0pK//5xEh' .
		'J+Mg/wXPK2h9kCce3Jk7m7qdNWpGIXVgoQggME/Vc1pMEZnBZGpT8vXv3QsmxSeeqSZOfODc8cXJx9Y/XQ4' .
		'wwBoE11vT/nNDvMtQQDpdYDpC9EpmITaXMdmfQW/VamGKxOEiagDU2tv9x5eFPfzz+pb+9Wkx7IuGJoKxj0' .
		'cnwuVvneQnxjzc4LQ4RgjRZr/9w73BlZV1d3Q+nTN9J5HvlwmEOhmmbUOpn4Q97r8ybhjNBcxaEhS2IDoay' .
		'W1NN1Nwv1wDixoGz+/b3tTaD9fXdPHM0+UuJeJhDNyhUceDGgw/Sj1yZOVVE8HxTdY/D4awOeCPyPSNF2bu' .
		'3pPQPAKK5ubi4WIGspNi278xR03eNjY3g44ql0JvmwbTUjN71U9T/9wKcGi1BgmkM1n490dCN4/U//dYHCI' .
		'VCp+NmZXG5XJ1OgTDWpsZGQdtm9N/MdbszHzxcPCVGzDKjhuBwOKgv2CTlspf/qmKZeyv7wAedLisvL5+2v' .
		'CyursR285apqdEw8HfQ8ZC1V+oGe1lTmdChictcWp5MWlAg5XGgxdlEefsF3HzwRGsZMLLy8vfsyka2a9ee' .
		'fMAo9t0yGQcu4izo58Alqqqt4VPy45JRIysQC9G8ZYRE2/CBqhcxSriAyM7enU7b7uxde/KydIqbj0534yx' .
		'8HlKmoKCpDee5t9ukBRIkuzm0JEKP6/s7DiM/uHl7gJCRkYksIwNhwJnimwd+MbNY+Kxp7A2vGwmpRJ5bVF' .
		'SERqEMuUKUf7Xf5mYAIs1tmYBBlNYz139UVeEbp75hhH9QLlPyc4s+BytCUx2aUKQ5BWWlA0Y6jdjGmIei2' .
		'3fgaFUVC58xdcgWtVQs38FA3LOQrW23leiy8sGPTBqxk7Zt4AxQ8riKs9efgCvrpwxJTNDIAPL5WAimtV/j' .
		'cvN3IQZCbE/dvj01laZkpGfTrjwGSNyUt9ilK8hx4cII+7WuLAgWYiBEKhj8sxNFDLkCWbmrYsX9Z7wCExc' .
		'v3hD+DC0LXUTxCiQQL5R4OZN45ElXHuPIzu2MH0DZzgQMXFHcvP6ziqV6ur5Cwt9GwwpfP34ihqxUc3jDQv' .
		'lICQOETbXbICPpGeAIDaAhDAVc2QPxOjpwSfX0jhgyD1eZa2trzXjcunGerHYSAh7TjGJoRnoYak51IUjmB' .
		'JA87te32kHeloz90Czc3DFUV1k3VKuKG6c0kVZKJGBkRcahgwV9ctWTkp3M91NHKXRSbLesNy7efX3Mx0I2' .
		'VnXU7X9w6MGRe7Xj6y5wpYFkFwo4SCFFzFSnTpdxJ4c0nz3Xbm+wm9ZEjjpSf3L/bmiq9CMHzawZ47c5DUx' .
		'0ZMxUZxMNV7tKRiBjKNtTRyEDTTIZoTEk/80d9bV4R+X5TDiUdqjuWbt2tJEiMY+JSG35o5LiEndOPBBPfT' .
		'E5gcR/iVLIIdQBb7lvNaqOwew0OLTtUGUt/ox1aIFRTTKRYpMOZ8PjE2VlCi5TXQBxf9+T+HSoLsUPd/hop' .
		'ZAVkuqEmDGe7ESbNniy4RkdGZvcoxdpYTBSLsvl7oeDtjJQrtE+GYGAftEt33rmS7Qc8QsEmLaaDljoYpST' .
		'7My0zN2Qk2dfTYIWxBubmpxNRgtZ3n0F6bxutOM9DT9Swbqvv2IgSgFGOsLcN4aqjsP7z8MSDNU1kUAHRkR' .
		'v2hT9VrJe018/1IeE3qNdO5mQgXTROoy0CyBozxNKAVLzsru8WOaOk4fpPkl6jj6/W000fPDwhA25kk8rvU' .
		'eDaQaICrSiYt/R7yR8vlAJN4sRiN9avKq+tqPWrIp73jiLWUSpTebD19wzK92t9fQ4cc8Trq7s7I1GGTQvE' .
		'lNPuKAdZ8XR2pX0/Dmzuodw/Xzgqq0YTXh6+nomY6ZnZpV8fcAKaxGz9GurY0czOxNsQ+AU1rwUynE/6tY1' .
		'W0lXV9a4GQ8Li07RXHnA6XDQKocJ1PFevCaEbDYQlvb206dPPbpavGvMtrLbs600n+j9sd1i6SGROOg7F06' .
		'fEbjkz8sNdmeNltKoy09f7cof3bv20HtXcfH+k/WqX9oclzVwxmCaPiN0btIFU4NDCoLM45AEVX7K1uXZIP' .
		'PQBqkAxr1a1RM7Yei3NlkDVkdO348NqgsBBrFEArpUIOMUigj1aVsX7MK06UoUxWWtJ07WV6mS26jOf8RGR' .
		'/p7cQeaEcdKUEtgSqKHFaUMKpRQnyouYbZ6cKKsta+y94oK/7CBMKzy9vVoPd5dDld4et7nCJVoZyXaHpWU' .
		'ua21b/D3n358cqnKpKY63/H2EertC3YZPEagzaVoB/NUACvYvuZWW2tra9+Jut++umF0uYwDl2ucYd7eFf2' .
		'TbpeLhcx2xKxHzMpaB/e5w78P9T7u78E4HILS91gGgr2FhLzeXqN8CoJWVnWCub62tt5s/tXFKRgehoIQED' .
		'XGZV5DPrvMQ283n48JF8bWdFbhLFjiuxukYiGoIszDQpIyRnsJif3CIpDRz1Ao8XwlveixKeMlJHt37YTYf' .
		'b+Av5P6RUu9YgSuvG9n3m7gU3K+mHYEY4ssA1u6P7z0a5tSQj9Q5fCHkcJXv+sVJCKqxYIBRQmPnELxsAxd' .
		'pQGCWdRt5Q12y9j3PIDoX/WmUUJXl357v0ZQyIFFTwqywig5JnJY4GasJTC7FHUQ89Qmwtj6ZG+ejGNutJR' .
		'+00Ow2QJY9AQC9/MNRjpfC4tfYe1x2dF9jH7+pONIBcz2Ju1R3x47bodvj9nzUN6tkX4hsyPX2B08dLMEvR' .
		'mm70kakzeQ+S0tx6M2txHYU0a0baYnXYSRKORJQTvFBfQNWaSPj/EC8llL6fEXg0xqciyDVCczr5wxJr0Ip' .
		'UvGY5Zy0ithmQ3ROvauX2yAmhyNFaHu9Oy682ESskWF7nKAKL7lBWT5Fy0V3wejZdJAMTuriKRG9mm/0NkB' .
		'Y30k2t4I9AKyqbTl+Dl00wwOMzr1FGyseqdx1RgdjLbqyREP2xK8UshVpaUVLzKDLmJ+fMoKe0r8/IinlG1' .
		'Zp1pLwuskeNiTEOlVv3/ccqzi0xGFWfrKK0vHxWPhympDjYhd4+xcs867WfJmy7Fjc57Xr8vDXvvoo1ef9n' .
		'AaFnyutOL7KYxUr19P3ep4/NwMP9/anC9Kv3nzBd9DKl72+wsgL/oa8tL/If9zkL8k8RFRf0EJI1n5JMTHk' .
		'KA3S30vK36ftHwTFeRryMctFVGzfQ15tfTY9+/4GrKq9H7Fcl9D1sCMf8/XkE2l/zoe5vNGuXg7qntmqI8p' .
		'6xa/f8HnELD/+vj9N6ItqwTMUVjqAAAAAElFTkSuQmCC';
	}

	private static function footer() {
		return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAFgAAAAfCAMAAABUFvrSAAACJVBMVEX' .
			'e3t62trbLy8vGxsbAwMDX19e6urqsrKxHcEzQ0NC2tratra2oqKixsbHd3eDFxcXe3t7U1NTa2trX19f' .
			'Q0NDV1dWNjY2dnZ2UlJSUlJTS0tKUlJSWlpbR0dGenp79/f38/Pz+/v7t7e3v7+/u7u7s7Oz19fX29vb' .
			'6+vr7+/vz8/Pz8/P4+fnw8PD39/gWTq/09PX6+vktX7b/sg8XT7F6mc/f5OwhVrNvkMthhsfT2+j7rQ/' .
			'M1eZqjcmFotPs7e9Cb722xuFResK/zOObstnu7/Grvd3M1+mNqNY6aLoaUbEaUbZWfsTk6fE2Zrnp7O8' .
			'bTq0kWLQuYcpcg8Xh5/Byk83a4eukud1/nM+6yeQkWcGwwuDxnRQyYrigttvI0+gpW7XGyttLdsBni8g' .
			'eVLo0Z9XlkCPb4u+QqtcfVLLo7PNHcr73phFGX6HsmBnpkxg6bNwnXcbX3er19vnr7/SVrdhuhLl+j70' .
			'vVqY6Xqv0oxJVcbQhV76DjKTz9ffQ2evE0OZAbbzu7/PS1uFufrDnrGgpSZjy8/SQnMFddrKuekXSiCs' .
			'gTKRaaZHejy9yang3VptNaqspWbnmliy3j2/m6OzT3e7x8fL4+v3p6uwaN2re0cifqLiLiZWYhYGYb0x' .
			'XYn+5g0xwiMKYpc2BcHZmW2srRXpGWIhecKfilz4+WID2+PyosMywqbGqss1aVnLZq33p1sGklZqrp7d' .
			'kfr7CpY9yg59Ud6QZAAAAH3RSTlPfBN/f39/f2wDf39vV3/7N2rrEz5D5c8ONuHaPuXXDImoxqwAABO1' .
			'JREFUSMe1kQdz01gQgBXaBS4w9OtHwMFSdPKTUNxkW+61xN2Je4tLHJNOGukkAUINJTzaUIbO9fr7TpL' .
			'tS44DZiC5b6Sn3dW+TysJaWz4cv9eZFPZu/+bhkakYR88WqP59u3vjxzdDCT7GpCD4qNH3sEGzOhB5MC' .
			'R/4PmA8j25uZ33t0A23lxff5hZTIzOrz2Ph8qK936l7i1tT7hk8DAoHNsbeLWKjPdQcZ+qvX9tHMNXca' .
			'1XBDXHtL6x2Bh0HWtuXUo8oTgC1gVpcdsdpew92PUYtjwzFq+E9mJYfXxx1x5l2/l5Vhy0DW6TuyQCZc' .
			'R2hnswXJM0h+203471u5WKMIE5mDpspIIO8sZTMZiIbczY6+JCaI+/sJZ38qjP28+nQjkx/mcqGJ26rW' .
			'3iKg/fYphCL1jNk5WbqXI0nBnu00RJ2j9bNRp6MmFS0SYIXLuUlQ5w22qizHr5MTyy2u/3XxVvHHlwtW' .
			'Va/xTCQIIlEaMzrgswzA5P9BrAciYASqNoiNdjL8b0GYAHN3AGAEgzPSQc9UtYCeyBQAMO2G9PhhzXfp' .
			'9qdifSCT6Lj788QQnrvWEuNPI2um0LW0D+m4A6E4AnIZOeq7ksQuJvi6eUXRW+8EWXsxNPhkYGJiSXr7' .
			'Rd1qtVp9MXHkgIurzAkdFxjrj7cmKbN4K5v8RGxT2sJMTpwBflNHhUJgBdoUylzy3TuwIDBRi0hf9p9V' .
			'tbW3qk30X7q2Je1IybZq7mLVWFKTSADXPoqi9AzVorda0kKTS6KkRbSgaR1GT1h5CObYiW1GU27yc5z6' .
			'F9Fn/yTYedeLi9AIA6AbgxGIxAMPZcZeUHH9cFavVfY97FaNA/DbazRZVV89/61at2BTlg1JXiFtrYoB' .
			'mRyd7p6/UP0XxaSDmy4pt0jmuh8qx6wQ2MmRz18TaJCUWO9xisUVhMMsoZZDiGHLLuXUbso2iatP/1Hv' .
			'hYu3n3bh6Pe/LUjZSj1NUJ8lSlNwahRRlMRjmyFDICikVn8+SacomdbZTVj+0mQSxweS1WqpiHK/Ncub' .
			's858f9idOJ/qKS1cv+RbFuIbMmHEvTbO4QeF25CxyD62nSbmJ1MUzRrpiwR0RPBJMpnCGxWVGXBn8Tka' .
			'rNGQHjuPrxOeWY4GY9FmxWHy19Gvv+ayYF6f8oYi7i8U93bjFkYokvbhWEFu8Og2ZxrUe3NMpc3vLhqq' .
			'YpVV4XbwDQkpgoVwYKOR9N5d+eXB5vJevQA0pD7oVqi4WOj16vX6EYSA08WKvyeOZJw1cQ6rsnXOa/RY' .
			'oM0Kls0zLuVoHhHAHL8YFXkwVCoE8uXhvMVCY8mW5Ci8eKkcgJ9azOl0UajNDFiUpj5JeloEqTgwd/iC' .
			'UJP0sFMSejkpO96b4zl1fPuYiyd6Vs4GBmHSsJoYmLy/WeBTlis5rlJYrgjhe1s+XOXGEtHJOMl4VB2E' .
			'HLauKdyG7JBLIc//uslRKkhOrcGJqyiU9z5UkOpVOwiGXSyQizRAfqlRc7Y5KJFnV6FReiWSV7xAWeQd' .
			'/SOSq6qY18eVHq4vjlyazEJ73ScmJPVxJVKdF9MFwYpFIEL+ehvDOcSH8YXRsAa4XfwS7kd0tLfw7Sp7' .
			'fl7xJywbYjTTVotfTb+849lEcb0K+2lMN/5o+tons+Rxp+PrQ8U3n0BcNSGPj4W+bPuX5ZLNo+uxwY+P' .
			'foLJHX1KXgyMAAAAASUVORK5CYII=';
	}

}
