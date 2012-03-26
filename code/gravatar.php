<?php

/**
 * Get either a Gravatar URL or complete image tag for a specified email address.
 *
 * @param string $email The email address
 * @param string $s Size in pixels, defaults to 80px [ 1 - 512 ]
 * @param string $d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
 * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
 * @param boole $img True to return a complete IMG tag False for just the URL
 * @param array $atts Optional, additional key/value attributes to include in the IMG tag
 * @return String containing either just a URL or a complete image tag
 * @source http://gravatar.com/site/implement/images/php/
 */
function make_gravatar_url( $email, $s = 80, $d = 'mm', $r = 'g', $img = false, $atts = array() ) {
	$url = 'http://www.gravatar.com/avatar/';
	$url .= md5( strtolower( trim( $email ) ) );
	$url .= "?s=$s&d=$d&r=$r";
	if ( $img ) {
		$url = '<img src="' . $url . '"';
		foreach ( $atts as $key => $val )
			$url .= ' ' . $key . '="' . $val . '"';
		$url .= ' />';
	}
	return $url;
}

function check_gravatar( $email, $d = '404', $r = 'g' ) {
  $context = stream_context_create(array(
      'http' => array(
          'timeout' => 0.250
        , 'ignore_errors' => TRUE
      )
  ));
  $gravatar = @file_get_contents(make_gravatar_url($email, 1, $d, $r), 0, $context);
  return isset($http_response_header[0]) && preg_match('/200 OK/', $http_response_header[0]);
}

function checked_gravatar_url( $email, $s = 80, $d = 'mm', $r = 'g') {
  if (check_gravatar($email, $d, $r))
    return make_gravatar_url($email, $s, $d, $r);
  return false;
}

?>
