<?php

/**
 * Image Class file
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\data;

/**
 * Image Class
 *
 * Image class that holds a URL and citation information
 *
 * @author Robbie Hott
 */
class Image extends AbstractData {

    /**
     * var string URL
     */
    protected $url;

    /**
     * var string information
     */
    protected $info;

    /**
     * var string information url
     */
    protected $infoURL;

    /**
     * var string author
     */
    protected $author;

    /**
     * var string author's URl
     */
    protected $authorURL;

    /**
     * var string license
     */
    protected $license;

    /**
     * var string license's URl
     */
    protected $licenseURL;

    /**
     * Get the author of this object
     *
     *  @return string author of this object
     */
    public function getAuthor() {
        return $this->author;
    }

    /**
     * Set the author of this object
     *
     * @param string $author author this object
     */
    public function setAuthor($author) {
        $this->author = $author;
    }


    /**
     * Get the authorURL of this object
     *
     *  @return string authorURL of this object
     */
    public function getAuthorURL() {
        return $this->authorURL;
    }

    /**
     * Set the authorURL of this object
     *
     * @param string $authorURL authorURL this object
     */
    public function setAuthorURL($authorURL) {
        $this->authorURL = $authorURL;
    }


    /**
     * Get the license of this object
     *
     *  @return string license of this object
     */
    public function getLicense() {
        return $this->license;
    }

    /**
     * Set the license of this object
     *
     * @param string $license license this object
     */
    public function setLicense($license) {
        $this->license = $license;
    }


    /**
     * Get the licenseURL of this object
     *
     *  @return string licenseURL of this object
     */
    public function getLicenseURL() {
        return $this->licenseURL;
    }

    /**
     * Set the licenseURL of this object
     *
     * @param string $licenseURL licenseURL this object
     */
    public function setLicenseURL($licenseURL) {
        $this->licenseURL = $licenseURL;
    }

    /**
     * Get the url of this object
     *
     *  @return string url of this object
     */
    public function getURL() {
        return $this->url;
    }

    /**
     * Set the url of this object
     *
     * @param string $url url this object
     */
    public function setURL($url) {
        $this->url = $url;
    }

    /**
     * Get the info url of this object
     *
     *  @return string info url of this object
     */
    public function getInfoURL() {
        return $this->infoURL;
    }

    /**
     * Set the info url of this object
     *
     * @param string $url info url this object
     */
    public function setInfoURL($url) {
        $this->infoURL = $url;
    }

    /**
     * Get the info of this object
     *
     *  @return string info of this object
     */
    public function getInfo() {
        return $this->info;
    }

    /**
     * Set the info of this object
     *
     * @param string $info info this object
     */
    public function setInfo($info) {
        $this->info = $info;
    }

    /**
     * Required method to convert this term structure to an array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This object as an associative array
     */
    public function toArray($shorten = true) {
        $return = array(
            'dataType' => "Image",
            'url' => $this->getURL(),
            'infoURL' => $this->getInfoURL(),
            'info' => $this->getInfo(),
            'author' => $this->getAuthor(),
            'authorURL' => $this->getAuthorURL(),
            'license' => $this->getLicense(),
            'licenseURL' => $this->getLicenseURL()
        );

        $return = array_merge($return, parent::toArray($shorten));

        // Shorten if necessary
        if ($shorten) {
            $return2 = array();
            foreach ($return as $i => $v)
                if ($v != null && !empty($v))
                    $return2[$i] = $v;
            unset($return);
            $return = $return2;
        }


        return $return;
    }

    /**
     * Required method to import an array into this term structure
     *
     * @param string[][] $data The data for this object in an associative array
     */
    public function fromArray($data) {

        if (!isset($data["dataType"]) || $data["dataType"] != "Image")
            return false;


        parent::fromArray($data);

        unset($this->author);
        if (isset($data["author"]))
            $this->author = $data["author"];
        else
            $this->author = null;

        unset($this->authorURL);
        if (isset($data["authorURL"]))
            $this->authorURL = $data["authorURL"];
        else
            $this->authorURL = null;

        unset($this->license);
        if (isset($data["license"]))
            $this->license = $data["license"];
        else
            $this->license = null;

        unset($this->licenseURL);
        if (isset($data["licenseURL"]))
            $this->licenseURL = $data["licenseURL"];
        else
            $this->licenseURL = null;

        unset($this->info);
        if (isset($data["info"]))
            $this->info = $data["info"];
        else
            $this->info = null;

        unset($this->infoURL);
        if (isset($data["infoURL"]))
            $this->infoURL = $data["infoURL"];
        else
            $this->infoURL = null;

        unset($this->url);
        if (isset($data["url"]))
            $this->url = $data["url"];
        else
            $this->url = null;
    }

    /**
     *
     * {@inheritDoc}
     *
     * @param \snac\data\Image $other Other object
     * @param boolean $strict optional Whether or not to check id, version, and operation
     * @param boolean $checkSubcomponents optional Whether or not to check SNACControlMetadata, nameEntries contributors & components
     * @return boolean true on equality, false otherwise
     *
     * @see \snac\data\AbstractData::equals()
     */
    public function equals($other, $strict = true, $checkSubcomponents = true) {

        if ($other == null || ! ($other instanceof \snac\data\Image))
            return false;

        if (!parent::equals($other, $strict, $checkSubcomponents))
            return false;

        if ($this->getURL() != $other->getURL())
            return false;

        if ($this->getInfo() != $other->getInfo())
            return false;

        if ($this->getInfoURL() != $other->getInfoURL())
            return false;

        if ($this->getAuthor() != $other->getAuthor())
            return false;

        if ($this->getAuthorURL() != $other->getAuthorURL())
            return false;

        if ($this->getLicense() != $other->getLicense())
            return false;

        if ($this->getLicenseURL() != $other->getLicenseURL())
            return false;

        return true;
    }

}
