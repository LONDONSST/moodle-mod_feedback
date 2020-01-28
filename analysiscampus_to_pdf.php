<?php

require_once("../../config.php");
require_once($CFG->libdir . '/pdflib.php');
TCPDF_FONTS::addTTFfont('/full_path_to/ARIALUNI.TTF', 'TrueTypeUnicode');

$doc = new pdf;
$doc->setPrintHeader(false);
$doc->setPrintFooter(false);
$doc->AddPage();
//$doc->Write(5, 'Hello World!');
//$doc->Output();
$html = "";
$html .= $_POST["htmlpdf"];
$html = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html);

// output the HTML content
$doc->writeHTML($html, true, false, true, false, '');

$doc->Output('campuswise-analysis_' . date('d-M-Y') . '.pdf', 'D');

?>

