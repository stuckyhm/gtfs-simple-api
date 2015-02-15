<?php

$app->group('/stops', function () use ($app) {

  $app->get('(/)(\.:format)', function ($format = 'json') use ($app){
    $line = $app->request()->get('bbox');
    $detail = explode(',', $app->request()->get('detail'));
    $limit = $app->request()->get('limit');
    $limitString = (is_numeric($limit) ? ' LIMIT ' .((int)$limit) : '');

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
    $stmt = $pdo->prepare("SELECT ".$sql_columns." FROM stops WHERE ".$sql_where.$limitString);
    $stmt->execute($sql_param);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    for($i=0; $i<sizeof($data); $i++){
      $data[$i]['stop_latlon'] = array($data[$i]['stop_lat'], $data[$i]['stop_lon']);
      unset($data[$i]['stop_lat']);
      unset($data[$i]['stop_lon']);

      if(in_array('full', $detail) || in_array('routes', $detail) || in_array('routes_full', $detail)){
        if(in_array('routes_full', $detail) || in_array('full', $detail)){
          $sqlRouteColumns = "r.*";
        }else{
          $sqlRouteColumns = "r.route_id, r.agency_id, r.route_short_name, r.route_long_name, r.route_color, r.route_text_color";
        }
        $stmt = $pdo->prepare("SELECT DISTINCT ".$sqlRouteColumns.
                              " FROM stops AS s ".
                              " LEFT JOIN xtra_stop_routes AS sr ON s.stop_id = sr.stop_id ".
                              " LEFT JOIN routes AS r ON r.route_id = sr.route_id ".
                              " WHERE (s.stop_id = :stop_id OR s.parent_station = :stop_id) ".
                              "  AND r.route_id IS NOT NULL");
        $stmt->execute(array('stop_id' => $data[$i]['stop_id']));
        $data[$i]['routes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
      }
    }

    $app->response->headers->set('Content-Type', 'application/json');
    $app->response->body(json_encode($data));
  })->name('get_stops')->conditions(array('format' => '(json)'));


  $app->get('/:stopId(\.:format)', function ($stopId, $format = 'json') use ($app){
    $detail = explode(',', $app->request()->get('detail'));

    $pdo = R::getDatabaseAdapter()->getDatabase()->getPDO();
    $stmt = $pdo->prepare("SELECT * FROM stops WHERE stop_id = ?");
    $stmt->execute([$stopId]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = (isset($result[0]) ? $result[0] : array());

    if(!isset($data['stop_id'])){
      $app->pass();
    }

    $data['stop_latlon'] = array($data['stop_lat'], $data['stop_lon']);
    unset($data['stop_lat']);
    unset($data['stop_lon']);

    if(in_array('routes', $detail) || in_array('full', $detail)){
      if(in_array('full', $detail)){
        $sqlRouteColumns = "r.*";
      }else{
        $sqlRouteColumns = "r.route_id, r.agency_id, r.route_short_name, r.route_long_name, r.route_color, r.route_text_color";
      }
      $stmt = $pdo->prepare("SELECT DISTINCT ".$sqlRouteColumns.
                            " FROM stops AS s ".
                            " LEFT JOIN xtra_stop_routes AS sr ON s.stop_id = sr.stop_id ".
                            " LEFT JOIN routes AS r ON r.route_id = sr.route_id ".
                            " WHERE (s.stop_id = :stop_id OR s.parent_station = :stop_id) ".
                            "  AND r.route_id IS NOT NULL");
      $stmt->execute(array('stop_id' => $data['stop_id']));
      $data['routes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $app->response->headers->set('Content-Type', 'application/json');
    $app->response->body(json_encode($data));
  })->name('get_stop')->conditions(array('stopId' => '[0-9A-Za-z_-]*', 'format' => '(json)'));


  $app->get('/:stopId/next_trips(\.:format)', function ($stopId, $format = 'json') use ($app){
    $detail = explode(',', $app->request()->get('detail'));
    $limit = $app->request()->get('limit');
    $limitString = ' LIMIT '.(is_numeric($limit) ? ($limit <= 50 ? ((int)$limit) : 50) : 10);

    $sql_columns = 't.trip_id, xtst.operating_date, xtst.arrival_utc, xtst.departure_utc, xtst.timezone, t.trip_headsign, t.trip_short_name, r.route_id, r.route_type, 
                    r.route_short_name, r.route_long_name, r.route_color, r.route_text_color';

    if(in_array('full', $detail)){
      $sql_columns .= ', t.direction_id, t.block_id, t.shape_id, t.wheelchair_accessible, t.bikes_allowed, r.agency_id, r.route_desc, r.route_url';
    }

    $pdo = R::getDatabaseAdapter()->getDatabase()->getPDO();
    $stmt = $pdo->prepare("SELECT ".$sql_columns."
                           FROM xtra_trip_stop_times AS xtst
                           LEFT JOIN trips AS t ON t.trip_id = xtst.trip_id
                           LEFT JOIN routes AS r ON r.route_id = t.route_id
                           WHERE xtst.stop_id IN
                               (SELECT stop_id
                                FROM stops
                                WHERE stop_id = ?
                                  OR parent_station = ?)
                             AND xtst.departure_utc >= now()
                           ORDER BY xtst.departure_utc ASC
                           ".$limitString);
    $stmt->execute([$stopId, $stopId]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /*if(!isset($data['stop_id'])){
      $app->pass();
    }*/

    $app->response->headers->set('Content-Type', 'application/json');
    $app->response->body(json_encode($data));
  })->name('get_stop_next_trips')->conditions(array('stopId' => '[0-9A-Za-z_-]*', 'format' => '(json)'));

});
