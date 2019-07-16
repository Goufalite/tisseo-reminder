<?php
header("Content-type:text/html;CHARSET=UTF-8");

// connection à la base de données
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
<title>Tisseo reminder</title>
<script type='text/javascript' src='functions.js'></script>
<script type='text/javascript'>
function changeloc()
{
	window.location.href= "reminder.php?id="+document.getElementById("location").value;
}
</script>
<style type='text/css'>
body
{
	font-size : 50pt;
}
.busline
{
	font-family:Arial;
	text-align:center;
	width:30px;
	font-weight:bold;
	color:white;
}
select
{
	font-size: 50pt;
}
option
{
	font-size: 50pt;
}
input[type=checkbox]
{
	width:50px;
	height:50px;
}
.before
{
	color:gray;
}
</style>
</head>
<body onload='loaded()';>
<select name='location' id='location' onchange='changeloc()'>
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

$connec->rawQuery("SELECT * from location order by ordre");
$currloc = null;
while ($loc = $connec->getRow())
{
	if ($currloc==null)
	{
		$currloc = $loc;
	}
	if ($loc["ordre"]>0)
	{
		echo "<option value='".$loc["idLocation"]."'";
		if (@$_GET["id"]==$loc["idLocation"])
		{
			echo " selected ";
			$currloc = $loc;
		}
		echo ">".$loc["label"]."</option>\n";
	}
	else
	{
		// récupération des messages importants
		if ($loc["label"]=="messages")
		{
			$msgs ="";
			$msgjson = @json_decode(file_get_contents($loc["url"]."&key=".$key));
			if (!$msgjson)
			{
				$msgs = "Impossible de récupérer les messages";
			}
			else
			{
				foreach(($msgjson->messages) as $msg)
				{
					// ma ligne en XXL... c'est bon
					if (!preg_match("/XXL/",$msg->message->content))
					{
						$msgs .= $msg->message->content."<br/>\n";
					}
				}
			}
		}
	}		
}
echo "</select>";
// affichage du message d'alerte s'il y en a après la listbox
if ($msgs!="")
{
	echo "<hr/>".$msgs."<hr/>\n";
}
$offset = $currloc["offset"]*60;

$time = time();
//$time = mktime(7,8,0,2,28,2018);

$json = json_decode(file_get_contents($currloc["url"]."&key=".$key));

$first = true;

$arr = array();
foreach($json->departures->departure as $d)
{
	$date = getdate(strtotime($d->dateTime));
	$ecart = $date[0]-$time;
	if ($ecart > $offset)
	{
		if ($first)
		{
			// estimation de départ
			echo "<div id='departOptimal'>D&eacute;part optimal dans ".intval(($ecart-$offset)/60)." minutes </div><br/>";
		
		}
		$arr[] =  "<tr><td class='busline' style='background-color:rgb".$d->line->color."'>".$d->line->shortName."</td><td>".substr(preg_split("@ @",$d->dateTime)[1],0,5)." -&gt; ".intval($ecart/60)." min</td></tr>\n";
		$first = false;
	}
	else
	{
		// départs inaccessibles mais affichés quand même
		$arr[] =  "<tr><td class='busline' style='background-color:rgb".$d->line->color."'>".$d->line->shortName."</td><td class='before'>".substr(preg_split("@ @",$d->dateTime)[1],0,5)." -&gt; ".intval($ecart/60)." min</td></tr>\n";
	}
}
echo "<div id='linesResult'><table>";
foreach($arr as $s)
{
	echo $s;
}

echo "</table></div>";

?>
<footer style='text-align:right;'><i>Base sous licence ObDL<br/>
Tisseo est une marque d&eacute;pos&eacute;e</i>
</body>
</html>