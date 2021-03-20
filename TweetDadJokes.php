<?php
  require_once(__DIR__ . '/config/bot.php');

  if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
  }

  // Grab the latest @-mentions
  if (file_exists($lastTweetFile)) {
    $lastTweetData = include($lastTweetFile);
    $lastTweetResponse = $lastTweetData[0];
  } else {
    $lastTweetResponse = null;
  }

  $result = TweetMentions($lastTweetResponse, $debug_bot);

  if (false === $result) {
    die("Failed to obtain tweet data.");
  }

  // If we have new mentions available, parse them
  if (count($result) > 0) {
    $tweetsAvailable = array();

    foreach ($result as $mention) {
      $tweetTime = $mention['created_at'];
      $tweetId = $mention['id'];
      $tweetsAvailable[$tweetId] = $tweetTime;
      $tweetText = $mention['text'];
      $replyTo = $mention['user']['screen_name'];
      
      if($debug_bot) {
        printf("%s: %s from %s\n\n", $tweetId, $tweetText, $replyTo);
      }

      // Is the mention asking for a [Dad] Joke?
      if ((false !== stripos($tweetText, 'dad')) || (false !== stripos($tweetText, 'joke'))) {
        $randomJoke = GetDadJoke();
        if (false === $randomJoke) {
          die("Failed to obtain dad joke payload.");
        }
        $tweetResponse = ".@$replyTo, " . $randomJoke['field_joke_opener'] . "\n\n" . $randomJoke['field_joke_response'] . " #DadJokes";
        // Send Tweet
        $reply = TweetReply($tweetResponse, $tweetId, $debug_bot);
        if (!$reply) {
          die ("Failed to post reply tweet.");
        }
      }
    }

    // Record the "last" tweet ID for the next tweet mention search
    if (count($tweetsAvailable) > 1) {
      $lastTweetId = array_keys($tweetsAvailable, max($tweetsAvailable));
    } else {
      reset($tweetsAvailable);
      $lastTweetId = array(key($tweetsAvailable));
    }

    if (!is_null($lastTweetId)) {
      file_put_contents($lastTweetFile, '<?php return ' . var_export($lastTweetId, true) . '; ?>');
    }
  }


  /**
   * getNonce($length = 11) - generate a nonce value for OAuth signatures
   * 
   * 100% stolen from the source at https://github.com/BaglerIT/OAuthSimple/blob/master/src/OAuthSimple.php and full credit goes to them! 
   *
   * @return string of a reasonably-unique nonce value
   */
  function getNonce($length = 11) {
    $nonce_chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
    $result = '';
    $cLength = strlen($nonce_chars);
    for ($i = 0; $i < $length; $i++) {
      $rnum = rand(0, $cLength - 1);
      $result .= substr($nonce_chars, $rnum, 1);
    }
    return $result;
  }


  /**
   * GetDadJoke() - return a random dad joke
   * 
   * Utility function to generate or update the dad joke data cache and randomly return the data for one dad joke.
   * 
   * @return array of a randomly-selected dad joke
   */
  function GetDadJoke() {
    global $dadJokeDataFile, $dadJokeDataExpires;

    // [Re-]Generate Dad Joke data as necessary
    if (file_exists($dadJokeDataFile)) {
      // Refresh the Dad Joke data if it's out of date
      if (filemtime($dadJokeDataFile) < (time() - $dadJokeDataExpires)) {
        GenerateDadJokeDataFile();
      }
    } else {
      GenerateDadJokeDataFile();
    }

    // Load the Dad Joke data file
    $dadJokes = include($dadJokeDataFile);

    // Return a random Dad Joke
    if (count($dadJokes) > 0) {
      return $dadJokes[array_rand($dadJokes)]['attributes'];
    } else {
      return false;
    }
  }


  /**
   * GenerateDadJokeDataFile() - poll fatherhood.gov API for dad joke data and write to file
   * 
   * Utility function to handle the cURL request and write its output to file.
   */
  function GenerateDadJokeDataFile() {
    global $dadjokesEndpoint, $dadJokeDataFile;

    $curl_request = curl_init();
    curl_setopt_array($curl_request, array(
      CURLOPT_URL => $dadjokesEndpoint,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_SSL_VERIFYPEER => true
    ));

    $json = curl_exec($curl_request);
    curl_close($curl_request);

    $dadJokePayload = json_decode($json, true);
    file_put_contents($dadJokeDataFile, '<?php return ' . var_export($dadJokePayload['data'], true) . '; ?>');
  }


  /**
   * TweetMentions($since, $debug = false) - search for @-mentions of the bot account
   * 
   * $since: integer value (Twitter's unique id) of the last tweet (only obtain tweets AFTER this instance)
   *    $since is used to help limit the number of possible results to _just_ tweets posted since the last run.
   *    When set to `0`, `null`, or `""`, all @-mentions up to Twitter's limit will be returned. 
   *
   * @return array of mentions/results discovered
   */
  function TweetMentions($since, $debug = false) {
    global $token, $token_secret, $token_account, $consumer_key, $consumer_secret, $twitterMentionsEndpoint;

    // Create OAuth Signature
    $timestamp = time();
    $nonce = getNonce();
    $baseArgs = "oauth_consumer_key=" . rawurlencode($consumer_key) . 
      "&oauth_nonce=" . rawurlencode($nonce) . 
      "&oauth_signature_method=" . rawurlencode("HMAC-SHA1") . 
      "&oauth_timestamp=" . rawurlencode($timestamp) . 
      "&oauth_token=" . rawurlencode($token) . 
      "&oauth_version=" . rawurlencode("1.0");
    if (!empty($since)) { $baseArgs .= "&since_id=" . rawurlencode($since); }

    $base = "GET&" . rawurlencode($twitterMentionsEndpoint) . "&" . 
      rawurlencode($baseArgs);

    $key = rawurlencode($consumer_secret) . '&' . rawurlencode($token_secret);

    $signature = rawurlencode(base64_encode(hash_hmac('SHA1', $base, $key, true)));

    // Create OAuth Header for cURL
    $oauth_header = 'Authorization: OAuth oauth_consumer_key="' . $consumer_key .'",';
    $oauth_header .= 'oauth_nonce="' . $nonce . '",';
    $oauth_header .= 'oauth_signature_method="HMAC-SHA1",';
    $oauth_header .= 'oauth_timestamp="' . $timestamp . '",';
    $oauth_header .= 'oauth_token="' . $token . '",';
    $oauth_header .= 'oauth_version="1.0",';
    $oauth_header .= 'oauth_signature="' . $signature . '",';
    if (!empty($since)) { $oauth_header .= 'since_id="' . $since . '",'; }
    $curl_header = array($oauth_header);

    // Modify the endpoint URL with appropriate argument if necessary
    $mentionsEndpointWithArgs = (!empty($since)) ? $twitterMentionsEndpoint . '?since_id=' . $since : $twitterMentionsEndpoint;

    // Create/Submit cURL request
    $curl_request = curl_init();
    curl_setopt_array($curl_request, array(
      CURLOPT_URL => $mentionsEndpointWithArgs,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_SSL_VERIFYPEER => true,
      CURLOPT_HTTPHEADER => $curl_header
    ));

    $json = curl_exec($curl_request);
    curl_close($curl_request);

    if (array_key_exists("errors", json_decode($json))) {
      return ($debug) ? "Bad Request: $json" : false;
    } else {
      return json_decode($json, true);
    }
  }


  /**
   * TweetReply($tweetText, $tweetId, $debug = false) - reply to a specific $tweetId
   * 
   * $tweetText: string of the response tweet text
   * $tweetId: Twitter's unique id of the parent tweet 
   *
   * @return boolean of success (or JSON string if $debug = true)
   */
  function TweetReply($tweetText, $tweetId, $debug = false) {
    global $token, $token_secret, $token_account, $consumer_key, $consumer_secret, $twitterUpdatesEndpoint;

    // Escape tweet text submitted
    $escapedTweet = rawurlencode($tweetText);

    // Create OAuth Signature
    $oauth_hash = 'in_reply_to_status_id=' . $tweetId .'&';
    $oauth_hash .= 'oauth_consumer_key=' . $consumer_key .'&';
    $oauth_hash .= 'oauth_nonce=' . time() . '&';
    $oauth_hash .= 'oauth_signature_method=HMAC-SHA1&';
    $oauth_hash .= 'oauth_timestamp=' . time() . '&';
    $oauth_hash .= 'oauth_token=' . $token . '&';
    $oauth_hash .= 'oauth_version=1.0&';
    $oauth_hash .= 'status=' . $escapedTweet;

    $base = 'POST&' . rawurlencode($twitterUpdatesEndpoint) . '&' .
      rawurlencode($oauth_hash);

    $key = rawurlencode($consumer_secret) . '&' . rawurlencode($token_secret);

    $signature = rawurlencode(base64_encode(hash_hmac('sha1', $base, $key, true)));

    // Create OAuth Header for cURL
    $oauth_header = '';
    $oauth_header .= 'in_reply_to_status_id="' . $tweetId .'", ';
    $oauth_header .= 'oauth_consumer_key="' . $consumer_key .'", ';
    $oauth_header .= 'oauth_nonce="' . time() . '", ';
    $oauth_header .= 'oauth_signature="' . $signature . '", ';
    $oauth_header .= 'oauth_signature_method="HMAC-SHA1", ';
    $oauth_header .= 'oauth_timestamp="' . time() . '", ';
    $oauth_header .= 'oauth_token="' . $token . '", ';
    $oauth_header .= 'oauth_version="1.0", ';
    $oauth_header .= 'status="' . $escapedTweet .'"';

    $curl_header = array("Authorization: OAuth {$oauth_header}", 'Expect:');

    // Create/Submit cURL request
    $curl_request = curl_init();
    curl_setopt($curl_request, CURLOPT_HTTPHEADER, $curl_header);
    curl_setopt($curl_request, CURLOPT_HEADER, false);
    curl_setopt($curl_request, CURLOPT_URL, $twitterUpdatesEndpoint);
    curl_setopt($curl_request, CURLOPT_POST, true);
    curl_setopt($curl_request, CURLOPT_POSTFIELDS, "in_reply_to_status_id=$tweetId&status=$escapedTweet");
    curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, false);

    $json = curl_exec($curl_request);
    curl_close($curl_request);

    if (array_key_exists("errors", json_decode($json))) {
      return ($debug) ? "Bad Request: $json" : false;
    } else {
      return ($debug) ? "Good Request: $json" : true;
    }
  }
?>