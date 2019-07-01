<?php
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$start = $time;

# See http://www.srh.noaa.gov/hun/?n=wmocodes for code list
include('./config.php');
include('./functions.php');
include('./functions_cacti.php');
$graphBaseURL	= "/cactigraphs/graphs/";
$graphBasePath	= "/var/www/html/cactigraphs/graphs/";

$nodetype = "";
$tree = "-1";
$host = "-1";
$graph = "-1";

# Open the DB connection
$cactiDB = new mysqli("$dbhost", $username, $password, $databaseCACTI);
if ($cactiDB->connect_error) { die("Connection failed: " . $conn->connect_error); }

print "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>\n";
print "<html xmlns='http://www.w3.org/1999/xhtml'>\n";
print "<head>\n";
print "<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1' />\n";
print "<META HTTP-EQUIV='Refresh' CONTENT='300;URL='>\n";
if ((array_key_exists('tree', $_GET)))
{
	$tree = $_GET['tree'];
	$tree = substr($tree,0,4);
}
if ((array_key_exists('host', $_GET)))
{
	$host = $_GET['host'];
	$host = substr($host,0,4);
}
if ((array_key_exists('graph', $_GET)))
{
	$graph = $_GET['graph'];
	$graph = substr($graph,0,4);
	list($graphName,$host) = getGraphName($cactiDB,$graph);
}
print "<title>Systems Monitoring - From Aperture Laboratories</title>\n";
print "<link href='./main.css' rel='stylesheet' type='text/css' />\n";
print "</head>\n";

print "<body>\n";
print "<div class='MailContent'>\n";
print "<div class='GraphWall'>\n";

if ($tree > 0)
{
	$treeList = getTrees($cactiDB);
	foreach ($treeList as $id => $name)
	{
		if ($id == $tree) 
		{
			print "<div class='SectionHead'>$name</div>\n";
			$treeMember = getTreeMembers($cactiDB,$id);
			foreach ($treeMember as $member)
			{
				list($host_id,$label) = $member;
	#			print "$host_id ($label)<br>\n";
				if ($host_id > 0)
				{
					$graphList = getGraphs($cactiDB,$host_id);
	#				$hostname = getName($cactiDB,$host_id);
					print "<div class='SectionHead'>Graphs for $label</div>\n";
					foreach ($graphList as $graphID => $graphName)
					{
						$idxG = getGraphIndex($graphID);
						$graphFile = "graph_${graphID}_$idxG.png";
#						print "$graphName<br>\n";
						print "<img src='$graphBaseURL$graphFile'>\n";
					}
				}
			}
		}
	}
} else if ($host > 0) {
	$graphList = getGraphs($cactiDB,$host);
	$hostname = getName($cactiDB,$host);
	print "<div class='SectionHead'>Graphs for $hostname</div>\n";
	foreach ($graphList as $graphID => $graphName)
	{
		$idxG = getGraphIndex($graphID);
		$graphFile = "graph_${graphID}_$idxG.png";
		print "$graphName<br>\n";
		print "<img src='$graphBaseURL$graphFile'><br><br>\n";
	}
}

print "\n";
print "\n";
print "\n";


mysqli_close($cactiDB);

$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$finish = $time;
$loadtime = round(($finish - $start), 4);

print "</div>\n";
print "<div class='FootNote'>\n";
print "Page Loaded at: $datetime -- ";
print "Client Address: $remoteaddr -- \n";
print "Load Time: $loadtime seconds\n";
print "</div>\n";
print "</div>\n";

print "</body>\n";
print "</html>\n";
