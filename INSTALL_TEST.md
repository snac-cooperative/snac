# Testing Install

This tutorial gives the steps needed to install the SNAC server in a user's home directory, mainly provided for testing and development purposes.  Eventually this install will be deprecated in favor of a PHP Composer auto-install.

## Installing Locally


1. Check out a copy of the repository in a local folder, `git clone git@gitlab.iath.virginia.edu:snac/snac.git`
2. Ensure that Apache's config allows user directories (/~userid) and is set to allow PUT requests (in the `Require` directive)
3. Symbolic link the `src/virtualhosts` directory into your `/home/userid/public_html` directory.  For example, this could be done with a command similar to the following:

        ln -s /home/userid/pathtosnac/src/virtualhosts /home/userid/public_html/snac

4. Copy the `src/snac/Config_dist.php` file to `src/snac/Config.php` and update the `INTERNAL_SERVERURL` to correctly point to the local copy of the internal server codebase.  For example,

        public static $INTERNAL_SERVERURL = "http://localhost/~userid/snac/internal/";

5. That's it! If configured correctly, you can access your local snac server, where `webserver.com` is the URL of your server, at:
    * Backend server at `http://webserver.com/~userid/snac/internal`
    * Web server at `http://webserver.com/~userid/snac/www`
    * REST server at `http://webserver.com/~userid/snac/rest` 
