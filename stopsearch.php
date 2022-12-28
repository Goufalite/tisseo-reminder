<?php
header("Content-type:text/html;CHARSET=UTF-8");
include "fonctions/config.inc.php";
include "fonctions/connector.php";

try{
		$connec = new SQLiteConnector();
		if ($connec->connect(DB,SQLITE3_OPEN_READWRITE))
		{
			throw new Exception($connec->getConnectionError);
		}
	}
	catch(Exception $e)
	{
		var_dump($e);
		die("Erreur lors de la connexion &agrave; la base : ".$e->getMessage());
	}

?>
<!DOCTYPE html>
<html>
<head>
<title>Tisseo stop search <?php if (isset($_GET["shortName"])) echo " : ".$_GET["shortName"]; ?></title>

<style type='text/css'>

</style>
<script type='text/javascript'>
function stopId(txt)
{
	document.getElementById("stopId").value = txt;
	generateUrl();
}
function stopAreaId(txt)
{
	document.getElementById("stopAreaId").value = txt;
	generateUrl();
}
function lineId(txt)
{
	document.getElementById("lineId").value = txt;
	generateUrl();
}
function terminusId(txt)
{
	document.getElementById("terminusId").value = txt;
	generateUrl();
}
function generateUrl()
{
	urlbase = "https://api.tisseo.fr/v2/stops_schedules.json?number=15&stopsList=";
	url = urlbase;
	if (document.getElementById("stopId").value != "")
		url = urlbase + document.getElementById("stopId").value;
	if (document.getElementById("stopAreaId").value != "")
		url = urlbase +  document.getElementById("stopAreaId").value;
	if (document.getElementById("stopId").value != "" || document.getElementById("stopAreaId").value != "")
		url = url +"|"+ document.getElementById("lineId").value;
	if ((document.getElementById("stopId").value != "" || document.getElementById("stopAreaId").value != "") && document.getElementById("terminusId").value != "")
		url = url +"|"+ document.getElementById("terminusId").value;
	
	
	document.getElementById("url").value = url;
}




</script>
</head>
<body>
<?php

// get key
try{
	$connec->prepare("SELECT stringValue from parameter where nameParam = ?");

	$idparamkey = "key";
	$connec->bind(array(new BindParameter("string",$idparamkey)));
	$connec->execute();
	$key = $connec->getRow()["stringValue"];
}
catch (Exception $e)
{
	die("fail query : ".$e->getMessage());
}

if (!isset($_GET["shortName"]) && !isset($_GET["lineId"]))
{
	echo "<form action='' method='get'><label for='shortName'>Ligne : </label>
	<input type='text' name='shortName' /><input type='submit' value='OK' /></form>";
}
if (isset($_GET["shortName"]))
{
	$jsonline = json_decode(file_get_contents("https://api.tisseo.fr/v2/lines.json?shortName=".$_GET["shortName"]."&key=".$key));
	foreach ($jsonline->lines->line as $l)
	{
		if ($l->bgXmlColor == "")
		{
			$l->bgXmlColor = "#0000000";
		}
		echo "<span style='background-color:".$l->bgXmlColor.";color:".$l->fgXmlColor.";font-weight:bold;'>&nbsp;".$l->shortName."&nbsp;</span><a href='?lineId=".$l->id."'> ".$l->name."</a><br/>";
	}
}
if (isset($_GET["lineId"]))
{
	echo "<br/><form action='val_stopsearch.php' method='POST'><label for='stopId'>Stop</label><input type='text' size='60' id='stopId'/><br/>
	<label for='stopAreaId'>Stop area</label><input type='text' size='60' id='stopAreaId'/><br/>
	<label for='lineId'>Line</label><input type='text' size='60' id='lineId' value='".$_GET["lineId"]."' readonly/><br/>
	<label for='terminusId'>Terminus</label><input type='text' size=60 id='terminusId'/>";
	echo "<br/><label for='url'>URL</label><input type='text' size='120' name='url' id='url'/><br/>
	<label for='label'>Label</label><input type='text size='120' id='label' name='label'/><input type='submit'/></form>";
	
	//echo file_get_contents("https://api.tisseo.fr/v2/stop_points.json?lineId=".$_GET["lineId"]."&displayDestinations=1&key=".$key);
	$jsonstops = json_decode(file_get_contents("https://api.tisseo.fr/v2/stop_points.json?lineId=".$_GET["lineId"]."&displayDestinations=1&key=".$key));
	foreach ($jsonstops->physicalStops->physicalStop as $stop)
	{
		foreach ($stop->lines as $line)
		{
			echo "[".$line->short_name."]";
		}
		echo "<a href='#' onclick='stopId(\"".$stop->id."\");'>".$stop->name."</a><a href='#' onclick='stopAreaId(\"".$stop->stopArea->id."\");'>(\"".$stop->stopArea->name."\")</a><br/>";
		foreach ($stop->destinations as $dest)
		{
			echo "--&gt; <a href='#' onclick='terminusId(\"".$dest->id."\");'>".$dest->name."</a><br/>";
		}
	}
	
}

?>
</body>
</html>