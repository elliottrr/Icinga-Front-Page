<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<META HTTP-EQUIV="Refresh" CONTENT="300;URL=">
<link REL="SHORTCUT ICON" HREF="/syslog/favicon.png">
<title>Cacti Graphs</title>
<style type="text/css">
body
{
background-color:#FFFFFF;
font-family: Veranda, Arial, Helvetica, sans-serif;
}
</style>
</head>

<body>
<?php

# Icon links
$detailspath  = "/cactigraphs";
$graphpath    = "/cactigraphs/graphs";
$graphimgtype = "png";
$usefullgraph = "yes";	# no = use Cacti-generated thumb image instead
$graphwidth   = "403";	# thumbs are ~403, graphs are ~610
date_default_timezone_set('America/Chicago');
$phpself      = $_SERVER['PHP_SELF'];
$datetime     = date("d-m-Y H:i:s");
$time12hr     = date("g:i:s A");
$longdate     = date("l, F j, Y");

$username     = "cacti";
$password     = "cactipassword";
$database     = "cacti";


if ((array_key_exists('size', $_GET)) && ($_GET['size'] == 'full')) {
        $usefullgraph = "yes";
}

$treelistrows = 0; 
mysql_connect("localhost", $username, $password);
@mysql_select_db($database) or die ("Unable to connect to database: $database");
if ((array_key_exists('tree', $_GET)) && ($_GET['tree'] != '0')) {
	        $treeid = mysql_real_escape_string($_GET['tree']);
		$treequery    = "SELECT id, name FROM graph_tree WHERE id = '$treeid';"; 
		$treelist     = mysql_query($treequery);
		$treelistrows = mysql_numrows($treelist);
		$treeidx      = 0;

} elseif ((array_key_exists('host', $_GET)) && ($_GET['host'] != '0')) {
		$hostid = mysql_real_escape_string($_GET['host']);
		print "<table>\n";
		PrintGraphs($hostid,"yes");
		print "</table>\n";

} elseif ((array_key_exists('graphhost', $_GET)) && ($_GET['graphhost'] != '')) {
                $hostname   = mysql_real_escape_string($_GET['graphhost']);
		$hostquery  = "SELECT id FROM host WHERE description LIKE '%$hostname%'";
		$hostresult = mysql_query($hostquery);
		$hostrows   = mysql_numrows($hostresult);
		if ($hostrows > 0) {
			$hostid = mysql_result($hostresult,0,"id");

	                print "<table>\n";
        	        PrintGraphs($hostid,"yes");
                	print "</table>\n";
		}

} else {
	PrintBuilder();
	$treequery    = "SELECT id, name FROM graph_tree;"; 
}

#$treelist     = mysql_query($treequery);
#$treelistrows = mysql_numrows($treelist);
#$treeidx      = 0;

if ($treelistrows > 0) {
#print "Number of graph trees: $treelistrows<hr>\n";
while ($treeidx < $treelistrows) 
	{
	$row_tree_name = mysql_result($treelist, $treeidx, "name");
	$row_tree_id = mysql_result($treelist, $treeidx, "id");

	print "<h1>$row_tree_name</h1>\n";
	print "<table>\n";

	$treehostquery = "SELECT host.id, host.hostname, host.description 
                          FROM host INNER JOIN graph_tree_items ON host.id=graph_tree_items.host_id  
                          INNER JOIN graph_tree ON graph_tree_items.graph_tree_id=graph_tree.id 
                          WHERE graph_tree.id = '$row_tree_id' 
                          ORDER BY graph_tree.id ASC;";

	$hostlist     = mysql_query($treehostquery);
	$hostlistrows = mysql_numrows($hostlist);
	$hostidx      = 0;
	if ($hostlistrows > 0) {
#		print "Hosts in the $row_tree_name tree: $hostlistrows <br>\n";
		while ($hostidx < $hostlistrows) {

			$hrow_host_id = mysql_result($hostlist, $hostidx, "host.id");
			$hrow_host_name = mysql_result($hostlist, $hostidx, "hostname");
			$hrow_host_desc = mysql_result($hostlist, $hostidx, "description");

			PrintGraphs($hrow_host_id,"no");

			$hostidx++;
		}
	}

	print "</table>\n";
	$treeidx++;
	} 
	print "\n";
}

mysql_close();
?>
<br>
<img src="/images/BlackMesaOpStatsSmall.jpg">

</body>
</html>
<?php
# Moving the code to print the graphs into a function...
function PrintGraphs($host_id,$single) {
	global $graphpath, $graphwidth, $graphimgtype, $detailspath, $usefullgraph, $usefullgraph;

	print "<tr>\n";
#	print "<td><h3>$hrow_host_desc</h3></td>\n";

        $graphquery = "SELECT graph_local.id, graph_local.graph_template_id, graph_templates.name
                       FROM graph_local
                       INNER JOIN graph_templates ON graph_local.graph_template_id=graph_templates.id
                       WHERE host_id = '$host_id'
                       ORDER BY graph_templates.name DESC;";

        if ((array_key_exists('excludegraphtype', $_GET)) && ($_GET['excludegraphtype'] != '0')) {
        	        $excludeid = mysql_real_escape_string($_GET['excludegraphtype']);
	        	$graphquery = "SELECT graph_local.id, graph_local.graph_template_id, graph_templates.name
        	        	       FROM graph_local
	                	       INNER JOIN graph_templates ON graph_local.graph_template_id=graph_templates.id
		                       WHERE host_id = '$host_id'
				       AND graph_local.graph_template_id NOT LIKE '$excludeid'
        	        	       ORDER BY graph_templates.name DESC;";
        }

        if ((array_key_exists('onlygraphtype', $_GET)) && ($_GET['onlygraphtype'] != '0')) {
        	        $includeid = mysql_real_escape_string($_GET['onlygraphtype']);
                	$graphquery = "SELECT graph_local.id, graph_local.graph_template_id, graph_templates.name
                        	       FROM graph_local
	                               INNER JOIN graph_templates ON graph_local.graph_template_id=graph_templates.id
        	                       WHERE host_id = '$host_id'
                	               AND graph_local.graph_template_id LIKE '$includeid'
                        	       ORDER BY graph_templates.name DESC;";
        }


        $graphlist     = mysql_query($graphquery);
        $graphlistrows = mysql_numrows($graphlist);
        $graphidx      = 0;
        if ($graphlistrows > 0) {
        	while ($graphidx < $graphlistrows) {

               		$grow_graph_id = mysql_result($graphlist, $graphidx, "id");
                        $grow_graph_tpl = mysql_result($graphlist, $graphidx, "graph_template_id");
                        $grow_tpl_name = mysql_result($graphlist, $graphidx, "graph_templates.name");

                        print "<td valign=\"top\" >\n";
                        print "<a href=\"$detailspath/graph_$grow_graph_id.html\">";
                        if ($usefullgraph == "yes") {
				$idxG = 1;
				$graphURL = "$graphpath/graph_${grow_graph_id}_$idxG.$graphimgtype";
				if (!file_exists("/var/www/html$graphURL"))
				{
					$idxG = 5;
					$graphURL = "$graphpath/graph_${grow_graph_id}_$idxG.$graphimgtype";
				}
	                        print "<img border=\"0\" width='425' src=\"$graphURL\"></a>\n";
                        } else {
        	                print "<img border=\"0\" src=\"$graphpath/thumb_${grow_graph_id}.$graphimgtype\"></a>\n";
                        }
                        print "</td>\n";
			if ($single == "yes") {
				print "</tr><tr>\n";
			}
		$graphidx++;
		}
	}
	print "</tr>\n";
}

function PrintBuilder() {
	global $phpself;



	print "\n";
	print "<h2>Graph Page Builder</h2>\n";
	print "<form method='GET' action='$phpself'>\n";
	print "Select a single host<br>\n";
	print "<SELECT NAME='host' SIZE='1' WIDTH='100'>\n";
	print "<OPTION VALUE='0' SELECTED>Select Host...</OPTION>\n";
        $hostlistquery = "SELECT host.id, host.hostname, host.description FROM host;";

        $hostlist     = mysql_query($hostlistquery);
        $hostlistrows = mysql_numrows($hostlist);
        $hostidx      = 0;
        if ($hostlistrows > 0) {
                while ($hostidx < $hostlistrows) {

                        $hrow_host_id = mysql_result($hostlist, $hostidx, "host.id");
                        $hrow_host_name = mysql_result($hostlist, $hostidx, "hostname");
                        $hrow_host_desc = mysql_result($hostlist, $hostidx, "description");
			print "<OPTION VALUE='$hrow_host_id'>$hrow_host_desc</OPTION>\n";
                        $hostidx++;
                }
        }

	print "</SELECT><br>\n";

	print " -- OR --<br>\n";
	
	print "Select a host tree<br>\n";
        print "<SELECT NAME='tree' SIZE='1' WIDTH='100'>\n";
        print "<OPTION VALUE='0' SELECTED>Select Tree...</OPTION>\n";
        $hostlistquery = "SELECT id, name FROM graph_tree;";

        $hostlist     = mysql_query($hostlistquery);
        $hostlistrows = mysql_numrows($hostlist);
        $hostidx      = 0;
        if ($hostlistrows > 0) {
                while ($hostidx < $hostlistrows) {

                        $hrow_host_id = mysql_result($hostlist, $hostidx, "id");
                        $hrow_host_name = mysql_result($hostlist, $hostidx, "name");
                        print "<OPTION VALUE='$hrow_host_id'>$hrow_host_name</OPTION>\n";
                        $hostidx++;
                }
        }

        print "</SELECT><br><br>\n";

        print "Exclude graphs of this type<br>\n";
        print "<SELECT NAME='excludegraphtype' SIZE='1' WIDTH='100'>\n";
        print "<OPTION VALUE='0' SELECTED>Select Graph Type...</OPTION>\n";
        $hostlistquery = "SELECT id, name FROM graph_templates;";

        $hostlist     = mysql_query($hostlistquery);
        $hostlistrows = mysql_numrows($hostlist);
        $hostidx      = 0;
        if ($hostlistrows > 0) {
                while ($hostidx < $hostlistrows) {

                        $hrow_host_id = mysql_result($hostlist, $hostidx, "id");
                        $hrow_host_name = mysql_result($hostlist, $hostidx, "name");
                        print "<OPTION VALUE='$hrow_host_id'>$hrow_host_name</OPTION>\n";
                        $hostidx++;
                }
        }

        print "</SELECT><br>\n";

	print " -- OR --<br>\n";

        print "Show only graphs of this type<br>\n";
        print "<SELECT NAME='onlygraphtype' SIZE='1' WIDTH='100'>\n";
        print "<OPTION VALUE='0' SELECTED>Select Graph Type...</OPTION>\n";
        $hostlistquery = "SELECT id, name FROM graph_templates;";

        $hostlist     = mysql_query($hostlistquery);
        $hostlistrows = mysql_numrows($hostlist);
        $hostidx      = 0;
        if ($hostlistrows > 0) {
                while ($hostidx < $hostlistrows) {

                        $hrow_host_id = mysql_result($hostlist, $hostidx, "id");
                        $hrow_host_name = mysql_result($hostlist, $hostidx, "name");
                        print "<OPTION VALUE='$hrow_host_id'>$hrow_host_name</OPTION>\n";
                        $hostidx++;
                }
        }

        print "</SELECT><br>\n";

	print "\n";
	print "<input type='Submit' value='Search'>\n";
	print "<input type='Reset' value='Reset'></form>\n";
	print "<hr>\n";

}
