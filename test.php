<?php session_start(); $_SESSION["user"]=["role"=>"admin","id"=>"dummy"]; $_GET["period"]="month"; ob_start(); require "api/reports/sales.php"; $out=ob_get_clean(); print_r($out);
