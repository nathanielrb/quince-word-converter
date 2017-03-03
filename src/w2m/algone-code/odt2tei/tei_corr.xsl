<?xml version="1.0" encoding="UTF-8"?>
<!--
Text grouping
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
  <xsl:template match="/*/tei:text/tei:body">
    <group>
      <xsl:apply-templates select="node() | @*"/>
    </group>
  </xsl:template>
  <xsl:template match="tei:div[tei:div]">
    <group>
      <xsl:apply-templates select="node() | @*"/>
    </group>
  </xsl:template>
  <xsl:template match="tei:div[local-name(*[1]) = 'figure']">
    <text>
      <front>
        <xsl:apply-templates select="tei:figure/*"/>
      </front>
      <xsl:if test="*[position() &gt; 1]">
        <body>
          <div>
            <xsl:apply-templates select="*[position() &gt; 1]"/>
          </div>
        </body>
      </xsl:if>
    </text>
  </xsl:template>
  <xsl:template match="tei:list[tei:item[@rend='witness']]">
    <listWit>
      <xsl:apply-templates/>
    </listWit>
  </xsl:template>
  <xsl:template match="tei:item[@rend='witness']">
    <witness>
      <xsl:apply-templates/>
    </witness>
  </xsl:template>
</xsl:transform>
