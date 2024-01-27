<?php
namespace PLUGIN_NAMESPACE;

if ( ! defined( 'ABSPATH' ) ) {	exit; }

class Settings extends SettingsPageBuilder
{
    protected $settings = array();

    public function __construct(){
        parent::__construct();
        $this->set_options(
            [
            'slug' => 'custom-plugin-settings-slug',              
            'title' => 'SETTINGS PAGE TITLE',
			'menu' => 'PLUGIN SETTINGS MENU NAME',
			'description' => '<p>PLUGIN DESCRIPTION</p>'
            ]);
            
            // Get and set the settings
            $this->get_settings();
    }

    /**
     * @return array of fieldnames matching the option keys
     */
    private function get_option_keys(){
        return [
            'token' => 'PREFIX_api_token',
            'username' => 'PREFIX_username',
            'password' => 'PREFIX_password',
            'post_type' => 'PREFIX_posttype',
            'taxonomy' => 'PREFIX_taxonomy',
            'fields' => 'PREFIX_fields',
        ];
    }
       
    /**
     * Main function to setup settings page
     * fields are grouped in sections by the parent item
     * @return array (multidimensional)
     */

    public function get_settings_fields(){
        return array(
            [
                'id' => 'settings',
                'label' => 'BuilderCMS Settings',
                'fields'=> array(
                    [
                        'id' => 'PREFIX_token',
                        'label' => 'API Token',
                        'type' => 'text',
                        'size' => '5',
                        'placeholder' => '99999',
                        //'below' => 'Enter External API TOKEN',
                    ],
                    [
                        'id' => 'PREFIX_username',
                        'label' => 'Username',
                        'type' => 'text',
                        'size' => '50',
                        'autocomplete'=>'off',
                        'placeholder' => 'username',
                        //'below' => '',
                    ],
                    [
                        'id' => 'PREFIX_password',
                        'label' => 'Password',
                        'type' => 'password',
                        'size' => '50',
                        'autocomplete'=>'new-password',
                        'placeholder' => 'password',
                        //'below' => '',
                    ],
                )
            ],
            [
                'id' => 'post_settings',
                'label' => 'Post Settings',
                'fields'=> array(
                    [
                        'id' => 'PREFIX_posttype',
                        'label' => 'Post Type',
                        'type' => 'select',
                        'options' => $this->get_post_types_list(),
                        'below' => 'The post-type to sync',
                    ],
                    [
                        'id' => 'PREFIX_taxonomy',
                        'label' => 'Status Taxonomy',
                        'type' => 'select',
                        'options' => $this->get_taxonomies_list(),
                        'below' => 'The category type used to set the status of posts',

                    ]
                )   
            ],
            [
                'id' => 'fields',
                'label' => 'Field Mapping',
                'fields'=> array(
                    [
                        'id' => 'PREFIX_fields',
                        'label' => 'Map local fields to external fields.&#10;Separate field names with a colon, one per line.&#10;&#10;Example:&#10;local_field:external_field',
                        'type' => 'textarea',
                        'size' => '10',
                        'placeholder' => '',
                    ],
                    
                )
            ],
        );
    }
    
    /**
     * @return array of plugin settings
     */

    public function get_settings(){
        if (!empty($this->settings) ) {
            return $this->settings;
        } 
        
        $keys = $this->get_option_keys();
        foreach ($keys as $key => $option) {
            $this->settings[$key] = get_option( $option );
        }
        
        return $this->settings;
    }

    public function get_terms_list()
	{
		$terms = get_terms([
			'taxonomy' => $this->settings['taxonomy'],
			'hide_empty' => true
		]);

		$terms_list = [];

		if (!empty($terms)) {
			foreach ($terms as $term) {
				$terms_list[$term->slug] = $term->name;
			}
		}

		return $terms_list;
	}


} // End of class