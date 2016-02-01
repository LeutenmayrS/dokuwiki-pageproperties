<?php
/**
 * English language file for pageproperties plugin
 *
 * @author Stefan Leutenmayr <stefan.leutenmayr@freenet.de>
 */

// menu entry for admin plugins
// $lang['menu'] = 'Your menu entry';

// custom language strings for the plugin
// $lang['fixme'] = 'FIXME';

$lang['pagetools_button'] = 'Properties';
$lang['header'] = 'Properties (Metadata)';
$lang['header_undefined'] = 'Undefined properties';
$lang['empty'] = '-';

$lang['last_change:type_o_C'] = 'created';
$lang['last_change:type_o_E'] = 'edit';
$lang['last_change:type_o_e'] = 'minor edit';
$lang['last_change:type_o_D'] = 'deleted';
$lang['last_change:type_o_R'] = 'revert';

$lang['title'] = ' first heading';
$lang['creator'] = ' full name of the user who created the page';
$lang['user'] = ' the login name of the user who created the page';
$lang['description'] = '';
$lang['description:abstract'] = 'raw text abstract (250 to 500 chars) of the page';
$lang['description:tableofcontents'] = ' list of arrays with header id ("hid") title ("title") list item type ("type") and header level ("level")';
$lang['contributor'] = ' list of user ID ⇒ full name of users who have contributed to the page';
$lang['date'] = '';
$lang['date:created'] = ' creation date';
$lang['date:modified'] = ' date of last non minor change';
$lang['date:valid'] = 'period in seconds before the page should be refreshed (used by "rss" syntax only)';
//$lang['date:valid:age'] = ' ';
$lang['last_change'] = ' the last changelog entry';
$lang['last_change:date'] = ' date of the last change';
$lang['last_change:ip'] = 'ip of the user editing';
$lang['last_change:type'] = 'type of the edit';
$lang['last_change:id'] = 'id of the page';
$lang['last_change:user'] = 'username of the user editing';
$lang['last_change:sum'] = 'summary of the editor';
$lang['last_change:extra'] = 'extra data used for storing the revision (timestamp) in the case of a revert';
$lang['relation'] = '';
$lang['relation:isreferencedby'] = ' list of pages that link to this page: ID ⇒ boolean exists this is not used or written by DokuWiki core';
$lang['relation:references'] = ' list of linked pages: page ID ⇒ boolean exists';
$lang['relation:media'] = ' list of linked media files: media ID ⇒ boolean exists';
$lang['relation:firstimage'] = 'id or url of the first image in the page';
$lang['relation:haspart'] = ' list of included rss feeds (and more see below)';
$lang['internal'] = '';
$lang['internal:cache'] = ' if the cache may be used';
$lang['internal:toc'] = ' if the toc shall be displayed';

//Setup VIM: ex: et ts=4 :
