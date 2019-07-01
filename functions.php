<?php

# Functions go in here.

# Example Function
function example($var1,$var2)
{
	$var3 = "$var1 $var2";
	return $var3;
}

# Returns mysqli_object
function DBcall($DB,$query)
{
	$result = mysqli_query($DB,$query);
	return $result;
}

function callIcinga2API($method, $url, $data = false)
{
	global $i2APIuser;
	global $i2APIpasswd;
	global $i2APIurlBase;
	$headers	= array(
                        "Content-Type: application/json",
#                       "X-HTTP-Method-Override: GET",
                        "Accept: application/json");
	if ($data)
	{
		$datalen = strlen($data);
		array_push($headers, "Content-Length: $datalen");
	}
	$url		= $i2APIurlBase . $url;
	$curl		= curl_init();

#	print "APICall Data: $data<br>";
#	print "APICall URL: $url<br>";
#	print "APICall Method: $method<br>";
#	foreach ($headers as $line) { print "Header: $line<br>\n"; }

	switch ($method)
	{
		case "POST":
		curl_setopt($curl, CURLOPT_POST, 1);
			if ($data)
				curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
			break;
		case "PUT":
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
			if ($data)
			{
				curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
			}
			break;
		default:
#			if ($data)
#				$url = sprintf("%s?%s", $url, http_build_query($data));
	}

	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($curl, CURLOPT_USERPWD, "$i2APIuser:$i2APIpasswd");
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	$result = curl_exec($curl);
	curl_close($curl);
	return $result;
}

function APItest()
{
	if (array_key_exists('host', $_GET))
	{
		$host = $_GET['host'];
		{
			$DTstart = time() + 1000;
			$DTend = time() + 2000;
			print "<br>Retrieving data for $host...<br>";
			$filter = "match(\"Linux\",host.vars.os)";
			$filter = urlencode($filter);
#			$data = false;
#			$data = "{ \"filter\": \"host.vars.os == os\", \"filter_vars\": { \"os\": \"Linux\" } }";
#			$data = "{ \"filter\": \"host.vars.isVirtual == env\", \"filter_vars\": { \"env\": \"1\" } }";
#			$data = "{ \"filter\": \"host.vars.appEnvironment == env\", \"filter_vars\": { \"env\": \"UAT\" } }";
			$data = "{ \"start_time\": $DTstart, \"end_time\": $DTend, \"duration\": 1000, \"author\": \"it_elliott\", \"comment\": \"Downtime Test\" }";
#			$data = urlencode($data);
			$url = "actions/schedule-downtime?type=Service&filter=";
			#$url = "objects/hosts";
			$url = $url . $filter;
			$apidata = callIcinga2API("POST", $url, $data);
#			$apidata = callIcinga2API("GET", $url, $data);
			$dejson = json_decode($apidata, true);

			print "<hr>URL: $url<br><pre>$apidata<hr>";
			$test = $dejson['results'];
			foreach ($test as $item)
			{ 
				print $item['name'];
				print " - \""; 
				print $item['attrs']['display_name'];
				print "\"<br>"; 
#				print $item['attrs']['__name'];
#				print "<hr>"; 
#				var_dump($item); 
			}
#			var_dump($test);
			var_dump($dejson);
			print "</pre><hr>";
			




		}
	} else {
		print "Provide a host.<br>";
	}
}



function sessionMgr()
{
	global $phpself;
	global $sessionLength;
	global $sessionLife;
	global $adminsession;
	session_start();
	# First, let's see if there is an existing session that has timed out.
	if ((isset($_SESSION['timeout'])) && ($sessionLife > $sessionLength))
	{
		 session_unset();
		 session_destroy();
		 $loginMessage = "Session timed out.";
	#	 header("Location: $loginpage");
	}
	
	if (array_key_exists('do', $_GET))
	{
		if ($_GET['do'] == "logout")
		{
		$sessionUser = $_SESSION['username'];
		syslog(LOG_INFO,"User: $sessionUser, Action: Logout Complete");
		session_unset();
		session_destroy();
		$loginMessage = "Logout complete.";
		header("Location: /");
		}
	}
	
	if ((isset($_SESSION['username']) == true) && ($_SESSION['label'] == $adminsession))
	{
		$_SESSION['timeout'] = time();
	 	$sessionLife	   = $_SESSION['timeout'];
		$sessionAccess		= $_SESSION['access'];
		$sessionName		= $_SESSION['name'];
		print "<div class='Section'>\n";
		print "<div class='SectionHeader'>\n";
		print "$sessionName ($sessionAccess)\n";
		print "</div>\n";
		print "<div class='SectionOption'><a href='$phpself?do=logout'>Logout</a></div>\n";
		print "</div>\n";
		print "<div class='SectionSpacer'></div>\n";
	} else {
#		print "No session.";
#		include("./login.php");
		sessionLogin();

	}
}

function sessionLogin()
{
	global $phpself;
	global $binduser;
	global $bindpasswd;
	global $ldapserver;
	global $realm;
	global $ldapsearchbase;
	global $ldapattrs;
	global $AdminGroup;
	global $adminsession;
	global $mainpage;

	$loginOutput = "";
	$loginMessage = "";
	$user = "";
	$passwd = "";
	if (array_key_exists('do', $_POST))
	{
		if ($_POST['do'] == "login")
		{
			if (array_key_exists('username', $_POST))
			{
			  $user = addslashes($_POST['username']);
			}
			if (array_key_exists('passwd', $_POST))
			{
				$passwd = addslashes($_POST['passwd']);
			}
			#--------------------------------------------
			# connect to ldap server
			$ldapconn = ldap_connect($ldapserver) or die("Could not connect to LDAP server.<br>");
	
			if ($ldapconn) 
			{
			# bind to ldap server
				$ldapbind = ldap_bind($ldapconn, $binduser, $bindpasswd);
			# verify binding
				if ($ldapbind)
				{
					# Search for accounts
					$searchString = "(sAMAccountName=$user)";
					$searchResult = ldap_search($ldapconn,$ldapsearchbase, $searchString);
					$resultCount = ldap_count_entries($ldapconn,$searchResult);
	
					if ($resultCount == 0)
					{
						$loginOutput = "<div class='LoginError'>The OU Net ID '$user' was not found in Active Directory.</div>\n";
					}
	
					$userInfo = ldap_get_entries($ldapconn,$searchResult);
				} else {
					$loginOutput = "<div class='LoginError'>LDAP bind failed. This would be a good time to panic.</div>";
				}
				ldap_close($ldapconn);
			}
	
			# This would actually return results for multiple accounts, but the ldap search shouldn't return more than one...
			for ($idx=0; $idx<$userInfo["count"]; $idx++)
			{
				$gidNumber = $userInfo[$idx]["gidnumber"][0];
				#  $groupType = getAcctType($gidNumber);
				$samaccountname = $userInfo[$idx]["samaccountname"][0];
				$dn = $userInfo[$idx]["distinguishedname"][0];
				$displayname = $userInfo[$idx]["displayname"][0];
				$sn = $userInfo[$idx]["sn"][0];
				$memberOf = $userInfo[$idx]["memberof"];

				# Now we'll make sure this is an account that should be on IRADS.
				$MonitorAdmin = 0;
				foreach ($memberOf as $groupName)
				{
					if ($groupName == $AdminGroup) { $MonitorAdmin = 1; }
				}
				if ($MonitorAdmin == 1)
				{
					$ldapconnA = ldap_connect($ldapserver) or die("<div class='LoginError'>Could not connect to LDAP server.</div>");
					if ($ldapconn)
					{
						# bind to ldap server
						$ldapbindA = ldap_bind($ldapconnA, $dn, $passwd);
						# verify binding
						if (($ldapbindA) && ($passwd != ""))
						{
							# Set the session access variable based on theier group membership.
							if ($MonitorAdmin == 1) { $sessionAccess = "Admin"; }
	
							#session_start();
							$_SESSION['name']	= $displayname;
							$_SESSION['access']	= $sessionAccess;
							$_SESSION['username']	= $samaccountname;
							$_SESSION['timeout']	= time();
							$_SESSION['label']	= $adminsession;
	
							$sessionUser		= $_SESSION['username'];;
							$sessionAccess		= $_SESSION['access'];
							$sessionName		= $_SESSION['name'];
							$sessionLife		= $_SESSION['timeout'];
							$sessionLabel		= $_SESSION['label'];
							syslog(LOG_INFO,"Monitoring Admin: User: $user, Action: Logged in.");
	
							header("Location: $mainpage");
						} else {
							$loginOutput = "<div class='LoginError'>Authentication failed.</div>\n";
							syslog(LOG_INFO,"Monitoring Admin: User: $user, Action: Authentication failed.");
						}
					}
					ldap_close($ldapconnA);
					# If they're not a member of a required group, we'll just tell them it failed.
				} else {
					$loginOutput = "<div class='LoginError'>Authentication failed.</div>\n";
					syslog(LOG_INFO,"Monitoring Admin: User: $user, Action: Access attempted by unauthorized user.");
				}
			}
			# --------------------------------------------
		}
	}
	
	print "<div class='Section'><form method='POST' action='$phpself'>\n";
	print "<div class='SectionHeader'>Management Login</div>\n";
	print "<div class='SectionTextBox'>Username:<br><input name='username' type='text' value='' class=SelectBox></div>\n";
	print "<div class='SectionTextBox'>Password:<br><input name='passwd' type='password' value='' class=SelectBox></div>\n";
	print "<input type='hidden' name='do' value='login'>\n";
	print "<div class='SectionTextBox'><input type='Submit' value='Login'></div>";
	print "</form>\n";
	print "$loginMessage<p>\n";
	print "$loginOutput\n";
	print "</div>\n";
}

function statusCell($status)
{
	$state_css  = "statusOK";
	$state_text = "OK";
	if ($status == "1")
	{
		$state_css  = "statusWarn";
		$state_text = "Warning";
	}
	if ($status == "2")
	{
		$state_css  = "statusCrit";
		$state_text = "Critical";
	}
	if ($status == "3")
	{
		$state_css  = "statusUnkn";
		$state_text = "Unknown";
	}
	$output = "<div class='$state_css'>$state_text</div>\n";
	return $output;
}


function displayStatsTable($DB)
{
#	$query_hoststat	= "select count(host_object_id),current_state from icinga_hoststatus group by current_state;";
#	$query_hostack	= "select count(host_object_id) from icinga_hoststatus where problem_has_been_acknowledged=1;";
#	$query_svcstat	= "select count(service_object_id),current_state from icinga_servicestatus group by current_state;";
#	$query_svcack	= "select count(service_object_id) from icinga_servicestatus where problem_has_been_acknowledged=1;";
# New queries to support the icinga_obects.is_active field
	$query_hoststat = "SELECT count(host_object_id),current_state FROM icinga_hoststatus INNER JOIN icinga_objects ON icinga_hoststatus.host_object_id = icinga_objects.object_id WHERE is_active = '1' GROUP BY current_state;";
	$query_hostack  = "SELECT count(host_object_id) FROM icinga_hoststatus INNER JOIN icinga_objects ON icinga_hoststatus.host_object_id = icinga_objects.object_id WHERE is_active = '1' AND problem_has_been_acknowledged=1 GROUP BY current_state;";
	$query_svcstat  = "SELECT count(service_object_id),current_state FROM icinga_servicestatus INNER JOIN icinga_objects ON icinga_servicestatus.service_object_id = icinga_objects.object_id WHERE is_active = '1' GROUP BY current_state;";
	$query_svcack   = "SELECT count(service_object_id) FROM icinga_servicestatus INNER JOIN icinga_objects ON icinga_servicestatus.service_object_id = icinga_objects.object_id WHERE is_active = '1' AND problem_has_been_acknowledged=1 GROUP BY current_state;";


	# 0=OK, 1=Warning, 2=Critical, 3=Unknown
	$hoststat = array("0","0","0","0");
	$svcstat = array("0","0","0","0");

	# Host Stats
	$hoststat_result = $DB->query($query_hoststat);
	if ($hoststat_result->num_rows > 0) {
		while($row = $hoststat_result->fetch_assoc()) {
	#		print "Count: " . $row["count(host_object_id)"]. " - State: " . $row["current_state"]. "<br>";
			$col = $row["current_state"];
			$hoststat[$col] = $row["count(host_object_id)"];
		}
	}
	# Hosts Ack'd
	$hostack_result = $DB->query($query_hostack);
	$hostack = 0;
	if ($hostack_result->num_rows > 0) {
		while($row = $hostack_result->fetch_assoc()) {
	#		$col = $row["current_state"];
			$hostack = $row["count(host_object_id)"];
		}
	}
	# Service Stats
	$svcstat_result = $DB->query($query_svcstat);
	if ($svcstat_result->num_rows > 0) {
		while($row = $svcstat_result->fetch_assoc()) {
			$col = $row["current_state"];
			$svcstat[$col] = $row["count(service_object_id)"];
		}
	}
	# Services Ack'd
	$svcack_result = $DB->query($query_svcack);
	if ($svcack_result->num_rows > 0) {
		while($row = $svcack_result->fetch_assoc()) {
	#		$col = $row["current_state"];
			$svcack = $row["count(service_object_id)"];
		}
	}

	$hostTotal = $hoststat[0] + $hoststat[1] + $hoststat[2] + $hoststat[3];
	$svcTotal = $svcstat[0] + $svcstat[1] + $svcstat[2] + $svcstat[3];

	print "<div class='SectionHead'>Monitoring Overview</div>\n";
	print "<div class='divTable'>\n";
	print "<div class='divTableBody'>\n";
	print "<div class='divTableRow'>\n";
	print "<div class='divTableHeadRight'>&nbsp;</div>\n";
	print "<div class='divTableHeadRight'>Total</div>\n";
	print "<div class='divTableHeadRight'>Normal</div>\n";
	print "<div class='divTableHeadRight'>Warning</div>\n";
	print "<div class='divTableHeadRight'>Critical</div>\n";
	print "<div class='divTableHeadRight'>Unknown</div>\n";
	print "<div class='divTableHeadRight'>Ack'd</div>\n";
	print "</div>\n";
	print "<div class='divTableRow'>\n";
	print "<div class='divTableHead'>Hosts</div>\n";
	print "<div class='divTableCellRight'>$hostTotal</div>\n";
	print "<div class='divTableCell'><div class='statusOK'>$hoststat[0]</div></div>\n";
	print "<div class='divTableCell'><div class='statusWarn'>0</div></div>\n";
	print "<div class='divTableCell'><div class='statusCrit'>$hoststat[2]</div></div>\n";
	print "<div class='divTableCell'><div class='statusUnkn'>$hoststat[1]</div></div>\n";
	print "<div class='divTableCell'><div class='statusPlain'>$hostack</div></div>\n";
	print "</div>\n";
	print "<div class='divTableRow'>\n";
	print "<div class='divTableHead'>Services</div>\n";
	print "<div class='divTableCellRight'>$svcTotal</div>\n";
	print "<div class='divTableCell'><div class='statusOK'>$svcstat[0]</div></div>\n";
	print "<div class='divTableCell'><div class='statusWarn'>$svcstat[1]</div></div>\n";
	print "<div class='divTableCell'><div class='statusCrit'>$svcstat[2]</div></div>\n";
	print "<div class='divTableCell'><div class='statusUnkn'>$svcstat[3]</div></div>\n";
	print "<div class='divTableCell'><div class='statusPlain'>$svcack</div></div>\n";
	print "</div>\n";
	print "</div>\n";
	print "</div>\n";
}

function getHostInfo($DB,$host_object_id)
{
	$items		= array("appEnvironment","appName","appRole","os","isVirtual","customer","datacenter");
	$attributes	= array();
	foreach ($items as $attr)
	{
		$query = "SELECT varvalue FROM icinga_customvariables WHERE varname = '$attr' and object_id = '$host_object_id';";
		$idx = 0;
		$result = $DB->query($query);
		$attributes[$attr]	= "";
		if ($result->num_rows > 0) {
			while($row = $result->fetch_assoc()) {
				$attributes[$attr]	= $row["varvalue"];
			}
		}
	}
	return $attributes;
}


function displayServiceIssues($DB)
{
global $svcStatusBase;
global $hostStatusBase;
$query= "SELECT icinga_hosts.host_object_id,icinga_hosts.alias,icinga_hosts.display_name, icinga_services.display_name, icinga_servicestatus.current_state,
icinga_servicestatus.last_hard_state_change, icinga_servicestatus.output, icinga_servicestatus.is_flapping,
icinga_servicestatus.active_checks_enabled, icinga_servicestatus.scheduled_downtime_depth
FROM icinga_hosts INNER JOIN icinga_services ON icinga_hosts.host_object_id=icinga_services.host_object_id
INNER JOIN icinga_servicestatus ON icinga_services.service_object_id=icinga_servicestatus.service_object_id
INNER JOIN icinga_objects ON icinga_servicestatus.service_object_id = icinga_objects.object_id
WHERE icinga_servicestatus.problem_has_been_acknowledged='0' 
AND icinga_servicestatus.current_state IN ('1','2','3')
AND icinga_objects.is_active = '1';";

# Host Stats
$idx = 0;
$result = $DB->query($query);
if ($result->num_rows > 0) {

	print "<div class='SectionHead'>Service Issues</div>\n";
	print "<div class='divTable'>\n";
	print "<div class='divTableBody'>\n";
	print "<div class='divTableRow'>\n";
	print "<div class='divTableHead'>Hostname</div>\n";
	if (isset($_SESSION['username']) == true) { print "<div class='divTableHead'>Host Information</div>\n"; }
	print "<div class='divTableHead'>Service</div>\n";
	print "<div class='divTableHead'>Status</div>\n";
	print "<div class='divTableHead'>Last Change</div>\n";
	print "<div class='divTableHead' title='Is Flapping'><img src='/icingaweb2/img/icons/flapping.png'></div>\n";
	print "<div class='divTableHead' title='Active Checks Disabled'><img src='/icingaweb2/img/icons/active_checks_disabled.png'></div>\n";
	print "<div class='divTableHead' title='In Downtime'><img src='/icingaweb2/img/icons/in_downtime.png'></div>\n";
	print "</div>\n";
	while($row = $result->fetch_assoc()) {

#		foreach ($row as $key => $value) { print "$key<br>\n"; }
		$row_host_object_id	= $row["host_object_id"];
		$row_host_alias		= $row["alias"];
		$row_host_name		= $row["display_name"];
		$row_svc_name		= $row["display_name"];
		$row_svc_state		= $row["current_state"];
		$row_svc_last_chg	= $row["last_hard_state_change"];
		$row_svc_status		= $row["output"];
		$row_svc_flapping	= $row["is_flapping"];
		$row_svc_active		= $row["active_checks_enabled"];
		$row_svc_downtime	= $row["scheduled_downtime_depth"];

		$row_css		= "divTableRow";
		if ($idx%2 !== 0) { $row_css = "divTableRowAlt"; }

		$svc_status	= statusCell($row_svc_state);
		$svc_flapping	= "&nbsp;";
		$svc_active	= "&nbsp;";
		$svc_downtime	= "&nbsp;";
		if ($row_svc_flapping == "1")
		{
			$svc_flapping	= "<img title='Service Is Flapping' src='/icingaweb2/img/icons/flapping.png'>";
		}
		if ($row_svc_active == "0")
		{
			$svc_active	= "<img title='Active Checks are disabled' src='/icingaweb2/img/icons/active_checks_disabled.png'>";
		}
		if ($row_svc_downtime == "1")
		{
			$svc_downtime	= "<img title='Service is in downtime' src='/icingaweb2/img/icons/in_downtime.png'>";
		}

		print "<div class='$row_css'>\n";
		if (isset($_SESSION['username']) == true) 
		{
			$attributes	= getHostInfo($DB,$row_host_object_id);
			$row_os		= "--";
			$row_appEnvironment	= ucfirst($attributes["appEnvironment"]);
			$row_appName	= $attributes["appName"];
			$row_appRole	= $attributes["appRole"];
			$row_os		= $attributes["os"];
			$row_vm		= "Physical";
			if ($attributes["isVirtual"] == '1') { $row_vm = "Virtual"; }
			$row_customer	= $attributes["customer"];
			$row_datacenter	= $attributes["datacenter"];
			$row_title	= "Application:\t$row_appName\nEnvironment:\t$row_appEnvironment\nRole:\t\t$row_appRole\nOS:\t\t\t$row_os";
			$row_title	= "$row_title\nType:\t\t$row_vm\nDatacenter:\t$row_datacenter";
			$row_svc_URL = $svcStatusBase . "host=$row_host_alias&service=$row_svc_name";
			$row_host_URL = $hostStatusBase . "host=$row_host_alias";

			print "<div class='divTableCellHostname'><a href='$row_host_URL'>$row_host_alias</a></div>\n";
			print "<div class='divTableCellHostname' title='$row_title'>$row_customer, $row_appName, $row_appEnvironment</div>\n"; 
			print "<div class='divTableCellService' title='$row_svc_status'><a href='$row_svc_URL'>$row_svc_name</a></div>\n";
		} else {
			print "<div class='divTableCellHostname'>$row_host_alias</div>\n";
			print "<div class='divTableCellService' title='$row_svc_status'>$row_svc_name</div>\n";
		}
		print "<div class='divTableCell'>$svc_status</div>\n";
		print "<div class='divTableCellDateTime'>$row_svc_last_chg</div>\n";
		print "<div class='divTableCellNarrow'>$svc_flapping</div>\n";
		print "<div class='divTableCellNarrow'>$svc_active</div>\n";
		print "<div class='divTableCellNarrow'>$svc_downtime</div>\n";
		print "</div>\n";
		$idx++;
	}
   print "</div>\n";
   print "</div>\n";
   } else {
	print "<div class='SectionHead'>No Current Service Issues</div>\n";
   }

}


function displayHostIssues($DB)
{
global $hostStatusBase;
$query = "SELECT icinga_hosts.host_object_id,icinga_hosts.alias,icinga_hosts.display_name, icinga_hoststatus.current_state, icinga_hoststatus.last_hard_state_change,
icinga_hoststatus.output, icinga_hoststatus.is_flapping, icinga_hoststatus.active_checks_enabled, icinga_hoststatus.scheduled_downtime_depth
FROM icinga_hosts INNER JOIN icinga_hoststatus ON icinga_hosts.host_object_id=icinga_hoststatus.host_object_id
INNER JOIN icinga_objects ON icinga_hoststatus.host_object_id = icinga_objects.object_id
WHERE icinga_hoststatus.problem_has_been_acknowledged='0' 
AND icinga_hoststatus.current_state IN ('1','2','3')
AND icinga_objects.is_active = '1';";


# Host Stats
$idx = 0;
$result = $DB->query($query);
if ($result->num_rows > 0) {

	print "<div class='SectionHead'>Host Issues</div>\n";
	print "<div class='divTable'>\n";
	print "<div class='divTableBody'>\n";
	print "<div class='divTableRow'>\n";
	print "<div class='divTableHead'>Hostname</div>\n";
	if (isset($_SESSION['username']) == true) 
	{
		print "<div class='divTableHead'>Service</div>\n"; 
		print "<div class='divTableHead'>Application</div>\n"; 
		print "<div class='divTableHead'>Environment</div>\n"; 
		print "<div class='divTableHead'>Location</div>\n"; 
		print "<div class='divTableHead'>&nbsp;</div>\n"; 
	}
	print "<div class='divTableHead'>Status</div>\n";
	print "<div class='divTableHead'>Last Change</div>\n";
	print "<div class='divTableHead' title='Is Flapping'><img src='/icingaweb2/img/icons/flapping.png'></div>\n";
	print "<div class='divTableHead' title='Active Checks Disabled'><img src='/icingaweb2/img/icons/active_checks_disabled.png'></div>\n";
	print "<div class='divTableHead' title='In Downtime'><img src='/icingaweb2/img/icons/in_downtime.png'></div>\n";
	print "</div>\n";
	while($row = $result->fetch_assoc()) {

#		foreach ($row as $key => $value) { print "$key<br>\n"; }
		$row_host_object_id	= $row["host_object_id"];
		$row_host_alias		= $row["alias"];
		$row_host_name		= $row["display_name"];
		$row_svc_state		= $row["current_state"];
		$row_svc_last_chg	= $row["last_hard_state_change"];
		$row_svc_status		= $row["output"];
		$row_svc_flapping	= $row["is_flapping"];
		$row_svc_active		= $row["active_checks_enabled"];
		$row_svc_downtime	= $row["scheduled_downtime_depth"];

		$row_css		= "divTableRow";
		if ($idx%2 !== 0) { $row_css = "divTableRowAlt"; }
		if ($row_svc_state == "1") { $row_svc_state = "2"; }

		$svc_status	= statusCell($row_svc_state);
		$svc_flapping	= "&nbsp;";
		$svc_active	= "&nbsp;";
		$svc_downtime	= "&nbsp;";
		if ($row_svc_flapping == "1")
		{
			$svc_flapping	= "<img title='Host Is Flapping' src='/icingaweb2/img/icons/flapping.png'>";
		}
		if ($row_svc_active == "0")
		{
			$svc_active	= "<img title='Active Checks are disabled' src='/icingaweb2/img/icons/active_checks_disabled.png'>";
		}
		if ($row_svc_downtime == "1")
		{
			$svc_downtime	= "<img title='Host is in downtime' src='/icingaweb2/img/icons/in_downtime.png'>";
		}

		print "<div class='$row_css'>\n";
		if (isset($_SESSION['username']) == true) 
		{
			$attributes	= getHostInfo($DB,$row_host_object_id);
			$row_os		= "--";
			$row_appEnvironment	= ucfirst($attributes["appEnvironment"]);
			$row_appName	= $attributes["appName"];
			$row_appRole	= $attributes["appRole"];
			$row_os		= $attributes["os"];
			$row_vm		= "";
			if ($attributes["isVirtual"] == '1') { $row_vm = "VM"; }
			$row_customer	= $attributes["customer"];
			$row_datacenter	= $attributes["datacenter"];
			$row_title	= "Application:\t$row_appName\nEnvironment:\t$row_appEnvironment\nRole:\t\t$row_appRole\nOS:\t\t\t$row_os";
			$row_title	= "$row_title\nType:\t\t$row_vm\nDatacenter:\t$row_datacenter";
			$row_host_URL = $hostStatusBase . "host=$row_host_alias";

			print "<div class='divTableCellHostname'><a href='$row_host_URL'>$row_host_alias</a></div>\n";
			print "<div class='divTableCellInfo' title='$row_title'>$row_customer</div>\n"; 
			print "<div class='divTableCellInfo' title='$row_title'>$row_appName</div>\n"; 
			print "<div class='divTableCell' title='$row_title'>$row_appEnvironment</div>\n"; 
			print "<div class='divTableCell' title='$row_title'>$row_datacenter</div>\n"; 
			print "<div class='divTableCell' title='$row_title'>$row_vm</div>\n"; 
		} else {
			print "<div class='divTableCellHostname'>$row_host_alias</div>\n";
		}
#		print "<div class='divTableCellHostname'>$row_host_alias</div>\n";
		print "<div class='divTableCell'>$svc_status</div>\n";
		print "<div class='divTableCellDateTime'>$row_svc_last_chg</div>\n";
		print "<div class='divTableCellNarrow'>$svc_flapping</div>\n";
		print "<div class='divTableCellNarrow'>$svc_active</div>\n";
		print "<div class='divTableCellNarrow'>$svc_downtime</div>\n";
		print "</div>\n";
		$idx++;
	}
   print "</div>\n";
   print "</div>\n";
#   } else {
#	print "<div class='SectionHead'>No Current Host Issues</div>\n";
   }

}


function displayDowntimes($DB)
{
$query = "SELECT scheduled_start_time,scheduled_end_time,comment_data,author_name,COUNT(*) AS records FROM icinga_scheduleddowntime GROUP BY scheduled_start_time,scheduled_end_time,comment_data,author_name;";

# Host Stats
$idx = 0;
$result = $DB->query($query);
if ($result->num_rows > 0) {

	print "<div class='SectionHead'>Scheduled Downtimes</div>\n";
	print "<div class='divTable'>\n";
	print "<div class='divTableBody'>\n";
	print "<div class='divTableRow'>\n";
	print "<div class='divTableHead'>Description</div>\n";
	print "<div class='divTableHead'>Created By</div>\n";
	print "<div class='divTableHead'>Start Time</div>\n";
	print "<div class='divTableHead'>End Time</div>\n";
	print "<div class='divTableHead'>Items</div>\n";
	print "</div>\n";
	while($row = $result->fetch_assoc()) {

#		foreach ($row as $key => $value) { print "$key<br>\n"; }
		$row_start_time		= $row["scheduled_start_time"];
		$row_end_time		= $row["scheduled_end_time"];
		$row_author		= $row["author_name"];
		$row_description	= $row["comment_data"];
		$row_item_count		= $row["records"];

		$row_css		= "divTableRow";
		if ($idx%2 !== 0) { $row_css = "divTableRowAlt"; }

		print "<div class='$row_css'>\n";
		print "<div class='divTableCellService'>$row_description</div>\n";
		print "<div class='divTableCellService'>$row_author</div>\n";
		print "<div class='divTableCellDateTime'>$row_start_time</div>\n";
		print "<div class='divTableCellDateTime'>$row_end_time</div>\n";
		print "<div class='divTableCell'>$row_item_count</div>\n";
		print "</div>\n";
		$idx++;
	}
   print "</div>\n";
   print "</div>\n";
#   } else {
#	print "<div class='SectionHead'>No Current Host Issues</div>\n";
   }

}


function displayNotificationsEnabled($DB)
{
	$query = "SELECT notifications_enabled FROM icinga_programstatus;";
	$result = $DB->query($query);
	if ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
			$notifenabled	= $row["notifications_enabled"];
		}
	
		if ($notifenabled == "0")
		{
			print "<div class='TopNotice'>Notifications are disabled.</div>\n";
		}
	}
}

# Returns associative array of host variables
function getHostFields($DB)
{
	$output = array();
	$query = "SELECT DISTINCT(icinga_host_var.varname) AS varName,director_datafield.caption AS varLabel,director_datafield_setting.datafield_id AS varID,director_datafield.datatype AS varType FROM icinga_host_var INNER JOIN director_datafield ON icinga_host_var.varname = director_datafield.varname INNER JOIN director_datafield_setting ON director_datafield.id = director_datafield_setting.datafield_id;";
	$result = mysqli_query($DB,$query);
	if ($result->num_rows > 0) 
	{
		while($row = $result->fetch_assoc()) 
		{
			$row_id		= $row["varID"];
			$row_name	= $row["varName"];
			$row_label	= $row["varLabel"];
			$row_type	= $row["varType"];
			$row_type	= substr($row_type, 40);
			array_push($output,array($row_id,$row_name,$row_label,$row_type));
		}
	}
	return $output;
}

function getVarListID($DB,$id)
{
	$output = "-1";
	$query = "SELECT setting_value FROM director_datafield_setting WHERE setting_name = 'datalist_id' AND datafield_id = '$id';";
	$result = mysqli_query($DB,$query);
	if ($result->num_rows == 1) 
	{
		$row = $result->fetch_assoc();
		$output = $row["setting_value"];
	}
	return $output;
}

function getVarList($DB,$id)
{
	$output = array();
	$listID = getVarListID($DB,$id);
	$query = "SELECT entry_name,entry_value FROM director_datalist_entry WHERE list_id = '$listID';";
	$result = mysqli_query($DB,$query);
	if ($result->num_rows > 0) 
	{
		while($row = $result->fetch_assoc()) 
		{
			$row_name	= $row["entry_name"];
			$row_value	= $row["entry_value"];
			$output[$row_name] = $row_value;
		}
	}
	return $output;
}

function getHostExists($DB,$name,$addr)
{
	$output = array();
	$query = "SELECT host_id,alias,display_name,address FROM icinga_hosts WHERE alias = '$name' OR address = '$addr';";
	$result = mysqli_query($DB,$query);
	if ($result->num_rows > 0) 
	{
		while($row = $result->fetch_assoc()) 
		{
			$row_id		= $row["host_id"];
			$row_alias	= $row["alias"];
			$row_disp_name	= $row["display_name"];
			$row_address	= $row["address"];
			array_push($output,array($row_id,$row_alias,$row_disp_name,$row_address));
		}
	}
	return $output;
}

