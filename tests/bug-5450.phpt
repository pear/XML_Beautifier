--TEST--
XML Beautifer - Bug #5450: Parser strip many tags
--FILE--
<?php
require_once 'XML/Beautifier.php';

$string = <<<EOF
<?xml version="1.0" encoding="iso-8859-1"?><!DOCTYPE bookmark SYSTEM "bookmark.dtd"><bookmark><category><![CDATA[ this cdata will be stripped ]]></category></bookmark>
EOF;

$xml = new XML_Beautifier();
echo $xml->formatString($string);
?>
--EXPECT--
<?xml version="1.0" encoding="iso-8859-1"?>
<!DOCTYPE bookmark SYSTEM "bookmark.dtd">
<bookmark>
    <category>
        <![CDATA[ this cdata will be stripped ]]>
    </category>
</bookmark>

