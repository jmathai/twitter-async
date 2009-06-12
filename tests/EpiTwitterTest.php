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
    $this->twitterObjBasic = new EpiTwitter();
    $this->screenName = 'jmathai_test';
  }

  function testBooleanResponse()
  {
    $resp = $this->twitterObj->get_friendshipsExists(array('user_a' => 'jmathai_test','user_b' => 'jmathai'));
    $this->assertTrue(gettype($resp->response) === 'boolean', 'response should be a boolean for friendship exists');
    $this->assertTrue($resp->response, 'response should be true for friendship exists');
  }

  function testGetVerifyCredentials()
  {
    $resp = $this->twitterObj->get_accountVerify_credentials();
    $this->assertTrue(strlen($resp->responseText) > 0, 'responseText was empty');
    $this->assertTrue($resp instanceof EpiTwitterJson, 'response is not an array');
    $this->assertTrue(!empty($resp->screen_name), 'member property screen_name is empty');
  }

  function testGetWithParameters()
  {
    $resp = $this->twitterObj->get_statusesFriends_timeline(array('since_id' => 1));
    $this->assertTrue(!empty($resp->response[0]['user']['screen_name']), 'first status has no screen name');
  }

  function testGetFollowers()
  {
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
    $resp = $this->twitterObj->post_statusesUpdate(array('status' => $statusText));
    $this->assertEquals($resp->text, $statusText, 'The status was not updated correctly');
  }

  function testDirectMessage()
  {
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

  /**
  * @expectedException EpiOAuthException
  */
  function testNoRequiredParameter()
  {
    $resp = $this->twitterObj->post_direct_messagesNew( array ( 'user' => $this->screenName, 'text' => ''));
    $this->assertTrue(!empty($resp->response['error']), "An empty direct message should return an error message");

  }

  function testResponseAccess()
  {
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
    $resp = $this->twitterObjBasic->get_search(array('q' => 'hello'));
    $this->assertTrue(is_array($resp->response['results']));
    $this->assertTrue(!empty($resp->results[0]->text), "search response is not an array {$resp->results[0]->text}");
  }

  function testTrends()
  {
    $resp = $this->twitterObjBasic->get_trends();
    $this->assertTrue(is_array($resp->response['trends']), "trends is empty");
    $this->assertTrue(!empty($resp->trends[0]->name), "current trends is not an array " . $resp->trends[0]->name);

    $resp = $this->twitterObjBasic->get_trendsCurrent();
    $this->assertTrue(is_array($resp->response['trends']), "current trends is empty");
  }

  function testSSl()
  {
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
    $method = "get_statusesFollowers{$screenName}";
    $resp = $this->twitterObj->$method();
    $this->assertTrue(count($resp) > 0, "Count for followers was not larger than 0");
    $resp = $this->twitterObj->$method(array('page' => 100));
    $this->assertTrue(count($resp) == 0, "Page 100 should return a count of 0");
  }

  function testUpdateAvatar()
  {
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
    $resp = $this->twitterObj->post_accountUpdate_profile_background_image(array('@image' => "@{$file}"));
    // api seems to be a bit behind and doesn't respond with the new image url - use code instead for now
    $this->assertEquals($resp->code, 200, 'Response code was not 200');

    $file = dirname(__FILE__) . '/avatar_test_image.png';
    $resp = $this->twitterObj->post_accountUpdate_profile_background_image(array('@image' => "@{$file}"));
    // api seems to be a bit behind and doesn't respond with the new image url - use code instead for now
    $this->assertEquals($resp->code, 200, 'Response code was not 200');
  }

  function testCreateFriendship()
  {
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
}
