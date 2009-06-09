<?php
/*
 *  Class to integrate with Twitter's API.
 *    Authenticated calls are done using OAuth and require access tokens for a user.
 *    API calls which do not require authentication do not require tokens (i.e. search/trends)
 * 
 *  Full documentation available on github
 *    http://wiki.github.com/jmathai/epicode/epitwitter
 * 
 *  @author Jaisen Mathai <jaisen@jmathai.com>
 */
class EpiTwitter extends EpiOAuth
{
  const EPITWITTER_SIGNATURE_METHOD = 'HMAC-SHA1';
  const EPITWITTER_AUTH_OAUTH = 'oauth';
  const EPITWITTER_AUTH_BASIC = 'basic';
  protected $requestTokenUrl= 'http://twitter.com/oauth/request_token';
  protected $accessTokenUrl = 'http://twitter.com/oauth/access_token';
  protected $authorizeUrl   = 'http://twitter.com/oauth/authorize';
  protected $authenticateUrl= 'http://twitter.com/oauth/authenticate';
  protected $apiUrl         = 'http://twitter.com';
  protected $searchUrl      = 'http://search.twitter.com';

  public function __call($name, $params = null)
  {
    $parts  = explode('_', $name);
    $method = strtoupper(array_shift($parts));
    $parts  = implode('_', $parts);
    $path   = '/' . preg_replace('/[A-Z]|[0-9]+/e', "'/'.strtolower('\\0')", $parts) . '.json';
    $args = !empty($params) ? array_shift($params) : null;

    // calls which do not have a consumerKey are assumed to not require authentication
    if(empty($this->consumerKey))
    {
      $query = isset($args) ? http_build_query($args) : '';
      $url = "{$this->searchUrl}{$path}?{$query}";
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      return new EpiTwitterJson(EpiCurl::getInstance()->addCurl($ch), self::EPITWITTER_AUTH_BASIC);
    }

    // parse the keys to determine if this should be multipart
    $isMultipart = false;
    if($args)
    {
      foreach($args as $k => $v)
      {
        if(strncmp('@',$k,1) === 0)
        {
          $isMultipart = true;
          break;
        }
      }
    }

    $url = $this->getUrl("{$this->apiUrl}{$path}");
    return new EpiTwitterJson(call_user_func(array($this, 'httpRequest'), $method, $url, $args, $isMultipart));
  }

  public function __construct($consumerKey = null, $consumerSecret = null, $oauthToken = null, $oauthTokenSecret = null)
  {
    parent::__construct($consumerKey, $consumerSecret, self::EPITWITTER_SIGNATURE_METHOD);
    $this->setToken($oauthToken, $oauthTokenSecret);
  }
}

class EpiTwitterJson implements ArrayAccess, Countable, IteratorAggregate
{
  private $__resp;
  private $__auth = EpiTwitter::EPITWITTER_AUTH_OAUTH;
  public function __construct($response, $auth = null)
  {
    $this->__resp = $response;
    if($auth !== null)
      $this->__auth = $auth;
  }

  // Implementation of the IteratorAggregate::getIterator() to support foreach ($this as $...)
  public function getIterator ()
  {
    return new ArrayIterator($this->__obj);
  }

  // Implementation of Countable::count() to support count($this)
  public function count ()
  {
    return count($this->__obj);
  }
  
  // Next four functions are to support ArrayAccess interface
  // 1
  public function offsetSet($offset, $value) 
  {
    $this->response[$offset] = $value;
  }

  // 2
  public function offsetExists($offset) 
  {
    return isset($this->response[$offset]);
  }
  
  // 3
  public function offsetUnset($offset) 
  {
    unset($this->response[$offset]);
  }

  // 4
  public function offsetGet($offset) 
  {
    return isset($this->response[$offset]) ? $this->response[$offset] : null;
  }

  public function __get($name)
  {
    if($this->__resp->code != 200 && $name !== 'responseText')
    {
      switch($this->__auth)
      {
        case EpiTwitter::EPITWITTER_AUTH_OAUTH:
          EpiOAuthException::raise($this->__resp->data, $this->__resp->code);
        case EpiTwitter::EPITWITTER_AUTH_BASIC:
          throw new EpiTwitterException($this->__resp->data, $this->__resp->code);
        default:
          throw new Exception("Unknown EpiTwitter Exception.  Response: {$this->__resp->data}", $this->__resp->code);
      }
    }

    $this->responseText = $this->__resp->data;
    $this->code         = $this->__resp->code;
    $this->response     = json_decode($this->responseText, 1);
    $this->__obj        = json_decode($this->responseText);

    if(gettype($this->__obj) === 'object')
    {
      foreach($this->__obj as $k => $v)
      {
        $this->$k = $v;
      }
    }

    return $this->$name;
  }

  public function __isset($name)
  {
    $value = self::__get($name);
    return empty($name);
  }
}

class EpiTwitterException extends Exception {}
