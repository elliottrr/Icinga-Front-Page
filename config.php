<?php
date_default_timezone_set('America/Chicago');
$myZone         = "OKZ029";
$myCounty       = "OKC027";
$myCountyN      = "CLEVELAND";
$phpself        = $_SERVER['PHP_SELF'];
$remoteaddr     = $_SERVER['REMOTE_ADDR'];
$dateRaw        = time();
$date           = date("Y-m-d");
$time           = date("H:i:s");
$datetime       = date("Y-m-d H:i:s");
$time12hr       = date("g:i:s A");
$longdate       = date("l, F j, Y");
$longdatetime   = date("l, M d, Y H:i:s");
$dflClass       = "tableRegular";
$username       = "icinga";
$password       = "icinga";
$database       = "icinga";
$databaseDIR	= "director";
$databaseCACTI	= "cacti";
$dbhost         = "localhost";


# Icinga2 API Settings
$i2APIuser	= "webAdmin";
$i2APIpasswd	= "webAdminPasswd";
$i2APIurlBase	= "https://localhost:5665/v1/";
$svcStatusBase	= "https://<hostname>/icingaweb2/monitoring/service/show?";
$hostStatusBase	= "https://<hostname>/icingaweb2/monitoring/host/show?";

# Session info
$sessionLength  = 7200; # two hours in seconds
if (isset($_SESSION['timeout']))
{
        $sessionLife      = time() - $_SESSION['timeout'];
        $sessionRemaining = $sessionLength - $sessionLife;
        $sessionUser      = $_SESSION['username'];
        $sessionAccess    = $_SESSION['access'];
        $sessionName      = $_SESSION['name'];
}
$loginMessage = "";
$AdminGroup = "<LDAP Group DN>";
$mainpage       = "/index.php";
$loginpage      = "/index.php";
$adminsession   = "Systems-Monitoring";

# LDAP Connection Stuff
$binduser='<Bind Username>';
$bindpasswd = "<Bind Password>";
$ldapserver="ldaps://<LDAP server>:636";
$realm="<AD Domain Name>";
$ldapsearchbase = "<LDAP Search Base>";
#                uniqueID, GroupID, Dotted Name, Unix home dir, Public Web URL, Last name
$ldapattrs = "uidNumber,gidNumber,extensionAttribute6,extensionAttribute12,wWWHomePage,sn";

