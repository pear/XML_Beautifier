--TEST--
XML Beautifier - Bug #2144: High-UTF entities in attributed decoded as ?
--FILE--
<?php
/*
 * The bug report complains of entities being changed to '?" marks,
 * but I cannot duplicate it here.  Instead, this test case
 * fails because the XML tag is not being included in the output.  
 * That problem is already reported in Bug #5450.  This test case 
 * should begin passing after #5450 is fixed.
 */

require_once 'XML/Beautifier.php';

$xml = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
  <bogustag
attribute="&#x418;&#x43D;&#x43D;&#x43E;&#x432;&#x430;&#x446;&#x438;&#x43E;&#x43D;&#x43D;&#x44B;&#x439;&#x434;&#x430;&#x439;&#x434;&#x436;&#x435;&#x441;&#x442;">
    <content />
  </bogustag>
EOF;

$bf = new XML_Beautifier();
echo $bf->formatString( $xml);
?>
--EXPECT--
<?xml version="1.0" encoding="UTF-8"?>
<bogustag attribute="Инновационный дайджест">
    <content />
</bogustag>
