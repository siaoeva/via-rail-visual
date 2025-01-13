<?php
date_default_timezone_set('America/Toronto');
$available_trips = array();
function setup(){
    global $available_trips;
    $hs= "localhost";
    $us = "root";
    $ps = "BlueLemonadeCats87/";
    $conn = mysqli_connect($hs, $us, $ps);
    if(!$conn){
        die('mysql not connected');
    }else{
        mysqli_select_db($conn, 'gtfs_to_sql');
        $i = 0;
        $sql = "SELECT trip_id FROM available_trips";
        $result = mysqli_query($conn, $sql);
        while ($row = mysqli_fetch_assoc($result)){
            $available_trips[] = $row["trip_id"];
            $i += 1;
        }

        $available_stops = array();
        $i = 0;
        $sql = "SELECT * FROM available_stops";
        $result = mysqli_query($conn, $sql);
        while ($row = mysqli_fetch_assoc($result)){
            $stop_info = array("x" => (float)$row["stop_lon"] , "y" => (float)$row["stop_lat"], "label" => $row["stop_name"]);
            $available_stops[] = $stop_info;
            $i += 1;
        }

        $coordinates = standardize_coors($available_stops);
        echo json_encode($coordinates);
    }
    
    mysqli_close($conn);
}

function standardize_coors($available_stops){
    $coordinates = array();
    $max_x = PHP_INT_MIN;
    $max_y = PHP_INT_MIN;
    $min_x = PHP_INT_MAX;
    $min_y = PHP_INT_MAX;
    foreach($available_stops as $stop){
        if ($stop["x"] > $max_x){
            $max_x = $stop["x"];
        }
        if ($stop["y"] > $max_y){
            $max_y = $stop["y"];
        }
        if ($stop["x"] < $min_x){
            $min_x = $stop["x"];
        }
        if ($stop["y"] < $min_y){
            $min_y = $stop["y"];
        }
    }
    $translation_x = ($max_x + $min_x)/2;
    $translation_y = ($max_y + $min_y)/2;
    $scale = max($max_x - $min_x, $max_y - $min_y);

    foreach($available_stops as $stop){
        $stop_x = (int) (($stop["x"]-$translation_x)*(2/$scale)*100 + 100);
        $stop_y = (int) (-($stop["y"]-$translation_y)*(2/$scale)*100 + 100); //flipped negative is a mcgyver solution
        $standardized_stop = array("x" => $stop_x , "y" => $stop_y, "label" => $stop["label"]);
        $coordinates[] = $standardized_stop;
    }

    return $coordinates;
}

function get_trips_update(){
    global $available_trips;
    foreach($available_trips as $trip_id){
        get_trip_update($trip_id);
    }
}

function get_trip_update($trip_id){
    $trip_info = array('running' => true);
    $hs= "localhost";
    $us = "root";
    $ps = "BlueLemonadeCats87/";
    $conn = mysqli_connect($hs, $us, $ps);
    if(!$conn){
        die('mysql not connected');
    }else{
        mysqli_select_db($conn, 'gtfs_to_sql');
        
        //check that trip is running on current day
        $sql = "SELECT * FROM calendar WHERE service_id = ?";
        $result = mysqli_execute_query($conn, $sql, [$trip_id]);
        $calendar_info = mysqli_fetch_assoc($result);

        $current_date = (int)('20' . date("ymd"));
        $current_weekday = strtolower(date("l"));

        $start_date = (int) $calendar_info['start_date'];
        $end_date = (int) $calendar_info['end_date'];
        if (!($current_date <= $end_date and $current_date >= $start_date)){
            $trip_info["running"] = false;
        }
            
        if ($calendar_info[$current_weekday] == 0){
            $trip_info["running"] = false;
        }
        
        $sql = "SELECT * FROM calendar_dates WHERE service_id = ?";
        $result = mysqli_execute_query($conn, $sql, [$trip_id]);
        if (mysqli_num_rows($result) > 0){
            $trip_info["running"] = false;
        }

        //get trip direction
        $sql = "SELECT direction_id FROM trips WHERE trip_id = ?";
        $result = mysqli_execute_query($conn, $sql, [$trip_id]);
        $trip_info['direction'] = (int) mysqli_fetch_assoc($result)['direction_id'];

        //check that trip is running at current time
        $current_time = strtotime("10:12:00");//time();
        $sql = "SELECT arrival_time, departure_time, stop_id FROM available_stop_times WHERE trip_id = ?";
        $result = mysqli_execute_query($conn, $sql, [$trip_id]);
        $trip_times = array();
        while ($stop_info = mysqli_fetch_assoc($result)){
            $trip_times[] = $stop_info;
        }
        if ($current_time < strtotime($trip_times[0]['arrival_time']) or $current_time > strtotime($trip_times[count($trip_times)-1]['arrival_time'])){
            $trip_info["running"] = false;
        }

        //if trip is running, get name of previous stop and progress
        if ($trip_info["running"]){
            $index_next_stop = search_next_stop($trip_times, $current_time, $current_date);
            $prev_arrival = strtotime($trip_times[$index_next_stop - 1]['arrival_time']);
            $prev_departure = strtotime($trip_times[$index_next_stop - 1]['departure_time']);
            $next_arrival = strtotime($trip_times[$index_next_stop]['arrival_time']);
            $prev_stop_id = $trip_times[$index_next_stop - 1]['stop_id'];

            //get stop name
            $sql = "SELECT stop_name FROM available_stops WHERE stop_id = ?";
            $result = mysqli_execute_query($conn, $sql, [$prev_stop_id]);
            $trip_info["prev_stop"] = mysqli_fetch_assoc($result)["stop_name"];
            
            //get trip progress
            if ($prev_arrival <= $current_time and $current_time <= $prev_departure){
                $trip_info["progress"] = 0;
            }else{
                $trip_info["progress"] = (int) (($current_time-$prev_departure)/($next_arrival-$prev_departure)*100);
            }
        }else{
            $sql = "SELECT stop_name FROM available_stops WHERE stop_id = ?";
            $result = mysqli_execute_query($conn, $sql, [$trip_times[0]['stop_id']]);
            $trip_info["prev_stop"] = mysqli_fetch_assoc($result)["stop_name"];
            $trip_info["progress"] = 0;
        }
        // echo json_encode($trip_info);
    }
    mysqli_close($conn);
}

function search_next_stop($trip_times, $current_time,$current_date){
    //does not account for trips that go across 00:00:00. Ex: trip from 23:30:00 to 01:00:00 will not find next stop properly
    //use linear search since number of stops likely < 10
    //pre-condition: current_time is between start and end time found in trip_times
    $i = 0; 
    while($i < count($trip_times) and $current_time >= strtotime($trip_times[$i]['arrival_time'])){
        $i++;
    }
    return $i;
}

setup();

?>