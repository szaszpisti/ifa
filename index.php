<?
function kuki_teszt() {
	session_start();
	$self = $_SERVER['PHP_SELF'];
	if (!$_GET['FID'] && !$_SESSION['cookie']) {
		header ("Location: " . $_SERVER['PHP_SELF'] . "?FID=" . session_id());
		exit;
	}
	if (!$_SESSION['cookie']) {
		if (session_id() == $_GET['FID']) {
			$_SESSION['cookie'] = "1";
			header ("Location: " . $_SERVER['PHP_SELF']);
			exit;
		} else {
			$_SESSION['cookie'] = "0";
			session_id($_GET['FID']);
		}
	}
	session_write_close();
	return $_SESSION['cookie'];
}

if (!kuki_teszt()) {
	echo "<a href=cookie.html>FIGYELEM!<br>Nincs cookie!</a>\n";
	exit;
}
?>
<html>
<base target="_top">
<head>
   <title>Fogadóóra</title>
   <meta name="Author" content="Szász Imre">
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-2">
</head>

<frameset cols="200,*" marginwidth=0 border=0>
  <frame src="osztaly.php" name="index" frameborder="0" marginwidth=4 marginheight=3>
  <frame src="leiras.html" name="duma" frameborder="0">
</frameset>

</html>
