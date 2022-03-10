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
     * @var string $eadVersion Version of EAD
     */
    private $eadVersion = null;

    /**
     * $var string $COLUMN_SEPARATOR The separator used by XSLT to denote split columns
     */
    private $COLUMN_SEPARATOR = "^%%^";

    /**
     * $var string $ROW_SEPARATOR The separator used by XSLT to denote split rows
     */
    private $ROW_SEPARATOR = "#%%#";

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
            // Validate the XML
            $errors = $this->validateDirectory($eaddir, false); // only check for well-formedness
            if (!empty($errors) || $this->eadVersion === false) {
                return $errors;
            }

            // Set up the SAXON environment
            $xmlfile = \snac\Config::$EAD_PARSER_DIR."/ead_parse_driver.xml";
            $xslfile = \snac\Config::$EAD_PARSER_DIR."/{$this->eadVersion}ToOR.xsl";
            $tmpoutfile = $tmpdir."/tmpoutput.xml";

            // Run SAXON
            $descriptorspec = array(
                0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
                1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
                2 => array("pipe", "a")
            );
            $pipes = array();
            $process = proc_open("java -cp ".\snac\Config::$SAXON_JARFILE." net.sf.saxon.Transform -s:$xmlfile -xsl:$xslfile -o:$tmpoutfile sourceFolderPath=$eaddir outputFolderPath=$outputdir 2>&1", $descriptorspec, $pipes);

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

            // Post-process the SAXON output
            $this->postProcess($outputdir);

            // Put the files into an exported ZIP file
            $this->logger->addDebug("Creating output ZIP file");
            $zip = new \ZipArchive();
            if ($zip->open($outfile, \ZipArchive::CREATE) !== true) {
                throw new \Exception("Could not create output Zip file");
            }
            $this->logger->addDebug("Adding Zip Content");
            $zip->addFile($outputdir."/Join-Table.tsv", "Join-Table.tsv");
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
        mkdir($eaddir);
        $errors = [];

        try {
            $this->logger->addDebug("Writing zip contents");
            file_put_contents($infile, $zipcontents);

            $this->logger->addDebug("Unzipping");
            $zip = new \ZipArchive();

            // NOTE: Mac OS Zip files seem to fail consistency checks
            // It works to ignore this check, but we should NOT let everyone upload
            // files -- only trusted sources!
            $result = $zip->open($infile, \ZipArchive::CHECKCONS);
            if ($result !== true) {
                switch($result) {
                case \ZipArchive::ER_NOZIP:
                    throw new \Exception('Uploaded file is not a zip archive.');
                case \ZipArchive::ER_INCONS :
                    // Workaround for Mac zip files -- if they are inconsistent, that's okay
                    $result = $zip->open($infile);
                    if ($result === true)
                        break;
                    throw new \Exception('Uploaded file failed consistency check.');
                case \ZipArchive::ER_CRC :
                    throw new \Exception('Uploaded file failed checksum.');
                default:
                    throw new \Exception('An error occurred: ' . $result);
                }
            }

            // Loop through all files in the zip and find those that have xml/XML extension
            for($i = 0; $i < $zip->numFiles; $i++) {
                $innerPath = $zip->getNameIndex($i);
                $fileinfo = pathinfo($innerPath);

                // check for MACOSX folder, too.
                if (strpos($innerPath, 'MACOSX') === false && isset($fileinfo['extension']) &&
                        ($fileinfo['extension'] == 'xml' || $fileinfo['extension'] == 'XML'))
                    file_put_contents($eaddir.$fileinfo['basename'], $zip->getFromIndex($i));
            }

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
                        $this->logger->addDebug("Validating: $eaddir$file");

                        $xml = new \DOMDocument();
                        $xml->load($eaddir . $file);

                        if ($this->eadVersion == null)
                            $this->getEADVersion($xml);

                        if ($fullValidate) {
                            if ($this->eadVersion)
                                @$xml->schemaValidate(\snac\Config::$EAD_PARSER_DIR."/{$this->eadVersion}.xsd");
                        }
                        $errors = array_merge($errors, $this->getXMLErrors());
                    }
                    closedir($dh);

                    if ($this->eadVersion === false) {
                        array_push($errors, [
                            "filename" => "",
                            "message" => "Unrecognized EAD Namespace",
                            "code" => "",
                            "line" => ""
                        ]);
                    }
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

    /**
     * Get Schema Version
     *
     * Returns the EAD version based on the default namespace.  Returns false
     * if it can not be determined
     *
     * @param $xml DOMDocument containing the EAD
     * @return string|boolean The ead version or false if not found
     */
    private function getEADVersion($xml) {
        $namespace = null;
        if (isset($xml->documentElement)) {
            $namespace = $xml->documentElement->lookupnamespaceURI(null);
        }
        $version = false;
        switch($namespace) {
            case 'urn:isbn:1-931666-22-9':
                $version = 'ead2002';
                break;
            case 'http://ead3.archivists.org/schema/':
                $version = 'ead3';
                break;
        }
        $this->eadVersion = $version;
        return $version;
    }

    /**
     * Split Multicell Rows and Columns in Created TSV files
     *
     * Reads in and modifies the TSV files created by the XSLT processing.
     * Currently modifies the CPF-Table and RD-Table to make them OR-ready for multiple
     * rows per record,
     *
     * RD-Table only needs to split on 'Language' and 'LangCode' columns, but
     * currently checks all columns.
     *
     * @param $dir string The directory where files are stored
     * @param $dir string The file name of the table to be split
     */
    private function splitMultiCellRowsAndColumns($dir, $tableName) {
        // Files in the directory
        //$dir."/Join-Table.tsv"
        //$dir."/CPF-Table.tsv"
        //$dir."/RD-Table.tsv"

        $table = [];
        $colsToSplit = [];

        // Read in the CPF table and add new rows as needed (we need to see all rows before new columns)
        if (($handle = fopen($dir . "/" .$tableName, "r")) !== false) {
            while (($line = fgetcsv($handle, 10000000, "\t")) !== false) {
                $additionalRows = [];
                $currentLine = [];
                $numCols = count($line);
                foreach ($line as $j => $v) {
                    $parts = explode($this->ROW_SEPARATOR, $v);
                    // check if we need to split this column
                    foreach ($parts as $part) {
                        if (strpos($part, $this->COLUMN_SEPARATOR) !== false)
                            $colsToSplit[$j] = true;
                    }
                    if (count($parts) == 1) {
                        $currentLine[$j] = $v;
                    } else {
                        $currentLine[$j] = array_shift($parts);
                        for ($k = 0; $k < count($additionalRows) && !empty($parts); $k++) {
                            $additionalRows[$k][$j] = array_shift($parts);
                        }
                        if (!empty($parts)) {
                            foreach ($parts as $part) {
                                $newLine = [];
                                for ($l = 0; $l < $numCols; $l++)
                                    $newLine[$l] = "";
                                $newLine[$j] = $part;
                                array_push($additionalRows, $newLine);
                            }
                        }
                    }
                }
                array_push($table, $currentLine);
                if (!empty($additionalRows)) {
                    foreach ($additionalRows as $newRow)
                        array_push($table, $newRow);
                }
            }
            fclose($handle);
        }

        if (!empty($table)) {
            $numOrigCols = count($table[0]);
            $headers = $table[0];

            $newtable = [];
            $newheaders = [];
            for ($i = 0; $i < $numOrigCols; $i++) {
                array_push($newheaders, $headers[$i]);
                if (isset($colsToSplit[$i]))
                    array_push($newheaders, $headers[$i] . " Type");
            }
            array_push($newtable, $newheaders);

            foreach ($table as $i => $row) {
                if ($i == 0) continue;

                $newRow = [];
                for ($j = 0; $j < count($row); $j++) {
                    if (isset($colsToSplit[$j])) {
                        // only split by two
                        $parts = explode($this->COLUMN_SEPARATOR, $row[$j]);
                        foreach ($parts as $part)
                            array_push($newRow, $part);
                        if (count($parts) < 2)
                            array_push($newRow, '');
                    } else {
                        array_push($newRow, $row[$j]);
                    }
                }
                array_push($newtable, $newRow);
            }

            // Write the updated CPF Table
            if (($handle = fopen($dir . "/" . $tableName, "w")) !== false) {
                foreach ($newtable as $row) {
                    fputcsv($handle, $row, "\t");
                }
                fclose($handle);
            }
        }
    }

    /**
     * Post-Process Created TSV files
     *
     * Reads in and modifies the TSV files created by the XSLT processing to make
     * them OR-ready for multiple rows per record.
     *
     * @param $dir string The directory where files are stored
     * @param $tableName string The name of the tsv table to be split
     */
    private function postProcess($dir) {
        // Files in the directory
        //$dir."/Join-Table.tsv"
        //$dir."/CPF-Table.tsv"
        //$dir."/RD-Table.tsv"

        $this->splitMultiCellRowsAndColumns($dir, "CPF-Table.tsv");
        $this->splitMultiCellRowsAndColumns($dir, "RD-Table.tsv");
    }



}
