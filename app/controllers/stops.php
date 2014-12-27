<?php

$app->group('/stops', function () use ($app) {

  $app->get('(/)(\.:format)', function ($format = 'json') use ($app){
    $line = $app->request()->get('bbox');
    $detail = explode(',', $app->request()->get('detail'));

    $sql_columns = 'stop_id, stop_code, stop_name, stop_lat, stop_lon';
    $sql_param = array();
    $sql_where = "(parent_station IS NULL OR parent_station = '')";

    if(in_array('full', $detail)){
      $sql_columns .= ', zone_id, stop_desc, stop_url, location_type, stop_timezone, wheelchair_boarding';
    }

    if($line != null && preg_match('/^(-?[0-9]+(?:\.[0-9]+)?),(-?[0-9]+(?:\.[0-9]+)?),(-?[0-9]+(?:\.[0-9]+)?),(-?[0-9]+(?:\.[0-9]+)?)$/', $line, $bbox)){
      $sql_param['lat1'] = doubleval($bbox[1]);
      $sql_param['lat2'] = doubleval($bbox[2]);
      $sql_param['lon1'] = doubleval($bbox[3]);
      $sql_param['lon2'] = doubleval($bbox[4]);
      $sql_where .= ' AND (stop_lat BETWEEN :lat1 AND :lat2) AND (stop_lon BETWEEN :lon1 AND :lon2) ';
    }

    $pdo = R::getDatabaseAdapter()->getDatabase()->getPDO();
    $stmt = $pdo->prepare("SELECT ".$sql_columns." FROM stops WHERE ".$sql_where);
    $stmt->execute($sql_param);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $app->response->headers->set('Content-Type', 'application/json');
    $app->response->body(json_encode($data));
  })->name('get_stops')->conditions(array('format' => '(json)'));


  $app->get('/:stopId(\.:format)', function ($stopId, $format = 'json') use ($app){
    $pdo = R::getDatabaseAdapter()->getDatabase()->getPDO();
    $stmt = $pdo->prepare("SELECT * FROM stops WHERE stop_id = ?");
    $stmt->execute([$stopId]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = (isset($result[0]) ? $result[0] : array());

    if(!isset($data['stop_id'])){
      $app->pass();
    }
    
    $app->response->headers->set('Content-Type', 'application/json');
    $app->response->body(json_encode($data));
  })->name('get_stop')->conditions(array('format' => '(json)'));

});
