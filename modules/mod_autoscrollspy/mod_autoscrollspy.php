<?php
/*
* @copyright   (C) 2023 Kevin Olson (kevinsguides.com)
* @license     GNU General Public License version 2 or later; see LICENSE.txt
* this module displays the article table of contents from the autoscrollspy plugin
*/

defined ( '_JEXEC' ) or die;
use Joomla\CMS\Factory;

$html = Factory::getApplication()->getUserState('autoscrollspy.html', '');
//check if we are on a com_content article view
if (Factory::getApplication()->input->get('option') == 'com_content' &&
    Factory::getApplication()->input->get('view') == 'article') :
?>
<?php echo $html; ?>
<?php endif; ?>