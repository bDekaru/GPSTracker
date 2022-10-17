<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

###### Adjust the following storage location #########
# Storage location for files with a trailing slash to indicate a directory
# Currently it is set to tracker's dir

$storagefolder = './data/';

###### Adjust autoResetTime time (if not using /tmp)
# Set to 24h. Change to fit your needs (time is in sec)

@$autoResetTime = 0;

###### Change the following only if you know what you're doing #########

@$view = $_GET['view'];
@$track = $_GET['track'];
@$reset = $_GET['reset'];
@$clearroute = $_GET['clearroute'];
@$route = $_GET['route'];
@$updateroute = $_GET['updateroute'];
@$checkfiletime = $_GET['checkfiletime'];

####### Locus Pro Parameters
# Latitude - current GPS latitude (in degree unit, rounded on 5 decimal places)
@$lat = $_GET['lat'];

# Longitude - current GPS longitude (in degree unit, rounded on 5 decimal places)
@$lon = $_GET['lon'];

# Altitude - current altitude value in meter (improved altitude value by filter and barometer)
@$alt = $_GET['alt'];

# Speed - current GPS speed (in m/s)
@$speed = $_GET['speed'];

# Accuracy - current GPS accuracy (in metres)
@$acc = $_GET['acc'];

# Battery - current akku (in percent)
@$battery = $_GET['battery'];

# GSM signal - current signal strength (in percent)
@$gsm_signal = $_GET['gsm_signal'];

# Message - variable Message you're able to provide with a selfdefined var in locus
@$message = $_GET['message'];

@$rec_time = $_GET['rec_time'];
@$rec_length = $_GET['rec_length'];
@$avg_speed_tot = $_GET['avg_speed_tot'];
@$avg_speed_mov = $_GET['avg_speed_mov'];

@$est_time = $_GET['est_time'];
@$dist_end = $_GET['dist_end'];

# Time - current GPS time (in format defined by user. You may choose from predefined styles or define you own by this specification)
#my $time = param('time') || '';
# Text field - text field with own define key/value pair.
#y $var = param('var') || '';

# Check storage location
if (!is_dir($storagefolder)) {
    if (!mkdir($storagefolder)) {
        die("Couldn't access storage folder.");
    }
}

# Current Unix timestamp
$time = time();
$now = $time;

# JSON output
#echo header('application/json');
header('Content-Type: application/json;charset=utf-8');
echo '{';

$trackFile = $storagefolder . "track.json";
$currentLocationFile = $storagefolder .  "currentLocation.json";
$routeFile = $storagefolder . 'route.gpx';

# autoResetTime
if ($autoResetTime != 0 && filemtime($currentLocationFile) < (time() - $autoResetTime)) {
    unlink($currentLocationFile);
    unlink($trackFile);
}

# Write latitude, longitude and other data into .latlon file when received from Locus
if ($lat != "" && $lon != "") {
    if ($handle = fopen($currentLocationFile, "w")) {
        fwrite($handle, '"lat":"' . $lat
         . '","lon":"' . $lon
         . '","alt":"' . $alt
         . '","speed":"' . $speed
         . '","acc":"' . $acc
         . '","time":"' . $time
         . '","battery":"' . $battery
         . '","gsm_signal":"' . $gsm_signal
         . '","rec_time":"' .  $rec_time
         . '","rec_length":"' . $rec_length
         . '","avg_speed_tot":"' . $avg_speed_tot
         . '","avg_speed_mov":"' . $avg_speed_mov
         . '","est_time":"' . $est_time
         . '","dist_end":"' . $dist_end
         . '","message":"' . $message
         . '"');
        fclose($handle);
    } else {
        echo "File access error: " . $currentLocationFile;
    }

    # Write latitude & longitude into .geojson file, collecting positions for track
    if ($handle = fopen($trackFile, "a+")) {
        fwrite($handle, "[ " . $lon . ", " . $lat . " ], ");
        fclose($handle);
    } else {
        echo "File access error:" . $trackFile;
    }
} else if ($view == '1') # Output latitude, longitude and other data as JSON
{
    if ($handle = fopen($currentLocationFile, "r")) {
        $contents = fread($handle, filesize($currentLocationFile));
        $contents = $contents . ',"now":"' . $now . '"';
        echo $contents;
        fclose($handle);
    } else {
        echo 'File access error: view=1';
    }
} else if ($checkfiletime == '1' || $checkfiletime == '2' || $checkfiletime == '3')
{
    if ($checkfiletime == '1') {
        $contents = filemtime($currentLocationFile);
    } elseif ($checkfiletime == '2') {
        $contents = filemtime($trackFile);
    } elseif ($checkfiletime == '3') {
        $contents = filemtime($routeFile);
    }
    if($contents)
        echo '"filetime":"' . $contents . '"';
    else
       echo 'File access error: checkfiletime=' . $checkfiletime;
} else if ($track == '1') # Output latitude, longitude as JSON for drawing track
{
    if ($handle = fopen($trackFile, "r")) {
        $contents = fread($handle, filesize($trackFile));
        //Get rid of last ","
        $contents = substr($contents, 0, strrpos($contents, ","));
        $contents = ' "type": "FeatureCollection",
                    "features": [
                        { "type": "Feature",
                        "geometry": {
                    "type": "MultiLineString",
                    "coordinates": [[ 
                    ' . $contents . '
                    ]]
                    }
                    }
                    ]
                    ';
        echo $contents;
        fclose($handle);
    } else {
        echo 'File access error: track=1';
    }
} else if ($route == '1') # Output route gpx location
{
    if (file_exists($routeFile)) {
        echo '"routeFile":"' . $routeFile . '"';
    } else {
        echo 'File access error: route=1';
    }
} else if ($reset == '1') # Delete track and location files
{
    unlink($currentLocationFile);
    unlink($trackFile);
} else if ($clearroute == '1') # Delete route.gpx
{
    unlink($storagefolder . 'route.gpx');
} else if ($updateroute == '1') # Upload route.gpx file
{
    if (isset($_POST["submit"])) {
        $file = $_FILES["fileToUpload"];
        $fileSize = $file["size"];
        if ($fileSize != 0) {
            if(move_uploaded_file($file["tmp_name"], $routeFile)){
                $uploadOk = 1;
            }
        }
    }

    if ($uploadOk != '1') {
        echo 'File upload error: updateroute=1';
    }
}

# Done!
echo '}';
