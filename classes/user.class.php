<?php
/*
 *   Ez a fájl az IFA (Iskolai Fogadóóra Adminisztráció) csomag része,
 *   This file is part of the IFA suite,
 *   Copyright 2004-2005 Szász Imre.
 *
 *   Ez egy szabad szoftver; terjeszthető illetve módosítható a GNU
 *   Általános Közreadási Feltételek dokumentumában leírtak -- 2. vagy
 *   későbbi verzió -- szerint, melyet a Szabad Szoftver Alapítvány ad ki.
 *
 *   This program is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU General Public License
 *   as published by the Free Software Foundation; either version
 *   2 of the License, or (at your option) any later version.
 */

/**
 * @file user.class.php
 *
 * User osztály
 */

#require_once('login.php');
require_once('ifa.inc.php');

class User
{
    public $logged_in = FALSE;
    public $admin = FALSE;
    public $nev = '';
    public $tip = '';
    public $id = -1;
    private $db = NULL;
#    public $menu = '';
    private $fields = array('tip', 'id', 'nev', 'logged_in', 'admin', 'jelszo', 'dnev', 'tnev', 'oszt', 'onev', 'ofo', 'ofonev');

    function __construct($request=NULL) {
        global $db;
        if( $db == NULL ) {
            throw new Exception( 'User: Database object is invalid!' );
        }

        $this->db = $db;
        $this->errors = array();

        // Ha van a $_SESSION-ben user, a sessionból kimásoljuk
        if (isset($_SESSION['id'])) {
            foreach ($this->fields as $key) {
                if (isset($_SESSION[$key])) {
                    $this->$key = $_SESSION[$key];
#                    print $this->$key . " = " . $_SESSION[$key];
                }
            }
        }
#        print_r($request);

        if (isset($request['id'])) {
            // Ha kaptunk id-t, de az más mint eddig
            if ($this->logged_in && ($request['id'] != $this->id) && !$this->admin) {
                $this->logout();
                #$this->logged_in = FALSE;
            } else {
                $this->get_user($request['tip'], $request['id']);
            }
        }
        /*
        if (isset($request['id']) && ($request['id'] != $this->id)) {

            if (!$this->admin) {
                $this->logout();
            }
            else {
                $this->get_user($request['tip'], $request['id']);
            }
        }
         */
        if ($this->tip == 'tanar') {
            $this->get_tanar();
        }
    }

    function get_user($tip, $id) {
        // A megadott user alapján beállítja: tip-id-nev
        // $this -> tip, id, 
        // egyébként logout

        // nem jó a típus vagy az id
        if (!in_array($tip, array('admin', 'tanar', 'diak')) || (!preg_match('/^[0-9]{1,10}$/', $id))) {
            throw new Exception( "Rossz tipp! tip: $tip, id: $id" );
        }

        // az admint is a diák táblából ellenőrizzük
        $table_name = (($tip == 'tanar') ? 'tanar' : 'diak');

        try {
            $user = $this->db->query("SELECT * FROM $table_name WHERE id=$id")->fetch(PDO::FETCH_ASSOC);
            // nincs találat
            if (isset($user['id'])) {
                $user['tip'] = $tip;
                foreach (array_keys($user) as $key) {
                    $this->$key = $user[$key];
                    $_SESSION[$key] = $user[$key];
                }

                if ($table_name == 'tanar') {
                    $this->nev = $user['tnev'];
                    $_SESSION['nev'] = $user['tnev'];
                } else {
                    $this->nev = $user['dnev'];
                    $_SESSION['nev'] = $user['dnev'];
                }
            } else {
                $this->logout();
                throw new Exception( 'Valami nem jó!' );
            }

        } catch (PDOException $e) {
            die($e->getMessage());
        }

    }

    function login_form($hiba=NULL) {
        global $FA;
        $out = '';

        if (isset($hiba)) hiba($hiba);

//függvény: valid_term: ha diák, akkor az időszakot is nézni kell

        if ($this->tip == 'diak' && !$FA->valid) {
            $out .= "<h2 style='color: red;'>Nincs feliratkozási időszak!</h2>\n"
                . "<h3>Fogadóóra napja: " . $FA->datum_str . "\n"
                . "<br><span class=\"kicsi\">" . $FA->valid_kezd_str . "</b> &nbsp; és &nbsp; <b>"
                . $FA->valid_veg_str . "</b> &nbsp; között lehet feliratkozni.</span>\n<hr>\n";
            return $out;
        }

        $out .= "<table width=\"100%\"><tr><td>\n";
        $out .= "\n<h3>" . $this->nev . ($this->tip =='diak'?' ('.$this->onev.')':'') . "</h3>\n"
            . "<form name=\"login\" action=\"" . $_SERVER['REQUEST_URI'] . "\" method=\"POST\">\n"
            . "  Jelszó: <input type=\"password\" size=\"8\" name=\"jelszo\" autofocus>\n"
            . "  <input type=\"hidden\" name=\"id\" value=\"" . $this->id . "\">\n"
            . "  <input type=\"hidden\" name=\"tip\" value=\"" . $this->tip . "\">\n"
            . "  <input type=\"submit\" value=\"Belépés\">\n"
            . "  " . $this->tip . " (" . $this->id . ")\n"
            . "</form>\n\n"
            . "<td align=\"right\" valign=\"top\" class=\"sans\"><a href=\"?leiras\"> Leírás </a>\n</table>\n";

        return $out;
    }

    function login($jelszo='')
    {
        global $tanar_auth;
        global $google_domain;

        // $_SESSION-ban már minden bent van, csak a jelszót kell ellenőrizni
        #if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === TRUE) {
        if (isset($this->logged_in) && $this->logged_in === TRUE) {
            return TRUE;
        }

        $jelszo = stripslashes($jelszo);
        $jo = FALSE;
        switch ($this->tip) {
            case 'tanar':
                switch ($tanar_auth) {
                    case 'PAM':
                        $jo = (pam_auth($user['emil'], $jelszo, $error));
                        break;
                    case 'DB':
                        $jo = (hash('sha256', $jelszo) == $this->jelszo);
                        break;
                    case 'LDAP':
                        if ((strlen(trim($jelszo)) != 0) && $connect = ldap_connect($ldap['host'])) {
                            ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION, $ldap['version']);
                            $filter = "(uid=".$this->emil.")";
                            $result = ldap_search($connect, $ldap['base'], $filter);
                            $entries = ldap_get_entries($connect, $result);
                            if ($entries['count'] != 1) { break; }
                            $dn = $entries[0]['dn'];
                            $jo = @ldap_bind($connect, $dn, $jelszo);
                            @ldap_unbind ($connect);
                        }
                        break;
                    case 'GOOGLE':
                            $jo = TRUE;
                        break;
                }
                break;

            case 'diak':
                $jo = (hash('sha256', $jelszo) == $this->jelszo);
                break;

            case 'admin':
                $jo = (hash('sha256', $jelszo) == $this->jelszo);
                if ($jo) {
                    $_SESSION['admin'] = TRUE;
                    $this->admin = TRUE;
                }
                break;
        }

        // végül logoljuk és kilépünk
        if ($jo) {
            $this->logged_in = TRUE;
            $_SESSION['logged_in'] = TRUE;
            foreach ($this->fields as $key) {
                print $key . " ";
                if (isset($this->$key)) {
                    $_SESSION[$key] = $this->$key;
                }
            }
            ulog ($this->id, $this->nev . " bejelentkezett.");
            return TRUE;
        }
        else {
            $this->logged_in = FALSE;
            return FALSE;
        }
    }

    function logout() {
        $_SESSION = array();
        session_destroy();
        $this->logged_in = FALSE;
    }

    function logged_in() {
        return $this->logged_in;
    }

    function is_admin() {
        return $this->admin;
    }


    function get_tanar()
    {
/*

        [id] => 83                # A tanár azonosítója
        [ODD] => 0                # Van-e benne páratlan (5 perces) időpont
        [fogad] => 1              # Van-e a tanárnál fogadóóra bejegyezve
        [emil] => 'monoton'       # azonosító
        [tnev] => 'Monoton Manó'  # név
        [fogado_ido] => Array (
                [192] => Array ( [diak] => 371,  [dnev] => 'Pumpa Pál (12. X)' )
                [193] => Array ( [diak] => -1,   [dnev] => '' )
                ...
            )
        [IDO_min] => 192          # első időpontja
        [IDO_max] => 228          # utolsó időpontja + 1

*/
        $this->ODD = false;
        $this->fogad = false;

        /*
        # Feltöltjük a tanár tulajdonságait
        try {
            $res = $db->prepare("SELECT * FROM Tanar WHERE id=?");
            $res->execute(array($this->id));
            $rows = $res->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { die($e->getMessage()); }

        if (count($rows) == 0) { die("Nincs ilyen tanár: $tid"); }

        foreach($rows[0] as $key => $value) {
            $this->$key = $value;
        }
         */

        $q = "SELECT ido, diak, dnev || ' (' || onev || ')' AS dnev"
                . "    FROM Fogado AS F"
                . "  LEFT OUTER JOIN"
                . "    Diak AS D"
                . "      ON (F.diak=D.id AND D.id>0)"
                . "  WHERE F.fid=" . fid . " AND F.tanar=" . $this->id
                . "      ORDER BY ido";

        try {
            $res = $this->db->query($q);

            $rows = $res->fetchAll(PDO::FETCH_ASSOC);
            if (count($rows) > 0) $this->fogad = true;             // ha van időpontja akkor fogad
            else $this->fogad = false;
            foreach ($rows as $f) {
                $this->fogado_ido[$f['ido']] = array('diak'=>$f['diak'], 'dnev'=>$f['dnev']);
            }
        }
        catch (PDOException $e) { echo $e->getMessage(); }

        if (!isset($this->fogado_ido)) { return; }

        // ODD: ido1 | ido2 | ido3 ... ha volt benne páratlan, végül páratlan lesz
        foreach (array_keys($this->fogado_ido) as $ido) {
            if ($this->fogado_ido[$ido]['diak'] >= 0) { // ha fogad ebben az időben
                $this->ODD |= $ido;
            }
        }
        // az ODD, az "összegzett" idők paritása kell nekünk
        $this->ODD &= 1;

        if ($this->fogad) {
            $this->IDO_min = min(array_keys($this->fogado_ido));
            $this->IDO_max = max(array_keys($this->fogado_ido)) + 1;
        }

    }

    function menu() {
        $out = ''
            . '<div id="menu" class="noprint sans">'
            . "  <a href='" . $_SERVER['PHP_SELF'] . '?tip=' . $this->tip . '&id=' . $this->id . "'>Táblázat</a> | \n"
            . "  <a href='" . $_SERVER['PHP_SELF'] . '?osszesit' . "'>Összesítés</a> | \n"
            . "  <a href='" . $_SERVER['PHP_SELF'] . '?leiras' . "'>Leírás</a> | \n"
            . " <a href='" . $_SERVER['PHP_SELF'] . "?kilep='>Kilépés</a>\n"
            . '</div>';
        return $out;
    }

    function fejlec() {
        global $FA;
        $Fejlec =
              "  <script language=JavaScript type='text/javascript'><!--\n"
            . "    function torol(sor) {\n"
            . "    eval('var s = document.tabla.'+sor);\n"
            . "    for (var i=0; i<s.length; i++)\n"
            . "      s[i].checked=0;\n"
            . "    }\n"
            . "  //--></script>\n\n"
            . "<table width='100%'><tr><td>\n"
            . "<h3>" . $this->dnev . " " . $this->onev
            . " <span class='kicsi'>(" . $FA->datum . ")</span><br>\n"
            . "<span class='kicsi'>(Osztályfőnök: " . $this->ofonev . ")</span></h3>\n"
            . "<td align=right valign=top><span class='noprint sans'></tr></table>\n";
        return $Fejlec;
    }

}
