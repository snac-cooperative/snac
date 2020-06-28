<?php

/**
 * EAD Parser File
 *
 * Contains the parser for EAD into TSV files 
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2020 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\util;

/**
 * EAD Parser
 *
 * This class provides the utility to parser EAD files into OpenRefine TSV files.
 * After parsing using Saxon, it returns the zip file containing the files.
 *
 * @author Robbie Hott
 *
 */
class EADParser {

	private $logger = null; 

	public function __construct() {
		global $log;

		// create a log channel
		$this->logger = new \Monolog\Logger('EADParser');
		$this->logger->pushHandler($log);
	}

	public function parseZip($zipcontents) {
		$tmpdir = \snac\Config::$EAD_PARSETMP_DIR . "/". microtime(true);
		$this->logger->addDebug("creating tmp directory");
		mkdir($tmpdir);
		$infile = $tmpdir."/upload.zip";
		$outfile = $tmpdir."/output.zip";
		$eaddir = $tmpdir."/ead";
		$outputdir = $tmpdir."/output";

		$toReturn = false;

		try {
			$this->logger->addDebug("Writing zip contents");
			file_put_contents($infile, $zipcontents);

			$this->logger->addDebug("Unzipping");
			$zip = new \ZipArchive();
			$result = $zip->open($infile, \ZipArchive::CHECKCONS);
			if ($result !== true) {
				switch($result) {
				case ZipArchive::ER_NOZIP:
					throw new \Exception('Uploaded file is not a zip archive.');
				case ZipArchive::ER_INCONS :
					throw new \Exception('Uploaded file failed consistency check.');
				case ZipArchive::ER_CRC :
					throw new \Exception('Uploaded file failed checksum.');
				default:
					throw new \Exception('An error occurred: ' . $res);
				}
			}
			// TODO make this a little prettier, flatten the structure and only get xml files
			$zip->extractTo($eaddir);
			$zip->close();

			$this->logger->addDebug("Unzipped");


			/* SAXON/C PHP does not work	

			// Define the XML file (full path)
			$xmlfile = \snac\Config::$EAD_PARSE_XSLT_DIR."/ead_parse_driver.xml";
			// Define the XSLT file (full path)
			$xslfile = \snac\Config::$EAD_PARSE_XSLT_DIR."/eadToORxsl.xsl";

			$this->logger->addDebug("Creating SAXON Parser");
			// Create the SAXON parser
			$proc = new \Saxon\SaxonProcessor();
			//$version = $proc->version();
			//echo 'Saxon Processor version: '.$version;

			// Create the XSLT3 parser and compile the XSLT code
			$xslt3p = $proc->newXslt30Processor();
			$this->logger->addDebug("Loading XSLT: $xslfile");
			$xslt3p->compileFromFile($xslfile);
			$this->logger->addDebug("Done loading XSLT");

			// Define the base output URI
			// this is the base of where the output directory will be, however it is
			// NOT the base of the XSLT execution.
			$xslt3p->setProperty("BaseOutputURI", $tmpdir);
			$xslt3p->setOutputFile("tmpoutput.xml");

			// The path of the source input files (EAD).  This should be a full path or
			// relative to the path that the XSLT file is being run (defined above)
			$xslt3p->setParameter("sourceFolderPath", $proc->createAtomicValue($eaddir));

			// The path of the output TSV files (this is relative to the BaseOutputURI above)
			$xslt3p->setParameter("outputFolderPath", $proc->createAtomicValue($outputdir));

			// The source identifier
			$xslt3p->setParameter("sourceID", $proc->createAtomicValue("test"));

			// Parse the XML file (unused) and run the XSLT template.  This will not actually
			// create a file, but it is needed
			$this->logger->addDebug("Running SAXON");
			$in = $proc->parseXmlFromFile($xmlfile);
			$xslt3p->setInitialMatchSelection($in);
			$out = $xslt3p->applyTemplatesReturningFile();
			 */

			$xmlfile = \snac\Config::$EAD_PARSE_XSLT_DIR."/ead_parse_driver.xml";
			$xslfile = \snac\Config::$EAD_PARSE_XSLT_DIR."/eadToORxsl.xsl";
			$tmpoutfile = $tmpdir."/tmpoutput.xml";

			$descriptorspec = array(
				0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
				1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
				2 => array("pipe", "a")
			);
			$pipes = array();
			$process = proc_open("java -cp ".\snac\Config::$SAXON_JARFILE." net.sf.saxon.Transform -s:$xmlfile -xsl:$xslfile -o:$tmpoutfile sourceFolderPath=$eaddir outputFolderPath=$outputdir sourceID=snac 2>&1", $descriptorspec, $pipes);

			$procOutput = "";

			if (is_resource($process)) {
				fclose($pipes[0]);
				$procOutput = stream_get_contents($pipes[1]);
				fclose($pipes[1]);
				fclose($pipes[2]);

				// It is important that you close any pipes before calling
				// proc_close in order to avoid a deadlock
				$return_value = proc_close($process);

			}
			if ($return_value != 0) {
				$this->logger->addDebug("Error occurred while running SAXON: $procOutput");
				throw new \Exception("Error in SAXON\n$procOutput");
			}


			$this->logger->addDebug("Done Running SAXON");

			$this->logger->addDebug("Creating output ZIP file");
			$zip = new \ZipArchive();
			if ($zip->open($outfile, \ZipArchive::CREATE) !== true) {
				throw new \Exception("Could not create output Zip file");
			} 
			$this->logger->addDebug("Adding Zip Content");
			$zip->addFile($outputdir."/CPF-Join-Table.tsv", "CPF-Join-Table.tsv");
			$zip->addFile($outputdir."/CPF-Table.tsv", "CPF-Table.tsv");
			$zip->addFile($outputdir."/RD-Table.tsv", "RD-Table.tsv");

			$this->logger->addDebug("Done writing Zip");
			// close zip for downloading
			$zip->close();


			$this->logger->addDebug("Loading Content of Zip file to return");
			// show ZIP file
			$toReturn = file_get_contents($outfile);

		} catch (\Exception $e) {
			$this->cleanup($tmpdir);
			throw new \snac\exceptions\SNACEADParserException($e);
		}


		$this->logger->addDebug("Cleaning up");
		$this->cleanup($tmpdir);

		$this->logger->addDebug("Returning Zip file");
		return $toReturn;
	} 

	private function cleanup($dir) {
		//unlink($dir);
		$this->delTree($dir);
	}

	/**
	 * From https://www.php.net/manual/en/function.rmdir.php
	 */
	public function delTree($dir) {
		$files = array_diff(scandir($dir), array('.','..'));
		foreach ($files as $file) {
			(is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
		}
		return rmdir($dir);
	} 

	public function parseURL($url) {
		$tmpdir = \snac\Config::$EAD_PARSETMP_DIR . "/". microtime(true);
		$this->logger->addDebug("creating tmp directory");
		mkdir($tmpdir);
		$outfile = $tmpdir."/output.zip";
		$outputdir = $tmpdir."/output";

		$toReturn = false;

		try {	
			$this->logger->addDebug("Running Saxon");
			$xmlfile = \snac\Config::$EAD_PARSE_XSLT_DIR."/ead_parse_driver.xml";
			$xslfile = \snac\Config::$EAD_PARSE_XSLT_DIR."/eadToORxsl.xsl";
			$tmpoutfile = $tmpdir."/tmpoutput.xml";

			$descriptorspec = array(
				0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
				1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
				2 => array("pipe", "a")
			);
			$pipes = array();
			$process = proc_open("java -cp ".\snac\Config::$SAXON_JARFILE." net.sf.saxon.Transform -s:$xmlfile -xsl:$xslfile -o:$tmpoutfile sourceFolderPath=$url outputFolderPath=$outputdir sourceID=snac 2>&1", $descriptorspec, $pipes);
			$this->logger->addDebug("java -cp ".\snac\Config::$SAXON_JARFILE." net.sf.saxon.Transform -s:$xmlfile -xsl:$xslfile -o:$tmpoutfile sourceFolderPath=$url outputFolderPath=$outputdir sourceID=snac 2>&1");

			$procOutput = "";

			if (is_resource($process)) {
				fclose($pipes[0]);
				$procOutput = stream_get_contents($pipes[1]);
				fclose($pipes[1]);
				fclose($pipes[2]);

				// It is important that you close any pipes before calling
				// proc_close in order to avoid a deadlock
				$return_value = proc_close($process);

			}
			if ($return_value != 0) {
				$this->logger->addDebug("Error occurred while running SAXON: $procOutput");
				throw new \Exception("SAXON Parsing Error:\n$procOutput");
			}


			$this->logger->addDebug("Done Running SAXON");

			$this->logger->addDebug("Creating output ZIP file");
			$zip = new \ZipArchive();
			if ($zip->open($outfile, \ZipArchive::CREATE) !== true) {
				throw new \Exception("Could not create output Zip file");
			} 
			$this->logger->addDebug("Adding Zip Content");
			$zip->addFile($outputdir."/CPF-Join-Table.tsv", "CPF-Join-Table.tsv");
			$zip->addFile($outputdir."/CPF-Table.tsv", "CPF-Table.tsv");
			$zip->addFile($outputdir."/RD-Table.tsv", "RD-Table.tsv");

			$this->logger->addDebug("Done writing Zip");
			// close zip for downloading
			$zip->close();


			$this->logger->addDebug("Loading Content of Zip file to return");
			// show ZIP file
			$toReturn = file_get_contents($outfile);

		} catch (\Exception $e) {
			$this->cleanup($tmpdir);
			throw new \snac\exceptions\SNACEADParserException($e);
		}


		$this->logger->addDebug("Cleaning up");
		$this->cleanup($tmpdir);

		$this->logger->addDebug("Returning Zip file");
		return $toReturn;
	} 
	
	public function validateZip($zipcontents) {
		$tmpdir = \snac\Config::$EAD_PARSETMP_DIR . "/". microtime(true);
		$this->logger->addDebug("creating tmp directory");
		mkdir($tmpdir);
		$infile = $tmpdir."/upload.zip";
		$eaddir = $tmpdir."/ead/";
		$errors = [];

		try {
			$this->logger->addDebug("Writing zip contents");
			file_put_contents($infile, $zipcontents);

			$this->logger->addDebug("Unzipping");
			$zip = new \ZipArchive();
			$result = $zip->open($infile, \ZipArchive::CHECKCONS);
			if ($result !== true) {
				switch($result) {
				case ZipArchive::ER_NOZIP:
					throw new \Exception('Uploaded file is not a zip archive.');
				case ZipArchive::ER_INCONS :
					throw new \Exception('Uploaded file failed consistency check.');
				case ZipArchive::ER_CRC :
					throw new \Exception('Uploaded file failed checksum.');
				default:
					throw new \Exception('An error occurred: ' . $res);
				}
			}
			// TODO make this a little prettier, flatten the structure and only get xml files
			$zip->extractTo($eaddir);
			$zip->close();

			$this->logger->addDebug("Unzipped");

			// Enable user error handling
			libxml_use_internal_errors(true);

			if (is_dir($eaddir)) {
				if ($dh = opendir($eaddir)) {
					while (($file = readdir($dh)) !== false) {
						if ($file == '.' || $file == '..')
							continue;

						$xml = new \DOMDocument();
						$xml->load($eaddir . $file);

						if (!$xml->schemaValidate(\snac\Config::$EAD_SCHEMA_FILE)) {
							$err = libxml_get_errors();
							foreach ($err as $e) {
								$tmpe = [
									"filename" => basename($e->file),
									"message" => $e->message,
									"code" => $e->code,
									"line" => $e->line
								];
								switch ($e->level) {
									case LIBXML_ERR_WARNING:
										$tmpe["type"] = "Warning";
										break;
									case LIBXML_ERR_ERROR:
										$tmpe["type"] = "Error";
										break;
									case LIBXML_ERR_FATAL:
										$tmpe["type"] = "Fatal Error";
										break;
								}
								array_push($errors, $tmpe);
							}
							libxml_clear_errors();
						}
					}
					closedir($dh);
				}
			}



			$this->logger->addDebug("Done Running SAXON");

		} catch (\Exception $e) {
			$this->cleanup($tmpdir);
			throw new \snac\exceptions\SNACEADParserException($e);
		}


		$this->logger->addDebug("Cleaning up");
		$this->cleanup($tmpdir);

		$this->logger->addDebug("Returning results");
		return $errors;
	} 
}

