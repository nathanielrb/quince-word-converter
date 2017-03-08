<?xml version="1.0"?>

<xsl:stylesheet version="2.0"
		xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
		xmlns:tei="http://www.tei-c.org/ns/1.0"
		xmlns:fn="http://www.w3.org/2005/xpath-functions"
		exclude-result-prefixes="tei fn">

  <!--  <xsl:output method="text" omit-xml-declaration="no" indent="no"/>-->

  <xsl:preserve-space elements="body"/>
  
  <xsl:variable name="blocks" select="tokenize('sidebar',',')" />
  
  <xsl:template match="/">
    <documents>
      <xsl:apply-templates select="//tei:body/tei:div
		       | //tei:body[not(child::*[1]/self::tei:div)]"/>
    </documents>
  </xsl:template>	

  <xsl:template match="//tei:body/tei:div
		       | //tei:body[not(child::*[1]/self::tei:div)]">
    <document>
      <title>
	<xsl:value-of select="tei:head[1]/text()"/>
      </title>
      <body>
	<xsl:apply-templates/>
      </body>
    </document>
  </xsl:template>
  
  <xsl:template match="*">
    <xsl:apply-templates select="*"/>
  </xsl:template>

  <xsl:template match="text()">
    <xsl:value-of select="."/>
  </xsl:template>

  <xsl:template match="tei:head">
    <xsl:call-template name="header-hashes">
      <xsl:with-param name="n" select="count(ancestor::tei:div | ancestor::tei:body)"/>
    </xsl:call-template>
    <xsl:text> </xsl:text>
    <xsl:apply-templates/>
    <xsl:text>&#xa;</xsl:text>
    <xsl:text>&#xa;</xsl:text>
  </xsl:template>

  <xsl:template name="header-hashes">
    <xsl:param name="n"/>
    <xsl:if test="$n &gt; 0">
      <xsl:text>#</xsl:text>
      <xsl:call-template name="header-hashes">
	<xsl:with-param name="n" select="$n - 1"/>
      </xsl:call-template>
    </xsl:if>
  </xsl:template>

  <xsl:template match="tei:p">
    <xsl:apply-templates/>
    <xsl:text>&#xa;</xsl:text>
    <xsl:text>&#xa;</xsl:text>
  </xsl:template>

  <xsl:template name="attr">
    <xsl:param name="at"/>
    <xsl:text>{.</xsl:text>
    <xsl:value-of select="$at"/>
    <xsl:text>}</xsl:text>
  </xsl:template>
  
  <xsl:template match="tei:p[@rend != 'figure']">
    <xsl:apply-templates/>
    <xsl:if test="not(index-of($blocks, string(@rend)))">
      <xsl:call-template name="attr">
	<xsl:with-param name="at" select="@rend"/>
      </xsl:call-template>
    </xsl:if>
    <xsl:text>&#xa;</xsl:text>
    <xsl:text>&#xa;</xsl:text>
  </xsl:template>

  <xsl:template match="tei:graphic">
    <xsl:text>![</xsl:text>

    <xsl:text>](</xsl:text>
    <xsl:value-of select="replace(@url, '^.+/','')"/>

    <xsl:if test="following-sibling::tei:head">
      <xsl:text> "</xsl:text>
      <xsl:value-of select="following-sibling::tei:head"/>
      <xsl:text>"</xsl:text>
    </xsl:if>
    
    <xsl:text>)</xsl:text>
  </xsl:template>

  <xsl:template match="tei:p[@rend='figure']/tei:head"/>

  <xsl:template match="tei:div">
    <xsl:for-each-group select="*" group-adjacent="name(.)">
      <xsl:choose>
	<xsl:when test="self::tei:p">
	  <xsl:for-each-group select="current-group()" group-adjacent="string(@rend)">
	    <xsl:choose>
	      <xsl:when test="index-of($blocks, string(@rend))">
		<div class="{@rend}">
		  <xsl:text>&#xa;</xsl:text>
		  <xsl:apply-templates select="current-group()"/>
		  <xsl:text>&#xa;</xsl:text>
		</div>
	      </xsl:when>
	      <xsl:otherwise>
		<xsl:apply-templates select="current-group()"/>
	      </xsl:otherwise>
	    </xsl:choose>
	  </xsl:for-each-group>
	</xsl:when>
      <xsl:otherwise>
	<xsl:apply-templates select="current-group()"/>
      </xsl:otherwise>
      </xsl:choose>
    </xsl:for-each-group>             
  </xsl:template>
  
  <xsl:template match="tei:hi[@rend='b']">
    <xsl:text>**</xsl:text>
    <xsl:apply-templates/>
    <xsl:text>**</xsl:text>
  </xsl:template>

  <xsl:template match="tei:hi[@rend='i']">
    <xsl:text>*</xsl:text>
    <xsl:apply-templates/>
    <xsl:text>*</xsl:text>
  </xsl:template>
  
  <xsl:template match="tei:note">
    <xsl:text>^[</xsl:text>
    <xsl:apply-templates/>
    <xsl:text>]</xsl:text>
  </xsl:template>

  <xsl:template match="tei:quote/tei:p">
    <xsl:text>&gt; </xsl:text>
    <xsl:apply-templates/>
    <xsl:text>&#xa;</xsl:text>
    <xsl:text>&#xa;</xsl:text>
  </xsl:template>

  <xsl:template name="cell-width">
    <xsl:param name="char"><xsl:text> </xsl:text></xsl:param>
    <xsl:param name="n">
      <xsl:value-of select="1 + count(preceding-sibling::tei:cell)"/>
    </xsl:param>

    <xsl:param name="count">
      <xsl:value-of select="max( ancestor::tei:table/tei:row/tei:cell[position() = $n]/string-length(.) )"/>
    </xsl:param>

    <xsl:param name="difference">
      <xsl:value-of select="$count - string-length(.)"/>
    </xsl:param>

    <xsl:for-each select="1 to $difference">
      <xsl:value-of select="$char"/>
    </xsl:for-each>
    
  </xsl:template>
  
  <xsl:template match="tei:table">
    <xsl:text>&#xa;</xsl:text>
    <xsl:apply-templates/>
    <xsl:text>&#xa;</xsl:text>
  </xsl:template>
  
  <xsl:template match="tei:row">
    <xsl:text>|</xsl:text>
    <xsl:apply-templates/>
    <xsl:text>&#xa;</xsl:text>
  </xsl:template>

  <xsl:template match="tei:row[@role='label']">
    <xsl:text>|</xsl:text>
    <xsl:apply-templates/>
    <xsl:text>&#xa;</xsl:text>
    <xsl:text>|</xsl:text>
    <xsl:apply-templates mode="tableheadersep"/>
    <xsl:text>&#xa;</xsl:text>
  </xsl:template>

  <xsl:template  match="tei:row[@role='label']/tei:cell" mode="tableheadersep">
    <xsl:text>-</xsl:text>
    <xsl:value-of select="replace(.,'.','-')"/>
    <xsl:call-template name="cell-width">
      <xsl:with-param name="char" select="'-'"/>
    </xsl:call-template>
    <xsl:text>-|</xsl:text>
  </xsl:template>

  <xsl:template match="tei:cell">
    <xsl:text> </xsl:text>
    <xsl:apply-templates/>
    <xsl:call-template name="cell-width"/>
    <xsl:text> |</xsl:text>
  </xsl:template>

  <xsl:template match="tei:p[@rend='table']">
    <xsl:text>Table: </xsl:text>
    <xsl:value-of select="replace(., 'Table [0-9]+: ', '')"/>
    <xsl:text>&#xa;</xsl:text>
  </xsl:template>
  
</xsl:stylesheet>
