<?php

header("Content-type:application/json;CHARSET=UTF-8");

// connection à la base de données
include "fonctions/config.inc.php";
include "fonctions/connector.php";

$ids = array(27,37); // 37 et 23

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



$obj = new stdClass();
$obj->alerte = "";
$obj->vitesse_marche = "8km/h";
$obj->heure = date("c");
$obj->arrets = array();

foreach($ids as $id)
{
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
    
    
    $file = file_get_contents($currloc["url"]."&key=".$key);
    $json = json_decode($file);
    
    $monarret = new stdClass();
    $monarret->distance = ($id==27?850:550)."m";
    $monarret->bus = $json->departures->departure[0]->line->shortName;
    $monarret->nom = $json->departures->stop->name;
    $monarret->passages = array();

    $first = true;
    foreach($json->departures->departure as $d)
    {
        $monarret->passages[] = date("H:i",strtotime($d->dateTime));
    }
    $obj->arrets[] = $monarret;
}

echo json_encode($obj);


?>