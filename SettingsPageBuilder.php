<?php
namespace PLUGIN_NAMESPACE;

if ( ! defined( 'ABSPATH' ) ) {	exit; }

abstract class SettingsPageBuilder
{
    protected $slug;
    protected $options;
    protected $settings = array();

    public function __construct(  )
    {
        
        $this->set_options();
        
        // Hook into admin Menu
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_sections'));  
         
    }

    protected function set_options($options = []){
        $this->options = array_merge($this->get_default_options(), $options);
        $this->slug = $this->options['slug'];
    }

    private function get_default_options(){
        return [
            'title' => 'My Custom Plugin Settings',
            'menu' => 'Custom Plugin Settings',
            'description' => 'These are the settings for my custom plugin....',
            'parent' => 'options-general.php',
            'capability' => 'manage_options',
            'slug' => Plugin::get_instance()->get_plugin_slug(),
        ];
    }

    protected function get_slug(){
        return $this->slug;
    }

    /**
     * Get the link to settings page used to display in plugins screen
     * @param array $links an array of links for the given plugin path
     * @return string
     */ 

    public function get_settings_page_link() {
        $url = add_query_arg( 'page', $this->options['slug'], get_admin_url() . $this->options['parent']  );
        return sprintf('<a href="%s">%s</a>', esc_url( $url ), __( 'Settings', 'bcms' ));
    }


    /**
     * This function must be set in child classes to return an array of all required settings and fields
     */
    abstract public function get_settings_fields();


    protected function get_post_types_list()
    {
        $post_types = get_post_types(['public'=>true], 'objects');
        $posts = array();
        foreach ($post_types as $post_type) {
            $posts[$post_type->name] = $post_type->labels->singular_name;
        }
        return $posts;
    }

    protected function get_taxonomies_list()
    {
        $taxonomies = get_taxonomies(['public'=>true], 'objects');
        $tax_list = array();

        foreach ($taxonomies as $tax) {
            $tax_list[$tax->name] = $tax->labels->name;
        }
        return $tax_list;
    }

    public function register_sections()
    {
        
        $sections = $this->get_settings_fields();

        foreach ($sections as $section) {
            add_settings_section( $section['id'], $section['label'], array($this, 'section_callback'),  $this->get_slug()  );

            // register form fields
            foreach ($section['fields'] as $subfield) {
                $this->option_keys[] = $subfield['id'];
                add_settings_field($subfield['id'], $subfield['label'], array($this, 'field_callback'), $this->get_slug(), $section['id'], $subfield);
                register_setting($this->get_slug(), $subfield['id']);
            }
        }
    }


    /**
    * Calls add_submenu_page( 
    * string $parent_slug, 
    * string $page_title, 
    * string $menu_title, 
    * string $capability, 
    * string $menu_slug, 
    * callable $callback = '', 
    * int|float $position = null 
     * )

     */
    public function add_settings_page()
    {
        add_submenu_page( 
            $this->options['parent'], 
            $this->options['title'], 
            $this->options['menu'],
            $this->options['capability'], 
            $this->get_slug(),
            [$this,'menu_page_callback']
        );
    }

    /**
     * Options page callback
    */
    public function menu_page_callback(){
        $this->render_settings_page();
    }

    /**
     * This renders the entire menu page based on the options
     */
    protected function render_settings_page()
    {
        ?>
        <div class="wrap">
            <?php /* $this->admin_notice(); */ ?>
            <h1><?php echo $this->options['title']; ?></h1>
            <div class="plugin_description"><?php echo $this->options['description']; ?></div>
            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields($this->get_slug());
                do_settings_sections($this->get_slug());
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }


    public function section_callback($section)
    {
        //echo $section['title'];
    }

    public function field_callback($arguments)
    {
        $defaults =[
            'type' => 'text',
            'label' => 'Field Name',
            'placeholder' => '',
            'size' => '',
            'autocomplete' => ''
        ];
        $arguments = array_merge($defaults, $arguments);

        // Get stored value from database
        $value = get_option($arguments['id']);
        $value = empty($value) && isset($arguments['default']) ? $arguments['default'] : $value;

        if (isset($arguments['above']) && !empty($arguments['above'])) {
            printf('<p class="above">%s</p>', $arguments['above']);
        }

        switch ($arguments['type']) {
            case 'color':    
            case 'date':    
            case 'text':
            case 'email':    
            case 'password':    
            case 'number':
                printf('<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" size="%4$s" value="%5$s" autocomplete="%6$s"/>', $arguments['id'], $arguments['type'], $arguments['placeholder'], $arguments['size'], $value, $arguments['autocomplete']);
                break;  
            case 'textarea':
                printf('<textarea name="%1$s" id="%1$s" placeholder="%2$s" rows="%3$s" cols="50">%4$s</textarea>', $arguments['id'], $arguments['placeholder'], $arguments['size'], $value);
                break;
            case 'select':
            case 'multiselect':
                if (!empty($arguments['options']) && is_array($arguments['options'])) {
                    $attributes = '';
                    $options_markup = '';

                    foreach ($arguments['options'] as $key => $label) {
                        $selected = (!$value) ? '' : selected( $value[ array_search( $key, $value, true ) ], $key, false );
                        $options_markup .= sprintf( '<option value="%s" %s>%s</option>', $key, $selected, $label );
                    }
                    if ($arguments['type'] === 'multiselect') {
                        $attributes = ' multiple="multiple" ';
                    }
                    if (isset($arguments['size'])){
                        $attributes .= sprintf(' size="%s"',$arguments['size']);
                    }
                    printf('<select name="%1$s[]" id="%1$s" %2$s>%3$s</select>', $arguments['id'], $attributes, $options_markup);
                }
                break;
            case 'radio':
            case 'checkbox':
                if (!empty($arguments['options']) && is_array($arguments['options'])) {
                    $options_markup = '';
                    $iterator = 0;
                    foreach ($arguments['options'] as $key => $label) {
                        $iterator++;
                        $checked = (!$value) ? '' : checked( $value[ array_search($key, $value, true ) ], $key, false );
                        $options_markup .= sprintf(
                            '<label for="%1$s_%6$s"><input id="%1$s_%6$s" name="%1$s[]" type="%2$s" value="%3$s" %4$s /> %5$s</label><br/>', 
                            $arguments['id'], $arguments['type'], $key, $checked, $label, $iterator
                        );
                    }
                    printf('<fieldset>%s</fieldset>', $options_markup);
                }
                break;
        }

        if (isset($arguments['below']) && !empty($arguments['below'])) {
            printf('<p class="below">%s</p>', $arguments['below']);
        }

    }

    public function admin_notice()
    {   
        if (isset($_GET['settings-updated']) && $_GET['settings-updated']) : ?>
        <div class="notice notice-success is-dismissible">
            <p>Your settings have been updated!</p>
        </div>
        <?php endif;
    }


} // End of class