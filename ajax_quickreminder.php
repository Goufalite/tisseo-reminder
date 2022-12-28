<?php
header("Content-type: application/json;charset=UTF-8");

function distance($lat1, $lon1, $lat2, $lon2) {
	// https://stackoverflow.com/a/11178145/555111
	
    $pi80 = M_PI / 180;
    $lat1 *= $pi80;
    $lon1 *= $pi80;
    $lat2 *= $pi80;
    $lon2 *= $pi80;

    $r = 6372.797; // mean radius of Earth in km
    $dlat = $lat2 - $lat1;
    $dlon = $lon2 - $lon1;
    $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlon / 2) * sin($dlon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $km = $r * $c;

    //echo '<br/>'.$km;
    return $km;
}

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