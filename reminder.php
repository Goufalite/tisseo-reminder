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
$elts = Array();
while ($loc = $connec->getRow())
{
	if ($currloc==null)
	{
		$currloc = $loc;
		$titrehtml = $loc["label"];
	}
	if ($loc["ordre"]>0)
	{
		$elts[$loc["idLocation"]] = $loc["label"];
		if (@$_GET["id"]==$loc["idLocation"])
		{
			$currloc = $loc;
			$titrehtml = $loc["label"];
		}
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
					if (!preg_match("/XXL/",$msg->message->content) 
						&& !preg_match("/TRAM T1 et T2 : interruption du service en soir/",$msg->message->content)
						&& !preg_match("/Covid-19/",$msg->message->content)
						)
					{
						$msgs .= $msg->message->content."<br/>\n";
					}
					
				}
			}
		}
	}		
}
?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo $titrehtml; ?></title>
<script type='text/javascript'>
function changeloc()
{
	window.location.href= "reminder.php?id="+document.getElementById("location").value;
}
function loaded()
{
	
}
</script>
<style type='text/css'>

@media (min-device-width: 2cm) and (max-device-width: 12cm) {
	* { font-size: 60pt; }
}


@media (min-device-width: 12cm) {
	* {font-size: 25pt;}
}

.busline
{
	font-family:Arial;
	text-align:center;
	width:30px;
	font-weight:bold;
	color:white;
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
foreach($elts as $i=>$v)
{
	echo "<option value='".$i."'";
	if (@$_GET["id"]==$i)
	{
		echo " selected ";
	}
	echo ">".$v."</option>\n";	
}
echo "</select>";
// affichage du message d'alerte s'il y en a après la listbox
if ($msgs!="")
{
	echo "<script type='text/javascript'>var alertmsg=\"".preg_replace("/\\r?\\n/","\\n",addslashes($msgs))."\";</script>\n";
	echo "<hr/><div onclick=\"Javascript:alert(alertmsg);\">".substr($msgs,0,30)."...</div><hr/>\n";
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
		$arr[] =  "<tr><td class='busline' style='background-color:rgb".$d->line->color."'>".$d->line->shortName."</td><td>".substr(preg_split("@ @",$d->dateTime)[1],0,5)." -&gt; ".intval($ecart/60)." min".($d->realTime==="no"?"*":"")."</td></tr>\n";
		$first = false;
	}
	else
	{
		// départs inaccessibles mais affichés quand même
		$arr[] =  "<tr><td class='busline' style='background-color:rgb".$d->line->color."'>".$d->line->shortName."</td><td class='before'>".substr(preg_split("@ @",$d->dateTime)[1],0,5)." -&gt; ".intval($ecart/60)." min".($d->realTime==="no"?"*":"")."</td></tr>\n";
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