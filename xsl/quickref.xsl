<?xml version="1.0" encoding="iso-8859-1"?>
<!-- 

  quickref.xsl: Stylesheet for generating quick-reference

  $Id: quickref.xsl,v 1.3 2004-11-14 17:36:11 techtonik Exp $

-->
<!DOCTYPE xsl:stylesheet [

<!ENTITY lowercase "'abcdefghijklmnopqrstuvwxyz'">
<!ENTITY uppercase "'ABCDEFGHIJKLMNOPQRSTUVWXYZ'">

]>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                version="1.0">

<xsl:output method="text"/>

<xsl:param name="sortbycase" select="0"/>

<xsl:template match="*"/>

<xsl:template match="/">
  <xsl:choose>
  <xsl:when test="$sortbycase">
    <xsl:apply-templates select="//refnamediv">
        <xsl:sort select="refname"/>
    </xsl:apply-templates>
  </xsl:when>
  <xsl:otherwise>
    <xsl:apply-templates select="//refnamediv">
        <xsl:sort select="translate(refname,&lowercase;,&uppercase;)"/>
    </xsl:apply-templates>
  </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<xsl:template match="refnamediv">
  <xsl:value-of select="normalize-space(refname)"/>
  <xsl:text> - </xsl:text>
  <xsl:value-of select="normalize-space(refpurpose)"/>
  <xsl:text>
</xsl:text>
</xsl:template>

</xsl:stylesheet>