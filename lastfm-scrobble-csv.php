<?php

/**
 * Submit track listens to the Last FM API endpoint 'track.scrobble'.
 * http://www.last.fm/api/show/track.scrobble
 *
 * Reads scrobbles from a CSV file so you can easily copy and paste from
 * your iTunes playlist into Microsoft Excel.
 *
 * Yes, I'm well aware this is an ugly procedure speghetti code legacy PHP
 * glob but it's self-contained, eh!
 *
 * @author Derek MacDonald <derekmd@hotmail.com>
 */

// Get these from http://www.last.fm/api/accounts
$apiKey = '';
$secret = '';

// See http://www.last.fm/api/show/auth.getSession
// Visit http://www.last.fm/api/auth/?api_key=xxx in your browser to get a
// token (it'll me in ?token= of the callback URL) then immediately perform
// a curl to attain the session ID.
// This key theoretically doesn't expire so it's only painful once.
$sessionId = '';

// Fill this in to generate a $sessionId
$token = ''; 

// CSV headers on line 1:
//     track,duration,artist,albumArtist,album,timestamp,chosenByUser
// e.g.,
//     You Caught My Eye,5:29,Big Wreck,Big Wreck,Albatross,16/12/2015 8:10 PM,1
$csv = 'scrobbles.csv';

// http://php.net/manual/en/function.date.php format for timestamp column
$dateFormat = 'd/m/Y g:i A e';

// date('e') local timestamp
$timezone = 'EST';

// take 50 scrobbles to process a time, Last.FM API limit
$tracksPerBatch = 50;

function assoc_getcsv($csv_path) {
    $f = [];

    function parse_csv_assoc($str, &$f) { 
        if (empty($f)) {
            $f = str_getcsv($str);
        }

        return array_combine($f, str_getcsv($str));         
    }

    return array_values(array_slice(array_map('parse_csv_assoc', file($csv_path), $f), 1));
}

function generateApiSignature($parameters) {
    global $secret;

    $sig = '';

    foreach ($parameters as $key => $parameter) {
        $sig .= utf8_encode($key) . utf8_encode($parameter);
    }

    $sig .= $secret;

    return md5($sig);
}

function getQuery($parameters) {
    $query = '';

    foreach ($parameters as $key => $value) {
        $query .= trim(urlencode($key)) . '=' . trim(urlencode($value)) . '&';
    }

    $query = substr($query, 0, -1);

    return $query;
}

function getScrobbleParameters($tracks) {
    global $apiKey, $sessionId, $dateFormat, $timezone;

    $parameters = [
        'method' => 'track.scrobble',
        'api_key' => $apiKey,
        'sk' => $sessionId,        
    ];

    foreach ($tracks as $i => $track) {
        $parameters['track[' . $i . ']'] = $track['track'];
        $parameters['artist[' . $i . ']'] = $track['artist'];
        $parameters['albumArtist[' . $i . ']'] = $track['albumArtist'];
        $parameters['album[' . $i . ']'] = $track['album'];
        $parameters['chosenByUser[' . $i . ']'] = $track['chosenByUser'];

        if (preg_match('/^([0-9]):([0-9]?[0-9]):([0-9]?[0-9])$/', $track['duration'], $matches)) {
            $parameters['duration[' . $i . ']'] = (int)$matches[1] * 3600 +
                (int)$matches[2] * 60 + (int)$matches[3];
        } elseif (preg_match('/^([0-9]?[0-9]):([0-9]?[0-9])$/', $track['duration'], $matches)) {
            $parameters['duration[' . $i . ']'] = (int)$matches[1] * 60 + (int)$matches[2];
        } elseif (preg_match('/^[0-9]+$/', $track['duration'])) {
            $parameters['duration[' . $i . ']'] = (int)$track['duration'];
        }

        $date = DateTime::createFromFormat($dateFormat, $track['timestamp'] . ' ' . $timezone);
        $parameters['timestamp[' . $i . ']'] = $date->getTimestamp();
    }

    ksort($parameters);

    $parameters['api_sig'] = generateApiSignature($parameters);

    return $parameters;
}

function getAuthGetSessionParameters() {
    global $apiKey, $token;

    $parameters = [
        'method' => 'auth.getSession',
        'api_key' => $apiKey,
        'token' => $token,        
    ];

    ksort($parameters);

    $parameters['api_sig'] = generateApiSignature($parameters);

    return $parameters;
}

function doApiInTheFace($parameters, $method = 'POST') {
	$query = getQuery($parameters);

    $curl = curl_init();

    if ($method === 'GET') {
	    curl_setopt($curl, CURLOPT_URL, 'http://ws.audioscrobbler.com/2.0/?' . $query);
	    curl_setopt($curl, CURLOPT_POST, false);
	} else {
		curl_setopt($curl, CURLOPT_URL, 'http://ws.audioscrobbler.com/2.0/');
	    curl_setopt($curl, CURLOPT_POST, true);
	    curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
	}

    curl_exec($curl);
    curl_close($curl);
}

if (!empty($sessionId)) {
	$tracks = assoc_getcsv($csv);

	for ($i = 0; $i < count($tracks); $i += $tracksPerBatch) {
	    $tracksBatch = array_slice($tracks, $i, $tracksPerBatch);

	    $parameters = getScrobbleParameters($tracksBatch);

	    doApiInTheFace($parameters);
	}
} elseif (!empty($token)) {
	$parameters = getAuthGetSessionParameters();

	doApiInTheFace($parameters, 'GET');
}
