<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
 
class Taxonomy_file_ft extends Taxonomy_field {

    /**
     * display_name
     * @var string
     */
    public $display_name = 'File';
     
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

        ee()->load->library('file_field');
        ee()->file_field->browser();

        if(is_array($value) && !empty($value['_directory']) && !empty($value['_hidden_file']))
        {
            $value = "{filedir_".$value['_directory']."}".$value['_hidden_file'];
        }
        elseif(is_array($value) && empty($value['_directory']))
        {
            $value = '';
        }

        // I wish you knew how long it took to figure out I needed this div
        $r  = '<div class="publish_file">'; 
        $r .=  ee()->file_field->field($name, $value, $allowed_file_dirs = 'all', $content_type = 'all');
        $r .= "</div>";

        // need to look at why the file class just appends keys onto our array elements
        // for now just find/replace
        $r = str_replace('_hidden_file', '[_hidden_file]', $r);
        $r = str_replace('_hidden_dir', '[_hidden_dir]', $r);
        $r = str_replace('_directory', '[_directory]', $r);

        return $r;
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
        if($value)
        {
            if(is_array($value) && !empty($value['_directory']) && !empty($value['_hidden_file']))
            {
                $value = "{filedir_".$value['_directory']."}".$value['_hidden_file'];
            }
            return ee()->typography->parse_file_paths($value);
        }
        else
        {
            return null;
        }
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

        if($value['_hidden_file'])
            return "{filedir_".$value['_directory']."}".$value['_hidden_file'];
        else
            return null;

    }
 
}