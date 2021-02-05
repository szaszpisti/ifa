<?php

require_once('ifa.inc.php');

$q = $_REQUEST["q"];

function get_meet_table() {
    global $db;
    $out = "";

    $res = $db->prepare('SELECT * FROM tanar');
    $res->execute();
    $tanarok = $res->fetchAll(PDO::FETCH_ASSOC);
    $out .= "<table>\n";
    foreach ($tanarok as $tanar) {
        $out .= "  <tr><td class='meetlink'>";
        $out .= "<a href='" . $tanar['meet'] . "'>" . $tanar['meet'] . "</a>\n";
        $out .= "</td><td>";
        $out .= $tanar['tnev'];
        $out .= "</td></tr>";
    }
    $out .= "</table>\n";
	return $out;
}

if ($q == 'meet') {
    print get_meet_table();
}

?>
