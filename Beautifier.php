<?PHP
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Stephan Schmidt <schst@php.net>                             |
// +----------------------------------------------------------------------+

/**
 * XML/Beautifier.php
 *
 * Package that formats your XML files, that means
 * it is able to add line breaks, and indents your tags.
 *
 * @category XML
 * @package  XML_Beautifier
 * @author   Stephan Schmidt <schst@php.net>
 * @todo     option to specify inline tags
 * @todo	 beautify DTD and XML declaration
 */

/**
 * XML_Parser is needed to parse the document
 */
require_once 'XML/Parser.php';

/**
 * XML_Util is needed to create the tags
 */
require_once 'XML/Util.php';

/**
 * element is empty
 */
define('XML_BEAUTIFIER_EMPTY', 0);

/**
 * CData
 */
define('XML_BEAUTIFIER_CDATA', 1);

/**
 * XML element
 */
define('XML_BEAUTIFIER_ELEMENT', 2);

/**
 * processing instruction
 */
define('XML_BEAUTIFIER_PI', 4);

/**
 * entity
 */
define('XML_BEAUTIFIER_ENTITY', 8);

/**
 * comment
 */
define('XML_BEAUTIFIER_COMMENT', 16);

/**
 * XML declaration
 */
define('XML_BEAUTIFIER_XML_DECLARATION', 32);

/**
 * default
 */
define('XML_BEAUTIFIER_DEFAULT', 128);

/**
 * overwrite the original file
 */
define('XML_BEAUTIFIER_OVERWRITE', -1);


/**
 * could not write to output file
 */
define('XML_BEAUTIFIER_ERROR_NO_OUTPUT_FILE', 151);

/**
 * XML_Beautifier is a class that adds linebreaks and
 * indentation to your XML files. It can be used on XML
 * that looks ugly (e.g. any generated XML) to transform it 
 * to a nicely looking XML that can be read by humans.
 *
 * It removes unnecessary whitespace and adds indentation
 * depending on the nesting level.
 *
 * It is able to treat tags, data, processing instructions
 * comments, external entities and the XML prologue.
 *
 * XML_Beautifier is parsing an XML document with a SAX based
 * parser and builds tokens of tags, comments, entities, data, etc.
 * These tokens are then serialized and indented with your indent
 * string. Future versions will include a separation of the 'tokenizer'
 * and the serializer, so it will be possible to create a syntax
 * highlighted HTML document, image or anything else from an XML
 * document.
 *
 * Example 1: Formatting a file
 * <code>
 * require_once 'XML/Beautifier.php';
 * $fmt = new XML_Beautifier();
 * $result = $fmt->formatFile('oldFile.xml', 'newFile.xml');
 * </code>
 *
 * Example 2: Formatting a string
 * <code>
 * require_once 'XML/Beautifier.php';
 * $xml = '<root><foo   bar = "pear"/></root>';
 * $fmt = new XML_Beautifier();
 * $result = $fmt->formatString($xml);
 * </code>
 *
 * @category XML
 * @package  XML_Beautifier
 * @version  0.2
 * @author   Stephan Schmidt <schst@php.net>
 */
class XML_Beautifier extends XML_Parser {

   /**
    * options for the output format
    * @var    array
    * @access private
    */
    var $_options = array(
                         "whitespace"    	 => "trim",
                         "indent"        	 => "    ",
                         "linebreak"     	 => "\n",
                         "caseFolding"   	 => false,
                         "caseFoldingTo"     => "upper",
						 "normalizeComments" => false,
						 "maxCommentLine"	 => -1,
                         "multilineTags"     => false
                        );

   /**
    * current depth
    * @var    integer
    * @access private
    */
    var $_depth = 0;

   /**
    * stack for all found elements
    * @var    array
    * @access private
    */
    var $_struct = array();

   /**
    * Constructor
    *
	* This is only used to specify the options of the
    * beautifying process.
	*
    * @access public
	* @param  array  $options   options that override default options
    */   
    function XML_Beautifier($options = array())
    {
        $this->_options = array_merge($this->_options, $options);
        $this->folding = false;
    }

   /**
    * format a file or URL
    *
    * @access public
    * @param  string    $file       filename
    * @param  mixed     $newFile    filename for beautified XML file (if none is given, the XML string will be returned.)
    *                               if you want overwrite the original file, use XML_BEAUTIFIER_OVERWRITE
    * @return mixed                 XML string of no file should be written, true if file could be written
    * @throws PEAR_Error
    */   
    function formatFile($file, $newFile = null)
    {
        if ($newFile == XML_BEAUTIFIER_OVERWRITE) {
            $newFile = $file;
        }
    
        $this->XML_Parser();
        $this->_resetVars();
        $this->setInputFile( $file );
        $result = $this->parse();
        if ($this->isError($result)) {
            return $result;
        }
        $xml = $this->_format();     

        if ($newFile == null) {
            return $xml;
        }
        
        if (!is_writeable($newFile)) {
            return $this->raiseError("Could not write to output file", XML_BEAUTIFIER_ERROR_NO_OUTPUT_FILE);
        }
        $fp = @fopen($newFile, "w");
        flock($fp, LOCK_EX);
        fwrite($fp, $xml);
        flock($fp, LOCK_UN);
        fclose($fp);
        return true;
    }

   /**
    * format an XML string
    *
    * @access public
    * @param  string    $string     XML
    * @return string    formatted XML string
    * @throws PEAR_Error
    */   
    function formatString($string)
    {
        $this->XML_Parser();
        $this->_resetVars();
        $result = $this->parseString($string);
        if ($this->isError($result)) {
            return $result;
        }
        return $this->_format();
    }

   /**
    * format the XML
    *
    * @access private
    * @return string    formatted XML string
    */
    function _format()
    {
		$xml = "";
        foreach ($this->_struct as $struct) {
            $struct = $this->_normalize($struct);
            $xml   .= $this->_serialize($struct);
        }
        return $xml;    
    }
    
   /**
    * return API version
    *
    * @access   public
    * @static
    * @return   string  $version API version
    */
    function apiVersion()
    {
        return "0.2";
    }

    /**
     * serialize the structure that has been read from the XML file.
     *
     * This method doeas the actual beautifying.
     *
     * @access  private 
     * @param   array   $struct structure that should be serialized
     */
    function _serialize($struct)
    {
        switch ($struct["type"]) {

            /*
			* serialize XML Element
			*/
            case    XML_BEAUTIFIER_ELEMENT:
                $indent = $this->_getIndentString($struct["depth"]);

                // adjust tag case
                if ($this->_options["caseFolding"] == true) {
                    switch ($this->_options["caseFoldingTo"]) {
                        case "uppercase":
                            $struct["tagname"] = strtoupper($struct["tagname"]);
                            $struct["attribs"] = array_change_key_case($struct["attribs"], CASE_UPPER);
                            break;
                        case "lowercase":
                            $struct["tagname"] = strtolower($struct["tagname"]);
                            $struct["attribs"] = array_change_key_case($struct["attribs"], CASE_LOWER);
                            break;
                    }
                }
                
                if ($this->_options["multilineTags"] == true) {
                    $attIndent = $indent . str_repeat(" ", (2+strlen($struct["tagname"])));
                } else {
                    $attIndent = null;
                }
                // check for children
                switch ($struct["contains"]) {
                    
                    // contains only CData or is empty
                    case    XML_BEAUTIFIER_CDATA:
                    case    XML_BEAUTIFIER_EMPTY:
                        $data = $struct["children"][0]["data"];
                        $xml  = $indent . XML_Util::createTag($struct["tagname"], $struct["attribs"], $data, null, $this->_options["multilineTags"], $attIndent)
                              . $this->_options["linebreak"];
                        break;
                    // contains mixed content
                    default:
                        $xml = $indent . XML_Util::createStartElement($struct["tagname"], $struct["attribs"], null, $this->_options["multilineTags"], $attIndent)
                             . $this->_options["linebreak"];
                        
                        $cnt = count($struct["children"]);
                        for ($i = 0; $i < $cnt; $i++) {
                            $xml .= $this->_serialize($struct["children"][$i]);
                        }
                        $xml .= $indent . XML_Util::createEndElement($struct["tagname"])
                             . $this->_options["linebreak"];
                        break;
                    break;
                }
                break;
            
            /*
			* serialize CData
			*/
            case    XML_BEAUTIFIER_CDATA:
                if ($struct["depth"] > 0) {
                    $xml = str_repeat($this->_options["indent"], $struct["depth"]);
                } else {
                    $xml = "";
                }
                $xml .= $struct["data"].$this->_options["linebreak"];
                break;      

            /*
			* serialize entity
			*/
            case    XML_BEAUTIFIER_ENTITY:
                if ($struct["depth"] > 0) {
                    $xml = str_repeat($this->_options["indent"], $struct["depth"]);
                } else {
                    $xml = "";
                }
                $xml .= "&".$struct["name"].";".$this->_options["linebreak"];
                break;      


            /*
			* serialize Processing instruction
			*/
            case    XML_BEAUTIFIER_PI:
                $indent = $this->_getIndentString($struct["depth"]);

                $xml  = $indent."<?".$struct["target"].$this->_options["linebreak"]
                      . $this->_indentTextBlock(rtrim($struct["data"]), $struct["depth"])
                      . $indent."?>".$this->_options["linebreak"];
                break;      

            /*
			* comments
			*/
            case    XML_BEAUTIFIER_COMMENT:
                $indent = $this->_getIndentString($struct["depth"]);

                if ($struct["lines"] > 1) {
                    $xml  = $indent . "<!--" . $this->_options["linebreak"]
                          . $this->_indentTextBlock($struct["data"], $struct["depth"]+1, true)
                          . $indent . "-->" . $this->_options["linebreak"];
                } else {
                    $xml = $indent . sprintf( "<!-- %s -->", trim($struct["data"]) ) . $this->_options["linebreak"];
                }
                break;      

            /*
			* xml declaration
			*/
            case    XML_BEAUTIFIER_XML_DECLARATION:
                $indent = $this->_getIndentString($struct["depth"]);
                $xml    = $indent . XML_Util::getXMLDeclaration($struct["version"], $struct["encoding"], $struct["standalone"]);
                break;      

            /*
			* all other elements
			*/
            case    XML_BEAUTIFIER_DEFAULT:
			default:
                $xml    = $struct["data"];
                break;      
        }
        return $xml;
    }
    
    /**
     * normalize the XML tree
     *
     * When normalizing an XML tree, adjacent data sections
     * are combine to one data section.
     *
     * @access  private
     * @param   array       XML tree
     * @return  array       XML tree
     */
    function _normalize($struct)
    {
        if (!is_array($struct["children"]) || empty($struct["children"])) {
            return $struct;
        }

        $children = $struct["children"];
        $struct["children"] = array();
        $cnt = count($children);
        $inCData = false;
        for ($i = 0; $i < $cnt; $i++ )
        {
            // no data section
            if ($children[$i]["type"] != XML_BEAUTIFIER_CDATA) {
                $children[$i] = $this->_normalize($children[$i]);

                $inCData = false;
                array_push($struct["children"], $children[$i]);
                continue;
            }

            if ($inCData) {
                $tmp = array_pop($struct["children"]);
                $tmp["data"] .= " " . $children[$i]["data"];
                array_push($struct["children"], $tmp);
            } else {
                array_push($struct["children"], $children[$i]);
            }

            $inCData = true;
        }

        return $struct;
    }
    
    /**
     * Start element handler for XML parser
     *
     * @access protected
     * @param  object $parser  XML parser object
     * @param  string $element XML element
     * @param  array  $attribs attributes of XML tag
     * @return void
     */
    function startHandler($parser, $element, $attribs)
    {
		$struct	= array(
                         "type"     => XML_BEAUTIFIER_ELEMENT,
                         "tagname"  => $element,
                         "attribs"  => $attribs,
                         "contains" => XML_BEAUTIFIER_EMPTY,
                         "depth"    => $this->_depth++,
                         "children" => array()
                      );

        array_push($this->_struct,$struct);
    }

    /**
     * End element handler for XML parser
     *
     * @access protected
     * @param  object XML parser object
     * @param  string
     * @return void
     */
    function endHandler($parser, $element)
    {
        $struct = array_pop($this->_struct);
        if ($struct["depth"] > 0) { 
            $parent = array_pop($this->_struct);
            array_push($parent["children"], $struct);
            $parent["contains"] = $parent["contains"] | XML_BEAUTIFIER_ELEMENT;
            array_push($this->_struct, $parent);
        } else {
            array_push($this->_struct, $struct);
        }
        $this->_depth--;
    }

    /**
     * Handler for character data
     *
     * @access protected
     * @param  object XML parser object
     * @param  string CDATA
     * @return void
     */
    function cdataHandler($parser, $cdata)
    {
        $cdata = trim($cdata);
        switch ($this->_options["whitespace"]) {
            case "trim":
                break;
        }

        if ((string)$cdata === '') {
            return true;
        }

		$struct	= array(
                         "type"  => XML_BEAUTIFIER_CDATA,
                         "data"  => $cdata,
                         "depth" => $this->_depth
                       );

        $this->_appendToParent($struct);
    }

    /**
     * Handler for processing instructions
     *
     * @access protected
     * @param  object XML parser object
     * @param  string target
     * @param  string data
     * @return void
     */
    function    piHandler($parser, $target, $data)
    {
		$struct	= array(
                         "type"    => XML_BEAUTIFIER_PI,
                         "target"  => $target,
                         "data"    => $data,
                         "depth"   => $this->_depth
                       );

		$this->_appendToParent($struct);
    }
    
    /**
     * Handler for external entities
     *
     * @access protected
     * @param  object XML parser object
     * @param  string target
     * @param  string data
     * @return void
     */
    function entityrefHandler($parser, $open_entity_names, $base, $system_id, $public_id)
    {
		$struct	= array(
                         "type"    => XML_BEAUTIFIER_ENTITY,
                         "name"    => $open_entity_names,
                         "depth"   => $this->_depth
                       );

        $this->_appendToParent($struct);
        return true;
    }

    /**
     * Handler for all other stuff
     *
     * @access protected
     * @param  object XML parser object
     * @param  string data
     * @return void
     */
    function defaultHandler($parser, $data)
    {
		/*
		* handle comment
		*/
		if (strncmp("<!--", $data, 4) == 0) {
        
            $regs = array();
            eregi("<!--(.+)-->", $data, $regs);
            $comment = trim($regs[1]);
			$lines	 = count(explode("\n",$comment));
			
			/*
			* normalize comment, i.e. combine it to one
			* line and remove whitespace
			*/
			if ($this->_options["normalizeComments"] && $lines > 1){
				$comment = preg_replace("/\s\s+/s", " ", str_replace( "\n" , " ", $comment));
				$lines   = 1;
			}

			/*
			* check for the maximum length of one line
			*/
			if ($this->_options["maxCommentLine"] > 0) {
				if ($lines > 1) {
					$commentLines = explode("\n", $comment);
				} else {
					$commentLines = array($comment);
				}

				$comment = "";
				for ($i = 0; $i < $lines; $i++) {
					if (strlen($commentLines[$i]) <= $this->_options["maxCommentLine"]) {
						$comment .= $commentLines[$i];
						continue;
					}
					$comment .= wordwrap($commentLines[$i], $this->_options["maxCommentLine"] );
					if ($i != ($lines-1)) {
						$comment .= "\n";
					}
				}
				$lines	 = count(explode("\n",$comment));
			}
			
			$struct	= array(
	                         "type"    => XML_BEAUTIFIER_COMMENT,
	                         "data"    => $comment,
                             "lines"   => $lines,
	                         "depth"   => $this->_depth
	                       );
		/*
		* handle XML declaration
		*/
		} elseif (strncmp("<?", $data, 2) == 0) {
    		preg_match_all('/([a-zA-Z_]+)="((?:\\\.|[^"\\\])*)"/', $data, $match);
            $cnt = count($match[1]);
            $attribs = array();
    		for ($i = 0; $i < $cnt; $i++) {
                $attribs[$match[1][$i]] = $match[2][$i];
    		}

            if (!isset($attribs["version"])) {
                $attribs["version"] = "1.0";
            }
            if (!isset($attribs["encoding"])) {
                $attribs["encoding"] = "UTF-8";
            }
            if (!isset($attribs["standalone"])) {
                $attribs["standalone"] = true;
            }
            
			$struct	= array(
	                         "type"       => XML_BEAUTIFIER_XML_DECLARATION,
	                         "version"    => $attribs["version"],
	                         "encoding"   => $attribs["encoding"],
	                         "standalone" => $attribs["standalone"],
	                         "depth"      => $this->_depth
	                       );
		} else {
		/*
		* handle all other data
		*/
			$struct	= array(
	                         "type"    => XML_BEAUTIFIER_DEFAULT,
	                         "data"    => $data,
	                         "depth"   => $this->_depth
	                       );
		}
		
        $this->_appendToParent($struct);
        return true;
    }

   /**
    * indent a text block consisting of several lines
    *
    * @access private
    * @param  string    $text   textblock
    * @param  integer   $depth  depth to indent
    * @param  boolean   $trim   trim the lines
    * @return string            indented text block
    */
    function _indentTextBlock($text, $depth, $trim = false)
    {
        $indent = $this->_getIndentString($depth);
        $tmp = explode("\n", $text);
        $cnt = count($tmp);
        for ($i = 0; $i < $cnt; $i++ ) {
			if ($trim) {
				$tmp[$i] = trim($tmp[$i]);
			}
            $xml .= $indent.$tmp[$i].$this->_options["linebreak"];
        }
        return $xml;
    }
    
   /**
    * get the string that is used for indentation in a specific depth
    *
    * This depends on the option 'indent'.
    *
    * @access private
    * @param  integer   $depth  nesting level
    * @return string            indent string
    */
    function _getIndentString($depth)
    {
        if ($depth > 0) {
            return str_repeat($this->_options["indent"], $depth);
        }
        return "";
    }
    
    /**
     * append a struct to the last struct on the stack
     *
     * @access private
     * @param  array    $struct structure to append
     */
    function _appendToParent($struct)
    {
        if ($this->_depth > 0) {
            $parent = array_pop($this->_struct);
            array_push($parent["children"], $struct);
            $parent["contains"] = $parent["contains"] | $struct["type"];
            array_push($this->_struct, $parent);
            return true;
        }
        array_push($this->_struct, $struct);
    }

   /**
    * reset all used object properties
    *
    * This method is called before parsing a new document
    *
    * @access private
    */
    function _resetVars()
    {
        $this->_depth  = 0;
        $this->_struct = array();
    }
}
?>