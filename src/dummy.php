<?php
$coordinates = [
    ["x"=>25,"y"=>200,"label"=>"Ottawa"],
    ["x"=>75,"y"=>200,"label"=>"Casselman"],
    ["x"=>125,"y"=>200,"label"=>"Alexandria"],
    ["x"=>175,"y"=>200,"label"=>"Coteau"],
    ["x"=>225,"y"=>200,"label"=>"Dorval"],
    ["x"=>250,"y"=>200,"label"=>"Montreal"]
];

echo json_encode($coordinates);
?>

<?php
    // setup initial trips allowed array
    function setup(){
        $hs= "localhost";
        $us = "root";
        $ps = "BlueLemonadeCats87/";
        // $hs= "sql309.infinityfree.com";
        // $us = "if0_38088559";
        // $ps = "hRhFNefifQ4hTf";
        $conn = mysqli_connect($hs, $us, $ps);
        if(!$conn){
            die('mysql not connected');
        }else{

            // echo('mysql connected! <br>');
            mysqli_select_db($conn, 'gtfs_to_sql');
            // echo('db connected!');

            $available_trips = array();
            $i = 0;
            $sql = "SELECT trip_id FROM available_trips";
            $result = mysqli_query($conn, $sql);
            while ($row = mysqli_fetch_assoc($result)){
                $available_trips[] = $row["trip_id"];
                // echo "$available_trips[$i] <br>";
                $i += 1;
            }

            $available_stops = array();
            $i = 0;
            $sql = "SELECT * FROM available_stops";
            $result = mysqli_query($conn, $sql);
            while ($row = mysqli_fetch_assoc($result)){
                $stop_info = array("x" => (float)$row["stop_lat"] , "y" => (float)$row["stop_lon"], "label" => $row["stop_name"]);
                $available_stops[] = $stop_info;
                $i += 1;
            }
            // for($i = 0; $i < count($available_stops); $i++){
            //     echo $available_stops[$i]["x"] ."<br>";
            //     echo $available_stops[$i]["y"] ."<br>";
            // }
            $coordinates = standardize_coors($available_stops);
            // for($i = 0; $i < count($coordinates); $i++){
            //     echo $coordinates[$i]["x"] ."<br>";
            //     echo $coordinates[$i]["y"] ."<br>";
            // }
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
            $stop_y = (int) (($stop["y"]-$translation_y)*(2/$scale)*100 + 100);
            $standardized_stop = array("x" => $stop_x , "y" => $stop_y, "label" => $stop["label"]);
            $coordinates[] = $standardized_stop;
        }

        return $coordinates;
    }
    setup();
    
?>