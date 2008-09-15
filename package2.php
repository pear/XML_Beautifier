<?php

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$desc = <<<EOT
XML_Beautifier will add indentation and linebreaks to you XML files, replace all entities, format your comments and makes your document easier to read.
You can  influence the way your document is beautified with several options, ranging from indentation to changing the case of tags and normalizing your comments.
EOT;

$version = '1.2.0RC1';
$apiver  = '1.2.0';
$state   = 'beta';

$notes = <<<EOT
- switched to BSD License
- switch to package.xml v2
- PEAR CS cleanup
- Fixed Bug #1009:  Data in <![CDATA[ ... ]]> [schst]
- Fixed Bug #1232:  The standalone attributes turned to 'on' [schst]
EOT;

$package = PEAR_PackageFileManager2::importOptions(
    'package2.xml',
    array(
    'filelistgenerator' => 'cvs',
    'changelogoldtonew' => false,
    'simpleoutput'	=> true,
    'baseinstalldir'    => 'XML',
    'packagefile'       => 'package2.xml',
    'packagedirectory'  => '.'));

if (PEAR::isError($result)) {
    echo $result->getMessage();
    die();
}

$package->clearDeps();

$package->setPackage('XML_Beautifier');
$package->setPackageType('php');
$package->setSummary('Class to format XML documents.');
$package->setDescription($desc);
$package->setChannel('pear.php.net');
$package->setLicense('BSD License', 'http://opensource.org/licenses/bsd-license');
$package->setAPIVersion($apiver);
$package->setAPIStability($state);
$package->setReleaseVersion($version);
$package->setReleaseStability($state);
$package->setNotes($notes);
$package->setPhpDep('4.2.0');
$package->setPearinstallerDep('1.3.0');
$package->addPackageDepWithChannel('required', 'XML_Parser', 'pear.php.net', '1.0');
$package->addPackageDepWithChannel('required', 'XML_Util', 'pear.php.net', '0.5');
$package->addIgnore(array('package.php', 'package2.php', 'package.xml', 'package2.xml'));
$package->addReplacement('Beautifier.php', 'package-info', '@package_version@', 'version');
$package->addReplacement('Beautifier/Tokenizer.php', 'package-info', '@package_version@', 'version');
$package->addReplacement('Beautifier/Renderer.php', 'package-info', '@package_version@', 'version');
$package->addReplacement('Beautifier/Renderer/Plain.php', 'package-info', '@package_version@', 'version');
$package->generateContents();

if ($_SERVER['argv'][1] == 'make') {
    $result = $package->writePackageFile();
} else {
    $result = $package->debugPackageFile();
}

if (PEAR::isError($result)) {
    echo $result->getMessage();
    die();
}
