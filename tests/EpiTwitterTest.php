<?php
require_once '../EpiCurl.php';
require_once '../EpiOAuth.php';
require_once '../EpiTwitter.php';
require_once 'PHPUnit/Framework.php';

class EpiTwitterTest extends PHPUnit_Framework_TestCase
{
  function setUp()
  {
    // key and secret for a test app (don't really care if this is public)
    $consumer_key = 'jdv3dsDhsYuJRlZFSuI2fg';
    $consumer_secret = 'NNXamBsBFG8PnEmacYs0uCtbtsz346OJSod7Dl94';
    $token = '25451974-uakRmTZxrSFQbkDjZnTAsxDO5o9kacz2LT6kqEHA';
    $secret= 'CuQPQ1WqIdSJDTIkDUlXjHpbcRao9lcKhQHflqGE8';
    $this->twitterObj = new EpiTwitter($consumer_key, $consumer_secret, $token, $secret);
    $this->twitterObjUnAuth = new EpiTwitter($consumer_key, $consumer_secret);
    $this->twitterObjBasic = new EpiTwitter();
    $this->twitterObjBadAuth = new EpiTwitter('foo', 'bar', 'foo', 'bar');
    $this->id = '25451974';
    $this->screenName = 'jmathai_test';
    $this->twitterUsername = 'jmathai_test';
    $this->twitterPassword = 'jmathai_test';
  }

//function testGetAuthenticateurl()
//{
//  $aUrl = $this->twitterObjUnAuth->getAuthenticateUrl();
//  $this->assertTrue(strstr($aUrl, 'http://twitter.com/oauth/authenticate') !== false, 'Authenticate url did not contain member definition from EpiTwitter class');

//  $aUrl = $this->twitterObjUnAuth->getAuthenticateUrl(null, array('force_login'=>'true'));
//  $this->assertTrue(strstr($aUrl, 'http://twitter.com/oauth/authenticate') !== false, 'Authenticate url did not contain member definition from EpiTwitter class');
//  $this->assertTrue(strstr($aUrl, 'force_login=true') !== false, 'Authenticate url did not contain member definition from EpiTwitter class');
//}

//function testGetAuthorizeUrl()
//{
//  $aUrl = $this->twitterObjUnAuth->getAuthorizeUrl($this->token);
//  $this->assertTrue(strstr($aUrl, 'http://twitter.com/oauth/authorize') !== false, 'Authorize url did not contain member definition from EpiTwitter class');
//}

//function testGetRequestToken()
//{
//  $resp = $this->twitterObjUnAuth->getRequestToken();
//  $this->assertTrue(strlen($resp->oauth_token) > 0, "oauth_token is longer than 0");
//  $this->assertTrue(strlen($resp->oauth_token_secret) > 0, "oauth_token_secret is longer than 0");
//  $this->assertTrue(strlen($resp->oauth_callback_confirmed) == 0, "oauth_callback is not = true");

//  $resp = $this->twitterObjUnAuth->getRequestToken(array('oauth_callback' => urlencode('http://www.yahoo.com')));
//  $this->assertTrue(strlen($resp->oauth_token) > 0, "oauth_token is longer than 0");
//  $this->assertTrue(strlen($resp->oauth_token_secret) > 0, "oauth_token_secret is longer than 0");
//  $this->assertTrue($resp->oauth_callback_confirmed == 'true', "oauth_callback is not = true");
//}

  function testBooleanResponse()
  {
    $resp = $this->twitterObj->get('/friendships/exists.json', array('user_a' => 'jmathai_test','user_b' => 'jmathai'));
    $this->assertTrue(gettype($resp->response) === 'boolean', 'response should be a boolean for friendship exists');
    $this->assertTrue($resp->response, 'response should be true for friendship exists');
    // __call
    $resp = $this->twitterObj->get_friendshipsExists(array('user_a' => 'jmathai_test','user_b' => 'jmathai'));
    $this->assertTrue(gettype($resp->response) === 'boolean', 'response should be a boolean for friendship exists');
    $this->assertTrue($resp->response, 'response should be true for friendship exists');
  }

  function testGetVerifyCredentials()
  {
    $resp = $this->twitterObj->get('/account/verify_credentials.json');
    $this->assertTrue(strlen($resp->responseText) > 0, 'responseText was empty');
    $this->assertTrue($resp instanceof EpiTwitterJson, 'response is not an array');
    $this->assertTrue(!empty($resp->screen_name), 'member property screen_name is empty');
    $this->assertFalse($resp->protected, 'protected is not false');
    // __call
    $resp = $this->twitterObj->get_accountVerify_credentials();
    $this->assertTrue(strlen($resp->responseText) > 0, 'responseText was empty');
    $this->assertTrue($resp instanceof EpiTwitterJson, 'response is not an array');
    $this->assertTrue(!empty($resp->screen_name), 'member property screen_name is empty');
    $this->assertFalse($resp->protected, 'protected is not false');
  }

  function testGetWithParameters()
  {
    $resp = $this->twitterObj->get('/statuses/friends_timeline.json', array('since_id' => 1));
    $this->assertTrue(!empty($resp->response[0]['user']['screen_name']), 'first status has no screen name');
    // __call
    $resp = $this->twitterObj->get_statusesFriends_timeline(array('since_id' => 1));
    $this->assertTrue(!empty($resp->response[0]['user']['screen_name']), 'first status has no screen name');
  }

  function testGetFollowers()
  {
    $resp = $this->twitterObj->get('/statuses/followers.json');
    $this->assertTrue(count($resp) > 0, 'Count of followers is not greater than 0');
    $this->assertTrue(!empty($resp[0]), 'array access for resp is empty');
    foreach($resp as $k => $v)
    {
      $this->assertTrue(!empty($v->screen_name), 'screen name for one of the resp nodes is empty');
    }
    $this->assertTrue($k > 0, 'test did not properly loop over followers');
    // __call
    $resp = $this->twitterObj->get_statusesFollowers();
    $this->assertTrue(count($resp) > 0, 'Count of followers is not greater than 0');
    $this->assertTrue(!empty($resp[0]), 'array access for resp is empty');
    foreach($resp as $k => $v)
    {
      $this->assertTrue(!empty($v->screen_name), 'screen name for one of the resp nodes is empty');
    }
    $this->assertTrue($k > 0, 'test did not properly loop over followers');
  }

  function testPostStatus()
  {
    $statusText = 'Testing really weird chars "~!@#$%^&*()-+\[]{}:\'>?<≈ç∂´ß©ƒ˙˙∫√√ƒƒ∂∂†¥∆∆∆ (time: ' . time() . ')';
    $resp = $this->twitterObj->post('/statuses/update.json', array('status' => $statusText));
    $this->assertEquals($resp->text, str_replace(array('<','>'),array('&lt;','&gt;'),$statusText), 'The status was not updated correctly');
    
    $statusText = 'Testing a random status (time: ' . time() . ')';
    $resp = $this->twitterObj->post('/statuses/update.json', array('status' => $statusText));
    $this->assertEquals($resp->text, $statusText, 'The status was not updated correctly');
    // reply to it
    $statusText = 'Testing a random status with reply to id (reply to: ' . $resp->id . ')';
    $resp = $this->twitterObj->post('/statuses/update.json', array('status' => $statusText, 'in_reply_to_status_id' => "{$resp->id}"));
    $this->assertEquals($resp->text, $statusText, 'The status with reply to id was not updated correctly');
    
    // __call
    $statusText = 'Testing really weird chars "~!@#$%^&*()-+\[]{}:\'>?<≈ç∂´ß©ƒ˙˙∫√√ƒƒ∂∂†¥∆∆∆ (time: ' . time() . ')';
    $resp = $this->twitterObj->post_statusesUpdate(array('status' => $statusText));
    $this->assertEquals($resp->text, str_replace(array('<','>'),array('&lt;','&gt;'),$statusText), 'The status was not updated correctly');
    
    $statusText = 'Testing a random status (time: ' . time() . ')';
    $resp = $this->twitterObj->post_statusesUpdate(array('status' => $statusText));
    $this->assertEquals($resp->text, $statusText, 'The status was not updated correctly');
    // reply to it
    $statusText = 'Testing a random status with reply to id (reply to: ' . $resp->id . ')';
    $resp = $this->twitterObj->post_statusesUpdate(array('status' => $statusText, 'in_reply_to_status_id' => "{$resp->id}"));
    $this->assertEquals($resp->text, $statusText, 'The status with reply to id was not updated correctly');
  }

  function testPostStatusUnicode()
  {
    $statusText = 'Testing a random status with unicode בוקר טוב (' . time() . ')';
    $resp = $this->twitterObj->post('/statuses/update.json', array('status' => $statusText));
    $this->assertEquals($resp->text, $statusText, 'The status was not updated correctly');
    // __call
    $statusText = 'Testing a random status with unicode בוקר טוב (' . time() . ')';
    $resp = $this->twitterObj->post_statusesUpdate(array('status' => $statusText));
    $this->assertEquals($resp->text, $statusText, 'The status was not updated correctly');
  }

  function testDirectMessage()
  {
    $resp = $this->twitterObj->post('/direct_messages/new.json',  array ( 'user' => $this->screenName, 'text' => "@username that's dirt cheap man, good looking out. I shall buy soon.You still play Halo at all?"));
    $this->assertTrue(!empty($resp->response['id']), "response id is empty");
    // __call
    $resp = $this->twitterObj->post_direct_messagesNew( array ( 'user' => $this->screenName, 'text' => "@username that's dirt cheap man, good looking out. I shall buy soon.You still play Halo at all?"));
    $this->assertTrue(!empty($resp->response['id']), "response id is empty");
  }

  function testPassingInTokenParams()
  {
    $this->twitterObj->setToken(null, null);
    $token = $this->twitterObj->getRequestToken();
    $authenticateUrl = $this->twitterObj->getAuthorizationUrl($token);
    $this->assertEquals($token->oauth_token, substr($authenticateUrl, (strpos($authenticateUrl, '=')+1)), "token does not equal the one which was passed in");
  }

  function testResponseAccess()
  {
    $resp = $this->twitterObj->get('/statuses/followers.json');
    $this->assertTrue(!empty($resp[0]), 'array access for resp is empty');
    $this->assertEquals($resp[0], $resp->response[0], 'array access for resp is empty');
    foreach($resp as $k => $v)
    {
      $this->assertTrue(!empty($v->screen_name), 'screen name for one of the resp nodes is empty');
    }
    $this->assertTrue($k > 0, 'test did not properly loop over followers');
    // __call
    $resp = $this->twitterObj->get_statusesFollowers();
    $this->assertTrue(!empty($resp[0]), 'array access for resp is empty');
    $this->assertEquals($resp[0], $resp->response[0], 'array access for resp is empty');
    foreach($resp as $k => $v)
    {
      $this->assertTrue(!empty($v->screen_name), 'screen name for one of the resp nodes is empty');
    }
    $this->assertTrue($k > 0, 'test did not properly loop over followers');
  }

  function testSearch()
  {
    $resp = $this->twitterObjBasic->get_basic('/search.json', array('q' => 'hello'));
    $this->assertTrue(is_array($resp->response['results']));
    $this->assertTrue(!empty($resp->results[0]->text), "search response is not an array {$resp->results[0]->text}");
    $resp = $this->twitterObjBasic->get_basic('/search.json', array('geocode' => '40.757929,-73.985506,25km', 'rpp' => 10));
    $this->assertTrue(is_array($resp->response['results']));
    $this->assertTrue(!empty($resp->results[0]->text), "search response is not an array {$resp->results[0]->text}");
    // __call
    $resp = $this->twitterObjBasic->get_search(array('q' => 'hello'));
    $this->assertTrue(is_array($resp->response['results']));
    $this->assertTrue(!empty($resp->results[0]->text), "search response is not an array {$resp->results[0]->text}");
    $resp = $this->twitterObjBasic->get_search(array('geocode' => '40.757929,-73.985506,25km', 'rpp' => 10));
    $this->assertTrue(is_array($resp->response['results']));
    $this->assertTrue(!empty($resp->results[0]->text), "search response is not an array {$resp->results[0]->text}");
  }

  function testTrends()
  {
    $resp = $this->twitterObjBasic->get('/trends.json');
    $this->assertTrue(is_array($resp->response['trends']), "trends is empty");
    $this->assertTrue(!empty($resp->trends[0]->name), "current trends is not an array " . $resp->trends[0]->name);

    $resp = $this->twitterObjBasic->get('/trends/current.json');
    $this->assertTrue(is_array($resp->response['trends']), "current trends is empty");
    // __call
    $resp = $this->twitterObjBasic->get_trends();
    $this->assertTrue(is_array($resp->response['trends']), "trends is empty");
    $this->assertTrue(!empty($resp->trends[0]->name), "current trends is not an array " . $resp->trends[0]->name);

    $resp = $this->twitterObjBasic->get_trendsCurrent();
    $this->assertTrue(is_array($resp->response['trends']), "current trends is empty");
  }

  function testBasicAuth()
  {
    $resp = $this->twitterObjBasic->get_basic('/account/verify_credentials.json', null, $this->twitterUsername, $this->twitterPassword);
    $this->assertEquals($resp->screen_name, $this->screenName, "Screenname from response is not {$this->screenName} when using get_basic");
    $status = 'Basic auth status update ' . time();
    $resp = $this->twitterObjBasic->post_basic('/statuses/update.json', array('status' => $status), $this->twitterUsername, $this->twitterPassword);
    $this->assertEquals(200, $resp->code, "Status update response code was not 200");
    $newStatus = $this->twitterObjBasic->get_basic('/statuses/show.json', array('id' => $resp->id));
    $this->assertEquals($status, $newStatus->text, "Updated status is not what it should be");
    // testing __call
    $resp = $this->twitterObjBasic->get_accountVerify_credentials(null, $this->twitterUsername, $this->twitterPassword);
    $this->assertEquals($resp->screen_name, $this->screenName, "Screenname from response is not {$this->screenName}");
    $status = 'Basic auth status update ' . time();
    $resp = $this->twitterObjBasic->post_statusesUpdate(array('status' => $status), $this->twitterUsername, $this->twitterPassword);
    $this->assertEquals(200, $resp->code, "Status update response code was not 200");
    $newStatus = $this->twitterObjBasic->get_statusesShow(array('id' => $resp->id));
    $this->assertEquals($status, $newStatus->text, "Updated status is not what it should be");
  }

  function testSSl()
  {
    $this->twitterObj->useSSL(true);
    $resp = $this->twitterObj->get('/account/verify_credentials.json');
    $this->assertTrue(strlen($resp->responseText) > 0, 'responseText was empty');
    $this->assertTrue($resp instanceof EpiTwitterJson, 'response is not an array');
    $this->assertTrue(!empty($resp->screen_name), 'member property screen_name is empty');
    $this->twitterObj->useSSL(false);
    // __call
    $this->twitterObj->useSSL(true);
    $resp = $this->twitterObj->get_accountVerify_credentials();
    $this->assertTrue(strlen($resp->responseText) > 0, 'responseText was empty');
    $this->assertTrue($resp instanceof EpiTwitterJson, 'response is not an array');
    $this->assertTrue(!empty($resp->screen_name), 'member property screen_name is empty');
    $this->twitterObj->useSSL(false);
  }

  function testCount()
  {
    $screenName = ucwords(strtolower($this->screenName));
    $resp = $this->twitterObj->get("/statuses/followers/{$screenName}.json");
    $this->assertTrue(count($resp) > 0, "Count for followers was not larger than 0");
    $resp = $this->twitterObj->get("/statuses/followers/{$screenName}.json", array('page' => 100));
    $this->assertTrue(count($resp) == 0, "Page 100 should return a count of 0");
    // __call
    $method = "get_statusesFollowers{$screenName}";
    $resp = $this->twitterObj->$method();
    $this->assertTrue(count($resp) > 0, "Count for followers was not larger than 0");
    $resp = $this->twitterObj->$method(array('page' => 100));
    $this->assertTrue(count($resp) == 0, "Page 100 should return a count of 0");
  }

  function testUpdateAvatar()
  {
    $file = dirname(__FILE__) . '/avatar_test_image.jpg';
    $resp = $this->twitterObj->post('/account/update_profile_image.json', array('@image' => "@{$file}"));
    // api seems to be a bit behind and doesn't respond with the new image url - use code instead for now
    $this->assertEquals($resp->code, 200, 'Response code was not 200');

    $file = dirname(__FILE__) . '/avatar_test_image.png';
    $resp = $this->twitterObj->post('/account/update_profile_image.json', array('@image' => "@{$file}"));
    // api seems to be a bit behind and doesn't respond with the new image url - use code instead for now
    $this->assertEquals($resp->code, 200, 'Response code was not 200');

    // __call
    $file = dirname(__FILE__) . '/avatar_test_image.jpg';
    $resp = $this->twitterObj->post_accountUpdate_profile_image(array('@image' => "@{$file}"));
    // api seems to be a bit behind and doesn't respond with the new image url - use code instead for now
    $this->assertEquals($resp->code, 200, 'Response code was not 200');

    $file = dirname(__FILE__) . '/avatar_test_image.png';
    $resp = $this->twitterObj->post_accountUpdate_profile_image(array('@image' => "@{$file}"));
    // api seems to be a bit behind and doesn't respond with the new image url - use code instead for now
    $this->assertEquals($resp->code, 200, 'Response code was not 200');
  }

  function testUpdateBackground()
  {
    $file = dirname(__FILE__) . '/avatar_test_image.jpg';
    $resp = $this->twitterObj->post('/account/update_profile_background_image.json', array('@image' => "@{$file}"));
    // api seems to be a bit behind and doesn't respond with the new image url - use code instead for now
    $this->assertEquals($resp->code, 200, 'Response code was not 200');

    $file = dirname(__FILE__) . '/avatar_test_image.jpg';
    $resp = $this->twitterObj->post('/account/update_profile_background_image.json', array('@image' => "@{$file}", 'tile' => 'true'));
    // api seems to be a bit behind and doesn't respond with the new image url - use code instead for now
    $this->assertEquals($resp->code, 200, 'Response code was not 200');

    $file = dirname(__FILE__) . '/avatar_test_image.png';
    $resp = $this->twitterObj->post('/account/update_profile_background_image.json', array('@image' => "@{$file}"));
    // api seems to be a bit behind and doesn't respond with the new image url - use code instead for now
    $this->assertEquals($resp->code, 200, 'Response code was not 200');

    $file = dirname(__FILE__) . '/avatar_test_image.png';
    $resp = $this->twitterObj->post('/account/update_profile_background_image.json', array('@image' => "@{$file}", 'tile' => 'true'));
    // api seems to be a bit behind and doesn't respond with the new image url - use code instead for now
    $this->assertEquals($resp->code, 200, 'Response code was not 200');

    // __call
    $file = dirname(__FILE__) . '/avatar_test_image.jpg';
    $resp = $this->twitterObj->post_accountUpdate_profile_background_image(array('@image' => "@{$file}"));
    // api seems to be a bit behind and doesn't respond with the new image url - use code instead for now
    $this->assertEquals($resp->code, 200, 'Response code was not 200');

    $file = dirname(__FILE__) . '/avatar_test_image.jpg';
    $resp = $this->twitterObj->post_accountUpdate_profile_background_image(array('@image' => "@{$file}", 'tile' => 'true'));
    // api seems to be a bit behind and doesn't respond with the new image url - use code instead for now
    $this->assertEquals($resp->code, 200, 'Response code was not 200');

    $file = dirname(__FILE__) . '/avatar_test_image.png';
    $resp = $this->twitterObj->post_accountUpdate_profile_background_image(array('@image' => "@{$file}"));
    // api seems to be a bit behind and doesn't respond with the new image url - use code instead for now
    $this->assertEquals($resp->code, 200, 'Response code was not 200');

    $file = dirname(__FILE__) . '/avatar_test_image.png';
    $resp = $this->twitterObj->post_accountUpdate_profile_background_image(array('@image' => "@{$file}", 'tile' => 'true'));
    // api seems to be a bit behind and doesn't respond with the new image url - use code instead for now
    $this->assertEquals($resp->code, 200, 'Response code was not 200');
  }

  function testCreateFriendship()
  {
    // check if friendship exists
    $exists = $this->twitterObj->get('/friendships/exists.json', array('user_a' => $this->screenName, 'user_b' => 'pbct_test'));
    if($exists->response)
    {
      $destroy = $this->twitterObj->post('/friendships/destroy.json', array('id' => 'pbct_test'));
      $destroy->responseText;
    }

    // perform checks now that env is set up
    $exists = $this->twitterObj->get('/friendships/exists.json', array('user_a' => $this->screenName, 'user_b' => 'pbct_test'));
    $this->assertFalse($exists->response, 'Friendship already exists and should not for create test');
    $create = $this->twitterObj->post('/friendships/create.json', array('id' => 'pbct_test'));
    $this->assertTrue($create->id > 0, 'ID is empty from create friendship call');

    // __call
    // check if friendship exists
    $exists = $this->twitterObj->get_friendshipsExists(array('user_a' => $this->screenName, 'user_b' => 'pbct_test'));
    if($exists->response)
    {
      $destroy = $this->twitterObj->post_friendshipsDestroy(array('id' => 'pbct_test'));
      $destroy->responseText;
    }

    // perform checks now that env is set up
    $exists = $this->twitterObj->get_friendshipsExists(array('user_a' => $this->screenName, 'user_b' => 'pbct_test'));
    $this->assertFalse($exists->response, 'Friendship already exists and should not for create test');
    $create = $this->twitterObj->post_friendshipsCreate(array('id' => 'pbct_test'));
    $this->assertTrue($create->id > 0, 'ID is empty from create friendship call');
  }

  function testDestroyFriendship()
  {
    // check if friendship exists
    $exists = $this->twitterObj->get('/friendships/exists.json', array('user_a' => $this->screenName, 'user_b' => 'pbct_test'));
    if(!$exists->response)
    {
      $create = $this->twitterObj->post('/friendships/create.json', array('id' => 'pbct_test'));
      $create->responseText;
    }
    
    // perform checks now that env is set up
    $exists = $this->twitterObj->get('/friendships/exists.json', array('user_a' => $this->screenName, 'user_b' => 'pbct_test'));
    $this->assertTrue($exists->response, 'Friendship does not exist to be destroyed');
    $destroy = $this->twitterObj->post('/friendships/destroy.json', array('id' => 'pbct_test'));
    $this->assertTrue($destroy->id > 0, 'ID is empty from destroy friendship call');

    //__call
    // check if friendship exists
    $exists = $this->twitterObj->get_friendshipsExists(array('user_a' => $this->screenName, 'user_b' => 'pbct_test'));
    if(!$exists->response)
    {
      $create = $this->twitterObj->post_friendshipsCreate(array('id' => 'pbct_test'));
      $create->responseText;
    }
    
    // perform checks now that env is set up
    $exists = $this->twitterObj->get_friendshipsExists(array('user_a' => $this->screenName, 'user_b' => 'pbct_test'));
    $this->assertTrue($exists->response, 'Friendship does not exist to be destroyed');
    $destroy = $this->twitterObj->post_friendshipsDestroy(array('id' => 'pbct_test'));
    $this->assertTrue($destroy->id > 0, 'ID is empty from destroy friendship call');
  }

  function testGetFriendsIds()
  {
    $twitterFriends = $this->twitterObj->get('/friends/ids.json', array('screen_name' => $this->screenName));
    $this->assertTrue(count($twitterFriends->response) > 0, 'Count of get friend ids is 0');;
    $this->assertTrue(!empty($twitterFriends[0]), 'First result in get friend ids is empty');;

    $twitterFriends = $this->twitterObj->get('/friends/ids.json', array('user_id' => $this->id));
    $this->assertTrue(count($twitterFriends->response) > 0, 'Count of get friend ids is 0');;
    $this->assertTrue(!empty($twitterFriends[0]), 'First result in get friend ids is empty');;

    // __call
    $twitterFriends = $this->twitterObj->get_friendsIds(array('screen_name' => $this->screenName));
    $this->assertTrue(count($twitterFriends->response) > 0, 'Count of get friend ids is 0');;
    $this->assertTrue(!empty($twitterFriends[0]), 'First result in get friend ids is empty');;

    $twitterFriends = $this->twitterObj->get_friendsIds(array('user_id' => $this->id));
    $this->assertTrue(count($twitterFriends->response) > 0, 'Count of get friend ids is 0');;
    $this->assertTrue(!empty($twitterFriends[0]), 'First result in get friend ids is empty');;
  }

  function testGetStatusesFriends()
  {
    $twitterFriends = $this->twitterObj->get('/statuses/friends.json', array('screen_name' => $this->screenName));
    $this->assertTrue(count($twitterFriends->response) > 0, 'Count of get statuses friends is 0');;
    $this->assertTrue(!empty($twitterFriends[0]), 'First result in get statuses friends is empty');;

    $twitterFriends = $this->twitterObj->get('/statuses/friends.json', array('user_id' => $this->id));
    $this->assertTrue(count($twitterFriends->response) > 0, 'Count of get statuses friends is 0');;
    $this->assertTrue(!empty($twitterFriends[0]), 'First result in get statuses friends is empty');;

    // __call
    $twitterFriends = $this->twitterObj->get_statusesFriends(array('screen_name' => $this->screenName));
    $this->assertTrue(count($twitterFriends->response) > 0, 'Count of get statuses friends is 0');;
    $this->assertTrue(!empty($twitterFriends[0]), 'First result in get statuses friends is empty');;

    $twitterFriends = $this->twitterObj->get_statusesFriends(array('user_id' => $this->id));
    $this->assertTrue(count($twitterFriends->response) > 0, 'Count of get statuses friends is 0');;
    $this->assertTrue(!empty($twitterFriends[0]), 'First result in get statuses friends is empty');;
  }

  function testGetLists()
  {
    $method = "get_{$this->id}Lists";
    $resp = $this->twitterObj->get("/{$this->id}/lists.json");
    $this->assertTrue(count($resp->lists) > 0, 'List count not greater than 0');
    $this->assertEquals($resp->lists[0]->id, 1900727, 'List name is not "Test"');
    $this->assertEquals($resp->lists[0]->member_count, 1, 'List member count not equal to 1');
  }

  function testDestructor()
  {
    $status = 'Testing destructor ' . time();
    $resp1 = $this->twitterObj->post_statusesUpdate(array('status' => $status));
    unset($resp1);
    $resp2 = $this->twitterObj->get_accountVerify_credentials();
    $this->assertEquals($status, $resp2->status->text, 'The destructor did not ensure that the status was updated');
  }

  function testHeaders()
  {
    $resp = $this->twitterObj->get_statusesFollowers();
    $this->assertTrue(!empty($resp->headers['Status']), 'header status response should not be empty');
  }

  function testExceptionWithDebug()
  {
    $this->twitterObj->setDebug(false);
    $resp = $this->twitterObj->post_direct_messagesNew( array ( 'user' => 'jaisen_does_not_exist_and_dont_create_or_this_will_break', 'text' => 'seriously'));

    try
    {
      $resp->response;
      $this->fail('Should throw a 404 for no user exists');
    }
    catch(EpiTwitterException $e)
    {
      $messageArr = json_decode($e->getMessage(), true);
      $this->assertTrue(empty($messageArr['headers']), "With debug off there should be no headers");
    }

    $this->twitterObj->setDebug(true);
    $resp2 = $this->twitterObj->post_direct_messagesNew( array ( 'user' => 'jaisen_does_not_exist_and_dont_create_or_this_will_break', 'text' => 'seriously'));

    try
    {
      $resp2->response;
      $this->fail('Should throw a 404 for no user exists');
    }
    catch(EpiTwitterException $e)
    {
      $messageArr = json_decode($e->getMessage(), true);
      $this->assertFalse(stristr($messageArr['headers']['Status'], '404 Not Found'), "With debug off there should be no headers");
    }
  }

  /**
  * @expectedException EpiTwitterForbiddenException
  */
  function testNoRequiredParameter()
  {
    $resp = $this->twitterObj->post_direct_messagesNew( array ( 'user' => $this->screenName, 'text' => ''));
    $this->assertTrue(!empty($resp->response['error']), "An empty direct message should return an error message");
  }

  /**
  * @expectedException EpiTwitterNotAuthorizedException
  */
  function testBadCredentials()
  {
    $resp = $this->twitterObjBadAuth->post_direct_messagesNew( array ( 'user' => $this->screenName, 'text' => 'hello world'));
    $this->assertTrue(!empty($resp->response['error']), "Bad credentials should return a not authorized exception");
  }

  /**
  * @expectedException EpiTwitterNotFoundException
  */
  function testNonExistantUser()
  {
    $resp = $this->twitterObj->post_direct_messagesNew( array ( 'user' => 'jaisen_does_not_exist_and_dont_create_or_this_will_break', 'text' => 'seriously'));
    $this->assertTrue(!empty($resp->response['error']), "Sending a message to a user that doesn't exist should return a 404");
  }
}
