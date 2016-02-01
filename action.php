<?php
/**
 * DokuWiki Plugin pageproperties (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Stefan Leutenmayr <stefan.leutenmayr@freenet.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

require_once(dirname(__FILE__).'/'.'meta.class.php');  // main configuration class and generic settings classes

class action_plugin_pageproperties extends DokuWiki_Action_Plugin {

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {

	   $controller->register_hook('TEMPLATE_PAGETOOLS_DISPLAY', 'BEFORE', $this, 'handle_template_pagetools_display');
       $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handle_action_act_preprocess');
       $controller->register_hook('TPL_ACT_UNKNOWN', 'BEFORE', $this, 'handle_tpl_act_unknown');

    }

    /**
     * [Custom event handler which performs action]
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */

    public function handle_action_act_preprocess(Doku_Event &$event, $param) {
		if($event->data != 'pageproperties') return; 
		$event->preventDefault();
		$event->stopPropagation();
		return true;
    }

    public function handle_tpl_act_unknown(Doku_Event &$event, $param) {
		if($event->data != 'pageproperties') return; 
		$event->preventDefault();
		
		global $lang;
		global $ID;
		
		// check user's rights
        if(auth_quickaclcheck($ID) < AUTH_READ) {
			print $lang['accessdenied'];
		} else {

			if ($this->getConf('use_simple_treeview') == 1) {
				$this->render_simple_treeview();
			} else {
				$this->render_complex();
			}
			
		}

    }
	
	
	
	
	private function render_complex(){
		
		$properties = new properties();
		
		ptln('<div id="config__manager">');
		ptln('<h1>'.$this->getLang('header'). '</h1>');

		/** @var property[] $undefined_properties */
		$undefined_properties = array();
		$in_fieldset = false;

		foreach($properties->property as $property) {
			if (is_a($property, 'property_hidden')) {
				// skip hidden (and undefined) settings
				if (is_a($property, 'property_undefined')) {
					$undefined_properties[] = $property;
				} else {
					continue;
				}
			} else if (is_a($property, 'property_fieldset')) {
				// config property group
				if ($in_fieldset) {
					ptln('  </table>');
					ptln('  </div>');
					ptln('  </fieldset>');
				} else {
					$in_fieldset = true;
				}

				ptln('  <fieldset id="'.$property->_key.'">');
				ptln('  <legend>'.$property->prompt($this).'</legend>');
				ptln('  <div class="table">');
				ptln('  <table class="inline">');
				
			} else {
				list($label,$input) = $property->html($this, $this->_error);

				$class = ' class="default"';
				$error = $property->error() ? ' class="value error"' : ' class="value"';
				$icon = $property->caution() ? '<img src="'.DOKU_PLUGIN_IMAGES.$property->caution().'.png" alt="'.$property->caution().'" title="'.$this->getLang($property->caution()).'" />' : '';

				ptln('    <tr'.$class.'>');
				ptln('      <td class="label">');
				ptln('        <span class="outkey">'.$property->_out_key().'</span>');
				ptln('        '.$icon.$label);
				ptln('      </td>');
				ptln('      <td'.$error.'>'.$input.'</td>');
				ptln('    </tr>');
			}
		}

		ptln('  </table>');
		ptln('  </div>');
		if ($in_fieldset) {
			ptln('  </fieldset>');
		}
		
		// show undefined properties list
		if (!empty($undefined_properties)) {
            /**
             * Callback for sorting settings
             *
             * @param setting $a
             * @param setting $b
             * @return int if $a is lower/equal/higher than $b
             */
            function _setting_natural_comparison($a, $b) {
                return strnatcmp($a->_key, $b->_key);
            }

            usort($undefined_properties, '_setting_natural_comparison');
            ptln('<h1>'. $this->getLang('header_undefined') .'</h1>');
            ptln('<fieldset>');
            ptln('<div class="table">');
            ptln('<table class="inline">');
			
            foreach($undefined_properties as $property) {
				
				list($label,$input) = $property->html($this, $this->_error);
				$error = $property->error() ? ' class="value error"' : ' class="value"';
				
                ptln('  <tr>');
                ptln('    <td class="label"><span title="'.$property->_key.'">'.$property->_out_key().'</span></td>');
				ptln('      <td'.$error.'>'.$input.'</td>');
                ptln('  </tr>');
            }
            ptln('</table>');
            ptln('</div>');
            ptln('</fieldset>');
        }

	}
	
	
	private function render_simple_treeview(){
		global $ID;
		
		$meta = p_get_metadata($ID, '', METADATA_DONT_RENDER);
		
		$html = '<h1>'.$this->getLang('header'). '</h1>';
		
		print $html . '<ul>' . $this->render_tree($meta, '') . '</ul>';
		
	}
	
	
	/**
     * Render the output for the plugin
     *
     * 
     */
	private function render_tree($meta, $parent_key){
		
		if (empty($parent_key) == false) {
			$parent_key .= ":";
		}
		
		foreach ($meta as $key => $value) {
				
			$translation = $this->getLang($parent_key . $key);
			
			$html .= '<li>';
			
			if (empty($translation) == false) {
				$html .= $translation;
			} else {
				$html .= $key;
			}
				
			if (is_array($value)) {	
			
				$html .= '</li><ul>' . $this->render_tree($value, $parent_key . $key) . '</ul>';
				
			} else {
			
				$html .= ': ' . $this->isempty($value) .'</li>';

			}
		}
		return $html;
	}
	
	
	/**
     * Checks if the current variable is empty
     *
     * @string variable value
     */
	private function isempty($string){
		if (empty($string) == true) {
			return $this->getLang('empty');
		} else {
			return $string;
		}
	}
	
	
	
	
	 /**
     * Add 'Properties'-button to pagetools
     *
     * @param Doku_Event $event
     */
    public function handle_template_pagetools_display(Doku_Event $event) {
        global $ID, $REV;
		
		if($this->check_user_permission() == true) {
		
            $params = array('do' => 'pageproperties');
            if($REV) {
                $params['rev'] = $REV;
            }

            // insert button at position before last (up to top)
            $event->data['items'] = array_slice($event->data['items'], 0, -1, true) +
                array('pageproperties' =>
                          '<li>'
                          . '<a href="' . wl($ID, $params) . '"  class="action pageproperties" rel="nofollow" title="' . $this->getLang('pagetools_button') . '">'
                          . '<span>' . $this->getLang('pagetools_button') . '</span>'
                          . '</a>'
                          . '</li>'
                ) +
                array_slice($event->data['items'], -1, 1, true);
				
			$event->data['view'] = 'main';
        }
    }
	
	
	 /**
     * Check if the current user is allowed to view the Properties
     *
     * 
     */	
	private function check_user_permission(){
		
		global $INFO;
		
		$username = $INFO['client'];
		$aGroups = $INFO['userinfo']['grps'];
		$result = false;

		if (isset ($INFO['userinfo'])) {
			
			$allowed_groups= explode(';',$this->getConf('allowed_groups'));
			foreach($allowed_groups as $i => $grp) {
				foreach($aGroups as $j => $uGrp) {
					if ($grp == $uGrp) {
						$result = true;
						break 2;
					}
				}
			}
			
			if($result == false) {
			
				$allowed_users = explode(';',$this->getConf('allowed_users'));
				foreach($allowed_users as $i => $user) {
					if ($user == $username) {
						$result = true;
						break;
					}
				}
				
			}
			
		}

		return $result;
		
	}

}

// vim:ts=4:sw=4:et:
