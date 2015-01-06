<?php

class CorsMiddleware extends \Slim\Middleware
{
    public function call()
    {
        //The Slim application
        $app = $this->app;

        //Response
        $response = $app->response;
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization');

        $this->next->call();
    }
}

$app->add(new \CorsMiddleware());
