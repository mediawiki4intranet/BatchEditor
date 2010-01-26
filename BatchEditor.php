<?php

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * @author Stas Fomin <stas-fomin@yandex.ru>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if (!defined('MEDIAWIKI')) die();

require_once('LinksUpdate.php');

$wgExtensionFunctions[] = "wfInitBatchEditor";
$wgExtensionMessagesFiles['BatchEditor'] = dirname(__FILE__) . '/BatchEditor.i18n.php';

function wfInitBatchEditor()
{
    global $IP;
    require_once("$IP/includes/SpecialPage.php");
    SpecialPage::addPage(new BatchEditorPage);
}

function wfSpecialBatchEditor($par = null)
{
    global $wgOut, $wgRequest, $wgTitle, $wgUser, $wgLang, $IP, $wgScriptPath;

    $wgOut->setPageTitle(wfMsg('batcheditor-title'));

    extract($wgRequest->getValues('a_titles'));
    extract($wgRequest->getValues('a_comment'));
    extract($wgRequest->getValues('a_find'));
    extract($wgRequest->getValues('a_replace'));
    extract($wgRequest->getValues('a_add'));
    extract($wgRequest->getValues('a_delete'));
    extract($wgRequest->getValues('a_run'));
    extract($wgRequest->getValues('a_preview'));

    $a_minor  = $wgRequest->getCheck('a_minor') ? ' checked="checked" ' : false;
    $a_regexp = $wgRequest->getCheck('a_regexp') ? ' checked="checked" ' : false;
    $a_one    = $wgRequest->getCheck('a_one') ? ' checked="checked" ' : false;

    /* If CustIS-modified Import/Export engine is available */
    require_once("$IP/includes/specials/SpecialExport.php");
    if (function_exists('wfExportAddPagesExec'))
    {
        $state = $_POST;
        $state['pages'] = $a_titles;
        if ($wgRequest->getCheck('addcat'))
        {
            wfExportAddPagesExec($state);
            $a_titles = $state['pages'];
        }
    }

    $parserOptions = ParserOptions::newFromUser( $wgUser );
    $numSessionID = preg_replace( "[\D]", "", session_id() );
    $action = $wgTitle->escapeLocalUrl("");

    ob_start();
?>
<form action='<?=$action?>' method='POST'>
<table>
<tr valign="top">
    <td style="vertical-align: top; padding-right: 20px"><?=wfMsgExt('batcheditor-list-title', array('parseinline'))?></td>
    <td><textarea name="a_titles" rows="8" cols="60"><?=htmlspecialchars($a_titles)?></textarea></td>
    <td style="padding-left: 16px"><?= function_exists('wfExportAddPagesExec') ? wfExportAddPagesForm($state) : '' ?></td>
</tr>
<tr valign="top">
    <td><?=wfMsgExt('batcheditor-comment-title', array('parseinline'))?></td>
    <td>
        <input name="a_comment" size="80" value="<?=htmlspecialchars($a_comment)?>" />
        <input type="checkbox" name="a_minor" <?=$a_minor?> value="1"/><label for='a_minor'><?=wfMsg('minoredit')?></label>
    </td>
</tr>
</table>
<table cellspacing="8">
<tr>
    <th><?=wfMsgExt('batcheditor-find', array('parseinline'))?></th>
    <th><?=wfMsgExt('batcheditor-replace', array('parseinline'))?></th>
</tr>
<tr>
    <td><textarea name="a_find"    rows="5" cols="45"><?=htmlspecialchars($a_find)?></textarea></td>
    <td><textarea name="a_replace" rows="5" cols="45"><?=htmlspecialchars($a_replace)?></textarea></td>
</tr>
<tr>
    <td colspan="2" align="right">
        <input type="checkbox" name="a_regexp" <?=$a_regexp?> value="1" />&nbsp;<label for='a_regexp'><?=wfMsgExt('batcheditor-pcre', array('parseinline'))?></label>
        &nbsp; &nbsp;
        <input type="checkbox" name="a_one" <?=$a_one?> value="1" />&nbsp;<label for='a_one'><?=wfMsgExt('batcheditor-oneline', array('parseinline'))?></label>
    </td>
</tr>
<tr>
    <th><?=wfMsgExt('batcheditor-addlines', array('parseinline'))?></th>
    <th><?=wfMsgExt('batcheditor-deletelines', array('parseinline'))?></th>
</tr>
<tr>
    <td><textarea name="a_add"    rows="5" cols="45"><?=htmlspecialchars($a_add)?></textarea></td>
    <td><textarea name="a_delete" rows="5" cols="45"><?=htmlspecialchars($a_delete)?></textarea></td>
</tr>
<tr><td align="center" colspan="2">
    <input name='a_preview' value='<?=wfMsgExt('batcheditor-preview', array('parseinline'))?>' type='submit' /> &nbsp; &nbsp;
    <input name='a_run' value='<?=wfMsgExt('batcheditor-run', array('parseinline'))?>' type='submit' /> &nbsp; &nbsp;
</td></tr>
</table>
</form>
<?
    $interface_form = ob_get_contents();
    ob_end_clean();
    $wgOut->addExtensionStyle($wgScriptPath . '/extensions/BatchEditor/BatchEditor.css');
    $wgOut->addHTML($interface_form);
    # Remove CRLF
    $a_find    = str_replace("\r", "", $a_find);
    $a_replace = str_replace("\r", "", $a_replace);
    $a_add     = str_replace("\r", "", $a_add);
    $a_delete  = str_replace("\r", "", $a_delete);
    # One replacement or multiple?
    if ($a_one)
    {
        $af = array($a_find);
        $ar = array($a_replace);
    }
    else
    {
        $af = explode("\n", $a_find);
        $ar = explode("\n", $a_replace);
    }
    # Replacements
    $a_find = array();
    foreach ($af as $a => $f)
    {
        $f = trim($f);
        if ($f)
        {
            if ($a_regexp)
                $f = '#'.preg_replace('/([#\\\\])/', '\\\\\1', $f).'#';
            $a_find[] = array($f, trim($ar[$a]));
        }
    }
    # Regexps for deleting whole lines
    $af = explode("\n", $a_delete);
    $a_delete = array();
    foreach ($af as $a => $f)
    {
        $f = trim($f);
        if ($f)
        {
            $f = '#^[ \t\r]*'.preg_replace('/([#\\\\])/', '\\\\\1', preg_quote($f)).'[ \t\r]*$#m';
            $a_delete[] = $f;
        }
    }
    if (trim($a_titles) != '' && ($a_run || $a_preview))
    {
        $wgOut->addWikiText(wfMsg('batcheditor-'.($a_run?'results':'preview').'-page'));
        $arr_titles = split("\n", $a_titles);
        foreach($arr_titles as $s_title)
        {
            $s_title = trim($s_title);
            $title = Title::newFromText($s_title);
            if (!$title)
                continue;
            $article = new Article($title);
            if ($article->mTitle->getArticleID() == 0)
                $wgOut->addWikiText("== [[$s_title]] ==\n".wfMsg('batcheditor-not-found', $s_title));
            else
            {
                $article->loadContent(false);
                $oldtext = $article->mContent;
                $newtext = $oldtext;
                if (count($a_find))
                {
                    $cb = $a_regexp ? 'preg_replace' : 'str_replace';
                    foreach ($a_find as $f)
                        $newtext = call_user_func($cb, $f[0], $f[1], $newtext);
                }
                if (count($a_delete))
                    foreach ($a_delete as $s)
                        $newtext = preg_replace($s, "", $newtext);
                if (@trim($a_add))
                    $newtext .= "\n" . $a_add;
                # Preview or run only if new text differs
                if ($newtext != $oldtext)
                {
                    $oldrev = wfMsgHTML('revisionasof', $wgLang->timeanddate($article->getTimestamp(), true));
                    $newrev = wfMsg('yourtext');
                    $wgOut->addStyle('common/diff.css');
                    $wgOut->addWikiText("== [[$s_title]] ==");
                    if (!($canedit = $article->getTitle()->userCan('edit')))
                        $wgOut->addWikiText('<div style="color:red">\'\'\''.wfMsg('batcheditor-edit-denied').'\'\'\'</div>');
                    else if ($a_run)
                    {
                        $flags = EDIT_UPDATE | EDIT_DEFER_UPDATES | EDIT_AUTOSUMMARY;
                        if ($a_minor)
                            $flags |= EDIT_MINOR;
                        $st = false;
                        if (($st = $article->doEdit($newtext, $a_comment, $flags)) && $st->isGood())
                            $newrev = wfMsgHTML('currentrev-asof', $wgLang->timeanddate($article->getTimestamp(), true));
                        else
                        {
                            $msg = wfMsg('batcheditor-edit-error');
                            if ($st)
                                $msg .= ': ' . $st->getWikiText();
                            else
                                $msg .= '.';
                            $wgOut->addWikiText('<div style="color:red">\'\'\'' . $msg . '\'\'\'</div>');
                        }
                    }
                    if (!$a_run || $canedit)
                    {
                        $de = new DifferenceEngine();
                        $de->setText($oldtext, $newtext);
                        $res = $de->getDiffBody();
                        $res = "
<table class='diff'>
    <col class='diff-marker' />
    <col class='diff-content' />
    <col class='diff-marker' />
    <col class='diff-content' />
    <tr>
        <td colspan='2' width='50%' align='center' class='diff-otitle'>$oldrev</td>
        <td colspan='2' width='50%' align='center' class='diff-ntitle'>$newrev</td>
    </tr>
" . $res . "</table>";
                        $wgOut->addHTML($res);
                    }
                }
            }
        }
    }
}

class BatchEditorPage extends SpecialPage
{
    function BatchEditorPage()
    {
        SpecialPage::SpecialPage('BatchEditor', 'edit');
        wfLoadExtensionMessages('BatchEditor');
    }
}

?>
