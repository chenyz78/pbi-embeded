<?php
error_reporting(0);
include_once('lib/powerbi.php');
include_once('lib/class.DotEnv.php');

$uname = getenv("AZURE_UNAME");
$passwd = getenv("AZURE_PASS");
$clientId = getenv("AZURE_CLIENTID");
$groupId = getenv("AZURE_GROUPID");
$baseUrl = getenv("PBI_BASEURL");

$repUser = new ReportUser($uname, $passwd);

$pbi = new PowerBi($repUser, $clientId, $baseUrl, $groupId);

$rs = $pbi->getReport("34dd2623-1ee4-4376-8600-87e91747dd27");

echo "<br> ------- report ------------ <br>";
var_dump($rs->data);
echo "<br> ---------- name ------- <br>";
echo $rs->data['name'];
echo "<br> ------ web url ------------ <br>";
echo $rs->data['webUrl'];
echo "<br> ------- embed url----------- <br>";
echo $rs->getEmbedUrl();
echo "<br> ------ dashboard ------------- <br>";
$rs = $pbi->getDashboard();
var_dump($rs->data);
echo "<br> ----- display name-------------- <br>";
echo $rs->data['displayName'];
echo "<br> --------- embed url ----------- <br>";
echo $rs->data['embedUrl'];
echo "<br><br>";


?>
