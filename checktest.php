<?php
if ((($phpself != $mainpage) || ($phpself == "")) && (isset($_SESSION['username']) == true))
{
        print "This page cannot be called directly.<br>What do you think you're doing?\n";
} else {

$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$start = $time;


$host = "";
$check = "";
$attr = "";
$format = "0";

if (array_key_exists('host', $_GET))
        {
        $host = $_GET['host'];
        $host = substr($host,0,50);
        }
if (array_key_exists('check', $_GET))
        {
        $check = $_GET['check'];
        $check = substr($check,0,30);
        }
if (array_key_exists('attr', $_GET))
        {
        $attr = $_GET['attr'];
        $attr = substr($attr,0,100);
	$attr = str_replace("+"," ",$attr);
        }
if (array_key_exists('format', $_GET))
        {
        $format = $_GET['format'];
        $format = substr($format,0,1);
        }

$execString = "/usr/lib64/nagios/plugins/check_nrpe -t 30 -H $host -c $check -a $attr 2>&1";
if ($attr == "") { $execString = "/usr/lib64/nagios/plugins/check_nrpe -t 30 -H $host -c $check 2>&1"; }
if ($check == "") { $execString = "/usr/lib64/nagios/plugins/check_nrpe -t 30 -H $host -a $attr 2>&1"; }
if (($check == "") && ($attr == "")) { $execString = "/usr/lib64/nagios/plugins/check_nrpe -H $host 2>&1"; }
#if (($host != "") && ($check != "")) 
if ($host != "") 
{ 
	exec($execString, $execResult, $execExit); 
} else {
	$execResult = "";
	$execExit = "3";
}

$pagetitle = "NRPE Remote Check Test - $host - $check";

#print "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>\n";
#print "<html xmlns='http://www.w3.org/1999/xhtml'>\n";
#print "<head>\n";
#print "<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1' />\n";
#print "<title>$pagetitle</title>\n";
#print "</head>\n";
#print "<body>\n";
#print "<h2>Icinga Remote Check Test</h2>\n";
print "<div class='SectionHead'>Icinga Remote Check Test</div>\n";
print "<form method='GET' action='$phpself'>\n";
print "<table><tr>\n";
print "<td>Host IP:</td>\n";
print "<td>Check Name:</td>\n";
print "<td>Attributes:</td>\n";
print "</tr><tr>\n";
print "<td><input name='host' type='text' value='$host'></td>\n";
print "<td><input name='check' type='text' value='$check'></td>\n";
print "<td><input name='attr' type='text' value='$attr'></td>\n";
print "<td><input name='do' type='hidden' value='checktest'></td>\n";
print "</tr><tr>\n";
print "<td>";
if ($format == "1")
{
	print "<input name='format' type='checkbox' value='1' checked='checked'>";
} else {
	print "<input name='format' type='checkbox' value='1'>";
}
print "Format Output</td>\n";
print "</tr></table>\n";
print "<input type='Submit' value='Submit'>\n";
print "</form>\n";
print "<br>\n";
print "Command: $execString\n<br>\n";
print "Check Output:\n<br>\n";
print "<pre>\n";
foreach ($execResult as $line)
{
	if ($format == "1")
	{
		$line = str_replace(";",";\n",$line);
		$line = str_replace("|","|\n",$line);
	}
	print "$line\n";
}

print "</pre>\n";

print "<br>\n";
#print "Exit Code: $execExit ";
$execExitText = "---";
$execExitCSS = "";
if ($execExit == 0) { $execExitText = "(OK)"; $execExitCSS = "statusOK"; }
if ($execExit == 1) { $execExitText = "(Warning)"; $execExitCSS = "statusWarn"; }
if ($execExit == 2) { $execExitText = "(Critical)"; $execExitCSS = "statusCrit"; }
if ($execExit == 3) { $execExitText = "(Unknown)"; $execExitCSS = "statusUnkn"; }

$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$finish = $time;
$loadtime = round(($finish - $start), 4);


print "<div class='divTable'>\n";
print "<div class='divTableBody'>\n";

print "<div class='divTableRow'>\n";
print "<div class='divTableHead'>Exit Code</div>\n";
print "<div class='divTableHead'>Processing Time</div>\n";
print "</div>\n";


print "<div class='divTableRow'>\n";
print "<div class='divTableCell'>\n";
print "<div class='$execExitCSS'>$execExit $execExitText</div>\n";
print "</div>\n";
print "<div class='divTableCellService'>$loadtime seconds</div>\n";
print "</div>\n";
print "</div>\n";
print "</div>\n";


print "\n";

}
#print "</body>\n</html>\n";
?>

