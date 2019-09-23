<?php
/*
Plugin Name: Do TLS
Plugin URI: https://github.com/joshp23/YOURLS-doTLS
Description: Always use TLS for destination url if available
Version: 0.0.1
Author: Josh Panter
Author URI: https://unfettered.net
*/

// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();

/*
	Ties into YOURLS core
*/

// do TLS when adding new links, etc
yourls_add_filter( 'esc_url', 'doTLS_esc' );
function doTLS_esc ( $url, $original_url, $context ) {
	if ( doTLS_is_valid( $url ) )
		$url = doTLS_check ( $url );
	return $url;
}

// do TLS when redirecting short links, stats, etc
yourls_add_filter( 'get_keyword_info', 'doTLS_info' );
function doTLS_info ( $value, $keyword, $field, $notfound ) {
	if ( $value && $field == 'url' )
		if ( doTLS_is_valid( $value ) ) {
			$value = doTLS_check ( $value );
			if ( yourls_get_protocol( $value ) == 'https://' )
				doTLS_update ( $value, $keyword );
	}
	return $value;
}

/*
	doTLS jobs
*/

function doTLS_is_valid ( $url ) {
	$value = ( ! yourls_get_protocol( $url ) ||  yourls_get_protocol( $url ) == 'http://' ) ? true : false;
	return $value;
}

function doTLS_check ( $url ) {

	if ( ! yourls_get_protocol( $url ) )
		$doTLS = 'https://'.$url;
	elseif ( yourls_get_protocol( $url ) == 'http://' )
		$doTLS = substr_replace($url, 's', 4, 0);

	if ($doTLS) {
		$ch = curl_init( $doTLS );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt( $ch, CURLOPT_NOBODY, TRUE );
		curl_exec($ch);

		if ( curl_errno($ch) == 0 )
				$url = $doTLS;

		curl_close($ch);
	}
	return $url;
}

function doTLS_update ( $url, $keyword ) {
	global $ydb;
	$table = defined( 'YOURLS_DB_PREFIX' ) ? YOURLS_DB_PREFIX . 'url' : 'url';
	if (version_compare(YOURLS_VERSION, '1.7.3') >= 0) {
		$binds = array('keyword' => $keyword, 'url' => $url);
		$sql = "UPDATE `$table` SET `url` = :url WHERE `keyword` = :keyword;";
		$update = $ydb->fetchAffected($sql, $binds);
	} else {
		$update = $ydb->query("UPDATE `$table` SET `url` = $url WHERE `keyword` = $keyword;");
	}
}
