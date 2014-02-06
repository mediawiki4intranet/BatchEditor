<?php

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * @author Stas Fomin <stas-fomin@yandex.ru>
 * @author Vitaliy Filippov <vitalif@mail.ru>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if (!defined('MEDIAWIKI'))
    die('Not an entry point');

$wgExtensionMessagesFiles['BatchEditor'] = __DIR__ . '/BatchEditor.i18n.php';
$wgSpecialPageGroups['BatchEditor'] = 'pagetools';
$wgSpecialPages['BatchEditor'] = 'BatchEditorPage';
$wgLogActionsHandlers['batchedit/batchedit'] = 'BatchEditLogFormatter';
$wgAutoloadClasses['BatchEditorPage'] = __DIR__ . '/BatchEditor.class.php';
$wgAutoloadClasses['BatchEditLogFormatter'] = __DIR__ . '/BatchEditor.class.php';

$wgExtensionCredits['other'][] = array(
    'name'           => 'BatchEditor',
    'version'        => '2014-02-06',
    'author'         => 'Stas Fomin, Vitaliy Filippov',
    'url'            => 'http://wiki.4intra.net/BatchEditor',
    'description'    => 'Batch editor for MediaWiki articles',
);
