<?php

$modHistoryFile = 'fileModHistory.php';

$modHistoryArray = [];
if (file_exists($modHistoryFile)) {
    $modHistoryArray = include $modHistoryFile;
    if (!is_array($modHistoryArray)) {
        exit("Corrupted modificiation history file\n");
    }
}

if (isset($modHistoryArray["last commit hash"]) && $modHistoryArray["last commit hash"] !== "") {
    $cmd = "git rev-parse --quiet --verify " . $modHistoryArray["last commit hash"];
    if (exec($cmd, $verifiedHash) === false) {
        exit("Could not retrieve hash of the last commit\n");
    }
    if (implode("", $verifiedHash) !== $modHistoryArray["last commit hash"]) {
        // we cannot handle reverted commits as we don't know what changes to roll back
        exit("Modification history file's commit hash is not in this branch's commit history\n");
    }
    $lastCommitHash = $modHistoryArray["last commit hash"];
} else {
    // since there is no modification history, generate it for all commits since the inital one
    // 4b825dc642cb6eb9a060e54bf8d69288fbee4904 is the SHA1 of the empty git tree
    $lastCommitHash = "4b825dc642cb6eb9a060e54bf8d69288fbee4904";
}

$modifiedFilescommand = <<<COMMAND
#!/usr/bin/env bash
echo "last commit hash:"
echo "$(git rev-parse HEAD)"
git diff --name-only HEAD $lastCommitHash | while read -r filename; do
  echo "filename:"
  echo "\$filename"
  echo "modified:"
  echo "$(git log -1 --format='%aI' -- \$filename)"
  echo "contributors:"
  git log --format='%an' -- \$filename|awk '!a[$0]++'
done
COMMAND;

if (exec($modifiedFilescommand, $output) === false) {
    exit("Could not retrieve info from last commit\n");
}

$modifiedFiles = [];
$currentType = "";
foreach ($output as $line) {
    switch ($line) {
        case "filename:":
            $currentType = "filename";
            continue 2;
        case "modified:":
            $currentType = "modDateTime";
            continue 2;
        case "contributors:":
            $currentType = "contributors";
            continue 2;
        case "last commit hash:":
            $currentType = "commitHash";
            continue 2;
    }
    if ($currentType === "") {
        continue;
    }

    switch ($currentType) {
        case "filename":
            $currentFile = $line;
            break;
        case "modDateTime":
            if ($currentFile === "") {
                continue 2;
            }
            $modifiedFiles[$currentFile]["modified"] = $line;
            break;
        case "contributors":
            if ($currentFile === "") {
                continue 2;
            }
            $modifiedFiles[$currentFile]["contributors"][] = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
            break;
        case "commitHash":
            $modifiedFiles["last commit hash"][] = $line;
            break;
    }
}

if (count($modifiedFiles) === 1) {
    // there will always be 1 entry with the last commit hash
    exit("No files have been modified\n");
}

$mergedModHistory = array_merge($modHistoryArray, $modifiedFiles);

$newModHistoryString = "<?php\n\n/* This is a generated file */\n\nreturn [\n";
foreach ($mergedModHistory as $fileName => $fileProps) {
    if ($fileName === "last commit hash") {
        $newModHistoryString .= "    \"last commit hash\" => \"" . implode("", $fileProps) . "\",\n";
        continue;
    }
    $newModHistoryString .= '    "' . $fileName . "\" => [\n";
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
}
$newModHistoryString .= "];\n";

if (file_put_contents($modHistoryFile, $newModHistoryString) === false) {
    exit("Could not write modification history file\n");
}

echo "Modification history updated\n";
