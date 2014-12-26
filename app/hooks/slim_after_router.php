<?php

// slim.after.dispatch would probably work just as well. Experiment
$app->hook('slim.after.router', function () use ($app) {
    $request = $app->request;
    $response = $app->response;

    $app->log->debug('Code: '.$response->getStatus().', Path: '.$request->getPathInfo());
});
