# SNAC Server

[![Gitter](https://badges.gitter.im/snac-cooperative/snac.svg)](https://gitter.im/snac-cooperative/snac?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge)

This repository contains the entirety of the SNAC Server code, including all components written by the UVA development team.

The [Testing Install](INSTALL_TEST.md) file includes instructions for how to install the server locally for testing purposes.

## Codebase Organization

The code is organized into the following PHP namespace organization, with mirroring directory structure.

```
\snac
    \client
        \webui
            \workflow
        \rest
        \testui
    \server
        \workflow
        \identityReconciliation
        \dateParser
        \nameEntryParser
        \dataValidation
        \reporting                  % Wrapper
        \authentication             % Wrapper for OAuth
        \authorization
        \database                   % Database interaction (mainly wrappers)
        % possible wrappers for Neo4J and Elastic Search
```


### Repository Organization

```
/
    /src                % containing all snac sourcecode
        /snac           % ...
        /virtualhosts
            /www
            /rest
            /internal
    /test               % containing all unit tests (mirrors /src)
        /snac           % ...
    LICENSE             % code license
    README.md           % this readme file
```

### Optional Web Application URL Mappings

All endpoints to the server will share the same codebase, but with an index.php that includes the codebase and instantiates and executes the `run()` method of the appropriate class.  Then, for example, we may have:

```
www.snaccooperative.org
    mapped to     /src/virtualhosts/www
    instantiates  \snac\client\webui\WebUI
api.snaccooperative.org
    mapped to     /src/virtualhosts/rest
    instantiates  \snac\client\rest\Rest
localhost:xxxx   
    mapped to     /src/virtualhosts/internal
    instantiates  \snac\server\Server
```
