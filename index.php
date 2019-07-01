<?php
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$start = $time;

# See http://www.srh.noaa.gov/hun/?n=wmocodes for code list
include('./config.php');
include('./functions.php');
$nodetype = "";

# Open the DB connection
$icingaDB = new mysqli("$dbhost", $username, $password, $database);
if ($icingaDB->connect_error) { die("Connection failed: " . $conn->connect_error); }

print "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>\n";
print "<html xmlns='http://www.w3.org/1999/xhtml'>\n";
print "<head>\n";
print "<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1' />\n";
if (!(array_key_exists('do', $_GET)))
{
	print "<META HTTP-EQUIV='Refresh' CONTENT='300;URL='>\n";
}
print "<title>Systems Monitoring - From Aperture Laboratories</title>\n";
print "<link href='/main.css' rel='stylesheet' type='text/css' />\n";
print "</head>\n";

print "<body>\n";


# Left Column - includes top left logo

print "<div class='LeftColumn'>\n";
print "<div class='BannerLogo'></div>\n";
print "<div class='SectionSpacer'></div>\n";
sessionMgr();
#print "<div class='SectionSpacer'></div>\n";
print "<div class='Section'>\n";
print "<div class='SectionHeader'>System Monitoring</div>\n";
print "<div class='SectionOption'><a href='/'>Overview</a></div>\n";
print "<div class='SectionOption'><a href='/cacti_graph_view.php'>Cacti Graphs</a></div>\n";
#print "<div class='SectionOptionSelected'>Choice 1</div>\n";
#print "<div class='SectionOption'>Choice 2</div>\n";
#print "<div class='SectionOption'>Choice 3</div>\n";
#print "<div class='SectionOption'>Choice 4</div>\n";
#print "<div class='SectionOption'>Choice 5</div>\n";
print "</div>\n";
print "<div class='SectionSpacer'></div>\n";
if ((isset($_SESSION['username']) == true) && ($_SESSION['label'] == $adminsession))
{
	print "<div class='Section'>\n";
	print "<div class='SectionHeader'>Monitoring Utilities</div>\n";
	print "<div class='SectionOption'><a href='$phpself?do=checktest'>Check Test</a></div>\n";
	print "<div class='SectionOption'><a href='$phpself?do=scheduledowntime'>Schedule Downtime</a></div>\n";
#	print "<div class='SectionOption'><a href='$phpself?do=addhost'>Add Client</a></div>\n";
	print "</div>\n";
	print "<div class='SectionSpacer'></div>\n";
}
print "<div class='Section'>\n";
print "<div class='SectionHeader'>Monitoring Administration</div>\n";
print "<div class='SectionOption'><a href='/icingaweb2'>Icinga</a></div>\n";
print "<div class='SectionOption'><a href='/cacti'>Cacti</a></div>\n";
print "</div>\n";
print "<div class='SectionSpacer'></div>\n";
print "<div class='Section'>\n";
print "<div class='SectionHeader'>Administrative Tools</div>\n";
print "<div class='SectionOption'><a href='http://cidr.xyz/'>CIDR Notation Tool</a></div>\n";
print "</div>\n";



#print "<div class='BottomSpacer'></div>\n";
print "</div>\n";
displayNotificationsEnabled($icingaDB);
print "<div class='MainContent'>\n";
print "<br>\n";

if (array_key_exists('do', $_GET))
{
	switch ($_GET['do'])
	{
		case "checktest":
#			print "Check Test Form<br>";
			include("./checktest.php");
			break;
		case "scheduledowntime":
#			print "Schedule Downtime<br>";
			include("./scheduledowntime.php");
			break;
		case "addhost":
#			print "Schedule Downtime<br>";
			include("./addhost.php");
			break;
		case "apitest":
			print "API Test Form";
			APItest();
			break;
		default:
			header("Location: /");
	}
} else {
	# Default Page Content
	displayStatsTable($icingaDB);
	print "<br>\n";
	displayServiceIssues($icingaDB);
	print "<br>\n";
	displayHostIssues($icingaDB);
	print "<br>\n";
	displayDowntimes($icingaDB);
}


print "<br>\n";
print "<br>\n";
#print "<br>\n";
#print "More stuff should go in the center column...\n";
print "\n";
print "\n";
print "\n";
print "\n";
print "\n";
print "\n";
print "\n";








mysqli_close($icingaDB);

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
