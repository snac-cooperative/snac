<?php
/**
 * Display Interface File
 *
 * File for the display interface
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\client\webui\display;

/**
 * Display Abstract Class
 *
 * This abstract class provides the methods required to implement a display for the user.
 *
 * @author Robbie Hott
 */
class Display {

    /**
     * @var string Template file name
     */
    private $templateFileName = null;

    /**
     * @var string[] Data to send to the template to display
     */
    private $data = array();

    /**
     * Constructor
     *
     * Creates the object.  May pass the template filename as a parameter to build the template.
     *
     * @param string optional $template Filename of the template to load
     */
    public function __construct($template = null) {

        $this->templateFileName = $template;
        return;
    }

    /**
     * Set Display Data
     * 
     * Sets the data fields to use in the template.
     * 
     * @param string[] $data Associative array of data strings
     */
    public function setData($data) {
        $this->data["data"] = $data;
    }

    /**
     * Set User Data
     * 
     * Sets the user fields for use in the template
     * 
     * @param string[] $data Associative array of user information
     */
    public function setUserData($data) {
        $this->data["user"] = $data;
    }

    /**
     * Add Debug Data
     * 
     * Adds debug data to the data sent to the template.
     * 
     * @param string $name The name of this debug information
     * @param string[] $data Associative array of debug information as strings
     */
    public function addDebugData($name, $data) {
        if (!isset($this->data["debug"]))
            $this->data["debug"] = array();
        $this->data["debug"][$name] = $data;
    }

    /**
     * Set the template
     * 
     * Sets the template for this display object.
     * @param string $template The name of the template (without extension)
     * @param string $extension optional The extension of the template, if it is not html
     */
    public function setTemplate($template, $extension = "html") {
        $this->templateFileName = $template . "." . $extension;
    }

    /**
     * Generate the page to return
     *
     * @return string Page to return to the user
     */
    public function getDisplay() {

        $loader = new \Twig_Loader_Filesystem(\snac\Config::$TEMPLATE_DIR);
        $twig = new \Twig_Environment($loader, array(
                //'cache' => \snac\Config::$TEMPLATE_CACHE,
            ));

        return $twig->render($this->templateFileName, $this->data);

        $template = create_function('$data', file_get_contents($this->templateFileName()));
        $template($this->data);
        return "<html><body><h1>Testing</h1></body></html>";
    }
}
