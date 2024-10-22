# User Note Editing Guidelines
These are some guidelines to follow when editing user notes in the manual.

To begin editing user notes in the manual, you must have a PHP.net account, and you must either:
- Subscribe to the `php-notes` mailing list or newsgroup. As a user submits a new user note, it will appear
  as a message on the mailing list with links in the footer of the email that enable you to delete, edit,
  or reject that particular note.
- Log on to the server at https://main.php.net/manage/user-notes.php using your PHP.net account username and password.
  The user notes administration interface enables you to search for user notes that match particular strings
  and edit or change the status of particular notes directly through the Web interface.

The thing that seems to confuse the most people is the difference between *rejecting* and *deleting* a note.
Basically, they both remove the note from the manual, but *rejecting* sends the user an email about the rejection
with links to support links and other information. Here are some guidelines of when to use each. You can also view
the exact text of the rejection email [here](https://github.com/php/web-master/blob/master/manage/user-notes.php).
- If the note is asking for help (support request, *Does this work...?*, etc.) or if the person is reporting a bug,
  *reject* the note. The email will show them the proper place to report such issues.
- If the note contains useful information appropriate for the manual proper, you may incorporate the information
  into the manual and then *delete* the note.
- If the note is in the wrong place, incorrect, a giant block of silly, unnecessary code, poorly written, an answer
  to another person's question, or just overall confusing, *delete* it. If it was an answer to a question, hunt down
  that note and *reject* it.
- If the note is in a language other than English, *delete* the note.
- If the note submitter's email address is obviously bogus, don't *reject* the note, just *delete* it.
  Rejecting the note just gives the mail server more work trying to send an email to a non-existent address,
  which doesn't help anything.

If for some reason you need to add to a note, first ask yourself if it's worth it. Make sure you're not answering
a user's question; if you are, then the note doesn't belong there (see above). If you're clarifying a point, see
if it is appropriate to add the clarification to the manual proper; if it is, add it and *delete* the note (see above).
If you still feel that adding your addition to the note will be the best option, then go ahead and add it. Usually, editors
add their note in a "Editor's Note" block at the top. Unless you are correcting a minor error, make it obvious that you edited the note.

If you have some free time and commit access to phpdoc, try going through some of the manual pages and adding some of
the better notes into the documentation proper. Be sure to *delete* these notes after they're implemented.

If you are in doubt about what to do with a note, you may ask for help on the `php-notes` mailing list (or `phpdoc`,
if what you're doing involves the documentation proper).
