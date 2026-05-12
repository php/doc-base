# ex: ts=4 sw=4 et filetype=sh

_phpdoc()
{
    local cur opts
    COMPREPLY=()
    cur="${COMP_WORDS[COMP_CWORD]}"
    opts="
        --help
        --version
        --quiet
        --srcdir=
        --basedir=
        --rootdir=
        --enable-chm
        --enable-xml-details
        --disable-version-files
        --disable-sources-file
        --disable-history-file
        --disable-libxml-check
        --with-php=
        --with-lang=
        --with-partial=
        --disable-broken-file-listing
        --disable-xpointer-reporting
        --redirect-stderr-to-stdout
        --output=
        --generate="

    if [[ ${cur} == -* ]] ; then
        COMPREPLY=( $(compgen -W "${opts}" -- ${cur}) )
        return 0
    fi
}

complete -F _phpdoc phpdoc
