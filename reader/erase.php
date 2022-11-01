<?php 
	/**
	*  Delete or protect a tweet from deletion, directly in the base
	*/


	if(isset($_POST['proteger']) && !empty($_POST['proteger']) && isset($_POST['ids']) && !empty($_POST['ids'])){
		$dbhandle = new SQLite3('./base.sqlite');
		if (!$dbhandle) die ($error);
		
		$dbhandle->busyTimeout(60000);
		
		$ids =  json_decode($_POST['ids']);
		foreach($ids as $id){
			$requete = "UPDATE feed SET protected='1' WHERE id='".$id."'";
			$result = $dbhandle->query($requete);
			if (!$result) die("Cannot execute query.");
		}
		$dbhandle->close();
		unset($dbhandle);
	}else if(isset($_POST['deproteger']) && !empty($_POST['deproteger']) && isset($_POST['ids']) && !empty($_POST['ids'])){
		$dbhandle = new SQLite3('./base.sqlite');
		if (!$dbhandle) die ($error);
		
		$dbhandle->busyTimeout(60000);
		
		$ids =  json_decode($_POST['ids']);
		foreach($ids as $id){
			$requete = "UPDATE feed SET protected='0' WHERE id='".$id."'";
			$result = $dbhandle->query($requete);
			if (!$result) die("Cannot execute query.");
		}
		$dbhandle->close();
		unset($dbhandle);
	}else if(isset($_POST['ids']) && !empty($_POST['ids'])){

		$dbhandle = new SQLite3('./base.sqlite');
		if (!$dbhandle) die ($error);
		
		$dbhandle->busyTimeout(60000);
		
		$ids =  json_decode($_POST['ids']);
		foreach($ids as $id){
			$requete = "DELETE FROM feed WHERE id='".$id."' AND protected='0'";
			$result = $dbhandle->query($requete);
			if (!$result) die("Cannot execute query.");
			
			$requete = "DELETE FROM discussion WHERE id_parent='".$id."'";
			$result = $dbhandle->query($requete);
			if (!$result) die("Cannot execute query.");
			
			$requete = "DELETE FROM citation WHERE id_parent='".$id."'";
			$result = $dbhandle->query($requete);
			if (!$result) die("Cannot execute query.");
		}
		$dbhandle->close();
		unset($dbhandle);
	}
	$result_array;
	if(isset($_GET['index'])){
		$index = $_GET['index'];
	}else{
		$index=0;
	}
	if($index == 0){
		$index = -1;
	}
	parse_str($_SERVER['QUERY_STRING'], $result_array);
	unset($result_array['index']);
	$_SERVER['QUERY_STRING'] = http_build_query($result_array);
	header('location: ./?'.$_SERVER['QUERY_STRING']."#".$index);
?>
