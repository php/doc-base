# Some popular tags and entities

    <filename>          filenames
    <constant>          constants
    <varname>           variables
    <parameter>         a function's parameter/argument
    <function>          functions, this links to function pages or bolds if
                        already on the function's page.  it also adds ().

    <literal>           teletype/mono-space font <tt>
    <emphasis>          italics
    <example>           see HOWTO, includes many other tags.
    <link>              internal manual links
                        <link linkend="language.variables">variables</link>

    <link>              external links via global.ent
                        <link xlink:href="&spec.cookies;">mmm cookies</link>

    <type>              types, this links to the given types manual
                        page: <type>object</type> -> php.net/types.object

    &return.success;    see: language-snippets.ent
    &true;              <constant>TRUE</constant>
    &false;             <constant>FALSE</constant>
    &php.ini;           <filename>php.ini</filename>

Be sure to check out `entities/global.ent` (in the `doc-base` repo) and
`language-snippets.ent` (located within each language's repo) for more
information for entities and URLs.
