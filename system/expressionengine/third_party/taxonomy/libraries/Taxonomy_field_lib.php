<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Taxonomy_field_lib {

    /**
     * fieldtypes
     * @var array
     */
    private static $fieldtypes = array();

    /**
     * is_publish
     * flag for if module editing or entry edit/publishing
     * @var bool
     */
    public $is_publish = false;
     
    // --------------------------------------------------------------------
    
    /**
     * load a fieldtype
     *
     * @access  public
     * @param   string
     * @return  object
     */ 
    public function load($ft) 
    {
        if( ! isset(self::$fieldtypes[$ft]))
        {   
            // load and instantiate the plugin if it doesn't exist already
            require_once PATH_THIRD . 'taxonomy/fieldtypes/ft.taxonomy_' . $ft . EXT;
            $ft_class = 'taxonomy_' . $ft . '_ft';
            self::$fieldtypes[$ft] = new $ft_class;
        }
        // check that we have an object that extends Taxonomy_field
        if(self::$fieldtypes[$ft] instanceof Taxonomy_field)
        {
            return self::$fieldtypes[$ft];
        }
        else
        {
            throw new RuntimeException('Taxonomy fieldtypes must extend Taxonomy_field');
        }
    }
 
}
 
abstract class Taxonomy_field {

    /**
     * display_name
     * @var string
     */
    public $display_name;
     
    /**
     * Display a field in the control panel
     *
     * @see     Taxonomy_mcp::manage_node()
     * @access  public
     * @param   string The name of the custom field
     * @param   string The value of the custom field
     * @return  string Field markup
     */
    abstract public function display_field($name, $value);

    /**
     * Manipulate a saved field value before it is output in a template
     *
     * @see     Taxonomy_model::get_nodes()
     * @access  public
     * @param   string 
     * @return  string 
     */
    abstract public function replace_value($value);

    /**
     * Alter value of a field before it is saved to the database
     *
     * @see     Taxonomy_mcp::update_node()
     * @access  public
     * @param   string The value of the custom field
     * @return  string Field value
     */
    abstract public function pre_save($value);
 
}