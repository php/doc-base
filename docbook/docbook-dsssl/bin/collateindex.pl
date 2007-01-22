#!/usr/bin/perl -- # -*- Perl -*-
#
# $Id$

=head1 NAME

collateindex.pl - generate DocBook index files

=head1 SYNOPSIS

B<collateindex.pl> [B<-f>] [B<-g>] [B<-i> I<id>] [B<-I> I<scope>] [B<-N>]
                [B<-o> F<file>] [B<-p>] [B<-P> F<file>] [B<-q>] [B<-s> I<name>]
                [B<-S> I<scope>] [B<-t> I<name>] [B<-x>] F<file>

=head1 DESCRIPTION

B<collateindex.pl> creates index data for DocBook XML or SGML files.

=cut

use File::Basename;
use Getopt::Std;

$me = basename($0);

$usage = "Usage: $0 [options] file
Try \"perldoc $me\" for documentation.\n";

( $version = '$Revision$' ) =~ s/^\$[R]evision:\s*([^ ]*)\s*\$$/$1/;

=head1 OPTIONS

=over 5

=item B<-f>

Force the output file to be written, even if it appears to have been
edited by hand.

=item B<-g>

Group terms with IndexDiv based on the first letter of the term (or
its SortAs attribute).  (This might not handle all language environments.)

=item B<-i> I<id>

The ID to use for the E<lt>indexE<gt> tag.

=item B<-I> I<scope>

The implied scope, must be C<all>, C<local>, or C<global>.  IndexTerms
which do not specify a scope will have the implied scope.  If
unspecified, C<all> is assumed.

=item B<-N>

New index (generates an empty index file).

=item B<-o> F<file>

Output to F<file>. Defaults to F<stdout>.

=item B<-p>

Link to points in the document.  The default is to link to the closest
containing section.

=item B<-P> F<file>

Read a preamble from F<file>.  The contents of F<file> will be
inserted before the E<lt>indexE<gt> tag.

=item B<-q>

Run quietly.

=item B<-s> I<name>

Name the IndexDiv that contains symbols.  The default is C<Symbols>.
Meaningless if B<-g> is not used.

=item B<-S> I<scope>

Scope of the index, must be C<all>, C<local>, or C<global>.  If
unspecified, C<all> is assumed.

=item B<-t> I<name>

Title for the index.

=item B<-x>

Make a SetIndex.

=item B<-V>

Print version number and exit.

=item F<file>

The file containing index data generated with the DocBook DSSSL
HTML stylesheet (usually called F<HTML.index>).

=back

=cut


die $usage if ! getopts('Dfgi:NpP:s:o:S:I:t:xqV');

$linkpoints   = $opt_p;
$lettergroups = $opt_g;
$symbolsname  = $opt_s || "Symbols";
$title        = $opt_t;
$preamble     = $opt_P;
$outfile      = $opt_o || '-';
$indexid      = $opt_i;
$scope        = uc($opt_S) || 'ALL';
$impliedscope = uc($opt_I) || 'ALL';
$setindex     = $opt_x;
$forceoutput  = $opt_f;
$newindex     = $opt_N;
$debug        = $opt_D;
$quiet        = $opt_q;

if ( $opt_V ) {
    print "collateindex.pl $version\n";
    exit 0;
}

$indextag     = $setindex ? 'setindex' : 'index';

if ($newindex) {
    safe_open(*OUT, $outfile);
    if ($indexid) {
	print OUT "<$indextag id='$indexid'>\n\n";
    } else {
	print OUT "<$indextag>\n\n";
    }

    print OUT "<!-- This file was produced by collateindex.pl.         -->\n";
    print OUT "<!-- Remove this comment if you edit this file by hand! -->\n";

    print OUT "</$indextag>\n";
    exit 0;
}

$dat = shift @ARGV || die $usage;
die "$me: file \"$dat\" does not exist\n" if ! -f $dat;

%legal_scopes = ('ALL' => 1, 'LOCAL' => 1, 'GLOBAL' => 1);
if ($scope && !$legal_scopes{$scope}) {
    die "$me: invalid scope: $scope\n";
}
if ($impliedscope && !$legal_scopes{$impliedscope}) {
    die "$me: invalid implied scope: $impliedscope\n";
}

@term = ();
%id   = ();

$termcount = 0;

$quiet || print STDERR "Processing $dat...\n";

# Read the index file, creating an array of objects.  Each object
# represents and indexterm and has fields for the content of the
# indexterm

open (F, $dat);
while (<F>) {
    chop;
    chop if /\r$/;

    if (/^\/indexterm/i) {
	push (@term, $idx);
	next;
    }

    if (/^indexterm (.*)$/i) {
	$termcount++;
	$idx = {};
	$idx->{'zone'} = {};
	$idx->{'href'} = $1;
	$idx->{'count'} = $termcount;
	$idx->{'scope'} = $impliedscope;
	next;
    }

    if (/^indexpoint (.*)$/i) {
	$idx->{'hrefpoint'} = $1;
	next;
    }

    if (/^title (.*)$/i) {
	$idx->{'title'} = $1;
	next;
    }

    if (/^primary[\[ ](.*)$/i) {
	if (/^primary\[(.*?)\] (.*)$/i) {
	    $idx->{'psortas'} = &escape($1);
	    $idx->{'primary'} = &escape($2);
	} else {
	    $idx->{'psortas'} = &escape($1);
	    $idx->{'primary'} = &escape($1);
	}
	next;
    }

    if (/^secondary[\[ ](.*)$/i) {
	if (/^secondary\[(.*?)\] (.*)$/i) {
	    $idx->{'ssortas'} = &escape($1);
	    $idx->{'secondary'} = &escape($2);
	} else {
	    $idx->{'ssortas'} = &escape($1);
	    $idx->{'secondary'} = &escape($1);
	}
	next;
    }

    if (/^tertiary[\[ ](.*)$/i) {
	if (/^tertiary\[(.*?)\] (.*)$/i) {
	    $idx->{'tsortas'} = &escape($1);
	    $idx->{'tertiary'} = &escape($2);
	} else {
	    $idx->{'tsortas'} = &escape($1);
	    $idx->{'tertiary'} = &escape($1);
	}
	next;
    }

    if (/^see (.*)$/i) {
	$idx->{'see'} = &escape($1);
	next;
    }

    if (/^seealso (.*)$/i) {
	$idx->{'seealso'} = &escape($1);
	next;
    }

    if (/^significance (.*)$/i) {
	$idx->{'significance'} = &escape($1);
	next;
    }

    if (/^class (.*)$/i) {
	$idx->{'class'} = &escape($1);
	next;
    }

    if (/^scope (.*)$/i) {
	$idx->{'scope'} = &escape(uc($1));
	next;
    }

    if (/^startref (.*)$/i) {
	$idx->{'startref'} = $1;
	next;
    }

    if (/^id (.*)$/i) {
	$idx->{'id'} = $1;
	$id{$1} = $idx;
	next;
    }

    if (/^zone (.*)$/i) {
	my($href) = $1;
	$_ = scalar(<F>);
	chop;
	die "$me: invalid zone: $_\n" if !/^title (.*)$/i;
	$idx->{'zone'}->{$href} = $1;
	next;
    }

    die "$me: unrecognized tag in input: $_\n";
}
close (F);

$quiet || print STDERR "$termcount entries loaded...\n";

# Fixup the startrefs...
# In DocBook, STARTREF is a #CONREF attribute; support this by copying
# all of the fields from the indexterm with the id specified by STARTREF
# to the indexterm that has the STARTREF.
foreach $idx (@term) {
    my($ididx, $field);
    if ($idx->{'startref'}) {
	$ididx = $id{$idx->{'startref'}};
	foreach $field ('primary', 'secondary', 'tertiary', 'see', 'seealso',
		        'psortas', 'ssortas', 'tsortas', 'significance',
		        'class', 'scope') {
	    $idx->{$field} = $ididx->{$field};
	}
    }
}

# Sort the index terms
@term = sort termsort @term;

# Move all of the non-alphabetic entries to the front of the index.
@term = sortsymbols(@term);

safe_open(*OUT, $outfile);

# Write the index...
if ($indexid) {
    print OUT "<$indextag id='$indexid'>\n\n";
} else {
    print OUT "<$indextag>\n\n";
}

print OUT "<!-- This file was produced by collateindex.pl.         -->\n";
print OUT "<!-- Remove this comment if you edit this file by hand! -->\n";

print OUT "<!-- ULINK is abused here.

      The URL attribute holds the URL that points from the index entry
      back to the appropriate place in the output produced by the HTML
      stylesheet. (It's much easier to calculate this URL in the first
      pass.)

      The Role attribute holds the ID (either real or manufactured) of
      the corresponding INDEXTERM.  This is used by the print backends
      to produce page numbers.

      The entries below are sorted and collated into the correct order.
      Duplicates may be removed in the HTML backend, but in the print
      backends, it is impossible to suppress duplicate pages or coalesce
      sequences of pages into a range.
-->\n\n";

print OUT "<title>$title</title>\n\n" if $title;

$last = {};     # the last indexterm we processed
$first = 1;     # this is the first one
$group = "";    # we're not in a group yet
$lastout = "";  # we've not put anything out yet
@seealsos = (); # See also stack.

foreach $idx (@term) {
    next if $idx->{'startref'}; # no way to represent spans...
    next if ($idx->{'scope'} eq 'LOCAL') && ($scope eq 'GLOBAL');
    next if ($idx->{'scope'} eq 'GLOBAL') && ($scope eq 'LOCAL');
    next if &same($idx, $last); # suppress duplicates

    $termcount--;

    # If primary changes, output a whole new index term, otherwise just
    # output another secondary or tertiary, as appropriate.  We know from
    # sorting that the terms will always be in the right order.
    if (!&tsame($last, $idx, 'primary')) {
	print "DIFF PRIM\n" if $debug;
	&end_entry() if not $first;

	if ($lettergroups) {
	    # If we're grouping, make the right indexdivs
	    $letter = $idx->{'psortas'};
	    $letter = $idx->{'primary'} if !$letter;
	    $letter = uc(substr($letter, 0, 1));

	    # symbols are a special case
	    if (($letter lt 'A') || ($letter gt 'Z')) {
		if (($group eq '')
		    || (($group ge 'A') && ($group le 'Z'))) {
		    print OUT "</indexdiv>\n" if !$first;
		    print OUT "<indexdiv><title>$symbolsname</title>\n\n";
		    $group = $letter;
		}
	    } elsif (($group eq '') || ($group ne $letter)) {
		print OUT "</indexdiv>\n" if !$first;
		print OUT "<indexdiv><title>$letter</title>\n\n";
		$group = $letter;
	    }
	}

	$first = 0; # there can only be on first ;-)

	print OUT "<indexentry>\n";
	print OUT "  <primaryie>", $idx->{'primary'};
	$lastout = "primaryie";

 	if ($idx->{'secondary'}) {
	    print OUT "\n  </primaryie>\n";
	    print OUT "  <secondaryie>", $idx->{'secondary'};
	    $lastout = "secondaryie";
	};

	if ($idx->{'tertiary'}) {
	    print OUT "\n  </secondaryie>\n";
	    print OUT "  <tertiaryie>", $idx->{'tertiary'};
	    $lastout = "tertiaryie";
	}
    } elsif (!&tsame($last, $idx, 'secondary')) {
	print "DIFF SEC\n" if $debug;

	print OUT "\n  </$lastout>\n" if $lastout;

	foreach (@seealsos) {
	    # it'd be nice to make this a link...
	    print OUT $indent, "  <seealsoie>", &escape($_), "</seealsoie>\n";
	}
	@seealsos = ();

	print OUT "  <secondaryie>", $idx->{'secondary'};
	$lastout = "secondaryie";
	if ($idx->{'tertiary'}) {
	    print OUT "\n  </secondaryie>\n";
	    print OUT "  <tertiaryie>", $idx->{'tertiary'};
	    $lastout = "tertiaryie";
	}
    } elsif (!&tsame($last, $idx, 'tertiary')) {
	print "DIFF TERT\n" if $debug;

	print OUT "\n  </$lastout>\n" if $lastout;

	foreach (@seealsos) {
	    # it'd be nice to make this a link...
	    print OUT $indent, "  <seealsoie>", &escape($_), "</seealsoie>\n";
	}
	@seealsos = ();

	if ($idx->{'tertiary'}) {
	    print OUT "  <tertiaryie>", $idx->{'tertiary'};
	    $lastout = "tertiaryie";
	}
    }

    &print_term($idx);

    $last = $idx;
}

# Termcount is > 0 iff some entries were skipped.
$quiet || print STDERR "$termcount entries ignored...\n";

&end_entry();

print OUT "</indexdiv>\n" if $lettergroups;
print OUT "</$indextag>\n";

close (OUT);

$quiet || print STDERR "Done.\n";

sub same {
    my($a) = shift;
    my($b) = shift;

    my($aP) = $a->{'psortas'} || $a->{'primary'};
    my($aS) = $a->{'ssortas'} || $a->{'secondary'};
    my($aT) = $a->{'tsortas'} || $a->{'tertiary'};

    my($bP) = $b->{'psortas'} || $b->{'primary'};
    my($bS) = $b->{'ssortas'} || $b->{'secondary'};
    my($bT) = $b->{'tsortas'} || $b->{'tertiary'};

    my($same);

    $aP =~ s/^\s*//; $aP =~ s/\s*$//; $aP = uc($aP);
    $aS =~ s/^\s*//; $aS =~ s/\s*$//; $aS = uc($aS);
    $aT =~ s/^\s*//; $aT =~ s/\s*$//; $aT = uc($aT);
    $bP =~ s/^\s*//; $bP =~ s/\s*$//; $bP = uc($bP);
    $bS =~ s/^\s*//; $bS =~ s/\s*$//; $bS = uc($bS);
    $bT =~ s/^\s*//; $bT =~ s/\s*$//; $bT = uc($bT);

#    print "[$aP]=[$bP]\n";
#    print "[$aS]=[$bS]\n";
#    print "[$aT]=[$bT]\n";

    # Two index terms are the same if:
    # 1. the primary, secondary, and tertiary entries are the same
    #    (or have the same SORTAS)
    # AND
    # 2. They occur in the same titled section
    # AND
    # 3. They point to the same place
    #
    # Notes: Scope is used to suppress some entries, but can't be used
    #          for comparing duplicates.
    #        Interpretation of "the same place" depends on whether or
    #          not $linkpoints is true.

    $same = (($aP eq $bP)
	     && ($aS eq $bS)
	     && ($aT eq $bT)
	     && ($a->{'title'} eq $b->{'title'})
	     && ($a->{'href'} eq $b->{'href'}));

    # If we're linking to points, they're only the same if they link
    # to exactly the same spot.
    $same = $same && ($a->{'hrefpoint'} eq $b->{'hrefpoint'})
	if $linkpoints;

    if ($same) {
       warn "$me: duplicated index entry found: $aP $aS $aT\n";
    }

    $same;
}

sub tsame {
    # Unlike same(), tsame only compares a single term
    my($a) = shift;
    my($b) = shift;
    my($term) = shift;
    my($sterm) = substr($term, 0, 1) . "sortas";
    my($A, $B);

    $A = $a->{$sterm} || $a->{$term};
    $B = $b->{$sterm} || $b->{$term};

    $A =~ s/^\s*//; $A =~ s/\s*$//; $A = uc($A);
    $B =~ s/^\s*//; $B =~ s/\s*$//; $B = uc($B);

    return $A eq $B;
}

sub end_entry {
    # End any open elements...
    print OUT "\n  </$lastout>\n" if $lastout;

    foreach (@seealsos) {
	# it'd be nice to make this a link...
	print OUT $indent, "  <seealsoie>", &escape($_), "</seealsoie>\n";
    }
    @seealsos = ();

    print OUT "</indexentry>\n\n";
    $lastout = "";
}

sub print_term {
    # Print out the links for an indexterm.  There can be more than
    # one if the term has a ZONE that points to more than one place.
    # (do we do the right thing in that case?)
    my($idx) = shift;
    my($key, $indent, @hrefs);
    my(%href) = ();
    my(%phref) = ();

    $indent = "    ";

    if ($idx->{'see'}) {
	# it'd be nice to make this a link...
	if ($lastout) {
	    print OUT "\n  </$lastout>\n";
	    $lastout = "";
	}
	print OUT $indent, "<seeie>", &escape($idx->{'see'}), "</seeie>\n";
	return;
    }

    if (keys %{$idx->{'zone'}}) {
	foreach $key (keys %{$idx->{'zone'}}) {
	    $href{$key} = $idx->{'zone'}->{$key};
	    $phref{$key} = $key;
	}
    } else {
	$href{$idx->{'href'}} = $idx->{'title'};
	$phref{$idx->{'href'}} = $idx->{'hrefpoint'};
    }

    # We can't use <LINK> because we don't know the ID of the term in the
    # original source (and, in fact, it might not have one).
    print OUT ",\n";
    @hrefs = keys %href;
    while (@hrefs) {
	my($linkend) = "";
	my($role) = "";
	$key = shift @hrefs;
	if ($linkpoints) {
	    $linkend = $phref{$key};
	} else {
	    $linkend = $key;
	}

	$role = $phref{$key};
	$role = $1 if $role =~ /\#(.*)$/;
	$role = $1 if $role =~ /(.*)\./;

	print OUT $indent;
	print OUT "<ulink url=\"$linkend\" role=\"$role\">";
	print OUT "<emphasis>" if ($idx->{'significance'} eq 'PREFERRED');
	print OUT &escape($href{$key});
	print OUT "</emphasis>" if ($idx->{'significance'} eq 'PREFERRED');
	print OUT "</ulink>";
    }

    if ($idx->{'seealso'}) {
	push @seealsos, $idx->{'seealso'};
    }
}

sub termsort {
    my($aP) = $a->{'psortas'} || $a->{'primary'};
    my($aS) = $a->{'ssortas'} || $a->{'secondary'};
    my($aT) = $a->{'tsortas'} || $a->{'tertiary'};
    my($ap) = $a->{'count'};

    my($bP) = $b->{'psortas'} || $b->{'primary'};
    my($bS) = $b->{'ssortas'} || $b->{'secondary'};
    my($bT) = $b->{'tsortas'} || $b->{'tertiary'};
    my($bp) = $b->{'count'};

    $aP =~ s/^\s*//; $aP =~ s/\s*$//; $aP = uc($aP);
    $aS =~ s/^\s*//; $aS =~ s/\s*$//; $aS = uc($aS);
    $aT =~ s/^\s*//; $aT =~ s/\s*$//; $aT = uc($aT);
    $bP =~ s/^\s*//; $bP =~ s/\s*$//; $bP = uc($bP);
    $bS =~ s/^\s*//; $bS =~ s/\s*$//; $bS = uc($bS);
    $bT =~ s/^\s*//; $bT =~ s/\s*$//; $bT = uc($bT);

    if ($aP eq $bP) {
	if ($aS eq $bS) {
	    if ($aT eq $bT) {
		# make sure seealso's always sort to the bottom
		return 1 if ($a->{'seealso'});
		return -1  if ($b->{'seealso'});
		# if everything else is the same, keep these elements
		# in document order (so the index links are in the right
		# order)
		return $ap <=> $bp;
	    } else {
		return $aT cmp $bT;
	    }
	} else {
	    return $aS cmp $bS;
	}
    } else {
	return $aP cmp $bP;
    }
}

sub sortsymbols {
    my(@term) = @_;
    my(@new) = ();
    my(@sym) = ();
    my($letter);
    my($idx);

    # Move the non-letter things to the front.  Should digits be thier
    # own group?  Maybe...
    foreach $idx (@term) {
	$letter = $idx->{'psortas'};
	$letter = $idx->{'primary'} if !$letter;
	$letter = uc(substr($letter, 0, 1));

	if (($letter lt 'A') || ($letter gt 'Z')) {
	    push (@sym, $idx);
	} else {
	    push (@new, $idx);
	}
    }

    return (@sym, @new);
}

sub safe_open {
    local(*OUT) = shift;
    local(*F, $_);

    if (($outfile ne '-') && (!$forceoutput)) {
	my($handedit) = 1;
	if (open (OUT, $outfile)) {
	    while (<OUT>) {
		if (/<!-- Remove this comment if you edit this file by hand! -->/){
		    $handedit = 0;
		    last;
		}
	    }
	    close (OUT);
	} else {
	    $handedit = 0;
	}

	if ($handedit) {
	    print STDERR "$me: file \"$outfile\" appears to have been edited by hand\n";
	    print STDERR "Use the -f option or specify a different output file name.\n";
	    exit 1;
	}
    }

    open (OUT, ">$outfile") || die "$me: could not open file \"$outfile\": $!\n";

    if ($preamble) {
	# Copy the preamble
	if (open(F, $preamble)) {
	    while (<F>) {
		print OUT $_;
	    }
	    close(F);
	} else {
	    warn "$me: could not open preamble file \"$preamble\": $!\n";
	}
    }
}

sub escape {
    # make sure & and < don't show up in the index
    local $_ = shift;
    s/&/&amp;/sg;
    s/</&lt;/sg;
    s/>/&gt;/sg; # what the heck

    return $_;
}



=head1 EXAMPLE

B<collateindex.pl> B<-o> F<index.sgml> F<HTML.index>

=head1 EXIT STATUS

=over 5

=item B<0>

Success

=item B<1>

Failure

=back

=head1 AUTHOR

Norm Walsh E<lt>ndw@nwalsh.comE<gt>

Minor updates by Adam Di Carlo E<lt>adam@onshore.comE<gt> and Peter Eisentraut E<lt>peter_e@gmx.netE<gt>

=cut

