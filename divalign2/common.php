<?php
/**
 * @file       divalign2/common.php
 * @brief      Common functions for the divalign2 plugin.
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @version    3.0b
 * @date       2013-08-05
 * @author     Luis Machuca Bezzaza <luis [dot] machuca [at] gulix [dot] cl>
 * 
 * This file and the files in syntax/ provide the syntax mode for the 
 * divalign2 plugin.
 * 
 * This work is a form from previous plugin (plugin:divalign)
 * by Jason Byrne. Check the wikipage for details.
 */ 

// must be run within DokuWiki
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) die();
if(!defined('DW_LF')) define('DW_LF',"\n");
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_divalign2_common extends DokuWiki_Syntax_Plugin {

    function getSort() { 
        return 180; 
    }

    function getType() { 
        return 'formatting'; 
    }

    function getAllowedTypes() { 
        return array( 
         'container', 'substition', 'protected', 
         'disabled', 'formatting', 'paragraphs'
         );
    }

    function getPType() {
        //return 'stack'; // for DokuWiki <= Rincewind
        return 'block';
    }
 
    function connectTo($mode) {
    }

    function postConnect() {
    }

    function handle($match, $state, $pos, Doku_Handler $handler){
        // unpack and process
        $content= $match['content'];
        $align= $match['align'];
        switch ( $state ) {
          case DOKU_LEXER_ENTER: {
            break;
            }
          case DOKU_LEXER_UNMATCHED: {
            $handler->_addCall('cdata', array($content), $pos);
            break;          
            }
        }
        return array($align,$state,$pos);
    }

    function render($mode, Doku_Renderer $renderer, $data) {
        list ($align, $state, $pos) = $data;
        if (false) {

        } else if ($mode=='xhtml') {
            
            switch ($state) {
            case DOKU_LEXER_ENTER: {                
                if ($align) { 
                    $renderer->doc .= '<p class="divalign-'.$align.'">'; 
                    }
                break;
            }
            case DOKU_LEXER_EXIT : {
                $renderer->doc .= '</p><!--divalign-->';
                break;
            }
            } // end switch
            return true;

        } else if ($mode=='odt') {
            if (!method_exists ($renderer, 'getODTPropertiesFromElement')) {
                $this->render_odt_v1 ($renderer, $state, $align);
            } else {
                $this->render_odt_v2 ($renderer, $state, $align);
            }
            return true;
        }
        return false;
    }

    function render_odt_v1 (Doku_Renderer $renderer, $state, $align) {
        $Align = ucfirst ($align);
        //static $center_defined= false;
        $st = <<<EOF
<style:style style:name="Text.Divalign.$Align" style:display-name="Text.Divalign.$Align" style:family="paragraph" style:parent-style-name="Text_20_body">
<style:paragraph-properties fo:text-align="$align" style:justify-single-word="false" />
</style:style>

EOF;

        $renderer->autostyles["Text.Divalign.$Align"]= $st;
        $center_defined= true;
        switch ($state) {
        case DOKU_LEXER_ENTER: {
            $renderer->doc.= "<text:p text:style-name=\"Text.Divalign.$Align\">";
            break;
        }
        case DOKU_LEXER_EXIT: {
            $renderer->doc.= '</text:p>';
            //reduce_odt();
            break;
        }
        } // end switch
    }

    function render_odt_v2 (Doku_Renderer $renderer, $state, $align) {
        static $first = true;
        $alignments = array ('left', 'right', 'center', 'justify');
        
        if ($first) {
            // First entrance of the function. Create our ODT styles.
            // Group them under a parent called "Plugin DivAlign2"
            $first = false;

            // Create parent style to group the others beneath it
            if (!$renderer->styleExists('Plugin_DivAlign2')) {
                $parent_properties = array();
                $parent_properties ['style-parent'] = 'Text_20_body';
                $parent_properties ['style-class'] = 'Plugin_DivAlign2';
                $parent_properties ['style-name'] = 'Plugin_DivAlign2';
                $parent_properties ['style-display-name'] = 'Plugin DivAlign2';
                $renderer->createParagraphStyle($parent_properties);
            }

            $properties = array ();
            $properties ['justify-single-word'] = 'false';
            $properties ['style-class'] = NULL;
            $properties ['style-parent'] = 'Plugin_DivAlign2';
            foreach ($alignments as $alignment) {
                $Align = ucfirst ($alignment);
                $name = 'Plugin DivAlign2 '.$Align;
                $style_name = 'Plugin_DivAlign2_'.$Align;
                if (!$renderer->styleExists($style_name)) {
                    $properties ['style-name'] = $style_name;
                    $properties ['style-display-name'] = $name;
                    $properties ['text-align'] = $alignment;
                    $renderer->createParagraphStyle($properties);
                }
            }
        }

        $Align = ucfirst ($align);
        switch ($state) {
            case DOKU_LEXER_ENTER:
                $renderer->p_close();
                $renderer->p_open('Plugin_DivAlign2_'.$Align);
                break;
            case DOKU_LEXER_EXIT:
                $renderer->p_close();
                break;
        }
    }

} // end class


/**
In DokuWiki versions previous to 2010-11-17 "Anteater", 'stack' mode is 
broken, leading to the generation of invalid XHTML code as well as breaking 
of consecutive related syntax modes. This version of the plugin is intended 
to be run with the old versions affected by this bug.

As of this version (2.2B), the only fix with regards to the code seems to be 
explicitly tag and fix the affected areas of code in the renderer output 
between each invocation of the syntax modes.

 @see http://github.com/splitbrain/dokuwiki/commit/f4daa9a18d9c09a1bac0696d92e2bceef8a6800f
 @see http://www.dokuwiki.org/devel:syntax_plugins#PType

**/
/*
static public function getPType () {
    return 'stack';
    }

static public function render ($mode, Doku_Renderer $renderer, $data) {
    list($align,$state,$pos) = $data;
    if ($mode == 'xhtml') {
        $epos= strlen($renderer->doc)-10;
        switch ($state) {
        case DOKU_LEXER_ENTER: {
            
            if ($align) { 
                $renderer->doc .= '<div class="divalign-'.$align.'">'; 
                }
            break;
        }
        case DOKU_LEXER_EXIT : {
            $renderer->doc .= '</div><!--divalign-->';
            //DW_common_divalign2::iterFixRenderStack($renderer->doc, $epos);
            break;
        }
        } // end switch
        return true;
    } // end if ($mode == 'xhtml')

    else if ($mode == 'odt' ) {
        $Align = ucfirst ($align);
        static $center_defined= false;
        $st = <<<EOF
<style:style style:name="Text.Divalign.$Align" style:display-name="Text.Divalign.$Align" style:family="paragraph" style:parent-style-name="Text_20_body">
    <style:paragraph-properties fo:text-align="$align" style:justify-single-word="false" />
    </style:style>

EOF;

        $renderer->autostyles["Text.Divalign.$Align"]= $st;
        $center_defined= true;
        switch ($state) {
        case DOKU_LEXER_ENTER: {
            $renderer->doc.= "<text:p text:style-name=\"Text.Divalign.$Align\">";
            break;
        }
        case DOKU_LEXER_EXIT: {
            $renderer->doc.= '</text:p>';
            break;
        }
        } // end switch
    return true;
    }

    else if ($mode == 'text' ) {
        switch ($state) {
        case DOKU_LEXER_ENTER: {
            $renderer->doc .= DW_LF;
            break;
        }
        case DOKU_LEXER_EXIT : {
            $renderer->doc .= DW_LF;
            break;
        }
        } // end switch
        return true;
    } // end if ($mode == 'text')

    return false;
    } // end function

static public function FixRenderStack (&$doc, $pos) {
    $done= false;
    $times= preg_match_all('#<p>\s+?<div class="divalign-.+?">#ms', $doc, $matches);
    $doc= preg_replace('#<p>\s+?<div class="divalign-(.+?)">#ms', '<div class="divalign-$1">', $doc, $times);
    $doc= preg_replace('#</div><!--divalign-->\s+?</p>#ms', '</div>', $doc, $times);
    return $times;
    }
*/

