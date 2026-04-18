<?php
global $conn;
function connectDB(){
    global $conn;
    if(!$conn){
        $conn = mysqli_connect("34.126.147.241","md162","Logistic@2026","logistics_db") 
                or die("Can not connect to database".mysqli_connect_error());
        mysqli_set_charset($conn, "utf8mb4");
}
}

function disconnectDB(){
    global $conn;
    if($conn){
        mysqli_close($conn);
    }
}
?>