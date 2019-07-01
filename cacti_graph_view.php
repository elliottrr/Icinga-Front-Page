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


# Left Column - includes top left logo

print "<div class='LeftColumn'>\n";
print "<div class='BannerLogo'></div>\n";
print "<div class='SectionSpacer'></div>\n";
print "<div class='Section'>\n";
print "<div class='SectionOption'><a href='/'>Home</a></div>\n";
print "</div>\n";
print "<div class='SectionSpacer'></div>\n";
print "<div class='Section'>\n";
print "<div class='SectionHeader'>Cacti Graphs</div>\n";
$treeList = getTrees($cactiDB);
foreach ($treeList as $id => $name)
{
	$rowCSS = "SectionOption";
	if ($id == $tree) 
	{
		$rowCSS = "SectionOptionSelected";
#		print "<div class='$rowCSS'><a href='$phpself?tree=$id'>$name</a></div>\n";
		print "<div class='$rowCSS'><a href='$phpself'>$name</a></div>\n";
		$treeMember = getTreeMembers($cactiDB,$id);
		foreach ($treeMember as $member)
		{
			list($host_id,$label) = $member;
			$memCSS = "SectionOptionL2";
			if ($host_id > 0)
			{
				if ($host_id == $host) 
				{
					$memCSS = "SectionOptionSelectedL2";
					print "<div class='$memCSS'><a href='$phpself?tree=$id'>$label</a></div>\n";
					if ((array_key_exists('graph', $_GET)))
					{
						print "<div class='$memCSS'>- $graphName</div>\n";
						

					}
				} else {
					print "<div class='$memCSS'><a href='$phpself?tree=$id&host=$host_id'>$label</a></div>\n";
				}
			} else {
				print "<div class='SectionHeaderL2'>$label</div>\n";
			}
		}
	} else {
		print "<div class='$rowCSS'><a href='$phpself?tree=$id'>$name</a></div>\n";
	}


}
print "</div>\n";
print "</div>\n";
#print "<div class='BottomSpacer'>$tree</div>\n";
print "</div>\n";
print "<div class='MainContent'>\n";
if ($graph > 0)
{
	$hostname = getName($cactiDB,$host);
	$idxG = getGraphIndex($graph);
	$graphFile = "graph_${graph}_$idxG.png";
	$idxG++;
	$graphFile2 = "graph_${graph}_$idxG.png";
	$idxG++;
	$graphFile3 = "graph_${graph}_$idxG.png";
	$idxG++;
	$graphFile4 = "graph_${graph}_$idxG.png";
	print "<div class='SectionHead'>$graphName for $hostname</div>\n";
	print "Day<br><img src='$graphBaseURL$graphFile'><br><br>\n";
	print "Week<br><img src='$graphBaseURL$graphFile2'><br><br>\n";
	print "Month<br><img src='$graphBaseURL$graphFile3'><br><br>\n";
	print "Year<br><img src='$graphBaseURL$graphFile4'><br><br>\n";

} else if ($host > 0) {
	$graphList = getGraphs($cactiDB,$host);
	$hostname = getName($cactiDB,$host);
	print "<div class='SectionHead'>Graphs for $hostname</div>\n";
	foreach ($graphList as $graphID => $graphName)
	{
		$idxG = getGraphIndex($graphID);
		$graphFile = "graph_${graphID}_$idxG.png";
		print "$graphName<br>\n";
		print "<a href='$phpself?tree=$tree&host=$host&graph=$graphID'><img src='$graphBaseURL$graphFile'></a><br><br>\n";
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

print "<div class='FootNote'>\n";
print "Page Loaded at: $datetime -- ";
print "Client Address: $remoteaddr -- \n";
print "Load Time: $loadtime seconds\n";
print "</div>\n";
print "</div>\n";

print "</body>\n";
print "</html>\n";
