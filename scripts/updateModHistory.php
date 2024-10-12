<?php

$commandLineOptions = getopt("", ["docs-path:", "history-path::"]);

verifyCommandLineOptions($commandLineOptions);

$documentationPath = realpath($commandLineOptions["docs-path"]);
$modHistoryFile = realpath($commandLineOptions["history-path"]);

$runningInGithubActions = (getenv("GITHUB_ACTIONS") !== false);

$head = $runningInGithubActions ? "\$GITHUB_SHA" : "HEAD";

$modHistoryArray = [];
if (file_exists($modHistoryFile)) {
    echo timeStamp() . " - Loading modification history file... ";
    $modHistoryArray = include $modHistoryFile;
    if (!is_array($modHistoryArray)) {
		echo "file is corrupted (not an array)\n";
        exit(1);
    }
    echo "done\n";
} else {
    echo timeStamp() . " - Modification history file doesn't exist\n";
}

echo timeStamp() . " - Switching to documentation directory... ";
chdir($documentationPath);
echo "done\n";

if (isset($modHistoryArray["last commit hash"]) && $modHistoryArray["last commit hash"] !== "") {
    echo timeStamp() . " - Found last commit hash: " . $modHistoryArray["last commit hash"] . "\n";
    echo timeStamp() . " - Retrieving hash of the common ancestor of HEAD and the last commit... ";
    $cmd = "git merge-base " . $modHistoryArray["last commit hash"] . " $head";
    if (exec($cmd, $commonAncestor, $exitCode) === false
        || $exitCode > 0) {
		echo "failed\n";
        exit(1);
    }
    $commonAncestorHash = implode("", $commonAncestor);
    echo "done: ";
} else {
    echo timeStamp() . " - Last commit hash not found. Using empty git tree hash: ";
    // since there is no modification history, generate it for all commits since the inital one
    // 4b825dc642cb6eb9a060e54bf8d69288fbee4904 is the SHA1 of the empty git tree
    $commonAncestorHash = "4b825dc642cb6eb9a060e54bf8d69288fbee4904";
}
echo $commonAncestorHash . "\n";

echo timeStamp() . " - Retrieving number of files with a diff... ";
$cmd = "git diff --name-only $commonAncestorHash $head | wc -l";
if (exec($cmd, $numOfFilesWithDiff, $exitCode) === false
    || $exitCode > 0) {
    echo "failed\n";
    exit(1);
}
$numOfFilesWithDiff = implode("", $numOfFilesWithDiff);
echo "done (" . $numOfFilesWithDiff . ")\n";

if ($numOfFilesWithDiff === "0") {
    echo timeStamp() . " - No changes since last commit. Exiting...\n";
    exit(0);
}

$modifiedFilescommand = <<<COMMAND
#!/usr/bin/env bash
echo "last commit hash:"
echo "$(git rev-parse $head)"
git diff --name-only $commonAncestorHash $head | while read -r filename; do
  echo "filename:"
  echo "\$filename"
  echo "modified:"
  echo "$(git log -1 --format='%aI' -- \$filename)"
  echo "contributors:"
  git log --format='%an' -- \$filename|awk '!a[$0]++'
done
COMMAND;

echo timeStamp() . " - Retrieving commit authors and last commit date/time of modified files... \n";

$modifiedFiles = [];

$proc = popen($modifiedFilescommand, 'rb');
while (($line = fgets($proc)) !== false) {
    processGitDiffLine(rtrim($line, "\n\r"), $modifiedFiles);
    if (! $runningInGithubActions) {
        $fileCounter = max(count($modifiedFiles) - 1, 0);
        fwrite(
            STDERR,
            sprintf("\033[0G{$fileCounter} of {$numOfFilesWithDiff} files read...", "", "")
        );
    }
}
pclose($proc);

echo " done\n";

$s = ($modifiedFiles > 2) ? "s" : "";
echo timeStamp() . " - Retrieved author$s and last commit date$s/time$s for " . (count($modifiedFiles) - 1) . " file$s\n";
if (count($modifiedFiles) === 1) {
    // there will always be at least 1 entry with the last commit hash
    exit(1);
}

$mergedModHistory = array_merge($modHistoryArray, $modifiedFiles);

echo timeStamp() . " - Writing modification history file... ";

$fp = fopen($modHistoryFile, "w");
fwrite($fp, "<?php\n\n/* This is a generated file */\n\nreturn [\n");
foreach ($mergedModHistory as $fileName => $fileProps) {
    if ($fileName === "last commit hash") {
        fwrite($fp, "    \"last commit hash\" => \"" . implode("", $fileProps) . "\",\n");
        continue;
    }
    $newModHistoryString = '    "' . $fileName . "\" => [\n";
    $newModHistoryString .= "        \"modified\" => \"" . ($fileProps["modified"] ?? "") . "\",\n";
    $newModHistoryString .= "        \"contributors\" => [\n";
    if (isset($fileProps["contributors"])) {
        if (!is_array($fileProps["contributors"])) {
            exit("Non-array contributors list\n");
        }
        foreach ($fileProps["contributors"] as $contributor) {
            $newModHistoryString .= "            \"" . $contributor . "\",\n";
        }
    }
    $newModHistoryString .= "        ],\n";
    $newModHistoryString .= "    ],\n";
    fwrite($fp, $newModHistoryString);
}
fwrite($fp, "];\n");
fclose($fp);

echo "done at " . date('H:i:s') . "\n";

function timeStamp(): string {
    return "[" . date('H:i:s') . "]";
}

function processGitDiffLine($line, &$modifiedFiles): void {
    static $currentType = "";
    static $currentFile = "";

    switch ($line) {
        case "filename:":
            $currentType = "filename";
            return;
        case "modified:":
            $currentType = "modDateTime";
            return;
        case "contributors:":
            $currentType = "contributors";
            return;
        case "last commit hash:":
            $currentType = "commitHash";
            return;
    }
    if ($currentType === "") {
        return;
    }

    switch ($currentType) {
        case "filename":
            $currentFile = $line;
            break;
        case "modDateTime":
            if ($currentFile === "") {
                return;
            }
            $modifiedFiles[$currentFile]["modified"] = $line;
            break;
        case "contributors":
            if ($currentFile === "") {
                return;
            }
            $modifiedFiles[$currentFile]["contributors"][] = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
            break;
        case "commitHash":
            $modifiedFiles["last commit hash"][] = $line;
            break;
    }
}

function verifyCommandLineOptions($commandLineOptions): void {
    $output = timeStamp() . " - Parsing command line arguments... ";
    echo $output;
    if ($commandLineOptions === false
        || !isset($commandLineOptions["docs-path"])) {
        echo "\"--docs-path\" is a required argument\n";
        exit(1);
    }
    echo "documentation path supplied\n";
    if (isset($commandLineOptions["history-path"])) {
        echo str_repeat(" ", strlen($output)) . "mod history file path supplied\n";
    }

    $output = timeStamp() . " - Verifying command line arguments... ";
    echo $output;
    if (!file_exists($commandLineOptions["docs-path"])) {
        echo "documentation path \"" . $commandLineOptions["docs-path"] . "\" doesn't exist\n";
        exit(1);
    } else if (!is_dir($commandLineOptions["docs-path"])) {
        echo "documentation path \"" . $commandLineOptions["docs-path"] . "\" is not a directory\n";
        exit(1);
    }
    echo "documentation path verified\n";

    if (isset($commandLineOptions["history-path"])) {
        echo "\n" . str_repeat(" ", strlen($output)) . "mod history file path ";
        if (!file_exists($commandLineOptions["history-path"])) {
            echo "\"" . $commandLineOptions["history-path"] . "\" doesn't exist\n";
            exit(1);
        } else if (!is_file($commandLineOptions["history-path"])) {
            echo "\"" . $commandLineOptions["history-path"] . "\" is not a file\n";
            exit(1);
        }
        echo "verified\n";
    }
}
