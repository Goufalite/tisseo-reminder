#!/usr/bin/php -q
<?php
include "fonctions/config.inc.php";
include "fonctions/connector.php";

// mettez vos variables de chemin/scripts
define("TISSEO_PATH","<chemin_de_notif.php>");
define("PUSHBULLET_PATH","<script à appeler>");

// connexion à la base
try
{
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

function unchangecars($txt)
{
	return(html_entity_decode($txt,ENT_QUOTES,"ISO-8859-15"));
}

if (!isset($_GET["id"]) && !isset($argv[1]))
{
	die("notif.php id [remind[only]]");
}

if (isset($_GET["id"]))
{
    $id = $_GET["id"];
    $remind = @$_GET["remind"];
}
else
{
 $id = @$argv[1];
 $remind = @$argv[2];
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

try{
$connec->prepare("SELECT * from location where idLocation = ?");

$idLoc = $id;
$connec->bind(array(new BindParameter("integer",$idLoc)));
$connec->execute();
}
catch (Exception $e)
{
	die("fail query : ".$e->getMessage());
}

if (($currloc = $connec->getRow())===false)
{
	die("fail fetch");
}
$offset = $currloc["offset"]*60;

$time = time();
$file = file_get_contents($currloc["url"]."&key=".$key);
$json = json_decode($file);

$first = true;
$cpt = 0;
$res = array();
$optimal = -99;
foreach($json->departures->departure as $d)
{
	$date = getdate(strtotime($d->dateTime));
	$ecart = $date[0]-$time;
	if ($ecart > $offset)
	{
		if ($first)
		{
			$optimal = intval(($ecart-$offset)/60);
		}
		$res[] = "[".$d->line->shortName."] ".substr(preg_split("@ @",$d->dateTime)[1],0,5)." -> ".intval($ecart/60)." min";
		$first = false;
		$cpt++;
	}
	
	// on récupère les trois prochains départs
	if ($cpt ==3)
	{
		break;
	}
}
$out = unchangecars($currloc["label"])." : ".$optimal." mins \\n";
foreach ($res as $s)
{
	$out .= $s." \\n";
}

// affichage direct
if ($remind!="remindonly")
{
	system(PUSHBULLET_PATH." Tisseo \"$out\"");
}

// affichage retardé
if (preg_match("@^remind@",$remind))
{
	$cmd = "screen -dm -S remindTisseo".$id." bash -c 'sleep ".(($optimal-1)*60)." && (cd ".TISSEO_PATH." ; php -q notif.php ".$id.")' >/dev/null 2>&1";
	exec($cmd);
}