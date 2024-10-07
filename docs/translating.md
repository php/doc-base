# Translating documentation

**Watch out:** this chapter describes special parts of the whole editing process.
You will also have to follow other steps from the [editing manual sources](editing.md) section.

Translating documentation into other languages might look like a complicated
process, but in fact, it's rather simple.

Every file in Git has a *commit hash*. It is basically the current version of
the specified file. We use commit hashes to check if a file is synchronized with its
English counterpart: to find out if the translation is up-to-date. That's why every
file in your translation requires an `EN-Revision` comment with the following syntax:
```
<!-- EN-Revision: [some commit hash] Maintainer: [username] Status: ready -->
```
The most important part of this comment is the commit hash of the English file
that this translated file is based on. Let's see examples:

## Translating new file
Say you want to translate the documentation of the `in_array()` function, which
doesn't exist in your language yet. Get the commit hash by changing into the `en` directory and executing the command `git log -n1 --pretty=format:%H -- reference/array/functions/in-array.xml`

For this example, let's say our commit hash is `68a9c82e06906a5c00e0199307d87dd3739f719b`. Let's see how your translated file header
should look like if we assume that your PHP.net username is *johnsmith*:
```
<?xml version="1.0" encoding="utf-8"?>
<!-- EN-Revision: 68a9c82e06906a5c00e0199307d87dd3739f719b Maintainer: johnsmith Status: ready -->
```

The rule is simple: if your revision number is equal to the revision number of
the English file you've translated, then your translation is up-to-date.
Otherwise, it needs to be synced.

## Updating translation of existing file
Let's assume that you want to update the translation of `password_needs_rehash()`.
One way to see which files require updating, and what has to be
changed to sync with the English version, is to use the [doc.php.net tools](http://doc.php.net).

Choose your language from the right sidebar and then use the "Outdated files" tool.
Filter files by directory or username (username used here comes from the `Mantainer`
variable in the header comment described above). Let's assume that the tool marked
`password-needs-rehash.xml` as outdated. Click on the filename and you will see
*diff* - list of changes between two versions of file: your version (current
commit hash in `EN-Revision` in your translation) and newest version in the English source
tree. The example below shows what the diff might look like:

```
diff --git a/reference/password/functions/password-needs-rehash.xml b/reference/password/functions/password-needs-rehash.xml
index 984eb2dc5c..860758a4a4 100644
--- a/reference/password/functions/password-needs-rehash.xml
+++ b/reference/password/functions/password-needs-rehash.xml
@@ -12,8 +12,8 @@
   <methodsynopsis>
    <type>boolean</type><methodname>password_needs_rehash</methodname>
    <methodparam><type>string</type><parameter>hash</parameter></methodparam>
-   <methodparam><type>string</type><parameter>algo</parameter></methodparam>
-   <methodparam choice="opt"><type>string</type><parameter>options</parameter></methodparam>
+   <methodparam><type>int</type><parameter>algo</parameter></methodparam>
+   <methodparam choice="opt"><type>array</type><parameter>options</parameter></methodparam>
   </methodsynopsis>
   <para>
    This function checks to see if the supplied hash implements the algorithm
```

As you can see, there is a difference between two lines. The `types` for the
parameters `options` and `algo` in the synopsis had been changed from `string`,
to `int` and `array` respectively. You have to perform these changes in your
translation to make it up-to-date. Open `phpdoc/{LANG}/reference/password/functions/password-needs-rehash.xml`
and change those lines to match the English version.

Then update the `EN-Revision` commit hash in the header comment.
Your file header might look like this initially:
```
<?xml version="1.0" encoding="utf-8"?>
<!-- EN-Revision: 6640ca4c12c6bcaf0b2a99e75871f417b38df1a2 Maintainer: someone Status: ready -->
```
and after changes it should look like this:
```
<?xml version="1.0" encoding="utf-8"?>
<!-- EN-Revision: a1b67e45e7c762a917323d260c491c0361040ce4 Maintainer: someone Status: ready -->
```
The new `EN-Revision` commit hash came from the doc tools page.

Your translation is now up-to-date. It is quite a long process but it's simple
and logical when you get used to it.
