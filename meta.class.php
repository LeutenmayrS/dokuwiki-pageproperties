<?php
/**
 * properties Class and generic property classes
 * 
 *
 * @author  Stefan Leutenmayr <stefan.leutenmayr@freenet.de>
 * @praise  Chris Smith <chris@jalakai.co.uk>
 * @praise  Ben Coburn <btcoburn@silicodon.net>
 */


if(!defined('CM_KEYMARKER')) define('CM_KEYMARKER','____');

if (!class_exists('properties')) {
    /**
     * Class properties
     */
    class properties {

        var $_loaded = false;          // set to true after configuration files are loaded
        var $_metatype = array();      // holds metadata describing the properties
        /** @var property[]  */
        var $property = array();        // array of property objects
		
		var $_plugin_list = null;


        /**
         * constructor
         *
         * @param string $datafile path to config metadata file
         */
        public function __construct() {
            $meta = array();
            $this->_metatype = array_merge($meta, $this->get_metatype());
            $this->retrieve_properties();
        }

        /**
         * Retrieve and stores properties in property[] attribute
         */
        public function retrieve_properties() {
            global $conf;
			
            if (!$this->_loaded) {
                $metavalue = $this->get_metadata_value();

                $keys = array_merge(array_keys($this->_metatype),array_keys($metavalue));
                $keys = array_unique($keys);

                $param = null;
                foreach ($keys as $key) {
                    if (isset($this->_metatype[$key])) {
                        $class = $this->_metatype[$key][0];

                        if($class && class_exists('property_'.$class)){
                            $class = 'property_'.$class;
                        } else {
                            if($class != '') {
                                $this->property[$key] = new property_no_class($key,$param);
                            }
                            $class = 'property';
                        }

                        $param = $this->_metatype[$key];
                        array_shift($param);
                    } else {
                        $class = 'property_undefined';
                        $param = null;
                    }

                    $this->property[$key] = new $class($key,$param);
                    $this->property[$key]->initialize($metavalue[$key]);
                }

                $this->_loaded = true;
            }
        }

		/**
         * Returns array of plugin names
         *
         * @return array plugin names
         */
        function get_plugin_list() {
            if (is_null($this->_plugin_list)) {
                $list = plugin_list('',false);
                $this->_plugin_list = $list;
            }

            return $this->_plugin_list;
        }

        /**
         * load metadata for page metadata
         *
         * @return array metadata of properties
         */
        function get_metatype(){
            $file     = '/conf/metatypes.php';
            $class    = '/conf/meta.class.php';
            $metatype = array();
			
			foreach ($this->get_plugin_list() as $plugin) {
                $plugin_dir = plugin_directory($plugin);
                if (file_exists(DOKU_PLUGIN.$plugin_dir.$file)){
					
                    $meta = array();
					
                    @include(DOKU_PLUGIN.$plugin_dir.$file);
                    @include(DOKU_PLUGIN.$plugin_dir.$class);
					
                    foreach ($meta as $key => $value){
                        $metatype[$key] = $value;
                    }
					
                }
            }

            return $metatype;
        }

        /**
         * Load values of metadata
         *
         * @return array default properties
         */
        function get_metadata_value(){
			global $ID;
			
            $metadata = array();

			$meta = p_get_metadata($ID, '', METADATA_DONT_RENDER);
			
			foreach ($meta as $key => $value){
				
				if ($this->_metatype[$key][0] == 'fieldset') {
					
					foreach ($value as $bKey => $bValue){
						$metadata[$key.':'.$bKey] = $bValue;
					}
					
				} else {
				
					$metadata[$key] = $value;
					
				}
			}

            return $metadata;
        }

    }
}

if (!class_exists('property')) {
    /**
     * Class property
     */
    class property {

        var $_key = '';
        var $_metavalue = null;

        var $_pattern = '';
        var $_error = false;            // only used by those classes which error check
        var $_input = null;             // only used by those classes which error check
        var $_caution = null;           // used by any property to provide an alert along with the property
                                        // valid alerts, 'warning', 'danger', 'security'
                                        // images matching the alerts are in the plugin's images directory

        static protected $_validCautions = array('warning','danger','security');

        /**
         * @param string $key
         * @param array|null $params array with metadata of property
         */
        public function __construct($key, $params=null) {
            $this->_key = $key;

            if (is_array($params)) {
                foreach($params as $property => $value) {
                    $this->$property = $value;
                }
            }
        }

        /**
         * Receives current values for the property $key
         *
         * @param mixed $metavalue   property value
         */
        public function initialize($metavalue) {
            if (isset($metavalue)) $this->_metavalue = $metavalue;
        }

        /**
         * Build html for label and input of property
         *
         * @param DokuWiki_Plugin $plugin object of config plugin
         * @param bool            $echo   true: show inputted value, when error occurred, otherwise the stored property
         * @return string[] with content array(string $label_html, string $input_html)
         */
        public function html(&$plugin, $echo=false) {
            $disable = '';


			if ($echo && $this->_error) {
				$value = $this->_input;
			} else {
				$value = $this->_metavalue;
			}

            $key = htmlspecialchars($this->_key);
            $value = formText($value);

            $label = '<label for="config___'.$key.'">'.$this->prompt($plugin).'</label>';
            $input = '<textarea rows="3" cols="40" id="config___'.$key.'" name="config['.$key.']" class="edit" '.$disable.'>'.$value.'</textarea>';
            return array($label,$input);
        }

        /**
         * Returns the localized prompt
         *
         * @param DokuWiki_Plugin $plugin object of config plugin
         * @return string text
         */
        public function prompt(&$plugin) {
            $prompt = $plugin->getLang($this->_key);
            if (!$prompt) $prompt = htmlspecialchars(str_replace(array('____','_'),' ',$this->_key));
            return $prompt;
        }

        /**
         * Has an error?
         *
         * @return bool
         */
        public function error() { return $this->_error; }

        /**
         * Returns caution
         *
         * @return false|string caution string, otherwise false for invalid caution
         */
        public function caution() {
            if (!empty($this->_caution)) {
                if (!in_array($this->_caution, property::$_validCautions)) {
                    trigger_error('Invalid caution string ('.$this->_caution.') in metadata for property "'.$this->_key.'"', E_USER_WARNING);
                    return false;
                }
                return $this->_caution;
            }
            // compatibility with previous cautionList
            // TODO: check if any plugins use; remove
            if (!empty($this->_cautionList[$this->_key])) {
                $this->_caution = $this->_cautionList[$this->_key];
                unset($this->_cautionList);

                return $this->caution();
            }
            return false;
        }
		
		/**
         * Returns setting key, eventually with referer to config: namespace at dokuwiki.org
         *
         * @param bool $pretty create nice key
         * @param bool $url    provide url to config: namespace
         * @return string key
         */
        public function _out_key() {
            return '<a href="https://www.dokuwiki.org/devel:metadata">'.$this->_key.'</a>';
        }


    }
}


if (!class_exists('property_array')) {
    /**
     * Class property_array
     */
    class property_array extends property {

        /**
         * Create a string from an array
         *
         * @param array $array
         * @return string
         */
        protected function _from_array($array){
            //return join(', ', (array) $array);
			return implode(', ', array_map(function ($v, $k) { return sprintf("%s='%s'", $k, $v); },$array,array_keys($array)));
        }
		
		/**
		 * Recursively implodes an array with optional key inclusion
		 * 
		 * Example of $include_keys output: key, value, key, value, key, value
		 * 
		 * @access  public
		 * @param   array   $array         multi-dimensional array to recursively implode
		 * @param   string  $glue          value that glues elements together	
		 * @param   bool    $include_keys  include keys before their values
		 * @param   bool    $trim_all      trim ALL whitespace from string
		 * @return  string  imploded array
		 */ 
		function recursive_implode(array $array, $glue = ',', $keyglue = '=', $include_keys = false, $trim_all = true)
		{
			$glued_string = '';
			// Recursively iterates array and adds key/value to glued string
			array_walk_recursive($array, function($value, $key) use ($glue, $keyglue, $include_keys, &$glued_string)
			{
				$include_keys and $glued_string .= $key.$keyglue;
				$glued_string .= $value.$glue;
			});
			// Removes last $glue from string
			strlen($glue) > 0 and $glued_string = substr($glued_string, 0, -strlen($glue));
			// Trim ALL whitespace
			$trim_all and $glued_string = preg_replace("/(\s)/ixsm", '', $glued_string);
			return (string) $glued_string;
		}


        /**
         * Build html for label and input of property
         *
         * @param DokuWiki_Plugin $plugin object of config plugin
         * @param bool            $echo   true: show inputted value, when error occurred, otherwise the stored property
         * @return string[] with content array(string $label_html, string $input_html)
         */
        function html(&$plugin, $echo=false) {

			if ($echo && $this->_error) {
				$value = $this->_input;
			} else {
				$array = $this->_metavalue;
			}

			if (is_array($array) == true){
				$value = $this->recursive_implode($array, '; ', '=',true, false);
			} else {
				$value = $array;
			}

            $key = htmlspecialchars($this->_key);
            $value = htmlspecialchars($value);

            $label = '<label for="config___'.$key.'">'.$this->prompt($plugin).'</label>';
            $input = '<input id="config___'.$key.'" name="config['.$key.']" type="text" class="edit" value="'.$value.'" '.$disable.'/>';
            return array($label,$input);
        }
    }
}

if (!class_exists('property_string')) {
    /**
     * Class property_string
     */
    class property_string extends property {
        /**
         * Build html for label and input of property
         *
         * @param DokuWiki_Plugin $plugin object of config plugin
         * @param bool            $echo   true: show inputted value, when error occurred, otherwise the stored property
         * @return string[] with content array(string $label_html, string $input_html)
         */
        function html(&$plugin, $echo=false) {

			if ($echo && $this->_error) {
				$value = $this->_input;
			} else {
				$value = $this->_metavalue;
			}

            $key = htmlspecialchars($this->_key);
            $value = htmlspecialchars($value);

            $label = '<label for="config___'.$key.'">'.$this->prompt($plugin).'</label>';
            $input = '<input id="config___'.$key.'" name="config['.$key.']" type="text" class="edit" value="'.$value.'" '.$disable.'/>';
            return array($label,$input);
        }
    }
}

if (!class_exists('property_timestamp')) {
    /**
     * Class property_string
     */
    class property_timestamp extends property {
        /**
         * Build html for label and input of property
         *
         * @param DokuWiki_Plugin $plugin object of config plugin
         * @param bool            $echo   true: show inputted value, when error occurred, otherwise the stored property
         * @return string[] with content array(string $label_html, string $input_html)
         */
        function html(&$plugin, $echo=false) {

			if ($echo && $this->_error) {
				$value = $this->_input;
			} else {
				if (is_numeric($this->_metavalue)){
					$value = date('d.m.y', intval($this->_metavalue));
				} else {
					$value = $this->_metavalue;
				}
				
			}

            $key = htmlspecialchars($this->_key);
            $value = htmlspecialchars($value);

            $label = '<label for="config___'.$key.'">'.$this->prompt($plugin).'</label>';
            $input = '<input id="config___'.$key.'" name="config['.$key.']" type="text" class="edit" value="'.$value.'" '.$disable.'/>';
            return array($label,$input);
        }
    }
}

if (!class_exists('property_email')) {
    /**
     * Class property_email
     */
    class property_email extends property_string {

    }
}

if (!class_exists('property_numeric')) {
    /**
     * Class property_numeric
     */
    class property_numeric extends property_string {

    }
}

if (!class_exists('property_numericopt')) {
    /**
     * Class property_numericopt
     */
    class property_numericopt extends property_numeric {

    }
}

if (!class_exists('property_onoff')) {
    /**
     * Class property_onoff
     */
    class property_onoff extends property_numeric {
        /**
         * Build html for label and input of property
         *
         * @param DokuWiki_Plugin $plugin object of config plugin
         * @param bool            $echo   true: show inputted value, when error occurred, otherwise the stored property
         * @return string[] with content array(string $label_html, string $input_html)
         */
        function html(&$plugin, $echo = false) {

            $value = $this->_metavalue;

            $key = htmlspecialchars($this->_key);
            $checked = ($value) ? ' checked="checked"' : '';

            $label = '<label for="config___'.$key.'">'.$this->prompt($plugin).'</label>';
            $input = '<div class="input"><input id="config___'.$key.'" name="config['.$key.']" type="checkbox" class="checkbox" value="1"'.$checked.$disable.'/></div>';
            return array($label,$input);
        }

    }
}

if (!class_exists('property_multichoice')) {
    /**
     * Class property_multichoice
     */
    class property_multichoice extends property_string {
        var $_choices = array();
        var $lang; //some custom language strings are stored in property

        /**
         * Build html for label and input of property
         *
         * @param DokuWiki_Plugin $plugin object of config plugin
         * @param bool            $echo   true: show inputted value, when error occurred, otherwise the stored property
         * @return string[] with content array(string $label_html, string $input_html)
         */
        function html(&$plugin, $echo = false) {
			
            $value = $this->_metavalue;

            // ensure current value is included
            if (!in_array($value, $this->_choices)) {
                $this->_choices[] = $value;
            }

            $key = htmlspecialchars($this->_key);

            $label = '<label for="config___'.$key.'">'.$this->prompt($plugin).'</label>';

            $input = "<div class=\"input\">\n";
            $input .= '<select class="edit" id="config___'.$key.'" name="config['.$key.']"'.$disable.'>'."\n";
            foreach ($this->_choices as $choice) {
                $selected = ($value == $choice) ? ' selected="selected"' : '';
                $option = $plugin->getLang($this->_key.'_o_'.$choice);
                if (!$option && isset($this->lang[$this->_key.'_o_'.$choice])) $option = $this->lang[$this->_key.'_o_'.$choice];
                if (!$option) $option = $choice;

                $choice = htmlspecialchars($choice);
                $option = htmlspecialchars($option);
                $input .= '  <option value="'.$choice.'"'.$selected.' >'.$option.'</option>'."\n";
            }
            $input .= "</select> $nochoice \n";
            $input .= "</div>\n";

            return array($label,$input);
        }
    }
}


if (!class_exists('property_dirchoice')) {
    /**
     * Class property_dirchoice
     */
    class property_dirchoice extends property_multichoice {

        var $_dir = '';

        /**
         * Receives current values for the property $key
         *
         * @param mixed $metavalue   default property value
         * @param mixed $local     local property value
         * @param mixed $protected protected property value
         */
        function initialize($default,$local,$protected) {

            // populate $this->_choices with a list of directories
            $list = array();

            if ($dh = @opendir($this->_dir)) {
                while (false !== ($entry = readdir($dh))) {
                    if ($entry == '.' || $entry == '..') continue;
                    if ($this->_pattern && !preg_match($this->_pattern,$entry)) continue;

                    $file = (is_link($this->_dir.$entry)) ? readlink($this->_dir.$entry) : $this->_dir.$entry;
                    if (is_dir($file)) $list[] = $entry;
                }
                closedir($dh);
            }
            sort($list);
            $this->_choices = $list;

            parent::initialize($default,$local,$protected);
        }
    }
}


if (!class_exists('property_hidden')) {
    /**
     * Class property_hidden
     */
    class property_hidden extends property {
        // Used to explicitly ignore a property in the configuration manager.
    }
}

if (!class_exists('property_fieldset')) {
    /**
     * Class property_fieldset
     */
    class property_fieldset extends property {
        // A do-nothing class used to detect the 'fieldset' type.
        // Used to start a new properties "display-group".
    }
}

if (!class_exists('property_undefined')) {
    /**
     * Class property_undefined
     */
    class property_undefined extends property_hidden {
        // A do-nothing class used to detect properties with no metadata entry.
        // Used internaly to hide undefined properties, and generate the undefined properties list.
    }
}

if (!class_exists('property_no_class')) {
    /**
     * Class property_no_class
     */
    class property_no_class extends property_undefined {
        // A do-nothing class used to detect properties with a missing property class.
        // Used internaly to hide undefined properties, and generate the undefined properties list.
    }
}

if (!class_exists('property_no_default')) {
    /**
     * Class property_no_default
     */
    class property_no_default extends property_undefined {
        // A do-nothing class used to detect properties with no default value.
        // Used internaly to hide undefined properties, and generate the undefined properties list.
    }
}

if (!class_exists('property_multicheckbox')) {
    /**
     * Class property_multicheckbox
     */
    class property_multicheckbox extends property_string {

        var $_choices = array();
        var $_combine = array();

        /**
         * Build html for label and input of property
         *
         * @param DokuWiki_Plugin $plugin object of config plugin
         * @param bool            $echo   true: show inputted value, when error occurred, otherwise the stored property
         * @return string[] with content array(string $label_html, string $input_html)
         */
        function html(&$plugin, $echo=false) {

			if ($echo && $this->_error) {
				$value = $this->_input;
			} else {
				$value = $this->_metavalue;
			}


            $key = htmlspecialchars($this->_key);

            // convert from comma separated list into array + combine complimentary actions
            $value = $this->_str2array($value);
            $metavalue = $this->_str2array($this->_metavalue);

            $input = '';
            foreach ($this->_choices as $choice) {
                $idx = array_search($choice, $value);
                $idx_default = array_search($choice,$default);

                $checked = ($idx !== false) ? 'checked="checked"' : '';

                // ideally this would be handled using a second class of "default", however IE6 does not
                // correctly support CSS selectors referencing multiple class names on the same element
                // (e.g. .default.selection).
                $class = (($idx !== false) == (false !== $idx_default)) ? " selectiondefault" : "";

                $prompt = ($plugin->getLang($this->_key.'_'.$choice) ?
                                $plugin->getLang($this->_key.'_'.$choice) : htmlspecialchars($choice));

                $input .= '<div class="selection'.$class.'">'."\n";
                $input .= '<label for="config___'.$key.'_'.$choice.'">'.$prompt."</label>\n";
                $input .= '<input id="config___'.$key.'_'.$choice.'" name="config['.$key.'][]" type="checkbox" class="checkbox" value="'.$choice.'" '.$disable.' '.$checked."/>\n";
                $input .= "</div>\n";

                // remove this action from the disabledactions array
                if ($idx !== false) unset($value[$idx]);
                if ($idx_default !== false) unset($default[$idx_default]);
            }

            // handle any remaining values
            $other = join(',',$value);

            $class = ((count($default) == count($value)) && (count($value) == count(array_intersect($value,$default)))) ?
                            " selectiondefault" : "";

            $input .= '<div class="other'.$class.'">'."\n";
            $input .= '<label for="config___'.$key.'_other">'.$plugin->getLang($key.'_other')."</label>\n";
            $input .= '<input id="config___'.$key.'_other" name="config['.$key.'][other]" type="text" class="edit" value="'.htmlspecialchars($other).'" '.$disable." />\n";
            $input .= "</div>\n";

            $label = '<label>'.$this->prompt($plugin).'</label>';
            return array($label,$input);
        }

        /**
         * convert comma separated list to an array and combine any complimentary values
         *
         * @param string $str
         * @return array
         */
        function _str2array($str) {
            $array = explode(',',$str);

            if (!empty($this->_combine)) {
                foreach ($this->_combine as $key => $combinators) {
                    $idx = array();
                    foreach ($combinators as $val) {
                        if  (($idx[] = array_search($val, $array)) === false) break;
                    }

                    if (count($idx) && $idx[count($idx)-1] !== false) {
                        foreach ($idx as $i) unset($array[$i]);
                        $array[] = $key;
                    }
                }
            }

            return $array;
        }
    }
}

if (!class_exists('property_regex')){
    /**
     * Class property_regex
     */
    class property_regex extends property_string {

    }
}
