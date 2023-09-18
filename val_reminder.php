<?php

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

if (isset($_POST["coords"]) && isset($_POST["offset"]) && isset($_POST["id"]))
{
	$connec->prepare("Update Location SET coords = ?, offset = ?, label = ?, ordre = ? WHERE idLocation = ?");
	$id = $_POST["id"];
	$coords = $_POST["coords"];
	$offset = $_POST["offset"];
	$ordre = $_POST["ordre"];
	$label = $_POST["label"];
	$connec->bind(array(new BindParameter("string",$coords),
						new BindParameter("int",$offset),
						new BindParameter("string",$label),
						new BindParameter("int",$ordre),
						new BindParameter("int",$id)));
	$connec->execute();
	if (!$connec->isSuccess())
	{
		$supprtext = $connec->getLastError()."<br/>";
	}
	else
    {
        header("Location: reminder.php?id=".$_POST["id"]);
    }
}

?>