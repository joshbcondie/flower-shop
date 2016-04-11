<?php

/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 4/9/2016
 * Time: 10:32 AM
 */
class ShopAPI extends API
{
    protected $User;

    public function __construct($request, $origin)
    {
        parent::__construct($request);

//        // Abstracted out for example
//        $APIKey = new APIKey();
//        $User = new User();
//
//        if (!array_key_exists('apiKey', $this->request)) {
//
//            throw new Exception('No API Key provided');
//
//        } else if (!$APIKey->verifyKey($this->request['apiKey'], $origin)) {
//
//            throw new Exception('Invalid API Key');
//
//        } else if (array_key_exists('token', $this->request) && !$User->get('token', $this->request['token']))
//        {
//
//            throw new Exception('Invalid User Token');
//        }
//
//        $this->User = $User;
    }


    protected function register_deliver()
    {
        if ($this->method == 'POST') {
            return "POST " . print_r($this->request, true);
        } else if ($this->method == 'GET') {
            return "GET " . print_r($this->request, true);
        }
    }

    protected function create_delivery_request()
    {
        if ($this->method == 'POST') {
            return "POST " . print_r($this->request, true);
        } else if ($this->method == 'GET') {
            return "GET " . print_r($this->request, true);
        }
    }

    protected function deliver_ready()
    {
        if ($this->method == 'POST') {
            return "POST " . print_r($this->request, true);
        } else if ($this->method == 'GET') {
            return "GET " . print_r($this->request, true);
        }
    }

    protected function bid_available()
    {
        if ($this->method == 'POST') {
            return "POST " . print_r($this->request, true);
        } else if ($this->method == 'GET') {
            return "GET " . print_r($this->request, true);
        }
    }
}