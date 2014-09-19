<?php

class Curlib {

    public $username = "";

    public $password = "";

    public $baseurl = "";

    public $custom_headers = array();

    public $cookie_file = "";

    private $valid_calls = array('GET', 'POST', 'PATCH', 'PUT', 'DELETE');

    public function addCustomHeader($header, $value) {
       $this->custom_headers[$header] = $value;
    }


    public function setCookieFile($cookie_file) {
        $this->cookie_file = $cookie_file;
    }


    /**
     * @param
     *  $url (string) 
     *  $data (string) - json formatted data
     *  $call_type (string) - GET, POST, PATCH, PUT or DELETE 
     */
    public function call($url, $data = NULL, $call_type = 'GET', $json = true) {
        
# check if call type is valid

        if (!in_array($call_type, $this->valid_calls)) {
            throw new Exception("Invalid curl call:".$call_type);
        }


# check if json is valid for POST, PUT, PATCH
        if ($json) {
            $is_valid_data = @json_decode($data);
            $data = json_encode($is_valid_data);
            if (in_array($call_type, array('POST', 'PUT', 'PATCH')) && $is_valid_data == NULL) {
                throw new Exception("POST, PUT and PATCH requires a valid JSON input");
            }
        }


# set base url if exists

        if ($this->baseurl) {
            $url = $this->baseurl . $url;
        }
        
# init curl

        $ch = curl_init($url);


# set auth if needed
        if ($this->username && $this->password) {
            
            curl_setopt($ch, CURLOPT_USERPWD, $this->username.':'.$this->password);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        }

# use cookies if cookie file was set

        if ($this->cookie_file) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
        }


# set custom call types
        
        if (in_array($call_type, array('PUT', 'PATCH', 'DELETE'))) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $call_type);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override:'.$call_type));
        } elseif ($call_type == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        }
        
        if ($call_type != 'GET') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            if ($json) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                   'Content-Type: application/json',
                   'Content-Length: ' . strlen($data))
                );
            };
        }


        if (count($this->custom_headers)) {
            foreach ($this->custom_headers as $header=>$value) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, array($header.':'.$value));
            }
        }

    
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 

        $result = curl_exec($ch);
        $info = curl_getinfo($ch);

        curl_close($ch);
        return array($result, $info['http_code']);
    }
}

