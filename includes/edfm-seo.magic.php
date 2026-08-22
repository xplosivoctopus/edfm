<?php
/**
 * Magic word registration for {{#seo:}}, used by edfm-seo.php.
 *
 * setFunctionHook() requires the name to already be a registered magic
 * word -- an arbitrary string doesn't work. This is the old-style
 * ExtensionMessagesFiles registration format, still fully supported and
 * the simplest option for a single parser function that isn't part of a
 * full extension.json-based extension.
 */
$magicWords = [];
$magicWords['en'] = [
	'seo' => [ 0, 'seo' ],
];
