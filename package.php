<?php
    require_once('PEAR/PackageFileManager.php');
    $packagexml = new PEAR_PackageFileManager;
    $e = $packagexml->setOptions(
                                array(
                                        'baseinstalldir' => 'XML',
                                        'version' => '0.1',
                                        'packagedirectory' => 'C:\www\projects\pear\XML_Beautifier',
                                        'state' => 'beta',
                                        'filelistgenerator' => 'file', // generate from cvs, use file for directory
                                        'notes' => "proposal",
                                        'ignore' => array('package.xml', 'package.php', 'doc/'), // ignore TODO, all files in tests/
                                        'installexceptions' => array(), // baseinstalldir ="/" for phpdoc
                                        'dir_roles' => array('examples' => 'doc'),
                                        'exceptions' => array()
                                      )
                                 );
    if (PEAR::isError($e)) {
        echo $e->getMessage();
        die();
    }

    // note use of  - this is VERY important
    if (isset($_GET['make']) || $_SERVER['argv'][2] == 'make') {
        $e = $packagexml->writePackageFile();
    } else {
        $e = $packagexml->debugPackageFile();
    }
    if (PEAR::isError($e)) {
    echo $e->getMessage();
    die();
}
?>