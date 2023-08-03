<?php

require_once __DIR__ . '/require.php';

/**
 * Parse `git log` to complement file state
 */

class GitLogParser
{
    static function parseInto( string $lang , RevcheckFileList & $list )
    {
        $cwd = getcwd();
        chdir( $lang );
        $fp = popen( "git log --name-only" , "r" );
        $hash = "";
        $date = "";
        $skip = false;
        while ( ( $line = fgets( $fp ) ) !== false )
        {
            // new commit block
            if ( substr( $line , 0 , 7 ) == "commit " )
            {
                $hash = trim( substr( $line , 7 ) );
                $date = "";
                $skip = false;
                continue;
            }
            // datetime of commit
            if ( strpos( $line , 'Date:' ) === 0 )
            {
                $line = trim( substr( $line , 5 ) );
                $date = strtotime( $line );
                continue;
            }
            // other headers
            if ( strpos( $line , ': ' ) > 0 )
                continue;
            // empty lines
            if ( trim( $line ) == "" )
                continue;
            // commit message
            if ( str_starts_with( $line , '    ' ) )
            {
                // commits with this mark are ignored
                if ( stristr( $line, '[skip-revcheck]' ) !== false )
                    $skip = true;
                continue;
            }
            // otherwise, a filename
            $filename = trim( $line );
            $info = $list->get( $filename );

            // untracked file (deleted, renamed)
            if ( $info == null )
                continue;

            // do not track skiped commits
            if ( $skip )
            {
                if ( $info->hash == "" && $info->skip != "" )
                    fwrite( STDERR , "Double [skip-revcheck] on $filename\n" );
                $info->skip = $hash;
                continue;
            }

            // already found a more recent hash
            if ( $info->hash != "" )
                continue;

            // finally, the oldest commit
            $info->date = $date;
            $info->hash = $hash;
        }

        pclose( $fp );
        chdir( $cwd );
    }
}