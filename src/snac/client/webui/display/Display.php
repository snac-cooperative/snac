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
     *
     * @var string[] Language tags for the display object
     */
    private $language = null;

    /**
     * @var \Monolog\Logger $logger Logger for this class
     */
    private $logger = null;


    /**
     * Constructor
     *
     * Creates the object.  May pass the template filename as a parameter to build the template.
     *
     * @param string optional $template Filename of the template to load
     */
    public function __construct($template = null) {
        global $log;

        $this->templateFileName = $template;

        // create a log channel
        $this->logger = new \Monolog\Logger('Display');
        $this->logger->pushHandler($log);
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
     * Set User Permissions Data
     *
     * Sets the permissions fields for use in the template.  Permission names/labels
     * MUST NOT have any special characters or spaces, so they can work with Twig
     *
     * @param boolean[] $data Associative array of Permission to boolean flag
     */
    public function setPermissionData($data) {
        $this->data["permissions"] = $data;
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
     * Has a template set
     *
     * Determines if this Display object has a template set or not.  If not, that
     * means the display won't work properly.
     *
     * @return boolean true if template set, false otherwise.
     */
    public function hasTemplate() {
        if ($this->templateFileName == null)
            return false;
        return true;
    }

    /**
    * Set the template language
    *
    * Sets the language for this display object.
    * @param string $language The language for the display (without extension)
    */
    public function setLanguage($language) {
        $this->language = json_decode(file_get_contents(\snac\Config::$TEMPLATE_LANGUAGE_DIR ."/$language.json"), true);
    }

    /**
     * Generate the page to return
     *
     * @return string Page to return to the user
     */
    public function getDisplay() {

        if ($this->language != null) {
            $this->data["X"] = $this->language;
        }

        // If the system is in DEBUG mode, then the display will disallow
        // caching of javascript.
        if (\snac\Config::$DEBUG_MODE == true) {
            $this->data["control"] = array (
                "noCache" => "?_=".`git rev-parse HEAD` 
            );
        }

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
