<?php
/**
 * script to automate the generation of the
 * package.xml file.
 *
 * @author      Stephan Schmidt <schst@php-tools.net>
 * @package     XML_Beautifier
 * @subpackage  Tools
 */

/**
 * uses PackageFileManager
 */ 
require_once 'PEAR/PackageFileManager.php';

$version = '1.2.0';

$notes = <<<EOT
- Added support for cdata sections (bug #1009)
- fixed bug #1232 (standalone attribute of XML declaration always set to "yes"
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
    'state'             => 'stable',
    'license'           => 'PHP License',
    'filelistgenerator' => 'cvs',
    'ignore'            => array('package.php', 'package.xml'),
    'notes'             => $notes,
    'simpleoutput'      => false,
    'baseinstalldir'    => 'XML',
    'packagedirectory'  => './',
    'dir_roles'         => array('docs' => 'doc',
                                 'examples' => 'doc',
                                 'tests' => 'test',
                                 )
    ));

if (PEAR::isError($result)) {
    echo $result->getMessage();
    die();
}

$package->addMaintainer('schst', 'lead', 'Stephan Schmidt', 'schst@php-tools.net');

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