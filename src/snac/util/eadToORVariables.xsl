<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:ead="urn:isbn:1-931666-22-9" xmlns:functx="http://www.functx.com" xmlns:snac="snac"
	xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xs="http://www.w3.org/2001/XMLSchema"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform" exclude-result-prefixes="#all" version="3.0">
	<!-- 
	
French Union Catalog 
 	
	-->

	
	


	<xsl:variable name="aposLiteral">'</xsl:variable>
	<xsl:variable name="quoteLiteral">"</xsl:variable>

	<xsl:variable name="quoteEscape" >&quot;</xsl:variable>
	<xsl:variable name="aposEscape">&apos;</xsl:variable>

	<xsl:variable name="sourceList">
		
		<!-- identifier
							eadid/@identifier
							baseFileName
							fileName
							
		-->
		<source>
			<sourceCode>aao</sourceCode>
			<sourceName>Arizona Archives Online</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>aar</sourceCode>
			<sourceName>Archives of American Art</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>afl</sourceCode>
			<sourceName>Archives Florida</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>ahub</sourceCode>
			<sourceName>ArchivesHub (UK)</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>anfra</sourceCode>
			<sourceName>Archives nationales (France)</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>aps</sourceCode>
			<sourceName>American Philosophical Society</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>bnf</sourceCode>
			<sourceName>Bibliothèque nationale de France / BnF Archives et manuscripts</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>byu</sourceCode>
			<sourceName>Brigham Young University</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>cjh</sourceCode>
			<sourceName>Center for Jewish History</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>colu</sourceCode>
			<sourceName>Columbia University</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>crnlu</sourceCode>
			<sourceName>Cornell University</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>duke</sourceCode>
			<sourceName>Duke University</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>fivecol</sourceCode>
			<sourceName>Five Colleges</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>harvard</sourceCode>
			<sourceName>Harvard University</sourceName>
			<url>http://oasis.lib.harvard.edu//oasis/deliver/deepLink?_collection=oasis&amp;uniqueId=</url>
			<identifier>baseFileName</identifier>
		</source>
		<source>
			<sourceCode>howard</sourceCode>
			<sourceName>Howard University</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>inu</sourceCode>
			<sourceName>Indiana University</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>lds</sourceCode>
			<sourceName>Church of Latter Day Saints Archives</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>loc</sourceCode>
			<sourceName>Library of Congress</sourceName>
			<url>http://hdl.loc.gov/</url>
			<identifier>eadid/@identifier</identifier>
			<!-- substring after hdl: -->
		</source>
		<source>
			<sourceCode>meas</sourceCode>
			<sourceName>Maine Archives Search</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>mhs</sourceCode>
			<sourceName>Minnesota Historical Society</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>mit</sourceCode>
			<sourceName>Massachusetts Institute of Technology</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>ncsu</sourceCode>
			<sourceName>North Carolina State University</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>nlm</sourceCode>
			<sourceName>National Library of Medicine</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>nwda</sourceCode>
			<sourceName>Northwest Digital Archives</sourceName>
			<url>http://nwda-db.wsulibs.wsu.edu/findaid/ark:/</url>
			<identifier>eadid/@identifier</identifier>
		</source>
		<source>
			<sourceCode>nwu</sourceCode>
			<sourceName>Northwestern University</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>nypl</sourceCode>
			<sourceName>New York Public Library</sourceName>
			<url/>
			<identifier/>
		</source>
		
		<source>
			<sourceCode>nysa</sourceCode>
			<sourceName>New York State Archives</sourceName>
			<url>http://iarchives.nysed.gov/xtf/view?docId=</url>
			<identifier>fileName</identifier>
		</source>
		<source>
			<sourceCode>nyu</sourceCode>
			<sourceName>New York University</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>oac</sourceCode>
			<sourceName>Online Archive of California</sourceName>
			<url>http://www.oac.cdlib.org/findaid/</url>
			<identifier>eadid/@identifier</identifier>
		</source>
		<source>
			<sourceCode>ohlink</sourceCode>
			<sourceName>EAD FACTORY (OhioLink)</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>pacscl</sourceCode>
			<sourceName>Philadelphia Area Consortium of Special Collections Libraries</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>pu</sourceCode>
			<sourceName>Princeton University</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>riamco</sourceCode>
			<sourceName>Rhode Island Archival &amp; Manuscript Collections Online</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>rmoa</sourceCode>
			<sourceName>Rocky Mountain Online Archive</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>rutu</sourceCode>
			<sourceName>Rutgers University</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>sia</sourceCode>
			<sourceName>Smithsonian Institution Archives</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>syru</sourceCode>
			<sourceName>Syracuse University</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>taro</sourceCode>
			<sourceName>Texas Archival Resources Online</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>ual</sourceCode>
			<sourceName>University of Alabama</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>uchic</sourceCode>
			<sourceName>University of Chicago</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>uct</sourceCode>
			<sourceName>University of Connecticut</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>ude</sourceCode>
			<sourceName>University of Delaware</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>ufl</sourceCode>
			<sourceName>University of Florida</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>uil</sourceCode>
			<sourceName>University of Illinois</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>uks</sourceCode>
			<sourceName>University of Kansas</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>umd</sourceCode>
			<sourceName>University of Maryland</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>umi</sourceCode>
			<sourceName>University of Michigan Bentley Library &amp; Special Collections</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>umn</sourceCode>
			<sourceName>University of Minnesota</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>unc</sourceCode>
			<sourceName>University of North Carolina, Chapel Hill</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>une</sourceCode>
			<sourceName>University of Nebraska</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>utsa</sourceCode>
			<sourceName>Utah State Archives</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>utsu</sourceCode>
			<sourceName>Utah State University</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>uut</sourceCode>
			<sourceName>University of Utah</sourceName>
			<url/>
			<identifier/>
		</source>
		<source>
			<sourceCode>vah</sourceCode>
			<sourceName>Virginia Heritage</sourceName>
			<url>http://ead.lib.virginia.edu/vivaead/published/</url>
			<identifier/>
		</source>
		<source>
			<sourceCode>yale</sourceCode>
			<sourceName>Yale University</sourceName>
			<url/>
			<identifier/>
		</source>
	</xsl:variable>

	<xsl:variable name="langCodeList">
		<xsl:copy-of select="document('iso639-2.new.xml')"/>
	</xsl:variable>

	<xsl:variable name="relatorList">
		<xsl:for-each select="document('relatorList.xml')/relatorList/relator">
			<xsl:copy-of select="."/>
		</xsl:for-each>
	</xsl:variable>

</xsl:stylesheet>
