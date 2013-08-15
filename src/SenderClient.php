<?php
/*
 * JBoss, Home of Professional Open Source
 * Copyright Red Hat, Inc., and individual contributors.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * 	http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class SenderClient {

  private $serverURL;
  private $type; //broadcast, selected
  private $pushApplicationID;
  private $masterSecret;
  private $variants = array(); //strings of targeted variants
  private $category;
  private $alias = array(); //strings of aliases
  private $devices = array(); //strings of device types
  private $messages = array(); //array of key:val arrays
  private $simplePush = array();
  private $responseCode;
  private $responseText;

  /*  Determines whether it's a broadcast send or a selected send   */
  function __construct($type) {
    try {
      if($type != null) {
        $this->type = $type;
      } else {
        throw new Exception("Server send type must be selected");
      }
    } catch (Exception $e) {
      die($e->getMessage());
    }
  }

  /*  Verifies URL's structure   */
  public function setServerURL($url) {

    try {
      if($url == null) {
          throw new Exception("Server URL cannot be null");
      }
    } catch(Exception $e) {
      die($e->getMessage());
    }

    //adds / to end of URL if needed
    if($url[strlen($url)-1] != "/") {
      $this->serverURL .= "/";
    } else {
      $this->serverURL = $url;
    }
    
    $this->serverURL .= "rest/sender/".$this->type;
  }

  /* Executes the curl command to send the message */
  public function sendMessage() {
    $credentials = base64_encode($this->pushApplicationID . ":" . $this->masterSecret);
    $con = curl_init($this->serverURL);
 
    curl_setopt($con, CURLOPT_HEADER, 0);
    curl_setopt($con, CURLOPT_POST, 1); 		//POST request
    curl_setopt($con, CURLOPT_RETURNTRANSFER, true); 	//hides(t)/shows(f) response (as value of curl_exec)
    curl_setopt($con, CURLOPT_HTTPHEADER, array("Authorization: Basic " .  $credentials,
                                                'Content-Type: application/json',
                                                'Accept: application/json'));    
    curl_setopt($con, CURLOPT_POSTFIELDS, json_encode($this->buildPayload()));  //send the message

    //try to connect to send the payload, throw exception upon failure
    try {
      if(!curl_exec($con)) {
        throw new Exception("A connection could not be made to the server.");
      } else {
        $this->setResponseText(curl_exec($con));
      }
    } catch (Exception $e) {
      die($e->getMessage());
    }
    
    $this->setResponseCode(curl_getinfo($con, CURLINFO_HTTP_CODE));
    curl_close($con);
  }

  /*  Put values that have been set into JSON-encodable format (PHP array) for request  */
  public function buildPayload() {
    try {
      if(!empty($this->messages)) {
        if($this->type == "selected") {
          return array(
                      "variants"     =>   $this->variants,
                      "category"     =>   $this->category,
                      "alias"        =>   $this->alias,
                      "deviceType"   =>   $this->devices,
                      "message"      =>   $this->messages,
                      "simple-push"  =>   $this->simplePush
                      );
        } else {
          /* Broadcast to all instances of the app
          * simply returns the array of key,val messages */
          return $this->messages;
        }
      } else {
        throw new Exception("At least one message must be submitted.");
      }
    } catch(Exception $e) {
        die($e->getMessage());
    }
  }


  /*  Allows variants to be added to an array   */
  public function addVariant($vid) {
    $this->variants[] = $vid;
  }

  /*  Adds key, value pairs to message payload array   */
  public function addMessage($k, $v) {
    $this->messages[$k] = $v;
  }

  /*  Adds key,value pairs to simple-push array   */
  public function addSimplePush($k, $v) {
    $this->simplePush[$k]  = $v;
  }

  /*  Allows aliases to be added to an array   */
  public function addAlias($aid) {
    $this->alias[]  = $aid;
  }

  /*  Allows devices to be added to an array   */
  public function addDevice($did) {
    $this->devices[]  = $did;
  }

  /*  Tells which application to send to   */
  public function setPushApplicationID($id) {
    try {
      if($id != null) {
        $this->pushApplicationID = $id;
      } else {
        throw new Exception("Push Application ID must not be null.");
      }
    } catch (Exception $e) {
        die($e->getMessage());
    }
  }

  /*  Used for server authentication   */
  public function setMasterSecret($secret) {
    try {
      if($secret != null) {
        $this->masterSecret = $secret;
      } else {
        throw new Exception("Master secret must not be null.");
      }
    } catch (Exception $e) {
        die($e->getMessage());
    }
  }
  /*  Allows category to be set */
  public function setCategory($cat) {
    $this->category = $cat;
  }

  /*  Sets the HTTP response */
  private function setResponseCode($http) {
    $this->responseCode = $http;
  }

  /*  Sets the HTTP body response */
  private function setResponseText($text) {
    $this->responseText = $text;
  }

  /*  Retrieves the HTTP response code from the request   */
  public function getResponseCode() {
    return $this->responseCode;
  }

  /*  Retrieves the HTTP response text from the request   */
  public function getResponseText() {
    return $this->responseText;
  }

}
