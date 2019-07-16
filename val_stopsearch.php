<?php

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

if (isset($_POST))
{
	$connec->prepare("INSERT INTO Location (url, label, offset, ordre) values (?,?,0,9999)");
	$url = $_POST["url"];
	$bindurl = new BindParameter("string",$url);
	$label = $_POST["label"];
	$bindlabel = new BindParameter("string",$label);
	$connec->bind(array($bindurl, $bindlabel));
	$connec->execute();
	if (!$connec->isSuccess())
	{
		echo $connec->getLastError();
	}
	else
	{
		echo "youpi";
	}
}

?>