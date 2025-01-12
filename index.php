<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <?php
        // setup initial trips allowed array

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

            echo('mysql connected! <br>');
            mysqli_select_db($conn, 'gtfs_to_sql');
            echo('db connected!');

            $available_trips = array();
            $i = 0;
            $sql = "SELECT trip_id FROM available_trips";
            $result = mysqli_query($conn, $sql);
            while ($row = mysqli_fetch_assoc($result)){
                $available_trips[] = $row["trip_id"];
                echo "$available_trips[$i] <br>";
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

        }
        
        mysqli_close($conn);


    ?>
</body>
</html>