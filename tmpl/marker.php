<?php
/* @package Joomla
 * @copyright Copyright (C) Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @extension Phoca Extension
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

use Joomla\CMS\Language\Text;

defined( '_JEXEC' ) or die( 'Restricted access' );

?>
<div class="pmMarkerTitle"><?= addslashes($data->title);?></div>
<div><?php echo PhocaMapsHelper::strTrimAll(addslashes($data->description)); ?></div>
<?php
if ($data->displaygps == 1) :
?>
<div class="pmgps">
	<table style="border:0">
		<tr>
			<td><strong><?php echo  Text::_('PLG_CONTENT_PHOCAMAPS_GPS');?>: </strong></td>
			<td><?php echo PhocaMapsHelper::strTrimAll(addslashes($data->gpslatitude));?></td>
		</tr>
		<tr>
			<td></td>
			<td><?php echo PhocaMapsHelper::strTrimAll(addslashes($data->gpslongitude));?></td>
		</tr>
	</table>
</div>
<?php
endif;
