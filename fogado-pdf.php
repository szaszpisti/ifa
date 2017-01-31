<?php
# -> apt install php-tcpdf vagy composer...

require_once('login.php');
require_once('ifa.inc.php');
require_once('tanar.class.php');

if (__DEBUG__) {
    $outputDir = getcwd() . '/';
    $outputTarget = 'F';
} else {
    $outputDir = '';
    $outputTarget = 'D';
}

class PDF extends TCPDF
{

    var $W = 85; # egy mező szélessége

    var $namePaddings = [0, 1, 0, 2]; /* a tanár nevének térköze */
    var $itemPadding = 15; /* két tanár közti hely */
    var $timeTab = 15; /* az időpont és a szülő neve közti tabulálási pozíció */

    var $X1 = 10;  /* az első és második oszlop X koordinátája */
    var $X2 = 115;

#    var $defaultFont = 'times';
    var $defaultFont = 'dejavusans';
    private $nameFont = ['B', 13];
    private $itemFont = ['', 11];
    private $szuloiFont = ['I', 11];

    var $szuloiText = 'Szülői értekezlet';
    private $firstItem = true;

    function __construct(){
        parent::__construct();
        $this->print_header = false;
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
        $this->AddPage();
    }

    private function mySetFont($font) {
        $this->SetFont($this->defaultFont, $font[0], $font[1]);
    }

    function addTanar($tanar) {
        $this->startTransaction();
        /* Egyesével hozzáadjuk a sorokat. Ha lapváltás van, akkor rollback és az egészet új lapra */
        $oldPageNo = $this->PageNo();

        # Ha ez az első tanár, akkor nem kell a padding
        if($this->firstItem) {
            $this->firstItem = false;
        } else {
            $this->SetY($this->y + $this->itemPadding);
        }
        $Y = $this->y;

        $this->putOszlop($tanar, $this->X1);

        if ($this->PageNo() == $oldPageNo) {
            $this->SetY($Y);
            $this->putOszlop($tanar, $this->X2);
            $this->commitTransaction();
        } else {
            $this->rollbackTransaction(true);
            $this->AddPage();
            $Y = $this->y;
            $this->putOszlop($tanar, $this->X1);
            $this->SetY($Y);
            $this->putOszlop($tanar, $this->X2);
        }

    }

    private function putOszlop($tanar, $x) {
        $this->mySetFont($this->nameFont);

        /* A kezdő vonalat vastagabban rajzoljuk */
        $lineWidth = $this->GetLineWidth();
        $this->Line($x, $this->y, $x+$this->W, $this->y, array('width'=>.7));
        $this->SetLineWidth($lineWidth);

        $p = $this->namePaddings;
        $this->setCellPaddings($p[0], $p[1], $p[2], $p[3]);
        $this->MultiCell($this->W, 0, $tanar->nev, 'TB', $align='L', $fill=false, $ln=1, $x=$x);
        $this->SetCellPadding(0);

        foreach ($tanar->szulok as $szulo){
            $this->mySetFont($this->itemFont);
            $this->MultiCell($this->timeTab, 0, $szulo['ido'], 0, 'L', false, 0, $x); # először beírjuk az időt timeTab szélességben
            if ($szulo['nev'] == $this->szuloiText) {
                $this->mySetFont($this->szuloiFont);
                $szulo['nev'] = '   '.$szulo['nev'];
            }
            $this->MultiCell(0, 0, $szulo['nev'], '', 'L'); # utána a szülő nevét
            $this->Line($x, $this->y, $x+$this->W, $this->y); # majd a végére egy voonalat
        }
    }

}

class TanarLista
{
    var $szulok = array();
    var $nev;

    function __construct($nev){
        $this->nev = $nev;
    }

    function putSzulo($ido, $nev) {
        $this->szulok[] = array('ido'=>$ido, 'nev'=>$nev);
    }

}

$pdf = new PDF();
$res = $db->query( "SELECT id FROM Tanar ORDER BY tnev;" );
foreach($res->fetchAll() as $tanar) {
    $TANAR = new Tanar($tanar['id']);

    # Ha egyáltalán itt van...
    if (isset($TANAR->IDO_min)) {
        $voltSzuloi = false;
        $t = new TanarLista($TANAR->tnev);
        for ($ido = $TANAR->IDO_min; $ido<$TANAR->IDO_max; $ido++) {
            if (isset($TANAR->fogado_ido[$ido]['diak'])) {
                $fogadoIdo = FiveToString($ido);
                $fogadoNev = $TANAR->fogado_ido[$ido]['dnev'];
                if ($TANAR->fogado_ido[$ido]['diak'] == '-2') {
                    if (!$voltSzuloi) {
                        $fogadoNev = 'Szülői értekezlet';
                        $voltSzuloi = true;
                    }
                }
            }
            if ($fogadoNev) {
                $t->putSzulo($fogadoIdo, $fogadoNev);
            }
        }

        $pdf->addTanar($t);
    }
}



$outputFilename = 'fogadoora-' . date('Y.m.d-His') . '.pdf';
$pdf->Output($outputDir . $outputFilename, $outputTarget);

?>
