<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <gohr@cosmocode.de>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

class syntax_plugin_pagenav extends DokuWiki_Syntax_Plugin {

    /**
     * What kind of syntax are we?
     */
    function getType(){
        return 'substition';
    }

    /**
     * What about paragraphs?
     */
    function getPType(){
        return 'block';
    }

    /**
     * Where to sort in?
     */
    function getSort(){
        return 155;
    }


    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\[<\d*>(?: [^\]]+)?\]',$mode,'plugin_pagenav');
    }


    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler){
        //split the match in it's parts
        $match = substr($match,1,-1);
        list($mode,$glob)    = explode(' ',$match,2);
        $mode = (int) substr($mode,1,-1);
        if(!$mode) $mode = 2+4+8;

        return array(strtolower(trim($glob)),$mode);
    }

    /**
     * Create output
     */
    function render($format, Doku_Renderer $renderer, $data) {
        require_once(DOKU_INC.'inc/search.php');
        global $INFO;
        global $conf;
        if($format != 'xhtml') return false;

        list($glob,$mode) = $data;
        $glob = preg_quote($glob,'/');

        // get all files in current namespace
        static $list = null; // static to reuse the array for multiple calls.
        if(is_null($list)){
            $list = array();
            $ns = str_replace(':','/',getNS($INFO['id']));
            search($list,$conf['datadir'],'search_list',array(),$ns);
        }
        $id = $INFO['id'];

        // find the start page
        $exist = false;
        $start = getNS($INFO['id']).':';
        resolve_pageid('',$start,$exist);

        $cnt = count($list);
        if($cnt < 2) return true; // there are no other doc in this namespace

        $first = '';
        $prev  = '';
        $last  = '';
        $next  = '';
        $self  = false;

        // we go through the list only once, handling all options and globs
        // only for the 'last' command the whole list is iterated
        for($i=0; $i < $cnt; $i++){
            if($list[$i]['id'] == $id){
                $self = true;
            }else{
                if($glob && !preg_match('/'.$glob.'/',noNS($list[$i]['id']))) continue;
                if($list[$i]['id'] == $start) continue;

                if($self){
                    // we're after the current id
                    if(!$next){
                        $next = $list[$i]['id'];
                    }
                    $last = $list[$i]['id'];
                }else{
                    // we're before the current id
                    if(!$first){
                        $first = $list[$i]['id'];
                    }
                    $prev = $list[$i]['id'];
                }
            }
        }

        $renderer->doc .= '<p class="plugin__pagenav">';
        if($mode & 4) $renderer->doc .= $this->_buildImgLink($first,'first');
        if($mode & 2) $renderer->doc .= $this->_buildImgLink($prev,'prev');
        if($mode & 8) $renderer->doc .= $this->_buildImgLink($start,'up');
        if($mode & 2) $renderer->doc .= $this->_buildImgLink($next,'next');
        if($mode & 4) $renderer->doc .= $this->_buildImgLink($last,'last');
        $renderer->doc .= '</p>';

        return true;
    }

    function _buildImgLink($page, $cmd) {
        if (!$page){
            return '<img src="'.DOKU_BASE.'lib/plugins/pagenav/img/'.$cmd.'-off.png" alt="" />';
        }

        $title = p_get_first_heading($page);

        return '<a href="'.wl($page).'" title="'.$this->getLang($cmd).': '.hsc($title).'" class="wikilink1"><img src="'.DOKU_BASE.'lib/plugins/pagenav/img/'.$cmd.'.png" alt="'.$this->getLang($cmd).'" /></a>';
    }

}

//Setup VIM: ex: et ts=4 enc=utf-8 :
