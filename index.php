<?
/*
 *   Ez a f�jl az IFA (Iskolai Fogad��ra Adminisztr�ci�) csomag r�sze,
 *   This file is part of the IFA suite,
 *   Copyright 2004-2005 Sz�sz Imre.
 *
 *   Ez egy szabad szoftver; terjeszthet� illetve m�dos�that� a GNU
 *   �ltal�nos K�zread�si Felt�telek dokumentum�ban le�rtak -- 2. vagy
 *   k�s�bbi verzi� -- szerint, melyet a Szabad Szoftver Alap�tv�ny ad ki.
 *
 *   This program is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU General Public License
 *   as published by the Free Software Foundation; either version
 *   2 of the License, or (at your option) any later version.
 */

function kuki_teszt() {
	session_start();
	$self = $_SERVER['PHP_SELF'];
	if (!$_GET['FID'] && !$_SESSION['cookie']) {
		header ("Location: " . $_SERVER['PHP_SELF'] . "?FID=" . session_id());
		exit;
	}
	if (!$_SESSION['cookie']) {
		if (session_id() == $_GET['FID']) {
			$_SESSION['cookie'] = true;
			header ("Location: " . $_SERVER['PHP_SELF']);
			exit;
		} else {
			$_SESSION['cookie'] = false;
		}
	}
	session_write_close();
	return $_SESSION['cookie'];
}

if (!kuki_teszt()) {
	echo "<div align=center><font color=red><h3>FIGYELEM!</h3></font><br>\n";
	echo "A b�ng�sz�j�ben enged�lyeznie kell a s�ti (cookie) fogad�s�t a " . $_SERVER['PHP_SELF'] . " g�pr�l!</div>\n";
	exit;
}
?>
<html>
<base target="_top">
<head>
  <title>Fogad��ra</title>
  <meta name="Author" content="Sz�sz Imre">
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-2">
</head>

<frameset cols="200,*" marginwidth=0 border=0>
  <frame src="osztaly.php" name="index" frameborder="0" marginwidth=4 marginheight=3>
  <frame src="leiras.html" name="duma" frameborder="0">
</frameset>

</html>
