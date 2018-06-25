<?php
class DatadogSettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
    const DATADOG__SETTING_ADMIN = 'datadog-setting-admin';
    const DATADOG__SETTING_GROUP = 'datadog-setting-group';
    const DATADOG__SETTING_TRACER = 'datadog-setting-tracer';
    const DATADOG__SETTING_METRIC = 'datadog-setting-metric';


    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin', 
            'Datadog Settings', 
            'manage_options', 
            self::DATADOG__SETTING_ADMIN, 
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( DATADOG__OPTION_NAME );
        // var_dump($this->options); die;
        ?>
        <div class="wrap">
            <h1>Settings</h1>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( self::DATADOG__SETTING_GROUP );
                do_settings_sections( self::DATADOG__SETTING_ADMIN );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            self::DATADOG__SETTING_GROUP, // Option group
            DATADOG__OPTION_NAME, // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            self::DATADOG__SETTING_TRACER, // ID
            'Tracer Setting', // Title
            array( $this, 'print_section_info' ), // Callback
            self::DATADOG__SETTING_ADMIN // Page
        );  

        add_settings_field(
            'tracer_enable', // ID
            'Enable', // Title 
            array( $this, 'tracer_enable_callback' ), // Callback
            self::DATADOG__SETTING_ADMIN, // Page
            self::DATADOG__SETTING_TRACER // Section           
        );    

        add_settings_field(
            'host', // ID
            'Host', // Title 
            array( $this, 'tracer_host_callback' ), // Callback
            self::DATADOG__SETTING_ADMIN, // Page
            self::DATADOG__SETTING_TRACER // Section           
        );      

        add_settings_field(
            'port', 
            'Port', 
            array( $this, 'tracer_port_callback' ), 
            self::DATADOG__SETTING_ADMIN, 
            self::DATADOG__SETTING_TRACER
        ); 

        add_settings_field(
            'service', 
            'Service', 
            array( $this, 'tracer_service_callback' ), 
            self::DATADOG__SETTING_ADMIN, 
            self::DATADOG__SETTING_TRACER
        );     

        add_settings_section(
            self::DATADOG__SETTING_METRIC, // ID
            'Metric Setting', // Title
            array( $this, 'print_section_info' ), // Callback
            self::DATADOG__SETTING_ADMIN // Page
        );   

        add_settings_field(
            'metric_enable', // ID
            'Enable', // Title 
            array( $this, 'metric_enable_callback' ), // Callback
            self::DATADOG__SETTING_ADMIN, // Page
            self::DATADOG__SETTING_METRIC // Section           
        ); 

        add_settings_field(
            'host', // ID
            'Host', // Title 
            array( $this, 'metric_host_callback' ), // Callback
            self::DATADOG__SETTING_ADMIN, // Page
            self::DATADOG__SETTING_METRIC // Section           
        );      

        add_settings_field(
            'port', 
            'Port', 
            array( $this, 'metric_port_callback' ), 
            self::DATADOG__SETTING_ADMIN, 
            self::DATADOG__SETTING_METRIC
        );  

        add_settings_field(
            'prefix', // ID
            'Prefix', // Title 
            array( $this, 'metric_prefix_callback' ), // Callback
            self::DATADOG__SETTING_ADMIN, // Page
            self::DATADOG__SETTING_METRIC // Section           
        );   
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['tracer_port'] ) )
            $new_input['tracer_port'] = absint( $input['tracer_port'] );

        if( isset( $input['tracer_host'] ) )
            $new_input['tracer_host'] = sanitize_text_field( $input['tracer_host'] );

        if( isset( $input['tracer_service'] ) )
            $new_input['tracer_service'] = sanitize_text_field( $input['tracer_service'] );

        if( isset( $input['metric_prefix'] ) )
            $new_input['metric_prefix'] = sanitize_text_field( $input['metric_prefix'] );

        $new_input['tracer_enable'] = 0;
        if( isset( $input['tracer_enable'] ) )
            $new_input['tracer_enable'] = 1;

        $new_input['metric_enable'] = 0;
        if( isset( $input['metric_enable'] ) )
            $new_input['metric_enable'] = 1;

        if( isset( $input['metric_port'] ) )
            $new_input['metric_port'] = absint( $input['metric_port'] );

        if( isset( $input['metric_host'] ) )
            $new_input['metric_host'] = sanitize_text_field( $input['metric_host'] );

        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Enter your settings below:';
    }

    public function tracer_enable_callback()
    {
        printf(
            '<input type="checkbox" id="tracer_enable" name="' . DATADOG__OPTION_NAME .'[tracer_enable]" value="1" %s />', checked( 1, $this->options['tracer_enable'], false )
        );
    }

    public function tracer_host_callback()
    {
        printf(
            '<input type="text" id="tracer_host" name="' . DATADOG__OPTION_NAME .'[tracer_host]" value="%s" />',
            isset( $this->options['tracer_host'] ) ? esc_attr( $this->options['tracer_host']) : ''
        );
    }

    public function tracer_port_callback()
    {
        printf(
            '<input type="text" id="tracer_port" name="' . DATADOG__OPTION_NAME .'[tracer_port]" value="%s" />',
            isset( $this->options['tracer_port'] ) ? esc_attr( $this->options['tracer_port']) : ''
        );
    }

    public function tracer_service_callback()
    {
        printf(
            '<input type="text" id="tracer_service" name="' . DATADOG__OPTION_NAME .'[tracer_service]" value="%s" />',
            isset( $this->options['tracer_service'] ) ? esc_attr( $this->options['tracer_service']) : ''
        );
    }

    public function metric_enable_callback()
    {
        printf(
            '<input type="checkbox" id="metric_enable" name="' . DATADOG__OPTION_NAME .'[metric_enable]" value="1" %s />', checked( 1, $this->options['metric_enable'], false )
        );
    }

    public function metric_host_callback()
    {
        printf(
            '<input type="text" id="metric_host" name="' . DATADOG__OPTION_NAME .'[metric_host]" value="%s" />',
            isset( $this->options['metric_host'] ) ? esc_attr( $this->options['metric_host']) : ''
        );
    }

    public function metric_port_callback()
    {
        printf(
            '<input type="text" id="metric_port" name="' . DATADOG__OPTION_NAME .'[metric_port]" value="%s" />',
            isset( $this->options['metric_port'] ) ? esc_attr( $this->options['metric_port']) : ''
        );
    }

    public function metric_prefix_callback()
    {
        printf(
            '<input type="text" id="metric_prefix" name="' . DATADOG__OPTION_NAME .'[metric_prefix]" value="%s" />',
            isset( $this->options['metric_prefix'] ) ? esc_attr( $this->options['metric_prefix']) : ''
        );
    }
}

if( is_admin() )
    $datadogSettingsPage = new DatadogSettingsPage();