<?php
/*
 * @package Joomla 1.5
 * @copyright Copyright (C) 2005 Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 *
 * @plugin Phoca Plugin
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */
defined( '_JEXEC' ) or die( 'Restricted access' );
if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
jimport( 'joomla.plugin.plugin' );
jimport( 'joomla.application.component.helper' );

class plgContentPhocaMaps extends JPlugin
{	
	protected $_plgPhocaMapsNr	= 0;
	

	public function __construct(& $subject, $config) {
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}
	
	public function _setPhocaMapsPluginNumber() {
		$this->_plgPhocaMapsNr = (int)$this->_plgPhocaMapsNr + 1;
	}

	public function onContentPrepare($context, &$article, &$params, $page = 0) {

		
		// Start Plugin
		$regex_one		= '/({phocamaps\s*)(.*?)(})/si';
		$regex_all		= '/{phocamaps\s*.*?}/si';
		$matches 		= array();
		$count_matches	= preg_match_all($regex_all,$article->text,$matches,PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER);

		$lang = JFactory::getLanguage();
		$lang->load('com_phocamaps.sys');
		$lang->load('com_phocamaps');
		
		// Start if count_matches
		
		if ($count_matches != 0) {
		
			if (!JComponentHelper::isEnabled('com_phocamaps', true)) {
				JText::_('PLG_CONTENT_PHOCAMAPS_PLUGIN_REQUIRE_COMPONENT');
				return true;
			}
		
			$document		= &JFactory::getDocument();
			$db 			= &JFactory::getDBO();
			//$menu 			= &JSite::getMenu();			
			//$plugin 		= &JPluginHelper::getPlugin('content', 'phocamaps');
			//$paramsP 		= new JParameter( $plugin->params );

			$paramsP		= $this->params;
			require_once( JPATH_ROOT.DS.'components'.DS.'com_phocamaps'.DS.'helpers'.DS.'route.php' );
			require_once( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_phocamaps'.DS.'helpers'.DS.'phocamapspath.php' );
			require_once( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_phocamaps'.DS.'helpers'.DS.'phocamaps.php' );
			require_once( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_phocamaps'.DS.'helpers'.DS.'phocamapsmap.php' );
			//$component 		= 'com_phocamaps';
			//$table 			=& JTable::getInstance('component');
			//$table->loadByOption( $component );
			//$paramsC	 	= new JParameter( $table->params );
			
			$component			=	'com_phocamaps';
			$paramsC			= JComponentHelper::getParams($component) ;
			
			$tmpl			= array();
			
			$tmpl['enable_kml']				= $paramsC->get( 'enable_kml', 0 );
			$tmpl['display_print_route']	= $paramsC->get( 'display_print_route', 1 );
			
			$document->addStyleSheet(JURI::base(true).'/media/com_phocamaps/css/phocamaps.css');
			$document->addStyleSheet(JURI::base(true).'/media/plg_content_phocamaps/css/default.css');
			
			for($i = 0; $i < $count_matches; $i++) {
				
				$this->_setPhocaMapsPluginNumber();
				$id	= 'PlgPM'.(int)$this->_plgPhocaMapsNr;
				
				$view	= '';
				$idMap	= '';
				$text	= '';
				//$lang   = '';
				
				// Get plugin parameters
				$phocaMaps	= $matches[0][$i][0];
				preg_match($regex_one,$phocaMaps,$phocaMaps_parts);
				$parts			= explode("|", $phocaMaps_parts[2]);
				$values_replace = array ("/^'/", "/'$/", "/^&#39;/", "/&#39;$/", "/<br \/>/");

				
				foreach($parts as $key => $value) {
					$values = explode("=", $value, 2);
					foreach ($values_replace as $key2 => $values2) {
						$values = preg_replace($values2, '', $values);
					}
					
					// Get plugin parameters from article
						 if($values[0]=='view')				{$view							= $values[1];}
					else if($values[0]=='id')				{$idMap							= $values[1];}
					else if($values[0]=='text')				{$text							= $values[1];}
					//else if($values[0]=='lang')				{$lang							= $values[1];}
					else if($values[0]=='kmlfile')			{$tmpl['enable_kml']			= $values[1];}
					else if($values[0]=='printroute')		{$tmpl['display_print_route']	= $values[1];}
					
				}
				
				$output = '';
				switch($view) {
					
					// - - - - - - - - - - - - - - - -
					// Map
					// - - - - - - - - - - - - - - - -
					case 'map':	

						$query = 'SELECT a.*'
							.' FROM #__phocamaps_map AS a'
							.' WHERE a.id = '.(int) $idMap;
						$db->setQuery($query);
						$mapp = $db->loadObject();
						
						if (empty($mapp)) {
							JError::raiseError(JText::_('PLG_CONTENT_PHOCAMAPS_PLUGIN_ERROR'), JText::_('PLG_CONTENT_PHOCAMAPS_MAP_NOT_EXISTS') . ' (ID = '.$idMap.')');
						}
						
						
						$query = 'SELECT a.*, i.id as iconid, i.url as iurl, i.urls as iurls, i.object as iobject, i.objects as iobjects, i.objectshape as iobjectshape'
								.' FROM #__phocamaps_marker AS a'
								.' LEFT JOIN #__phocamaps_map AS c ON c.id = a.catid '
								.' LEFT JOIN #__phocamaps_icon AS i ON i.id = a.iconext '
								.' WHERE c.id = '.(int) $idMap
								.' AND a.published = 1'
								.' ORDER BY a.ordering ASC';
						$db->setQuery($query);
						$markerp = $db->loadObjectList();
						
						// Parameters
						$tmpl['apikey']					= $paramsC->get( 'google_maps_api_key', '' );
						$tmpl['displayphocainfo']		= $paramsC->get( 'display_phoca_info', 1 );
						$tmpl['displaymapdescription']	= $paramsP->get( 'display_map_description', 0 );
// - - - - - - - - - - - - - - - 						
// RENDER
// - - - - - - - - - - - - - - - 
// Display Description
$tmpl['description'] = '';
if (isset($mapp->description) && $mapp->description != '') {
	$tmpl['description'] = '<div class="pm-desc">'.$mapp->description.'</div>';
}

// Check Width and Height
$tmpl['fullwidth'] = 0;
if (!isset($mapp->width)) {
	$mapp->width = 400;
}
if (isset($mapp->width) && (int)$mapp->width < 1) {
	$tmpl['fullwidth'] = 1;
}
if (!isset($mapp->height) || (isset($mapp->height) && (int)$mapp->height < 1)) {
	$mapp->height = 400;
}
if (!isset($mapp->zoom) || (isset($mapp->zoom) && (int)$mapp->zoom < 1)) {
	$mapp->zoom = 2;
}

// Map Langugage
$tmpl['params'] = '';
if (!isset($mapp->lang) || (isset($mapp->lang) && $mapp->lang == '')) {
	$tmpl['params'] 		= '{other_params:"sensor=false"}';
	$tmpl['paramssearch'] 	= '';
	$tmpl['lang']			= '';
} else {
	$tmpl['params'] 		= '{other_params:"sensor=false&language='.$mapp->lang.'"}';
	$tmpl['paramssearch'] 	= '{"language":"'.$mapp->lang.'"}';
	$tmpl['lang']			= $mapp->lang;
}


// Design
$tmpl['border'] = '';
if (isset($mapp->border)) {
	switch ($mapp->border) {
		case 1:
			$tmpl['border'] = '-grey';
		break;
		case 2:
			$tmpl['border'] = '-greywb';
		break;
		case 3:
			$tmpl['border'] = '-greyrc';
		break;
		case 4:
			$tmpl['border'] = '-black';
		break;
	}
}

// Plugin - no border
$tmpl['stylesite'] 	= 'margin:0;padding:0;margin-top:10px;';

$tmpl['stylesitewidth']	= '';
if ($tmpl['fullwidth'] == 1) {
	$tmpl['stylesitewidth'] = 'style="width:100%"';
}

// Parameters
if (isset($mapp->continuouszoom) && (int)$mapp->continuouszoom == 1) {
	$mapp->continuouszoom = 1;
} else {
	$mapp->continuouszoom = 0;
}

if (isset($mapp->doubleclickzoom) && (int)$mapp->doubleclickzoom == 1) {
	$mapp->disabledoubleclickzoom = 0;
} else {
	$mapp->disabledoubleclickzoom = 1;
}

if (isset($mapp->scrollwheelzoom) && (int)$mapp->scrollwheelzoom == 1) {
	$mapp->scrollwheelzoom = 1;
} else {
	$mapp->scrollwheelzoom = 0;
}

// Since 1.1.0 zoomcontrol is alias for navigationcontrol
if (empty($mapp->zoomcontrol)) {
	$mapp->zoomcontrol = 0;
}

if (empty($mapp->scalecontrol)) {
	$mapp->scalecontrol = 0;
}

if (empty($mapp->typecontrol)) {
	$mapp->typecontrol = 0;
}
if (empty($mapp->typecontrolposition)) {
	$mapp->typecontrolposition = 0;
}


if (empty($mapp->typeid)) {
	$mapp->typeid = 0;
}


// Display Direction
$tmpl['displaydir'] = 0;
if (isset($mapp->displayroute) && $mapp->displayroute == 1) {
	if (isset($markerp) && !empty($markerp)) {
		$tmpl['displaydir'] = 1;
	}
}

// KML Support
$tmpl['load_kml'] = FALSE;
if($tmpl['enable_kml'] == 1) {
	jimport( 'joomla.filesystem.folder' ); 
	jimport( 'joomla.filesystem.file' );
	$path = PhocaMapsPath::getPath();
	if (isset($mapp->kmlfile) && JFile::exists($path->kml_abs . $mapp->kmlfile)) {
		$tmpl['load_kml'] = $path->kml_rel_full . $mapp->kmlfile;
	}
}

$output .= '<div class="phocamaps">';

if ((!isset($mapp->longitude))
		|| (!isset($mapp->latitude))
		|| (isset($mapp->longitude) && $mapp->longitude == '')
		|| (isset($mapp->latitude) && $mapp->latitude == '')) {
	$output .= '<p>' . JText::_('COM_PHOCAMAPS_GOOGLE_MAPS_ERROR_FRONT') . '</p>';
} else {
	$output .= $tmpl['description'];
	
	//$id		= '';
	$map	= new PhocaMapsMap($id);
	//$map->loadAPI();
	$map->loadAPI('jsapi',$paramsC->get( 'load_api_ssl',0));
	$map->loadGeoXMLJS();
	$map->loadBase64JS();
	
	// Map Box
	if ($tmpl['border'] == '') {
		$output .= '<div class="phocamaps-box" align="center" style="'.$tmpl['stylesite'].'">';
		if ($tmpl['fullwidth'] == 1) {
			$output .= '<div id="phocaMap'.$id.'" style="margin:0;padding:0;width:100%;height:'.$mapp->height.'px"></div>';
		} else {
			$output .= '<div id="phocaMap'.$id.'" style="margin:0;padding:0;width:'.$mapp->width.'px;height:'.$mapp->height.'px"></div>';
		}
		$output .= '</div>';
	} else {
		$output .= '<div id="phocamaps-box"><div class="pmbox'.$tmpl['border'].'" '. $tmpl['stylesitewidth'].'><div><div><div>';
		if ($tmpl['fullwidth'] == 1) {
			$output .= '<div id="phocaMap'.$id.'" style="width:100%;height:'.$mapp->height.'px"></div>';
		} else {
			$output .= '<div id="phocaMap'.$id.'" style="width:'.$mapp->width.'px;height:'.$mapp->height.'px"></div>';
		}
		$output .= '</div></div></div></div></div>';
	}

	// Direction
	if ($tmpl['displaydir']) {
			
		$countMarker 	= count($markerp);
		$form 			= '';
		if ((int)$countMarker > 1) {
		
			$form .= ' ' . JText::_('PLG_CONTENT_PHOCAMAPS_TO').': <select name="pmto'.$id.'" id="toPMAddress'.$id.'">';
			foreach ($markerp as $key => $markerV) {
				if ((isset($markerV->longitude) && $markerV->longitude != '')
				&& (isset($markerV->latitude) && $markerV->latitude != '')) {
					$form .= '<option value="'.$markerV->latitude.','.$markerV->longitude.'">'.$markerV->title.'</option>';
				}
			}
			$form .= '</select>';
		} else if ((int)$countMarker == 1) {
		
			foreach ($markerp as $key => $markerV) {
				if ((isset($markerV->longitude) && $markerV->longitude != '')
				&& (isset($markerV->latitude) && $markerV->latitude != '')) {
					$form .= '<input name="pmto'.$id.'" id="toPMAddress'.$id.'" type="hidden" value="'.$markerV->latitude.','.$markerV->longitude.'" />';
				}
			}
		
		}
		
		if ($form != '') {
			/*$output .= '<div class="pmroute"><form action="#" onsubmit="setPhocaDir'.$id.'(this.pmfrom'.$id.'.value, this.pmto'.$id.'.value); return false;">';
			$output .= JText::_('PLG_CONTENT_PHOCAMAPS_FROM_ADDRESS').': <input type="text" size="30" id="fromPMAddress'.$id.'" name="pmfrom'.$id.'" value=""/>';
			$output .= $form;
			$output .= ' <input name="pmsubmit'.$id.'" type="submit" value="'.JText::_('PLG_CONTENT_PHOCAMAPS_GET_ROUTE').'" /></form></div>';
			$output .= '<div id="phocaDir'.$id.'">';
			if ($tmpl['display_print_route'] == 1) {
				$output .= '<div id="phocaMapsPrintIcon'.$id.'" style="display:none"></div>';
			}
			$output .= '</div>';*/
			
			$output .= '<div class="pmroute">';
			$output .= '<form class="form-inline" action="#" onsubmit="setPhocaDir'.$id.'(this.pmfrom'.$id.'.value, this.pmto'.$id.'.value); return false;">';
			$output .= JText::_('PLG_CONTENT_PHOCAMAPS_FROM_ADDRESS').': <input class="pm-input-route input" type="text" size="30" id="fromPMAddress'.$id.'" name="pmfrom'.$id.'" value=""/>';
			$output .= $form;
			$output .= ' <input name="pmsubmit'.$id.'" type="submit" class="pm-input-route-btn btn" value="'.JText::_('PLG_CONTENT_PHOCAMAPS_GET_ROUTE').'" />';
			$output .= '</form></div>';
			$output .= '<div id="phocaDir'.$id.'">';
			if ($tmpl['display_print_route'] == 1) {
				$output .= '<div id="phocaMapsPrintIcon'.$id.'" style="display:none"></div>';
			}
			$output .= '</div>';
		}
	}	

	// $id is not used anymore as this is added in methods of Phoca Maps Class
	// e.g. 'phocaMap' will be not 'phocaMap'.$id as the id will be set in methods
	
	$output .= $map->startJScData();
	$output .= $map->addAjaxAPI('maps', '3', $tmpl['params']);
	$output .= $map->addAjaxAPI('search', '1', $tmpl['paramssearch']);

	$output .= $map->createMap('phocaMap', 'mapPhocaMap', 'phocaLatLng', 'phocaOptions','tstPhocaMap', 'tstIntPhocaMap', FALSE, FALSE, $tmpl['displaydir']);
	$output .= $map->cancelEventFunction();
	$output .= $map->checkMapFunction();
	$output .= $map->startMapFunction();

		$output .= $map->setLatLng( $mapp->latitude, $mapp->longitude );

		$output .= $map->startMapOptions();
		$output .= $map->setMapOption('zoom', $mapp->zoom).','."\n";
		$output .= $map->setCenterOpt().','."\n";
		$output .= $map->setTypeControlOpt($mapp->typecontrol, $mapp->typecontrolposition).','."\n";
		$output .= $map->setNavigationControlOpt($mapp->zoomcontrol).','."\n";
		$output .= $map->setMapOption('scaleControl', $mapp->scalecontrol, TRUE ).','."\n";
		$output .= $map->setMapOption('scrollwheel', $mapp->scrollwheelzoom).','."\n";
		$output .= $map->setMapOption('disableDoubleClickZoom', $mapp->disabledoubleclickzoom).','."\n";
	//	$output .= $map->setMapOption('googleBar', $mapp->googlebar).','."\n";// Not ready yet
	//	$output .= $map->setMapOption('continuousZoom', $mapp->continuouszoom).','."\n";// Not ready yet
		$output .= $map->setMapTypeOpt($mapp->typeid)."\n";
		$output .= $map->endMapOptions();
		$output .= $map->setMap();
		
		// Markers
		jimport('joomla.filter.output');
		if (isset($markerp) && !empty($markerp)) {
		
			$iconArray = array(); // add information about created icons to array and check it so no duplicity icons js code will be created
			foreach ($markerp as $key => $markerV) {
			
				if ((isset($markerV->longitude) && $markerV->longitude != '')
				&& (isset($markerV->latitude) && $markerV->latitude != '')) {
					
					
		
					$hStyle = 'font-size:120%;margin: 5px 0px;font-weight:bold;';
					$text = '<div style="'.$hStyle.'">' . addslashes($markerV->title) . '</div>';
					// Try to correct images in description
					$markerV->description = PhocaMapsHelper::fixImagePath($markerV->description);
					$markerV->description = str_replace('@', '&#64;', $markerV->description);
					//$markerV->description = str_replace("/", '&#47;', $markerV->description);
					$markerV->description = str_replace("'", '&#39;', $markerV->description);
					//$markerV->description = str_replace('"', '&#34;', $markerV->description);
					
					//$markerV->description = htmlentities($markerV->description);
					$text .= '<div>'. PhocaMapsHelper::strTrimAll(addslashes($markerV->description)).'</div>';
					
					
					if ($markerV->displaygps == 1) {
						$text .= '<div class="pmgps"><table border="0"><tr><td><strong>'. JText::_('PLG_CONTENT_PHOCAMAPS_GPS') . ': </strong></td>'
								.'<td>'.PhocaMapsHelper::strTrimAll(addslashes($markerV->gpslatitude)).'</td></tr>'
								.'<tr><td></td>'
								.'<td>'.PhocaMapsHelper::strTrimAll(addslashes($markerV->gpslongitude)).'</td></tr></table></div>';
					}
				
					
					
					if(empty($markerV->icon)) {
						$markerV->icon = 0;
					}
					if(empty($markerV->title)){
						$markerV->title = '';
					}
					if(empty($markerV->description)){
						$markerV->description = '';
					}
					
					
					$iconOutput = $map->setMarkerIcon($markerV->icon, $markerV->iconext, $markerV->iurl, $markerV->iobject, $markerV->iurls, $markerV->iobjects, $markerV->iobjectshape);
					$output .= $map->outputMarkerJs($iconOutput['js'], $markerV->icon, $markerV->iconext);
					
					$output .= $map->setMarker($markerV->id,$markerV->title,$markerV->description,$markerV->latitude, $markerV->longitude, $iconOutput['icon'], $iconOutput['iconid'], $text, $markerV->contentwidth, $markerV->contentheight,  $markerV->markerwindow,  $iconOutput['iconshadow'], $iconOutput['iconshape']);
					
				}
			}
		}

		if ($tmpl['load_kml']) {
			$output .= $map->setKMLFile($tmpl['load_kml']);
		}
		
		if ($tmpl['displaydir']) {
			$output .= $map->setDirectionDisplayService('phocaDir');
		}
		$output .= $map->setListener();
		$output .= $map->endMapFunction();

		if ($tmpl['displaydir']) {
			$output .= $map->setDirectionFunction($tmpl['display_print_route'], $mapp->id, $mapp->alias, $tmpl['lang']);
		}
		
		$output .= $map->setInitializeFunction();
	$output .= $map->endJScData();
}


$output .= '<div style="clear:both"></div>';
$output .= '</div>';

// END RENDER
// - - - - - - - - - - - - - - - 						
						
						
						
					break;
					
					// - - - - - - - - - - - - - - - -
					// Link
					// - - - - - - - - - - - - - - - -
					case 'link':
						if ((int)$idMap > 0) {
						
							$query = 'SELECT a.*,'
							. ' CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(\':\', a.id, a.alias) ELSE a.id END as slug'
							.' FROM #__phocamaps_map AS a'
							.' WHERE a.id = '.(int) $idMap;
							$db->setQuery($query);
							$mapp = $db->loadObject();
								
							if (empty($mapp)) {
								
								JError::raiseError(JText::_('PLG_CONTENT_PHOCAMAPS_PLUGIN_ERROR'), JText::_('PLG_CONTENT_PHOCAMAPS_MAP_NOT_EXISTS') . ' (ID = '.$idMap.')');
							}
							
							$query = 'SELECT a.id'
								.' FROM #__phocamaps_marker AS a'
								.' LEFT JOIN #__phocamaps_map AS c ON c.id = a.catid '
								.' WHERE c.id = '.(int) $idMap
								.' AND a.published = 1';
							$db->setQuery($query);
							$markerp = $db->loadObjectList();
							
							
							$linkMap 		= PhocaMapsHelperRoute::getMapRoute( $mapp->id, $mapp->alias);
					
							if ($text =='') {
								$text = JText::_('PLG_CONTENT_PHOCAMAPS_LINK_TO_MAP');
							}
							
							// Parameters
							$tmpl['detailwindow']		= $paramsP->get( 'detail_window', 0 );
							$tmpl['mbbordercolor']		= $paramsP->get( 'modal_box_border_color', '#6b6b6b' );
							$tmpl['mbborderwidth']		= $paramsP->get( 'modal_box_border_width', 2 );
							$tmpl['mboverlaycolor']		= $paramsP->get( 'modal_box_overlay_color', '#000000' );
							$tmpl['mboverlayopacity']	= $paramsP->get( 'modal_box_overlay_opacity', 0.3 );
							
							
							if ($mapp->width > 0) {
								$tmpl['windowwidth'] = (int)$mapp->width + 20;
							} else {
								$tmpl['windowwidth'] = 640;
							}
							if ($mapp->width > 0) {
								$tmpl['windowheight'] = (int)$mapp->height + 20;
							} else {
								$tmpl['windowheight'] = 360;
							}
							
							
							
							//Route
							if (isset($mapp->displayroute) && $mapp->displayroute == 1) {
								if (isset($markerp) && !empty($markerp)) {
									$tmpl['windowheight'] = (int)$tmpl['windowheight'] + 40;
								}
							}
							
							
							if ($tmpl['detailwindow'] == 1) {
								
								$button = new JObject();
								$button->set('name', 'phocamaps');
								$button->set('methodname', 'js-button');
								$button->set('options', "window.open(this.href,'win2','width=".$tmpl['windowwidth'].",height=".$tmpl['windowheight'].",menubar=no,resizable=yes'); return false;");
								$output .= '<a title="'.$text.'"  href="'.JRoute::_($linkMap . '&tmpl=component').'" onclick="'. $button->options.'">'.$text.'</a>';
								
								
							} else if ($tmpl['detailwindow'] == 0) {
							
								// Button
								JHTML::_('behavior.modal', 'a.modal-button');
								$cssSbox = " #sbox-window {background-color:".$tmpl['mbbordercolor'].";padding:".$tmpl['mbborderwidth']."px} \n"
								." #sbox-overlay {background-color:".$tmpl['mboverlaycolor'].";} \n";
				
								$document->addCustomTag( "<style type=\"text/css\">\n" . $cssSbox . "\n" . " </style>\n");
								
								$button = new JObject();
								$button->set('name', 'phocamaps');
								$button->set('modal', true);
								$button->set('methodname', 'modal-button');
								$button->set('options', "{handler: 'iframe', size: {x: ".$tmpl['windowwidth'].", y: ".$tmpl['windowheight']."}, overlayOpacity: ".$tmpl['mboverlayopacity'].", classWindow: 'phocamaps-plugin-window', classOverlay: 'phocamaps-plugin-overlay'}");
							
								$output .= '<a class="modal-button" title="'.$text.'"  href="'.JRoute::_($linkMap . '&tmpl=component').'" rel="'. $button->options.'">'.$text.'</a>';
							}

						}
					break;
					
				}
				$article->text = preg_replace($regex_all, $output, $article->text, 1);
			}
		}// end if count_matches
		return true;
	}
}
?>