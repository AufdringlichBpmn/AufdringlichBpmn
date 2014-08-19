<?php

global $CONFIG;
$CONFIG->taskImpls[] = CreateLyxDocument::class;
$CONFIG->taskImpls[] = CreatePdf::class;
$CONFIG->taskImpls[] = PdfDownload::class;

class CreateLyxDocument extends \elements\AbstractServiceTaskImpl{
	function processServiceTask(){
		global $CONFIG;
		$lyx = file_get_contents("../examples/VertragKuendigen.lyx");
		$lyx = preg_replace("/\{\{Kunde_Name\}\}/", $this->process->get("Kunde_Name"), $lyx);
		$lyx = preg_replace("/\{\{Kunde_Strasse\}\}/", $this->process->get("Kunde_Strasse"), $lyx);
		$lyx = preg_replace("/\{\{Kunde_Plz_Ort\}\}/", $this->process->get("Kunde_Plz_Ort"), $lyx);
		$lyx = preg_replace("/\{\{Vertragspartner_Name\}\}/", $this->process->get("Vertragspartner_Name"), $lyx);
		$lyx = preg_replace("/\{\{Vertragspartner_Strasse\}\}/", $this->process->get("Vertragspartner_Strasse"), $lyx);
		$lyx = preg_replace("/\{\{Vertragspartner_Plz_Ort\}\}/", $this->process->get("Vertragspartner_Plz_Ort"), $lyx);

		$tmpfname = '/temp/VertragKuendigen'.uniqid();
		mkdir($_SERVER['DOCUMENT_ROOT'].'/temp');
		file_put_contents($_SERVER['DOCUMENT_ROOT'].$tmpfname, $lyx);
		
		$this->process->put("filename.lyx", $tmpfname);
		return "ok";
	}
}

class CreatePdf extends \elements\AbstractServiceTaskImpl{
	function processServiceTask(){
		$tmpfname = $this->process->get("filename.lyx");
		$lyxFile = $_SERVER['DOCUMENT_ROOT'].$tmpfname;
		`lyx -e pdf $lyxFile`;
		$this->process->put("filename.pdf", $tmpfname.".pdf");
		return "ok";
	}
}

class PdfDownload extends \elements\AbstractServiceTaskImpl{
	function processServiceTask(){
// 		$tmpfname = $this->process->get("filename.lyx");
// 		passthru('cat '.$tmpfname.'.pdf');
		return "ok";
	}
}

