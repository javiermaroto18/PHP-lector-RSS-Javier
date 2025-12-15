<?php

$Repit=false;
$host="localhost";
$user="root";
$password="";

$link= mysqli_connect($host,$user,$password);
$tildes=$link->query("SET NAMES 'utf8'");
mysqli_select_db($link,'periodicos');