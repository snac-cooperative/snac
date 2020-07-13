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

    /**
     * @var \Monolog\Logger $logger Logger for this server
     */
    private $logger = null;

    /**
     * Constructor
     */
	public function __construct() {
		global $log;

		// create a log channel
		$this->logger = new \Monolog\Logger('EADParser');
		$this->logger->pushHandler($log);
	}

    /**
     * Parse a zip file
     * 
     * Uses SAXON to parse all the XML in a zip file and returns the contents of a 
     * zip file containing the TSV files produced by SAXON's parsing.
     *
     * @param $zipcontents string the contents of the zip file to be parsed
     * @return string The contents of the result zip
     */
	public function parseZip($zipcontents) {
		$tmpdir = $this->unzip($zipcontents);
		$outfile = $tmpdir."/output.zip";
		$eaddir = $tmpdir."/ead/";
		$outputdir = $tmpdir."/output";

		$toReturn = false;

		try {
		    $errors = $this->validateDirectory($eaddir, false); // only check for well-formedness

			if (!empty($errors)) {
				return $errors;
			}
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

    /**
     * Unzip a zip file 
     * 
     * Unzips the file given as a parameter to a temporary directory and
     * returns the location of the zip contents.
     *
     * @param $zipcontents string the contents of the zip file (not filename)
     * @return string The path to the folder containing the zip contents
     */
	private function unzip($zipcontents) {
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

		} catch (\Exception $e) {
			$this->cleanup($tmpdir);
			throw new \snac\exceptions\SNACEADParserException($e);
		}
		
		return $tmpdir;
	}

    /**
     * Cleanup a temporary directory 
     *
     * Deletes a temporary directory from the filesystem. 
     *
     * @param $dir The temporary directory to delete
     */
	private function cleanup($dir) {
		$this->delTree($dir);
	}

	/**
     * Recursively delete a directory 
     *
     * Deletes a directory and it's contents from the filesystem. 
	 * From https://www.php.net/manual/en/function.rmdir.php
     *
     * @param $dir The temporary directory to delete
	 */
	private function delTree($dir) {
		$files = array_diff(scandir($dir), array('.','..'));
		foreach ($files as $file) {
			(is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
		}
		return rmdir($dir);
	}

	/**
     * Get libxml Errors 
     *
     * Gets the XML parsing errors from PHP's libxml.  This creates an array of the errors 
	 * including the message, line, filename, and error code.
     *
     * @return array[] An array of errors
	 */
	private function getXMLErrors() {
			$errors = [];
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
			return $errors;
	}

	/**
     * Validate a Directory of XML 
     *
     * Loads all XML files from the given directory into PHP's libxml parser to check 
	 * for well-formedness or EAD schema validation.
     *
     * @param $eaddir string The directory to parse through
     * @param $fullValidate boolean optional Whether or not to use full Schema validation
     * @return array[] An array of errors, or empty array if no error
	 */
	private function validateDirectory($eaddir, $fullValidate = true) {
		$errors = [];
		try {
			// Enable user error handling
			libxml_use_internal_errors(true);

			if (is_dir($eaddir)) {
				if ($dh = opendir($eaddir)) {
					while (($file = readdir($dh)) !== false) {
						if ($file == '.' || $file == '..')
							continue;

						$xml = new \DOMDocument();
						$xml->load($eaddir . $file);

						if ($fullValidate) {
								@$xml->schemaValidate(\snac\Config::$EAD_SCHEMA_FILE);
						}
						$errors = array_merge($errors, $this->getXMLErrors());	
					}
					closedir($dh);
				}
			}
			$this->logger->addDebug("EAD Validation Complete");

		} catch (\Exception $e) {
			$this->cleanup($tmpdir);
			throw new \snac\exceptions\SNACEADParserException($e);
		}
		return $errors;

	}	
	
    /**
     * Validate a zip file
     * 
     * Runs PHP's libxml implementation to validate all the contents of the zip file 
     * against the EAD2002 schema file.  Returns a list of validation errors.
     *
     * @param $zipcontents string the contents of the zip file to be parsed
     * @return string The contents of the result zip
     */
	public function validateZip($zipcontents) {
		$tmpdir = $this->unzip($zipcontents); 
		$eaddir = $tmpdir."/ead/";
		$errors = $this->validateDirectory($eaddir);

		$this->logger->addDebug("Cleaning up");
		$this->cleanup($tmpdir);

		$this->logger->addDebug("Returning results");
		return $errors;
	} 
}

