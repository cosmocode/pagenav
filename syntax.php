<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <gohr@cosmocode.de>
 */

class syntax_plugin_pagenav extends DokuWiki_Syntax_Plugin
{

    /** @inheritDoc */
    public function getType()
    {
        return 'substition';
    }

    /** @inheritDoc */
    public function getPType()
    {
        return 'block';
    }

    /** @inheritDoc */
    public function getSort()
    {
        return 155;
    }


    /** @inheritDoc */
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern('\[<\d*>(?: [^\]]+)?\]', $mode, 'plugin_pagenav');
    }


    /** @inheritDoc */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        //split the match in it's parts
        $match = substr($match, 1, -1);
        list($mode, $glob) = explode(' ', $match, 2);
        $mode = (int)substr($mode, 1, -1);
        if (!$mode) $mode = 2 + 4 + 8;

        return array(strtolower(trim($glob)), $mode);
    }

    /** @inheritDoc */
    public function render($format, Doku_Renderer $renderer, $data)
    {
        global $INFO;
        global $conf;
        if ($format != 'xhtml') return false;

        list($glob, $mode) = $data;
        $glob = preg_quote($glob, '/');

        // get all files in current namespace
        static $list = null; // static to reuse the array for multiple calls.
        if (is_null($list)) {
            $list = array();
            $ns = str_replace(':', '/', getNS($INFO['id']));
            search($list, $conf['datadir'], 'search_list', array(), $ns);
        }
        $id = $INFO['id'];

        // find the start page
        $exist = false;
        $start = getNS($INFO['id']) . ':';
        resolve_pageid('', $start, $exist);

        $cnt = count($list);
        if ($cnt < 2) return true; // there are no other doc in this namespace

        $first = '';
        $prev = '';
        $last = '';
        $next = '';
        $self = false;

        // we go through the list only once, handling all options and globs
        // only for the 'last' command the whole list is iterated
        for ($i = 0; $i < $cnt; $i++) {
            if ($list[$i]['id'] == $id) {
                $self = true;
            } else {
                if ($glob && !preg_match('/' . $glob . '/', noNS($list[$i]['id']))) continue;
                if ($list[$i]['id'] == $start) continue;

                if ($self) {
                    // we're after the current id
                    if (!$next) {
                        $next = $list[$i]['id'];
                    }
                    $last = $list[$i]['id'];
                } else {
                    // we're before the current id
                    if (!$first) {
                        $first = $list[$i]['id'];
                    }
                    $prev = $list[$i]['id'];
                }
            }
        }

        $renderer->doc .= '<p class="plugin__pagenav">';
        if ($mode & 4) $renderer->doc .= $this->buildImgLink($first, 'first');
        if ($mode & 2) $renderer->doc .= $this->buildImgLink($prev, 'prev');
        if ($mode & 8) $renderer->doc .= $this->buildImgLink($start, 'up');
        if ($mode & 2) $renderer->doc .= $this->buildImgLink($next, 'next');
        if ($mode & 4) $renderer->doc .= $this->buildImgLink($last, 'last');
        $renderer->doc .= '</p>';

        return true;
    }

    /**
     * @param string $page
     * @param string $cmd
     * @return string
     */
    protected function buildImgLink($page, $cmd)
    {
        if (!$page) {
            return '<img src="' . DOKU_BASE . 'lib/plugins/pagenav/img/' . $cmd . '-off.png" alt="" />';
        }

        $title = p_get_first_heading($page);
        $img = '<img src="' . DOKU_BASE . 'lib/plugins/pagenav/img/' . $cmd . '.png" alt="' . $this->getLang($cmd) . '" />';

        return '<a href="' . wl($page) . '" title="' . $this->getLang($cmd) . ': ' . hsc($title) . '" class="wikilink1">' . $img . '</a>';
    }

}
