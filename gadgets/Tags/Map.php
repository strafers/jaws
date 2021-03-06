<?php
/**
 * Tags URL maps
 *
 * @category   GadgetMaps
 * @package    Tags
 * @author     Mojtaba Ebrahimi <ebrahimi@zehneziba.ir>
 * @copyright  2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
$maps[] = array('ViewTag', 'tag/{tag}[/user/{user}][/gname/{gname}][/page/{page}]');
$maps[] = array('TagCloud', 'tagcloud/[/gname/{gname}]');
$maps[] = array('ManageTags', 'tags/manage[/page/{page}][/gadgets_filter/{gadgets_filter}][/term/{term}][/pageitem/{page_item}]');
$maps[] = array('EditTagUI', 'tags/edit/tag/{tag}');
