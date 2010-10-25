<?php

require_once 'PEAR/PackageFileManager.php';

$version = '1.2.0';
$state   = 'stable';

$notes = <<<EOT
- switched to BSD License
- switch to package.xml v2
- PEAR CS cleanup
- Fixed Bug #1009:  Data in <![CDATA[ ... ]]> [schst]
- Fixed Bug #1232:  The standalone attributes turned to 'on' [schst]
EOT;

$description = <<<EOT
XML_Beautifier will add indentation and linebreaks to you XML files, replace all entities, format your comments and makes your document easier to read.
You can  influence the way your document is beautified with several options, ranging from indentation to changing the case of tags and normalizing your comments.
EOT;

$package = new PEAR_PackageFileManager();

$result = $package->setOptions(array(
    'package'           => 'XML_Beautifier',
    'summary'           => 'Class to format XML documents.',
    'description'       => $description,
    'version'           => $version,
    'state'             => $state,
    'license'           => 'BSD License',
    'filelistgenerator' => 'cvs',
    'ignore'            => array('package.php', 'package.xml', 'package2.php', 'package2.xml'),
    'notes'             => $notes,
    'simpleoutput'      => true,
    'baseinstalldir'    => 'XML',
    'packagedirectory'  => './',
    'dir_roles'         => array('examples' => 'doc')
    ));

/**
if (PEAR::isError($result)) {
    echo $result->getMessage();
    die();
}
**/

$package->addMaintainer('schst', 'lead', 'Stephan Schmidt', 'schst@php-tools.net');
$package->addMaintainer('ashnazg', 'lead', 'Chuck Burgess', 'ashnazg@php.net');

$package->addDependency('XML_Parser', '', 'has', 'pkg', false);
$package->addDependency('XML_Util', '0.5', 'ge', 'pkg', false);
$package->addDependency('php', '4.2.0', 'ge', 'php', false);

if (isset($_GET['make']) || (isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] == 'make')) {
    $result = $package->writePackageFile();
} else {
    $result = $package->debugPackageFile();
}

if (PEAR::isError($result)) {
    echo $result->getMessage();
    die();
}
?>
