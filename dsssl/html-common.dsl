;; -*- Scheme -*-
;;
;; $Id$
;;

;; Returns the depth of the auto-generated TOC (table of
;; contents) that should be made at the nd-level
(define (toc-depth nd)
  (if (string=? (gi nd) "book")
      2 ; the depth of the top-level TOC
      3 ; the depth of all other TOCs
      ))

;; re-defining element-id as we need to get the id of the parent
;; element not only for title but also for question in the faq
(define (element-id #!optional (nd (current-node)))
  (let ((elem (if (equal? (gi nd) (normalize "title")) (parent nd)  
                   (if (equal? (gi nd) (normalize "question")) (parent nd) 
                       nd))))
    (if (attribute-string (normalize "id") elem)
        (attribute-string (normalize "id") elem)
        (generate-anchor elem))))

;; Make function definitions bold
(element (funcdef function) 
  ($bold-seq$
   (make sequence
     (process-children)
     )
   )
  )


;; There are two different kinds of optionals
;; optional parameters and optional parameter parts.
;; An optional parameter is identified by an optional tag
;; with a parameter tag as its parent 
;; and only whitespace between them
(element optional 
  ;;check for true optional parameter
  (if (is-true-optional (current-node))
      ;; yes - handle '[...]' in paramdef
      (process-children-trim) 
      ;; no - do '[...]' output
      (make sequence
        (literal %arg-choice-opt-open-str%)
        (process-children-trim)
        (literal %arg-choice-opt-close-str%)
        )
      )
  )                

;; Print out parameters in italic
(element (paramdef parameter)
  (make sequence
    font-posture: 'italic                                                       
    (process-children-trim)
    )
  )                                                       

;; Now this is going to be tricky
(element paramdef  
  (make sequence
    ;; special treatment for first parameter in funcsynopsis
    (if (equal? (child-number (current-node)) 1)
        ;; is first ?
        (make sequence
          ;; start parameter list
          (literal " (") 
          ;; is optional ?
          ( if (has-true-optional (current-node))
               (literal %arg-choice-opt-open-str%)
               (empty-sosofo)
               )
          )
        ;; not first
        (empty-sosofo)
        )
    
    ;;
    (process-children-trim)
    
    ;; special treatment for last parameter 
    (if (equal? (gi (ifollow (current-node))) (normalize "paramdef"))                                        
        ;; more parameters will follow
        (make sequence
          ;; next is optional ?
          ( if (has-true-optional (ifollow (current-node)))
               ;; optional
               (make sequence
                 (literal " ")
                 (literal %arg-choice-opt-open-str%)
                 )
               ;; not optional
               (empty-sosofo)
               )
          (literal ", " ) 
          )
        ;; last parameter
        (make sequence
          (literal 
           (let loop ((result "")(count (count-true-optionals (parent (current-node)))))
             (if (<= count 0)
                 result
                 (loop (string-append result %arg-choice-opt-close-str%)(- count 1))
                 )
             )
           )
          ( literal ")" )
          )
        )
    )
  )

(element function
  (let* ((function-name (data (current-node)))
     (linkend 
      (string-append
       "function." 
       (case-fold-down (string-replace
                        (string-replace function-name "_" "-")
                        "::" "."))))
     (target (element-with-id linkend))
     (parent-gi (gi (parent))))
    (cond
     ;; function names should be plain in FUNCDEF
     ((equal? parent-gi "funcdef")
      (process-children))
     
     ;; If a valid ID for the target function is not found, or if the
     ;; FUNCTION tag is within the definition of the same function,
     ;; make it bold, add (), but don't make a link
     ((or (node-list-empty? target)
      (equal? (case-fold-down
           (data (node-list-first
              (select-elements
               (node-list-first
                (children
                 (select-elements
                  (children
                   (ancestor-member (parent) (list "refentry")))
                  "refnamediv")))
               "refname"))))
          (case-fold-down function-name)))
      ($bold-seq$
       (make sequence
     (process-children)
     (literal "()"))))
     
     ;; Else make a link to the function and add ()
     (else
      (make element gi: "A"
        attributes: (list
             (list "HREF" (href-to target)))
        ($bold-seq$
         (make sequence
           (process-children)
           (literal
        )
           (literal "()"))))))))


;; Dispaly of examples
(element example
  (make sequence
    (make element gi: "TABLE"
      attributes: (list
               (list "WIDTH" "100%")
               (list "BORDER" "0")
               (list "CELLPADDING" "0")
               (list "CELLSPACING" "0")
               (list "CLASS" "EXAMPLE"))
      (make element gi: "TR"
        (make element gi: "TD"
              ($formal-object$))))))


;; Prosessing tasks for the frontpage
(mode book-titlepage-recto-mode
  (element authorgroup
    (process-children))
    
  (element author
    (let ((author-name  (author-string))
          (author-affil (select-elements (children (current-node)) 
                                         (normalize "affiliation"))))
      (make sequence      
        (make element gi: "DIV"
              attributes: (list (list "CLASS" (gi)))
              (literal author-name))
        (process-node-list author-affil))))

  (element editor
    (let ((editor-name (author-string)))
      (make sequence
        (if (first-sibling?)
            (make element gi: "H2"
                  attributes: (list (list "CLASS" "EDITEDBY"))
                  (literal (gentext-edited-by)))
            (empty-sosofo))
        (make element gi: "DIV"
              attributes: (list (list "CLASS" (gi)))
              (literal editor-name)))))
)

;; Display of question tags, link targets
(element question
  (let* ((chlist   (children (current-node)))
         (firstch  (node-list-first chlist))
         (restch   (node-list-rest chlist)))
    (make element gi: "B"
    (make element gi: "DIV"
          attributes: (list (list "CLASS" (gi)))
          (make element gi: "P"
                (make element gi: "A"
                      attributes: (list (list "NAME" (element-id)))
                      (empty-sosofo))
                (make element gi: "B"
                      (literal (question-answer-label (current-node)) " "))
                (process-node-list (children firstch)))
          (process-node-list restch))))   )          

;; Adding class HTML parameter to examples
;; having a role parameter, to make PHP examples
;; distinguisable from other ones in the manual
(define ($verbatim-display$ indent line-numbers?)
  (let (
(content (make element gi: "PRE"
       attributes: (list
    (list "CLASS" (if (attribute-string (normalize "role"))
      (attribute-string (normalize "role"))
      (gi))))
       (if (or indent line-numbers?)
   ($verbatim-line-by-line$ indent line-numbers?)
   (process-children-trim)))))
    (if %shade-verbatim%
(make element gi: "TABLE"
      attributes: (list 
                   (list "BORDER" "0")
                   (list "BGCOLOR" "#E0E0E0")
                   (list "CELLPADDING" "5")
                   )
      (make element gi: "TR"
    (make element gi: "TD"
  content)))
(make sequence
  (para-check)
  content
  (para-check 'restart)))))

(define (linebreak) (make element gi: "BR" (empty-sosofo)))

(define %html-header-tags%
  '(("META" ("HTTP-EQUIV" "Content-type") ("CONTENT" "text/html; charset=ISO-8859-1"))))
