<?php

global $CONFIG;
$CONFIG->taskImpls[] = CreateLyxDocument::class;
$CONFIG->taskImpls[] = CreatePdf::class;
$CONFIG->taskImpls[] = GenerateTexts::class;
$CONFIG->taskImpls[] = PrintPdf::class;

class CreateLyxDocument extends \elements\AbstractServiceTaskImpl{
	function processServiceTask(){
		// fill template
		$lyx = file_get_contents("../examples/VertragKuendigen.lyx");
		// apply Mustache
		foreach($this->process->variables as $name => $value){
			$lyx = preg_replace("/\{\{$name\}\}/", $value, $lyx);
		}

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

class GenerateTexts extends \elements\AbstractServiceTaskImpl{
	function processServiceTask(){
		// fill variables
		$vertragsnummer = $this->process->get("Vertragsnummer");
		$termin = $this->process->get("Kündigungstermin");
		$this->process->put("Datum", date("d.m.Y"));
		$this->process->put("Betreff", "Kündigung des Vertrages $vertragsnummer");
		$this->process->put("Anrede", "Sehr geehrte Damen und Herren");
		$this->process->put("Text", "Hiermit kündige ich den Vertrag mit der Nummer $vertragsnummer fristgerecht zum $termin. Bitte bestätigen Sie mir den Erhalt der Kündigung.");
		return "ok";
	}
}

class PrintPdf extends \elements\AbstractServiceTaskImpl{
	function processServiceTask(){
		$tmpfname = $this->process->get("filename.pdf");
		$pdfFile = $_SERVER['DOCUMENT_ROOT'].$tmpfname;
		`lp $pdfFile`;
		return "ok";
	}
}


