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

    private $templateFileName = null;

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

    public function setData($data) {
        $this->data["data"] = $data;
    }

    public function setUserData($data) {
        $this->data["user"] = $data;
    }

    public function setTemplate($template) {
        $this->templateFileName = $template . ".html";
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
