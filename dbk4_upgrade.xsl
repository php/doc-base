<!-- 
  DocBook 3->4 conversion for reference files
-->

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                version="1.0">

<xsl:output method="xml" />

<!-- default rule -> copy element, attributes and recursive content -->
<xsl:template match="*">
  <xsl:copy >
    <xsl:copy-of select="@*"/>
    <xsl:apply-templates/>
  </xsl:copy>
</xsl:template>

<!-- toplevel comments need linefeeds around them
     as toplevel whitespace is not processed -->
<xsl:template match="/comment()">
  <xsl:text>
</xsl:text>
  <xsl:copy >
    <xsl:apply-templates/>
  </xsl:copy>
  <xsl:text>
</xsl:text>
</xsl:template>

<!-- all other comments are just copied -->
<xsl:template match="*/comment()">
  <xsl:copy >
    <xsl:apply-templates/>
  </xsl:copy>
</xsl:template>

<!-- ignore funcsynopsis tags -->
<xsl:template match="*/funcsynopsis">
     <xsl:apply-templates/>   
</xsl:template>

<!-- convert foncprototype to methodsynopsis -->
<xsl:template match="*/funcsynopsis/funcprototype">
    <methodsynopsis>
     <xsl:apply-templates/>
    </methodsynopsis>
</xsl:template>

<!-- ignore funcdef tag -->
<xsl:template match="*/funcsynopsis/funcprototype/funcdef">
    <xsl:apply-templates/>
</xsl:template>

<!-- function is now methodname in this context -->
<xsl:template match="*/funcsynopsis/funcprototype/funcdef/function">
<methodname><xsl:apply-templates/></methodname>
</xsl:template>

<!-- first text element is the return type
     needs to be enclused in type tags now
-->
<xsl:template match="*/funcsynopsis/funcprototype/funcdef/text()[1]">
  <xsl:if test="position() = 1"> <!-- first only -->
     <type>
      <xsl:value-of select="normalize-space(.)"/>
     </type>
  </xsl:if>
</xsl:template>

<!-- paramdef is now methodparam, empty paramdef should be void/ -->
<xsl:template match="*/funcsynopsis/funcprototype/paramdef">
  <xsl:choose>
    <xsl:when test="count(parameter)>0">
     <methodparam>
      <xsl:if test="*/optional"> <!-- optional is now attribute and not special tag -->
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

<!-- first text in paramdef is paramter type and needs type tags -->
<xsl:template match="*/funcsynopsis/funcprototype/paramdef/text()[1]">
  <xsl:if test="position() = 1">
     <type>
      <xsl:value-of select="normalize-space(.)"/>
     </type>
  </xsl:if>
</xsl:template>

<!-- ignore optional tag here, already processed above -->
<xsl:template match="*/funcsynopsis/funcprototype/paramdef/parameter/optional">
      <xsl:apply-templates/>
</xsl:template>

<!-- there is no varargs in methodsynopsis, but a rep attribute for methodparam -->
<xsl:template match="*/funcsynopsis/funcprototype/varargs">
     <methodparam rep="repeat"><type>mixed</type><parameter>...</parameter></methodparam>
</xsl:template>





</xsl:stylesheet>
