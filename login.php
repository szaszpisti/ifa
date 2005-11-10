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

require_once('fogado.inc.php');

session_start();

function redirect($uri = '') {
	if ($uri==='') $uri = $_SERVER['REQUEST_URI'];
	header ("Location: $uri");
}

// ellen�rzi, hogy adott t�pus, id eset�n l�tezik-e az userid
function get_user($tip, $id) {
	global $db;
	if ($tip == 'admin') $tip='diak';
	if (($tip != 'tanar') && ($tip != 'diak')) return (NULL);

	$user =& $db->getRow("SELECT * FROM $tip WHERE id=$id");
	if (DB::isError($user)) { die($user->getMessage()); }

	$user['nev'] = $user['tnev'] . $user['dnev']; // pontosan az egyik l�tezik
	return ($user);
}

if (isset($_REQUEST['kilep']) ) {
	session_destroy();
	redirect('leiras.html');
}

// Ha tip, id j�tt a REQUEST-ben, akkor azt vessz�k figyelembe,
// egy�bk�nt a SESSION-v�ltoz�t.

$tip = isset($_REQUEST['tip'])?$_REQUEST['tip']:$_SESSION['tip'];
$id  = isset($_REQUEST['id'])?$_REQUEST['id']:$_SESSION['id'];
if (!isset($id)) { $tip = 'admin'; $id = 0; }

$user = get_user($tip, $id);
if (!$user) $hiba = "Nincs ilyen felhaszn�l�!";

if ((!$_SESSION['admin']) && ($tip == 'diak') && (!$FA->valid)) {
	print "<h3>Nincs bejelentkez�si id�szak!</h3>\n"
		. "<b>" . substr($FA->valid_kezd, 0, 16) . "</b> &nbsp; �s &nbsp; <b>"
		. substr($FA->valid_veg, 0, 16) . "</b> &nbsp; k�z�tt lehet bejelentkezni.\n";
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

elseif ( (isset($_POST['jelszo'])) && (strlen($_POST['jelszo']) > 0) ) {
	$jo = false;
	switch ($tip) {
		case 'tanar':
			switch ($tanar_auth) {
				case 'PAM':
					$jo = (($user) && (pam_auth($user['emil'], $_POST['jelszo'], &$error)));
					break;
				case 'DB':
					$jo = (($user) && (md5($_POST['jelszo']) == $user['jelszo']));
					break;
				case 'LDAP':
					$dn = preg_replace ('/#USER#/', $user['emil'], $ldap['base']);
//					$dn = 'uid=' . $user['emil'] . ',' . $ldap['base'];
					if($connect = ldap_connect($ldap['host'])) {
						ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION, $ldap['version']);
						$jo = @ldap_bind($connect, $dn, $_POST['jelszo']);
						@ldap_unbind ($connect);
					}
					break;
			}
			break;

		case 'diak':
			$jo = (($user) && (md5($_POST['jelszo']) == $user['jelszo']));
			break;

		case 'admin':
			$jo = (($user) && (md5($_POST['jelszo']) == $user['jelszo']));
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

	print "<table width=\"100%\"><tr><td>\n";
	if (isset($hiba)) { hiba($hiba); }
	print "\n<h3>" . $user['nev'] . ($tip=='diak'?' ('.$user['onev'].')':'') . "</h3>\n"
		. "<form name=login action='" . $_SERVER['REQUEST_URI'] . "' method=post>\n"
		. "  Jelsz�: <input type=password size=8 name=jelszo>\n"
		. "  <input type=hidden name=id value=" . $id . ">\n"
		. "  <input type=hidden name=tip value=" . $tip . ">\n"
		. "  <input type=submit value='Bel�p�s'>\n"
		. "</form>\n\n"
		. "<td align=right valign=top><a href=\"leiras.html\"> Le�r�s </a>\n</table>\n";
	tail();
	exit;
}

define('ADMIN', $_SESSION['admin']);

session_write_close();

?>
