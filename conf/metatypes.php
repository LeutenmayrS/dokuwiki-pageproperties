<?php
/**
 * Metadata type
 *
 * @author Stefan Leutenmayr <stefan.leutenmayr@freenet.de>
 */

// custom type strings for the plugin
// $meta['fixme'] = 'FIXME';

$meta['_global'] = array('fieldset');
$meta['title'] = array('string');
$meta['creator'] = array('string');
$meta['user'] = array('string');

$meta['description'] = array('fieldset');
$meta['description:abstract'] = array('string');
$meta['description:tableofcontents'] = array('array');

$meta['_contributor'] = array('fieldset');
$meta['contributor'] = array('array');

$meta['date'] = array('fieldset');
$meta['date:created'] = array('timestamp');
$meta['date:modified'] = array('timestamp');
$meta['date:valid'] = array('string');
//$meta['date:age'] = array('numeric');

$meta['last_change'] = array('fieldset');
$meta['last_change:date'] = array('timestamp');
$meta['last_change:ip'] = array('string');
$meta['last_change:type'] = array('multichoice','_choices' => array('C','E','e','D','R'));
$meta['last_change:id'] = array('string');
$meta['last_change:user'] = array('string');
$meta['last_change:sum'] = array('string');
$meta['last_change:extra'] = array('timestamp');

$meta['relation'] = array('fieldset');
$meta['relation:isreferencedby'] = array('array');
$meta['relation:references'] = array('array');
$meta['relation:media'] = array('array');
$meta['relation:firstimage'] = array('array');
$meta['relation:haspart'] = array('array');

$meta['internal'] = array('fieldset');
$meta['internal:cache'] = array('onoff');
$meta['internal:toc'] = array('onoff');


//Setup VIM: ex: et ts=4 :