<!DOCTYPE style-sheet PUBLIC "-//James Clark//DTD DSSSL Style Sheet//EN" [
<!ENTITY docbook.dsl SYSTEM "/usr/lib/dsssl/stylesheets/docbook/html/docbook.dsl" CDATA DSSSL>
]>

<!--

  $Id$

  Stylesheet customization for install.txt

-->

<style-sheet>
<style-specification id="docbook-php-html" use="docbook">
<style-specification-body>

(define %generate-chapter-toc% #f)
(define %generate-book-toc% #f)
(define ($generate-book-lot-list$) (list))
(define %generate-chapter-titlepage% #f)
(define %generate-part-titlepage% #f)
(define %generate-book-titlepage% #f)
(define %chapter-autolabel% #f)

</style-specification-body>
</style-specification>

<external-specification id="docbook" document="docbook.dsl">

</style-sheet>
