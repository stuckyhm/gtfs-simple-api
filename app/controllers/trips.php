<?php

$app->group('/trips', function () use ($app) {

  $app->get('(/)(\.:format)', function ($format = 'json') use ($app){
    $limit = $app->request()->get('limit');
    $limitString = (is_numeric($limit) ? ' LIMIT ' .((int)$limit) : '');

    $pdo = R::getDatabaseAdapter()->getDatabase()->getPDO();
    $stmt = $pdo->prepare("SELECT * FROM trips".$limitString);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $app->response->headers->set('Content-Type', 'application/json');
    $app->response->body(json_encode($data));
  })->name('get_trips')->conditions(array('format' => '(json)'));


  $app->get('/:tripId(/:serviceDate)(\.:format)', function ($tripId, $serviceDate = false, $format = 'json') use ($app){
    $detail = explode(',', $app->request()->get('detail'));

    if($serviceDate == false){
      $serviceDate = date('Y-m-d');
    }

    $pdo = R::getDatabaseAdapter()->getDatabase()->getPDO();
    $stmt = $pdo->prepare("SELECT t.*, r.route_color, r.route_text_color, r.route_short_name, r.route_long_name".
                          " FROM trips AS t".
                          " LEFT JOIN routes AS r ON r.route_id = t.route_id".
                          " WHERE t.trip_id = ?");
    $stmt->execute([$tripId]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = (isset($result[0]) ? $result[0] : array());

    $data['route_text_color'] = ($data['route_text_color'] == '' ? '000000' : $data['route_text_color']);

    $data['service_date'] = $serviceDate;

    if(!isset($data['trip_id'])){
      $app->pass();
    }

    if(in_array('stops', $detail) || in_array('stops_full', $detail) || in_array('full', $detail)){
      $sqlColumns = "tst.stop_id, tst.stop_sequence, s.stop_name, s.stop_code, tst.arrival_utc, tst.departure_utc, tst.timezone, s.wheelchair_boarding";

      if(in_array('full', $detail) || in_array('stops_full', $detail)){
        $sqlColumns .= ", s.stop_desc, s.zone_id, s.stop_url";
      }

      $stmt = $pdo->prepare("SELECT DISTINCT ".$sqlColumns.
                            " FROM xtra_trip_stop_times AS tst ".
                            " LEFT JOIN stops AS s ON s.stop_id = tst.stop_id ".
                            " WHERE tst.trip_id = :trip_id ".
                            "  AND tst.service_date = :service_date ".
                            " ORDER BY tst.arrival_utc ASC ");
      $stmt->execute(array('trip_id' => $data['trip_id'], 'service_date' => $serviceDate));
      $data['stops'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
  
    $app->response->headers->set('Content-Type', 'application/json');
    $app->response->body(json_encode($data));
  })->name('get_trip')->conditions(array('format' => '(json)', 'serviceDate' => '[0-9]{4}-[0-1][0-9]-[0-9]{2}')); // 'tripId' => '[0-9A-Za-z_-]*', 


  $app->get('/:tripId(/:serviceDate)/next_stops(\.:format)', function ($tripId, $serviceDate = false, $format = 'json') use ($app){
    $detail = explode(',', $app->request()->get('detail'));
    $limit = $app->request()->get('limit');
    $limitString = (is_numeric($limit) ? ' LIMIT ' .((int)$limit) : '');

    if($serviceDate == false){
      $serviceDate = date('Y-m-d');
    }

    $pdo = R::getDatabaseAdapter()->getDatabase()->getPDO();

    $sqlColumns = "tst.stop_id, tst.stop_sequence, s.stop_name, s.stop_code, tst.arrival_utc, tst.departure_utc, tst.timezone, s.wheelchair_boarding";
    if(in_array('full', $detail)){
      $sqlColumns .= ", s.stop_desc, s.zone_id, s.stop_url";
    }

    $stmt = $pdo->prepare("SELECT DISTINCT ".$sqlColumns.
                          " FROM xtra_trip_stop_times AS tst ".
                          " LEFT JOIN stops AS s ON s.stop_id = tst.stop_id ".
                          " WHERE tst.trip_id = :trip_id ".
                          "  AND tst.service_date = :service_date ".
                          "  AND tst.arrival_utc >= now() ".
                          " ORDER BY tst.arrival_utc ASC ".
                          $limitString);
    $stmt->execute(array('trip_id' => $tripId, 'service_date' => $serviceDate));
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /*if(!isset($data['stop_id'])){
      $app->pass();
    }*/

    $app->response->headers->set('Content-Type', 'application/json');
    $app->response->body(json_encode($data));
  })->name('get_trip_next_stops')->conditions(array('format' => '(json)'));

});
