<?php



###################################################################################################
# Main Code
###################################################################################################
if (($phpself != $mainpage) || ($phpself == ""))
{
	print "This page cannot be called directly.<br>What do you think you're doing?\n";
} else if ((isset($_SESSION['username']) == true) && ($_SESSION['label'] == $adminsession)) {

	$directorDB = new mysqli("$dbhost", $username, $password, $databaseDIR);
	if ($directorDB->connect_error) { die("Connection failed: " . $conn->connect_error); }

	$submitReady = array();
	$query1vars = array();
	if (array_key_exists('alias', $_GET))
	{
		$alias = $_GET['alias'];
		$alias = strip_tags(substr($alias,0,50));
		$alias = trim($alias);
		$query1vars['alias'] = $alias;
		if ($alias == "") { array_push($submitReady,"Alias"); }
	}
	if (array_key_exists('display_name', $_GET))
	{
		$host_disp_name = $_GET['display_name'];
		$host_disp_name = strip_tags(substr($host_disp_name,0,50));
		$host_disp_name = trim($host_disp_name);
		$query1vars['display_name'] = $host_disp_name;
		if ($host_disp_name == "") { array_push($submitReady,"Display Name"); }
	}
	if (array_key_exists('address', $_GET))
	{
		$address = $_GET['address'];
		$address = strip_tags(substr($address,0,20));
		$address = trim($address);
		$query1vars['address'] = $address;
		if ($address == "") { array_push($submitReady,"IP Address"); }
	}
	print "<div class='SectionHead'>Add host to be monitored</div>\n";
	print "<form method='GET' action='$phpself'>\n";
	print "<input type='hidden' name='do' value='addhost'>\n";
	print "<input type='hidden' name='submit' value='do'>\n";
	print "<div class='divTable'>\n";
	print "<div class='divTableBody'>\n";


	print "<div class='divTableRow'>\n";
	print "<div class='divTableHead'>Hostname</div>\n";
	print "<div class='divTableInput'><input name='alias' type='text' value='$alias' class='SectionTextBox'>\n";
	if ($alias == "") { print "*"; }
	print "</div>\n";
	print "</div>\n";

	print "<div class='divTableRow'>\n";
	print "<div class='divTableHead'>Display Name</div>\n";
	print "<div class='divTableInput'><input name='display_name' type='text' value='$host_disp_name' class='SectionTextBox'>\n";
	if ($host_disp_name == "") { print "*"; }
	print "</div>\n";
	print "</div>\n";

	print "<div class='divTableRow'>\n";
	print "<div class='divTableHead'>IP Address</div>\n";
	print "<div class='divTableInput'><input name='address' type='text' value='$address' class='SectionTextBox'>\n";
	if ($address == "") { print "*"; }
	print "</div>\n";
	print "</div>\n";

	$hostFields = getHostFields($directorDB);
	foreach ($hostFields as $field)
	{
		print "<div class='divTableRow'>\n";
		list($row_id,$row_name,$row_label,$row_type) = $field;
		print "<div class='divTableHead'>$row_label";
		print "</div>\n";
		if ($row_type == "Datalist")
		{
			print "<div class='divTableInput'><select name='$row_name' class='SelectBox'>\n";
			print "<option value='---'>&nbsp;</option>\n";
			$listID = getVarListID($directorDB,$row_id);
			$valueList = getVarList($directorDB,$row_id);
			$value = "-1";
			if (array_key_exists($row_name, $_GET))
			{
				$value = $_GET[$row_name];
				$value = substr($value,0,20);
				$query1vars["vars.$row_name"] = $value;
				if ($value == "---") { array_push($submitReady,$row_label); }
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
			if ($value == "---") { print "*"; }
			print "</div>\n";
		}
		if ($row_type == "String")
		{
			$value = "";
			if (array_key_exists($row_name, $_GET))
			{
				$value = $_GET[$row_name];
				$value = substr($value,0,50);
				$query1vars["vars.$row_name"] = $value;
				if ($value == "") { array_push($submitReady,$row_label); }
			}
			print "<div class='divTableInput'>";
			print "<input name='$row_name' type='text' value='$value' class='SectionTextBox'>\n";
			if ($value == "") { print "*"; }
			print "</div>\n";

		}
		print "</div>\n";
	}
	print "</div>\n";
	print "</div>\n";
	print "<div class='SectionTextBox'><input type='Submit' value='Submit'></div>";
	print "</form>\n";

	if (array_key_exists('submit', $_GET))
	{
		$subReadyCnt = count($submitReady);
		$hostExists = getHostExists($icingaDB,$alias,$address);
		$existCount = count($hostExists);
#		print "$subReadyCnt - $existCount<br>\n";
		print "<br>\n";
		if (count($submitReady) > 0)
		{
			print "<B>Error:</B> Cannot add host. The following fields are not correct:<ul>\n";
			foreach ($submitReady as $field)
			{
				print "<li>$field</li>\n";
			}
			print "</ul>\n";
		} else if (count($hostExists) > 0) {
			print "Cannot add host. Submitted information conflicts with the below existing systems:<br>\n";
			print "<div class='divTable'>\n";
			print "<div class='divTableBody'>\n";
			print "<div class='divTableRow'>\n";
			print "<div class='divTableHead'>ID</div>\n";
			print "<div class='divTableHead'>Alias</div>\n";
			print "<div class='divTableHead'>Display Name</div>\n";
			print "<div class='divTableHead'>Address</div>\n";
			print "</div>\n";
			foreach ($hostExists as $field)
			{
				list($row_id,$row_alias,$row_disp_name,$row_address) = $field;
				print "<div class='divTableRow'>\n";
				print "<div class='divTableCell'>$row_id</div>\n";
				print "<div class='divTableCellService'>$row_alias</div>\n";
				print "<div class='divTableCellHostname'>$row_disp_name</div>\n";
				print "<div class='divTableCell'>$row_address</div>\n";
				print "</div>\n";
			}
			print "</div>\n";
			print "</div>\n";
		} else {
			$data	= "{ \"templates\": [ \"generic-host\" ], \"attrs\": { \"check_command\": \"hostalive\", ";
			$idx	= 1;
			$alias	= $query1vars['alias'];
			$url	= "objects/hosts/$alias";
			foreach ($query1vars as $key => $val)
			{
				if ($key != "alias")
				{
					$data = $data . "\"$key\": \"$val\"";
					if ($idx < count($query1vars)) { $data = $data . ", "; }
				}
				$idx++;
			}
			$data = $data . "} }";
#			print "URL: $url<br>\n";
#			print "Data: $data<br>\n";
			$apidata = callIcinga2API("PUT", $url, $data);
			$dejson = json_decode($apidata, true);
			$result = $dejson['results'];
			foreach ($result as $svc)
			{
				$status = $svc['status'];
				$code = $svc['code'];
				$error = $svc['errors'];
				print "<div class='SmallText'>Response: $code</div>\n";
				print "<div class='SmallText'>Status: $status</div>\n";
				foreach ($error as $msg)
				{
					print "<div class='SmallText'>$msg</div>\n";
				}
			}
			print "<br>\n";

#			print "<pre>\n";
#			var_dump($dejson);
#			print "</pre><hr>";
		}
	}

	mysqli_close($directorDB);
} else {
	print "Must be logged in...";
}
?>

