<?php
header("Content-type: application/json;charset=UTF-8");

include "fonctions/fonctions.inc.php";

if (isset($_POST["loc"]))
{

	$connec->rawQuery("SELECT coords, idLocation FROM Location");
	$found = false;
	$lastDistance = 9999;
	
	while ($q = $connec->getRow())
	{
		$from = preg_split("#, ?#",$_POST["loc"]);
		$to = preg_split("#, ?#",$q["coords"]);
		$d = distance($from[0],$from[1],$to[0],$to[1]);
		
		if ($d < $lastDistance)
		{
			$lastDistance = $d;
			$lastid = $q["idLocation"];
		}
		
	}

	if ($lastDistance != 9999)
	{
		echo "{\"id\":".$lastid.",\"distance\":".$lastDistance."}";
	}
	else
	{
		echo "{\"id\":".(2).",\"distance\":".(9999)."}";
	}
}
else
{
	echo '{"id":"2","distance":"-1"}';
}

?>