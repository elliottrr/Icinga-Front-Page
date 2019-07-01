<?php
if (($phpself != $mainpage) || ($phpself == ""))
{
        print "This page cannot be called directly.<br>What do you think you're doing?\n";
} else {

$host = "";

if (array_key_exists('host', $_GET))
        {
        $host = $_GET['host'];
        $host = substr($host,0,50);
        }

print "\n";

}
?>

