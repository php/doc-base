;; -*- Scheme -*-
;;
;; $Id$
;;
;; This file contains stylesheet customization for locale specific
;; preferences in HTML version.
;;

(define ($component-title$ #!optional (titlegi "H1") (subtitlegi "H2"))
  (let* ((info (cond
		((equal? (gi) (normalize "appendix"))
		 (select-elements (children (current-node)) (normalize "docinfo")))
		((equal? (gi) (normalize "article"))
		 (select-elements (children (current-node)) (normalize "artheader")))
		((equal? (gi) (normalize "bibliography"))
		 (select-elements (children (current-node)) (normalize "docinfo")))
		((equal? (gi) (normalize "chapter"))
		 (select-elements (children (current-node)) (normalize "docinfo")))
		((equal? (gi) (normalize "dedication"))
		 (empty-node-list))
		((equal? (gi) (normalize "glossary"))
		 (select-elements (children (current-node)) (normalize "docinfo")))
		((equal? (gi) (normalize "index"))
		 (select-elements (children (current-node)) (normalize "docinfo")))
		((equal? (gi) (normalize "preface"))
		 (select-elements (children (current-node)) (normalize "docinfo")))
		((equal? (gi) (normalize "reference"))
		 (select-elements (children (current-node)) (normalize "docinfo")))
		((equal? (gi) (normalize "setindex"))
		 (select-elements (children (current-node)) (normalize "docinfo")))
		(else
		 (empty-node-list))))
	 (exp-children (if (node-list-empty? info)
			   (empty-node-list)
			   (expand-children (children info) 
					    (list (normalize "bookbiblio") 
						  (normalize "bibliomisc")
						  (normalize "biblioset")))))
	 (parent-titles (select-elements (children (current-node)) (normalize "title")))
	 (info-titles   (select-elements exp-children (normalize "title")))
	 (titles        (if (node-list-empty? parent-titles)
			    info-titles
			    parent-titles))
	 (subtitles     (select-elements exp-children (normalize "subtitle"))))
    (make sequence
      (make element gi: titlegi
	    (make element gi: "A"
		  attributes: (list (list "NAME" (element-id)))
		  (if (and %chapter-autolabel%
			   (or (equal? (gi) (normalize "chapter"))
			       (equal? (gi) (normalize "appendix"))))

		      (if %prefers-ordinal-label-name-format%
			  (literal (element-label (current-node)) ". "
				   (gentext-element-name (gi))
				   (gentext-label-title-sep (gi)))
			  (literal (gentext-element-name-space (gi))
				   (element-label (current-node))
				   (gentext-label-title-sep (gi))))

		      (empty-sosofo))
		  (if (node-list-empty? titles)
		      (element-title-sosofo) ;; get a default!
		      (with-mode title-mode
			(process-node-list titles)))))
      (if (node-list-empty? subtitles) 
	  (empty-sosofo)
	  (with-mode subtitle-mode
	    (make element gi: subtitlegi
		  (process-node-list subtitles)))))))


(define (nav-context-sosofo elemnode)
  (let* ((component     (ancestor-member elemnode
					 (append (book-element-list)
						 (division-element-list)
						 (component-element-list))))
	 (context-text  (inherited-dbhtml-value elemnode "context-text")))
    (if (and context-text (not (string=? context-text "")))
	(literal context-text)
	(if (equal? (element-label component) "")
	    (make sequence
	      (element-title-sosofo component))
	    (make sequence
	      ;; Special case.  This is a bit of a hack.
	      ;; I need to revisit this aspect of 
	      ;; appendixes. 
	      (if (and (equal? (gi component) (normalize "appendix"))
		       (or (equal? (gi elemnode) (normalize "sect1"))
			   (equal? (gi elemnode) (normalize "section")))
		       (equal? (gi (parent component)) (normalize "article")))
		  (empty-sosofo)

		  (if %prefers-ordinal-label-name-format%
		      (make sequence
		       (element-label-sosofo component)
		       (literal ". " (gentext-element-name (gi component))))
		      (make sequence
		       (literal (gentext-element-name-space (gi component)))
		       (element-label-sosofo component))))

	      (literal (gentext-label-title-sep (gi component)))
	      (element-title-sosofo component))))))




