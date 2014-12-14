<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
 
class Taxonomy_text_input_ft extends Taxonomy_field {

    /**
     * display_name
     * @var string
     */
    public $display_name = 'Text Input';
     
    /**
     * Display a field in the control panel
     *
     * @access  public
     * @param   string 
     * @param   string 
     * @return  string 
     */
    public function display_field($name, $value) 
    {
        $options = array(
            'name' => $name,
            'value' => $value,
            'style' => "width: 60%;"
        );
        return form_input($options);
    }

    /**
     * Manipulate a saved field value before it is output in a template
     *
     * @access  public
     * @param   string 
     * @return  string 
     */
    public function replace_value($value)
    {
        return $value;
    }

    /**
     * Alter value of a field before it is saved to the database
     *
     * @see     Taxonomy_mcp::update_node()
     * @access  public
     * @param   string The value of the custom field
     * @return  string Field value
     */
    public function pre_save($value)
    {
        return $value;
    }
 
}