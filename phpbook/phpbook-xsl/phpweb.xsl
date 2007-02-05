<?xml version="1.0" encoding="iso-8859-1"?>
<!-- 

  PHP.net web site specific stylesheet

  $Id: phpweb.xsl,v 1.3 2007-02-05 22:28:36 bjori Exp $

-->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                version="1.0">

<xsl:import href="../../docbook/docbook-xsl/html/chunkfast.xsl"/>
<xsl:include href="html-common.xsl"/>
<xsl:include href="html-chunk.xsl"/>

<!-- Ignore the annoying "ID recommended on..." warnings
     They are flooding the real warnings -->
<xsl:param name="id.warnings" select="0"/>

<!-- Write files to the 'php' dir, use the '.php' extension -->
<xsl:param name="base.dir" select="'php/'"/>
<xsl:param name="html.ext" select="'.php'"/>

<!-- Special PHP code navigation for generated pages
     There are some xsl:text parts added for formatting!
 --> 
<xsl:template name="header.navigation">
 <xsl:param name="prev" select="/foo"/>
 <xsl:param name="next" select="/foo"/>
 <xsl:variable name="home" select="/*[1]"/>
 <xsl:variable name="up" select="parent::*"/>

 <xsl:processing-instruction name="php">
  <xsl:text>
include_once $_SERVER['DOCUMENT_ROOT'] . '/include/shared-manual.inc';</xsl:text>
  <xsl:text disable-output-escaping="yes">
manual_setup(array(
    'home' =&gt; </xsl:text>
 
  <xsl:call-template name="phpdoc.nav.array">
   <xsl:with-param name="node" select="$home"/>
  </xsl:call-template>
  
  <xsl:call-template name="phpweb.encoding"/>
  
 <xsl:text disable-output-escaping="yes">,
    'this' =&gt; </xsl:text>

 <xsl:call-template name="phpdoc.nav.array">
  <xsl:with-param name="node" select="."/>
 </xsl:call-template>

 <xsl:text disable-output-escaping="yes">,
    'prev' =&gt; </xsl:text>

 <xsl:call-template name="phpdoc.nav.array">
  <xsl:with-param name="node" select="$prev"/>
 </xsl:call-template>

 <xsl:text disable-output-escaping="yes">,
    'next' =&gt; </xsl:text>

 <xsl:call-template name="phpdoc.nav.array">
  <xsl:with-param name="node" select="$next"/>
 </xsl:call-template>
 
 <xsl:text disable-output-escaping="yes">,
    'up'   =&gt; </xsl:text>

 <xsl:call-template name="phpdoc.nav.array">
  <xsl:with-param name="node" select="$up"/>
 </xsl:call-template>

 <xsl:text disable-output-escaping="yes">,
    'toc'  =&gt; array(</xsl:text>
 
 <xsl:for-each select="../*">
  <xsl:variable name="ischunk"><xsl:call-template name="chunk"/></xsl:variable>
  <xsl:if test="$ischunk='1'">
   <xsl:text>
      </xsl:text>
   <xsl:call-template name="phpdoc.nav.array">
    <xsl:with-param name="node" select="."/>
    <xsl:with-param name="useabbrev" select="'1'"/>
   </xsl:call-template>
   <xsl:text>,</xsl:text>
  </xsl:if>
 </xsl:for-each>

 <xsl:text>
    )
));
manual_header();
?</xsl:text>
 </xsl:processing-instruction>
 <xsl:text>
</xsl:text>
</xsl:template>

<!-- Similar to the manualHeader() call above -->
<xsl:template name="footer.navigation">
 <xsl:text>
</xsl:text>
 <xsl:processing-instruction name="php">
  <xsl:text>manual_footer(); ?</xsl:text>
  </xsl:processing-instruction>
</xsl:template>

<!-- Eliminate HTML from chunked file contents -->
<xsl:template name="chunk-element-content">
 <xsl:param name="prev"></xsl:param>
 <xsl:param name="next"></xsl:param>
 <xsl:param name="content">
   <xsl:apply-templates/>
 </xsl:param>

 <xsl:call-template name="header.navigation">
  <xsl:with-param name="prev" select="$prev"/>
  <xsl:with-param name="next" select="$next"/>
 </xsl:call-template>

 <xsl:copy-of select="$content"/>

 <xsl:call-template name="footer.navigation">
  <xsl:with-param name="prev" select="$prev"/>
  <xsl:with-param name="next" select="$next"/>
 </xsl:call-template>

</xsl:template>

<!-- Prints out one PHP array with page name and title -->
<xsl:template name="phpdoc.nav.array">
 <xsl:param name="node" select="/foo"/>
 <xsl:param name="useabbrev" select="'0'"/>
 
 <!-- Get usual title -->
 <xsl:variable name="title">
  <xsl:apply-templates select="$node" mode="phpdoc.object.title"/>
 </xsl:variable>
 
 <!-- Compute titleabbrev value -->
 <xsl:variable name="titleabbrev">
  <xsl:if test="$useabbrev = '1' and string($node/titleabbrev) != ''">
    <xsl:value-of select="$node/titleabbrev" />
  </xsl:if>
 </xsl:variable>
 
 <!-- Print out PHP array -->
 <xsl:text>array('</xsl:text>
 <xsl:choose>
  <!-- special handling for copyright, as we have it
       in it's own chunk, but the stylesheets don't
       know about it -->
  <xsl:when test="local-name($node) = 'legalnotice'">
   <xsl:value-of select="concat('copyright',$html.ext)"/>
  </xsl:when>
  <xsl:otherwise>
   <xsl:call-template name="href.target">
    <xsl:with-param name="object" select="$node"/>
   </xsl:call-template>
  </xsl:otherwise>
 </xsl:choose>
 <xsl:text>','</xsl:text>
 <!-- use the substring replace template defined in
      Docbook XSL's lib to escape apostrophes -->
 <xsl:call-template name="string.subst">
  <xsl:with-param name="string">
   <xsl:choose>
    <xsl:when test="$titleabbrev != ''">
     <xsl:value-of select="$titleabbrev"/>
    </xsl:when>
    <xsl:otherwise>
     <xsl:value-of select="$title"/>
    </xsl:otherwise>
   </xsl:choose>
  </xsl:with-param>
  <xsl:with-param name="target" select='"&apos;"'/>
  <xsl:with-param name="replacement" select='"\&apos;"'/>
 </xsl:call-template>
 <xsl:text>')</xsl:text>
</xsl:template>

<!-- Custom mode for titles for navigation without
     "Chapter 1" and other autogenerated content -->
<xsl:template match="*" mode="phpdoc.object.title">
 <xsl:call-template name="substitute-markup">
  <xsl:with-param name="allow-anchors" select="0"/>
  <xsl:with-param name="template" select="'%t'"/>
 </xsl:call-template>
</xsl:template>

</xsl:stylesheet>
