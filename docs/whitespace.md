# Why we care about whitespace
I just wanted to post a little reminder about why we care so much about whitespace.
Over the years this topic has come up in many forms, usually via a whitespace unfriendly
commit that is immediately followed by a fellow team members complaint. And soon after,
a fix is made. There are reasons we as a group (especially all past and present editors)
have been strict and anal about this topic and here's why:

## Consistent look to the documentation
All the documentation ends up looking the same within all editors. The indention here is one
space, no tabs, and only PHP code itself is four spaces as per our [coding standards](http://pear.php.net/manual/en/standards.php).
This is a beautiful and consistent feature of the PHP manual sources.

## Pretty diffs
Peer review is critical to the success of any Open Source project, including the PHP Manual.
Fellow members of the team follow and read the commits so a diff should focus on changed content
and not mix in whitespace manipulations. If something doesn't fit or look right, our eyes focus on it...
this wastes time. Translators also rely on pretty diffs.

## Diff === Artistic Masterpiece
Each diff can be seen as a piece of art. Look it over... is this something you might send your lover?
Professor? Potential employer? Remember that every commit we make goes on record forever and represents
the author. Making them pretty and useful means we will be pretty and useful, both individually and as a group.

## A Diff should be perfect
Sometimes we want to quickly commit something and if needed update it again later. This is no good and
instead we should wait until later before making the commit. There is no need to spam the mailing list
with *"I didn't have time and was going to fix it later"* commits… there is no rush, the manual has existed
for 10 years without the information and can wait another hour/day... simply save the file and attend to it later.
The perfect diff meets all of the above descriptions.

# How to create pretty whitespace
Unified diffs make this easy. Before any commit is made, the author should look over a diff of the
edited file(s). This allows said author to find errors (like typos) as well as accidental whitespace
changes. After making another change, check it again. Once the diff is as intended then commit it
(after make test of course!) and move on.

I hope this helps explain why we are so strict about whitespace.
