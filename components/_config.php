<?php

//Para DEBUG
$debug = false;
function debug($message){
    global $debug;
    if($debug){
        echo $message . "\n";
    }
}

/*Imprimir mi JSON*/
function debugPrint_r($array){
    global $debug;
    if ($debug){
        echo '<pre>';
        print_r($array);
        echo '</pre>';
    }
}
/*Connectar de la base de datos */



// Database configuration
    $host = 'localhost';
    $dbname = 'events_db';
    $user = 'root';
    $pass = 'root'; // Asegúrate que esta es tu contraseña correcta


    // Establish database connection using MySQLi procedural
    $conn = mysqli_connect($host, $user, $pass, $dbname);


    // Check connection
    if (mysqli_connect_errno()) {
        echo "Database Connection Error: " . mysqli_connect_error();
        die();
    }
?>