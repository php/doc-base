<!-- 
  DocBook 3->4 conversion for reference files
-->

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                version="1.0">

<xsl:output method="xml" />

<xsl:template match="*">
  <xsl:copy >
    <xsl:copy-of select="@*"/>
    <xsl:apply-templates/>
  </xsl:copy>
</xsl:template>

<xsl:template match="comment()">
  <xsl:text>
</xsl:text>
  <xsl:copy >
    <xsl:apply-templates/>
  </xsl:copy>
  <xsl:text>
</xsl:text>
</xsl:template>

<xsl:template match="*/funcsynopsis">
     <xsl:apply-templates/>   
</xsl:template>

<xsl:template match="*/funcsynopsis/funcprototype">
    <methodsynopsis>
     <xsl:apply-templates/>
    </methodsynopsis>
</xsl:template>

<xsl:template match="*/funcsynopsis/funcprototype/funcdef">
    <xsl:apply-templates/>
</xsl:template>

<xsl:template match="*/funcsynopsis/funcprototype/funcdef/function">
<methodname><xsl:apply-templates/></methodname>
</xsl:template>

<xsl:template match="*/funcsynopsis/funcprototype/funcdef/text()[1]">
  <xsl:if test="position() = 1">
     <type>
      <xsl:value-of select="normalize-space(.)"/>
     </type>
  </xsl:if>
</xsl:template>

<xsl:template match="*/funcsynopsis/funcprototype/paramdef">
  <xsl:choose>
    <xsl:when test="count(parameter)>0">
     <methodparam>
      <xsl:if test="*/optional">
       <xsl:attribute name="choice">opt</xsl:attribute>
      </xsl:if>
      <xsl:apply-templates/>
     </methodparam>
  </xsl:when>
  <xsl:otherwise>
     <void/>
  </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<xsl:template match="*/funcsynopsis/funcprototype/paramdef/text()[1]">
  <xsl:if test="position() = 1">
     <type>
      <xsl:value-of select="normalize-space(.)"/>
     </type>
  </xsl:if>
</xsl:template>


<xsl:template match="*/funcsynopsis/funcprototype/paramdef/parameter/optional">
      <xsl:apply-templates/>
</xsl:template>

<xsl:template match="*/funcsynopsis/funcprototype/varargs">
     <methodparam rep="repeat"><type>mixed</type><parameter>...</parameter></methodparam>
</xsl:template>





</xsl:stylesheet>
