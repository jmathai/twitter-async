<?php
/*
 *  Class to integrate with Twitter's API.
 *    Authenticated calls are done using OAuth and require access tokens for a user.
 *    API calls which do not require authentication do not require tokens (i.e. search/trends)
 * 
 *  Full documentation available on github
 *    http://wiki.github.com/jmathai/twitter-async
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
  protected $userAgent      = 'EpiTwitter (http://github.com/jmathai/twitter-async/tree/)';

  public function __call($name, $params = null/*, $username, $password*/)
  {
    $parts  = explode('_', $name);
    $method = strtoupper(array_shift($parts));
    $parts  = implode('_', $parts);
    $path   = '/' . preg_replace('/[A-Z]|[0-9]+/e', "'/'.strtolower('\\0')", $parts) . '.json';
    $args = !empty($params) ? array_shift($params) : null;

    // calls which do not have a consumerKey are assumed to not require authentication
    if($this->consumerKey === null)
    {
      $query = !is_null($args) ? http_build_query($args, '', '&') : '';
      $url = (preg_match('@^/(search|trends)@', $path) ? $this->searchUrl : $this->apiUrl) . "{$path}?{$query}";
      $ch  = curl_init($url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_TIMEOUT, $this->requestTimeout);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
      if(!empty($params))
      {
        $username = array_shift($params);
        $password = !empty($params) ? array_shift($params) : null;
        if($username !== null && $password !== null)
          curl_setopt($ch, CURLOPT_USERPWD, "{$username}:{$password}");
      }

      return new EpiTwitterJson(EpiCurl::getInstance()->addCurl($ch), $this->debug);
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
    return new EpiTwitterJson(call_user_func(array($this, 'httpRequest'), $method, $url, $args, $isMultipart), $this->debug);
  }

  public function __construct($consumerKey = null, $consumerSecret = null, $oauthToken = null, $oauthTokenSecret = null)
  {
    parent::__construct($consumerKey, $consumerSecret, self::EPITWITTER_SIGNATURE_METHOD);
    $this->setToken($oauthToken, $oauthTokenSecret);
  }
}

class EpiTwitterJson implements ArrayAccess, Countable, IteratorAggregate
{
  private $debug;
  private $__resp;
  public function __construct($response, $debug = false)
  {
    $this->__resp = $response;
    $this->debug  = $debug;
  }

  // ensure that calls complete by blocking for results, NOOP if already returned
  public function __destruct()
  {
    $this->responseText;
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
    $accessible = array('responseText'=>1,'headers'=>1);
    if(($this->__resp->code < 200 || $this->__resp->code >= 400) && !isset($accessible[$name]))
      EpiTwitterException::raise($this->__resp, $this->debug);

    // if no exception is thrown, continue
    $this->responseText = $this->__resp->data;
    $this->code         = $this->__resp->code;
    $this->headers      = $this->__resp->headers;
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

class EpiTwitterException extends EpiException 
{
  public static function raise($response, $debug)
  {
    $message = array();
    $message['text'] = 'An exception occurred in EpiTwitter';
    $message['response'] = $response->data;
    if($debug === true)
      $message['headers'] = json_encode($response->headers);
    $message = json_encode($message);
 
    switch($response->code)
    {
      case 400:
        throw new EpiTwitterBadRequestException($message, $response->code);
      case 401:
        throw new EpiTwitterNotAuthorizedException($message, $response->code);
      case 403:
        throw new EpiTwitterForbiddenException($message, $response->code);
      case 404:
        throw new EpiTwitterNotFoundException($message, $response->code);
      default:
        throw new EpiTwitterException($message, $response->code);
    }
  }
}
class EpiTwitterBadRequestException extends EpiTwitterException{}
class EpiTwitterNotAuthorizedException extends EpiTwitterException{}
class EpiTwitterForbiddenException extends EpiTwitterException{}
class EpiTwitterNotFoundException extends EpiTwitterException{}
