<?
require_once('fogado.inc.php');


session_start();

if (isset($_REQUEST['kilep']) ) {
	session_destroy();
	redirect('leiras.html');
}

function redirect($uri = '') {
	if ($uri==='') $uri = $_SERVER['REQUEST_URI'];
	header ("Location: $uri");
}

function get_user($tip, $id) {
	if ($tip == 'admin') $tip='diak';
	if (($tip != 'tanar') && ($tip != 'diak')) return (NULL);
	if (($res = pg_query("SELECT * FROM $tip WHERE id=" . $id)) && pg_num_rows($res)===1) {
		$user = pg_fetch_assoc($res);
		$user['nev'] = $user['tnev'] . $user['dnev']; // pontosan az egyik l�tezik
		return ($user);
	}
	else return(NULL);
}

$tip = isset($_REQUEST['tip'])?$_REQUEST['tip']:$_SESSION['tip'];
$id  = isset($_REQUEST['id'])?$_REQUEST['id']:$_SESSION['id'];

$user = get_user($tip, $id);
if (!$user) $hiba = "Nincs ilyen felhaszn�l�!";

if ((!$_SESSION['admin']) && ($tip == 'diak') && (!$FA->valid)) {
	print "<h3>Nincs bejelentkez�si id�szak!</h3>\n"
		. "<b>" . substr($FA->valid_kezd, 0, -3) . "</b> &nbsp; �s &nbsp; <b>"
		. substr($FA->valid_veg, 0, -3) . "</b> &nbsp; k�z�tt lehet bejelentkezni.\n";
	exit;
}

if ($_SESSION['valid']) {

	// ha kaptunk id-et, akkor vsz. �j identit�s kell
	if (isset($_REQUEST['tip']) && isset($_REQUEST['id'])) {

		// admin automatikusan megkapja, regisztr�ljuk a sessionbe.
		if ($_SESSION['admin'] && get_user($tip, $id)) {
			$_SESSION['tip'] = $tip;
			$_SESSION['id']  = $id;
		}
		// Ha v�ltozott, akkor �jrakezdj�k a bejelentkez�st
		elseif (($_SESSION['tip'] !== $tip) || ($_SESSION['id'] !== $id)) {
			$_SESSION['valid'] = false;
			redirect();
		}
	}
}

elseif (isset($_POST['jelszo']) ) {
	$jo = false;
	switch ($tip) {
		case 'tanar':
			$jo = (($user) && (pam_auth($user['emil'], $_POST['jelszo'], &$error)));
			break;

		case 'diak':
			$jo = (($user) && (md5($_POST['jelszo']) === $user['jelszo']));
			break;

		case 'admin':
			$jo = (($user) && (md5($_POST['jelszo']) === $user['jelszo']));
			if ($jo) $_SESSION['admin'] = true;
			break;
	}
	if ($jo) {
		$_SESSION['tip']   = $tip;
		$_SESSION['id']    = $id;
		$_SESSION['nev']   = $user['nev'];
		$_SESSION['valid'] = true;
	}
	if ($_SESSION['valid']) ulog ($user['id'], $user['nev'] . " bejelentkezett.");
	elseif (!isset($hiba)) $hiba = "�rv�nytelen bejelentkez�s ($tip, $id)!";
}

if (!$_SESSION['valid']) {
	session_destroy();

	head("Fogad��ra - " . $user['nev'], ' onLoad="document.login.jelszo.focus()"');

	if (isset($hiba)) { hiba($hiba); }
	print "\n<h3>" . $user['nev'] . ($tip=='diak'?' ('.$user['onev'].')':'') . "</h3>\n"
		. "<form name=login action='" . $_SERVER['REQUEST_URI'] . "' method=post>\n"
		. "  Jelsz�: <input type=password size=8 name=jelszo>\n"
		. "  <input type=hidden name=id value=" . $id . ">\n"
		. "  <input type=hidden name=tip value=" . $tip . ">\n"
		. "  <input type=submit value='Bel�p�s'>\n"
		. "</form>\n\n";
	tail();
	exit;
}

define('ADMIN', $_SESSION['admin']);

session_write_close();

?>
