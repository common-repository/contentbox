<?php

class ContentboxClient
{
    public $token = '';

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function get($url, $body = '')
    {
        try {
            if (!$this->token) {
                throw new Exception('Invalid token');
            }

            $result = file_get_contents($url, false, stream_context_create(array(
                'http' => array(
                    'method' => "POST",
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n" .
                        "Authorization: Bearer " . $this->token . "\r\n",
                    'content' => $body,
                )
            )));

            return $result;
        } catch (Exception $e) {

        }
    }

    public function put($url)
    {
        try {
            if (!$this->token) {
                throw new Exception('Invalid token');
            }

            $result = file_put_contents($url, false, stream_context_create(array(
                'http' => array(
                    'method' => "POST",
                    'header' => "Content-Type: application/json\r\n" .
                        "Authorization: Bearer " . $this->token . "\r\n"
                )
            )));

            return $result;
        } catch (Exception $e) {

        }
    }
}