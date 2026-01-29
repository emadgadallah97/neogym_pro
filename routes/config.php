

<?php

date_default_timezone_set("egypt");

$host="127.0.0.1";

$user="mshendy";

$password="mshendy";

$DB_name="vaccine_db";



$conn=mysqli_connect($host,$user,$password,$DB_name);

mysqli_set_charset($conn,"utf8");

if(!$conn)

{

	echo mysqli_connect_error("خطا في الاتصال"). mysqli_connect_errno();

}

function close_d()

{

	global $conn;

	mysqli_close($conn);

}



?>

