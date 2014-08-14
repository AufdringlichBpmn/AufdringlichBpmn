<?php

global $CONFIG;
$CONFIG->taskImpls[] = CreateLyxDocument::class;
$CONFIG->taskImpls[] = CreatePdf::class;

class CreateLyxDocument extends \elements\AbstractServiceTaskImpl{
	function processServiceTask(){
		$lyx = file_get_contents("../examples/VertragKuendigen.lyx");
		$lyx = preg_replace("/\{\{Kunde_Name\}\}/", $this->process->get("Kunde_Name"), $lyx);
		$lyx = preg_replace("/\{\{Kunde_Strasse\}\}/", $this->process->get("Kunde_Strasse"), $lyx);
		$lyx = preg_replace("/\{\{Kunde_Plz_Ort\}\}/", $this->process->get("Kunde_Plz_Ort"), $lyx);
		$lyx = preg_replace("/\{\{Vertragspartner_Name\}\}/", $this->process->get("Vertragspartner_Name"), $lyx);
		$lyx = preg_replace("/\{\{Vertragspartner_Strasse\}\}/", $this->process->get("Vertragspartner_Strasse"), $lyx);
		$lyx = preg_replace("/\{\{Vertragspartner_Plz_Ort\}\}/", $this->process->get("Vertragspartner_Plz_Ort"), $lyx);

		$tmpfname = tempnam(sys_get_temp_dir(), 'VertragKuendigen');
		file_put_contents($tmpfname, $lyx);
		
		$this->process->put("filename.lyx", $tmpfname);
		return "ok";
	}
}

class CreatePdf extends \elements\AbstractServiceTaskImpl{
	function processServiceTask(){
		$tmpfname = $this->process->get("filename.lyx");
		`lyx -e pdf $tmpfname`;
		return "ok";
	}
}

