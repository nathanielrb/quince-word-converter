<?xml version="1.0" encoding="UTF-8"?>
<!--
Clean TEI for PhiloLogic3.
Tested on TEI output by odt_tei

See also http://wiki.tei-c.org/index.php/NotesToRefs.xsl

-->
<xsl:transform version="1.1"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns="http://www.tei-c.org/ns/1.0"
  xmlns:tei="http://www.tei-c.org/ns/1.0"
  exclude-result-prefixes="tei"
>
  <xsl:output encoding="UTF-8"/>
  <!-- default copy/all -->
  <xsl:template match="node() | @*">
    <xsl:copy>
      <xsl:apply-templates select="node() | @*"/>
    </xsl:copy>
  </xsl:template> 
  <!-- Transform notes as links in normal flow -->
  <xsl:template match="tei:note">
    <xsl:variable name="n">
      <xsl:call-template name="n"/>
    </xsl:variable>
    <xsl:variable name="id">
      <xsl:call-template name="id"/>
    </xsl:variable>
    <ref type="note" target="{$id}">
      <xsl:attribute name="xml:id">
        <xsl:text>_</xsl:text>
        <xsl:value-of select="$id"/>
      </xsl:attribute>
      <xsl:choose>
        <!-- Source doc has a number, keep it -->
        <xsl:when test="@n">
          <xsl:value-of select="@n"/>
        </xsl:when>
        <!-- Or give the one we build -->
        <xsl:otherwise>
          <xsl:call-template name="n"/>
        </xsl:otherwise>
      </xsl:choose>
    </ref>
  </xsl:template> 
  <!-- Reinsert notes at the en of body (if no back) -->
  <xsl:template match="/*/tei:text/tei:body | /*/tei:text/tei:group">
    <xsl:copy>
      <xsl:apply-templates select="node() | @*"/>
    </xsl:copy>
    <!-- no back, so add one for notes -->
    <xsl:if test="not(../tei:back)">
      <back>
        <xsl:call-template name="notes"/>
      </back>
    </xsl:if>
  </xsl:template>
  <!-- notes at the end of back (if one) -->
  <xsl:template match="/*/tei:text/tei:back">
    <xsl:copy>
      <xsl:apply-templates select="node()|@*"/>
      <xsl:call-template name="notes"/>
    </xsl:copy>
  </xsl:template>
  <!-- Build the div for notes -->
  <xsl:template name="notes">
    <div xml:id="notes" type="notes">
      <xsl:for-each select="//tei:note">
        <!-- a line break (a bit odd, yes) -->
        <xsl:text>
</xsl:text>
        <xsl:copy>
          <!-- default atts, maybe overriden by local -->
          <xsl:attribute name="place">foot</xsl:attribute>
          <!-- local atts -->
          <xsl:apply-templates select="@*"/>
          <!-- linking atts, should come over local -->
          <xsl:attribute name="xml:id">
            <xsl:call-template name="id"/>
          </xsl:attribute>
          <!-- Return to ref -->
          <xsl:attribute name="target">
            <xsl:text>_</xsl:text>
            <xsl:call-template name="id"/>
          </xsl:attribute>
          <xsl:apply-templates select="node()"/>
        </xsl:copy>
      </xsl:for-each>
    </div>
  </xsl:template>
  <!-- auto numbering, unique in document for the type element,
    can be use to build id, centralize to share same id policy -->
  <xsl:template name="n">
    <xsl:number level="any"/>
  </xsl:template>
  <xsl:template name="id">
    <xsl:choose>
      <!-- keep author id if one -->
      <xsl:when test="@xml:id">
        <xsl:value-of select="@xml:id"/>
      </xsl:when>
      <!-- or build ours if none -->
      <xsl:otherwise>
        <xsl:value-of select="local-name()"/>
        <xsl:call-template name="n"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
</xsl:transform>
