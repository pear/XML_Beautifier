<?PHP
/**
* XML_Beautifier example 1
*
* This example displays the multilineTags option.
*
* @author	Stephan Schmidt <schst@php.net>
*/

    require_once 'XML/Beautifier.php';
    $fmt = new XML_Beautifier( array( "multilineTags" => true ) );
    $result = $fmt->formatFile('test.xml', 'test2.xml');

    echo "<h3>Original file</h3>";
    echo "<pre>";
    echo htmlspecialchars(implode("",file('test.xml')));
    echo "</pre>";
        
    echo    "<br><br>";
    
    echo "<h3>Beautified file</h3>";
    echo "<pre>";
    echo htmlspecialchars(implode("",file('test2.xml')));
    echo "</pre>";
?>
