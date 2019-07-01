<?php


function buildHostList($vars)
{
	global $datetime;
	$authorName	= $_SESSION['name'];
	$authorUser	= $_SESSION['username'];
	$startEpoch	= time();
	$endEpoch	= $startEpoch + 7200;
	$start_time	= date('Y-m-d H:i:s', $startEpoch);
	$end_time	= date('Y-m-d H:i:s', $endEpoch);
	$idx = 1;
	$cnt = sizeof($vars);
	$url = "objects/hosts?";
	$data = false;
	foreach($vars as $field)
	{
		$value = $_GET[$field];
		$value = substr($value,0,50);
		if (!(($value == "---") || ($value == "")))
		{
			$filter = "match(\"$value\",host.vars.$field)";
			$filter = urlencode($filter);
			$url = $url . "filter=$filter";
			if ($idx < $cnt) { $url = $url . "&"; }
		}
		$idx++;
	}
	$apidata = callIcinga2API("GET", $url, $data);
	$dejson = json_decode($apidata, true);

	$result = $dejson['results'];
	$result_count = count($result);


	if ($result_count > 0)
	{
		print "<form method='GET' action='$phpself'>\n";
		print "<input type='hidden' name='do' value='scheduledowntime'>\n";
		print "<input type='hidden' name='submit' value='show'>\n";
	
		print "Step 2: Additional Information";
		print "<div class='divTable'>\n";
		print "<div class='divTableBody'>\n";
		print "<div class='divTableRow'>";
		print "<div class='divTableHead'>Start Time</div>";
		print "<div class='SectionTextBox'><input name='start_time' type='text' value='$start_time' class='SectionTextBox'></div>\n";
		print "</div>\n";
		print "<div class='divTableRow'>";
		print "<div class='divTableHead'>End Time</div>";
		print "<div class='SectionTextBox'><input name='end_time' type='text' value='$end_time' class='SectionTextBox'></div>\n";
		print "</div>\n";
		print "<div class='divTableRow'>";
		print "<div class='divTableHead'>Comment</div>";
		print "<div class='SectionTextBox'><input name='comment' type='text' value='Scheduled Downtime' class='SectionTextBox'></div>\n";
		print "</div>\n";
		print "<div class='divTableRow'>";
		print "<div class='divTableHead'>Submitted By</div>";
		print "<div class='SectionTextBox'>$authorName ($authorUser)</div>\n";
		print "<input type='hidden' name='author' value='$authorUser'>\n";
		print "</div>\n";
		print "</div>\n";
		print "</div>\n";
	
		print "<br>\n";
		print "<div class='SectionHead'>$result_count Hosts matched by filter</div>\n";
		print "<div class='divTable'>\n";
		print "<div class='divTableBody'>\n";
		print "<div class='divTableRow'>";
		print "<div class='divTableHead'>Status</div>";
		print "<div class='divTableHead'>Hostname</div>";
		print "<div class='divTableHead'>Address</div>";
		print "<div class='divTableHead'>OS</div>";
		print "<div class='divTableHead'>Application</div>";
		print "<div class='divTableHead'>Environment</div>";
		print "</div>\n";
	
		foreach ($result as $item)
		{
			$host_name	= $item['name'];
			$display_name	= $item['attrs']['display_name'];
			$address	= $item['attrs']['address'];
			$host_check	= $item['attrs']['last_check_result']['exit_status'];
			$host_os	= $item['attrs']['vars']['os'];
			$host_app_name	= $item['attrs']['vars']['appName'];
			$host_app_env	= $item['attrs']['vars']['appEnvironment'];
			$host_status	= statusCell($host_check);
			print "<div class='divTableRow'>";
			print "<div class='divTableCellNarrow'>$host_status</div>";
			print "<div class='divTableCellHostname'>$host_name</div>";
			print "<div class='divTableCell'>$address</div>";
			print "<div class='divTableCell'>$host_os</div>";
			print "<div class='divTableCell'>$host_app_name</div>";
			print "<div class='divTableCell'>$host_app_env</div>";
			print "</div>\n";
			print "<input type='hidden' name='host[]' value='$host_name'>\n";
		}
		print "</div>\n";
		print "</div>\n";
		print "<br>\n";
	
		print "<div class='SectionTextBox'><input type='Submit' value='Submit'></div>";
		print "</form>\n";
	} else {
		print "<div class='SectionHead'>$result_count Hosts matched by filter</div>\n";
	}
}


function submitDowntime()
{
	$host_list	= $_GET['host'];
	$start_time	= $_GET['start_time'];
	$start_time	= strip_tags(substr($start_time,0,24));
	$start_epoch	= strtotime($start_time);
	$end_time	= $_GET['end_time'];
	$end_time	= strip_tags(substr($end_time,0,24));
	$end_epoch	= strtotime($end_time);
	$comment	= $_GET['comment'];
	$comment	= strip_tags(substr($comment,0,50));
	$author		= $_GET['author'];
	$author		= strip_tags(substr($author,0,24));

	print "<div class='SectionHead'>Schedule Downtime</div>\n";
	print "<div class='divTable'>\n";
	print "<div class='divTableBody'>\n";
	print "<div class='divTableRow'>";
	print "<div class='divTableHead'>Start:</div><div class='divTableCellHostname'>$start_time</div>\n";	
	print "</div>\n";
	print "<div class='divTableRow'>";
	print "<div class='divTableHead'>End:</div><div class='divTableCellHostname'>$end_time</div>\n";	
	print "</div>\n";
	print "<div class='divTableRow'>";
	print "<div class='divTableHead'>Comment:</div><div class='divTableCellHostname'>$comment</div>\n";	
	print "</div>\n";
	print "<div class='divTableRow'>";
	print "<div class='divTableHead'>Author:</div><div class='divTableCellHostname'>$author</div>\n";
	print "</div>\n";
	print "</div>\n";
	print "</div>\n";
	print "<br>\n";

	foreach ($host_list as $host)
	{
		$host     = strip_tags(substr($host,0,30));
		print "<div class='SectionHead'>Downtimes for $host</div>\n";

		$url = "actions/schedule-downtime?type=Host&host=$host";
		$data = "{ \"start_time\": \"$start_epoch\", \"end_time\": \"$end_epoch\", \"duration\": 10, \"author\": \"$author\", \"comment\": \"$comment\" }";
		$apidata = callIcinga2API("POST", $url, $data);
		$dejson = json_decode($apidata, true);
		$status = $dejson['results'][0]['status'];
		print "<div class='SmallText'>$status</div>\n";

		$filter = "match(\"$host\",host.name)";
		$filter = urlencode($filter);
		$url = "actions/schedule-downtime?type=Service&filter=";
		$url = $url . $filter;
		$data = "{ \"start_time\": \"$start_epoch\", \"end_time\": \"$end_epoch\", \"duration\": 10, \"author\": \"$author\", \"comment\": \"$comment\" }";
		$apidata = callIcinga2API("POST", $url, $data);
		$dejson = json_decode($apidata, true);
		$result = $dejson['results'];
		foreach ($result as $svc)
		{
			$status = $svc['status'];
			print "<div class='SmallText'>$status</div>\n";
		}
		print "<br>\n";
#		print "<hr><pre>\n";
#		var_dump($dejson);
#		print "</pre><hr>";
	}

}


###################################################################################################
# Main Code
###################################################################################################
if (($phpself != $mainpage) || ($phpself == ""))
{
	print "This page cannot be called directly.<br>What do you think you're doing?\n";
} else if ((isset($_SESSION['username']) == true) && ($_SESSION['label'] == $adminsession)) {

	$directorDB = new mysqli("$dbhost", $username, $password, $databaseDIR);
	if ($directorDB->connect_error) { die("Connection failed: " . $conn->connect_error); }

	if (array_key_exists('submit', $_GET))
	{
		submitDowntime();
#		$host = $_GET['dt'];
#		$host = substr($host,0,50);
	} else {
		$query1vars = array();
		print "<div class='SectionHead'>Schedule Downtime</div>\n";
		print "Step 1: Select Hosts";
		print "<form method='GET' action='$phpself'>\n";
		print "<input type='hidden' name='do' value='scheduledowntime'>\n";
		print "<input type='hidden' name='step2' value='show'>\n";
		print "<div class='divTable'>\n";
		print "<div class='divTableBody'>\n";
		$hostFields = getHostFields($directorDB);
		foreach ($hostFields as $field)
		{
			print "<div class='divTableRow'>\n";
			list($row_id,$row_name,$row_label,$row_type) = $field;
			print "<div class='divTableHead'>$row_label";
#			print "<br>[$row_name,$row_type]";
			print "</div>\n";
			if ($row_type == "Datalist")
			{
#				print "<div class='divTableCellService'>\n";
				print "<div class='SectionTextBox'><select name='$row_name' class='SelectBox'>\n";
				print "<option value='---'>&nbsp;</option>\n";
				$listID = getVarListID($directorDB,$row_id);
	#			print "-> Datalist ID $listID<br>\n";
				$valueList = getVarList($directorDB,$row_id);
				$value = "-1";
				if (array_key_exists($row_name, $_GET))
				{
					$value = $_GET[$row_name];
					$value = substr($value,0,20);
					array_push($query1vars,$row_name);
				}
				foreach ($valueList as $val => $name)
				{
					if ($val == $value)
					{
						print "<option value='$val' selected>$name</option>\n";
					} else {
						print "<option value='$val'>$name</option>\n";
					}
				}
				print "</select>\n";
				print "</div>\n";
#				print "</div>\n";
			}
			if ($row_type == "String")
			{
				$value = "";
				if (array_key_exists($row_name, $_GET))
				{
					$value = $_GET[$row_name];
					$value = substr($value,0,50);
					array_push($query1vars,$row_name);
				}
				print "<div class='SectionTextBox'>";
				print "<input name='$row_name' type='text' value='$value' class='SectionTextBox'></div>\n";


			}
			print "</div>\n";
		}
		print "</div>\n";
		print "</div>\n";
		print "<div class='SectionTextBox'><input type='Submit' value='Search'></div>";
		print "</form>\n";
		if (array_key_exists('step2', $_GET))
		{
			print "<br>\n";
			buildHostList($query1vars);
		}
	}

	mysqli_close($directorDB);
} else {
	print "Must be logged in...";
}
?>

