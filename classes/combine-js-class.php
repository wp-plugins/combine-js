<?php

class CombineJS {

        /**
        *Variables
        */
	const nspace = 'combine-js';
	const pname = 'Combine JS';
	const version = 0.4;

        protected $_plugin_file;
        protected $_plugin_dir;
        protected $_plugin_path;
        protected $_plugin_url;

	var $cachetime = '';
	var $create_cache = false;
	var $upload_path = '';
	var $upload_uri = '';
	var $js_domain = '';
	var $js_path = '';
	var $js_path_footer = '';
        var $js_uri = '';
	var $js_uri_footer = '';
        var $js_path_tmp = '';
	var $js_path_tmp_footer = '';
	var $js_token = '';
	var $js_settings_path = '';
	var $settings_fields = array();
	var $settings_data = array();
	var $js_files_ignore = array( 'admin-bar.js' );
	var $js_handles_found = array();
	var $js_footer_handles_found = array();
	var $debug = false;

        /**
        *Constructor
        *
        *@return void
        *@since 0.1
        */
        function __construct() {}

	/**
        *Init function
        *
        *@return void
        *@since 0.1
        */
        function init() {

                // internationalize

                add_action( 'init', array( &$this, 'internationalize' ) );

                // settings data

                $this->settings_data = unserialize( get_option( self::nspace . '-settings' ) );
		$this->cachetime = $this->get_settings_value( 'cachetime' );
		if ( ! @strlen( $this->cachetime ) ) $this->cachetime = 300;
		$this->js_domain = $this->get_settings_value( 'js_domain' );
		if ( ! @strlen( $this->js_domain ) ) $this->js_domain = get_option( 'siteurl' );
                if ( $this->settings_data['debug'] == 'Yes' ) $this->debug = true;
		if ( ! $this->settings_data['compress'] ) $this->settings_data['compress'] = 'Yes';

                // add ignore files

                $ignore_list = explode( "\n", $this->settings_data['ignore_files'] );
                foreach ( $ignore_list as $item ) $this->js_files_ignore[] = $item;
		$this->debug( 'Ignore files: ' . implode( ', ', $this->js_files_ignore ) );

		// check upload dirs

		$this->check_upload_dirs();

		if ( is_admin() ) {

			// add settings page

			add_action( 'admin_menu', array( &$this, 'add_settings_page' ), 30 );

			// settings fields

			$this->settings_fields = array(
							'legend_1' => array(
									'label' => 'General Settings',
									'type' => 'legend'
									),
							'js_domain' => array(
									'label' => 'JavaScript Domain',
									'type' => 'text',
									'default' => get_option( 'siteurl' )
									),
							'cachetime' => array(
									'label' => 'Cache Expiration',
									'instruction' => 'How often to refresh JS files in seconds.',
									'type' => 'select',
									'values' => array( '60' => '1 minute', '300' => '5 minutes', '900' => '15 minutes', '1800' => '30 minutes', '3600' => '1 hour' ),
									'default' => '300'
									),
							'htaccess_user_pw' => array(
                                                                        'label' => 'Username and Password',
									'instruction' => 'Use when site is accessed behind htaccess authentication -- syntax: username:password.',
                                                                        'type' => 'text',
                                                                        'default' => 'username:password'
                                                                        ),
							'ignore_files' => array(
                                                                        'label' => 'JS Files to Ignore',
                                                                        'instruction' => 'Enter one per line. Only use the name of the JS file (like main.js). You can be more specific about what JS file to ignore by specifying the plugin and then the JS file (like plugin:main.js).',
                                                                        'type' => 'textarea'
                                                                        ),
                                                        'compress' => array(
                                                                        'label' => 'GZip Compress JS output?',
                                                                        'type' => 'select',
                                                                        'values' => array( 'No' => 'No', 'Yes' => 'Yes' ),
                                                                        'default' => 'Yes'
                                                                        ),
                                                        'debug' => array(
                                                                        'label' => 'Turn on debugging?',
                                                                        'type' => 'select',
                                                                        'values' => array( 'No' => 'No', 'Yes' => 'Yes' ),
                                                                        'default' => 'No'
                                                                        )
						);
		}
		elseif ( strstr( $_SERVER['REQUEST_URI'], 'wp-login' ) || strstr( $_SERVER['REQUEST_URI'], 'gf_page=' ) || strstr( $_SERVER['REQUEST_URI'], 'preview=' ) ) {}
		elseif ( ! file_exists( $this->js_settings_path ) ) {}
		else {

			// gather and install functions

			add_action( 'wp_print_scripts', array( $this, 'gather_js' ), 500 );
			add_action( 'wp_head', array( $this, 'install_combined_js' ), 500 );
			add_action( 'wp_footer', array( $this, 'install_combined_js_footer' ), 500 );

			// get rid of browser prefetching of next page from link rel="next" tag

			remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
			remove_action( 'wp_head', 'adjacent_posts_rel_link' );
		}
        }

	/**
	*Check upload dirs
	*
	*@return void
	*@since 0.3
	*/
	function check_upload_dirs() {

		// make sure upload dirs exist and set file path and uri

		$upload_dir = wp_upload_dir();
		if ( ! file_exists( $upload_dir['basedir'] ) ) mkdir ( $upload_dir['basedir'] );
		$this->upload_path = $upload_dir['basedir'] . '/' . self::nspace . '/';
		$this->upload_uri = $upload_dir['baseurl'] . '/' . self::nspace . '/';
		if ( ! file_exists( $this->upload_path ) ) mkdir ( $this->upload_path );

		// create tmp directory

		$this->create_tmp_dir();

		// write settings to temp file so that js script has access to WP settings without having to load WP infrastructure

		$this->js_settings_path = $this->tmp_dir . $_SERVER['HTTP_HOST'] . '-settings.dat';
		if ( $this->cache_expired( $this->js_settings_path ) ) {
			$args = array( 'upload_path' => $this->upload_path, 'compress' => $this->settings_data['compress'] );
			$this->write_file( $this->js_settings_path, serialize( $args ) );
		}
	}

	/**
	*Create tmp dir
	*
	*@return void
	*@since 0.3
	*/
	function create_tmp_dir() {
		$this->tmp_dir = $this->get_plugin_path() . '/tmp/';
		if ( ! file_exists( $this->tmp_dir ) ) mkdir ( $this->tmp_dir );
	}

        /**
        *Translation
        *
        *@return void
        *@since 0.1
        */
        function internationalize() {
                load_plugin_textdomain( self::nspace, false, $this->get_plugin_dir() . '/lang' );
        }

        /**
        *Cached expired
        *
        *@return boolean
        *@since 0.1
        */
	function cache_expired ( $path ) {
		$mtime = 0;
		if( file_exists( $path ) ) $mtime = @filemtime( $path );
		if ( ( time() - $mtime ) > $this->cachetime ) return true;
		return false;
	}

        /**
        *File exists
        *
        *@return boolean
        *@since 0.1
        */
	function file_exists ( $src ) {
		if ( @strlen( $src ) && file_exists( ABSPATH . $src ) ) return true;
		return false;
	}

        /**
        *Get file from source
        *
        *@return string
        *@since 0.1
        */
	function get_file_from_src ( $src ) {
		$frags = explode( '/', $src );
		return $frags[count( $frags ) -1];
	}

        /**
        *Gather javascript
        *
        *@return void
        *@since 0.1
        */
        function gather_js () {

		$this->debug( 'Function gather_js' );

                global $wp_scripts;

		// loop through all scripts and store them in options

		$queue = $wp_scripts->queue;
                $wp_scripts->all_deps( $queue );
                $to_do = $wp_scripts->to_do;
                foreach ( $to_do as $key => $handle ) {

			// keep track of footer files

			if ( $wp_scripts->registered[$handle]->extra['group'] ) $this->js_footer_handles_found[$handle] = $js_src;
                        $js_src = $this->strip_domain( $wp_scripts->registered[$handle]->src );
                        $js_file = $this->get_file_from_src( $js_src );
                        if ( $wp_scripts->registered[$handle]->extra['data'] ) {
                                echo "<script type='text/javascript'>/* <![CDATA[ */ ";
                                echo $this->compress( $wp_scripts->registered[$handle]->extra['data'] ) . "\n";
                                echo " /* ]]> */ </script>";
                        }
                        elseif ( $wp_scripts->registered[$handle]->extra['l10n'] ) {
                                $vars = array();
                                foreach ( $wp_scripts->registered[$handle]->extra['l10n'][1] as $key => $val ) {
                                        $vars[] = "\t\t\t" . $key . ': "' . $val . '"';
                                }
				$extra = "<script type='text/javascript'>/* <![CDATA[ */ ";
				$extra .= "var " . $wp_scripts->registered[$handle]->extra['l10n'][0] . " = { " . implode( ",\n", $vars ) . " }; ";
				$extra .= " /* ]]> */ </script>";
				echo $this->compress( $extra ) . "\n";
                        }

			// get context (plugin or theme)

			$context = $this->get_context( $js_src );
			$this->debug( '     -> context ' . $context );

			// don't include js that we are to ignore

			$this->debug( 'ignore: ' . $context . ':' . $js_file );
                        if( ! in_array( $context . ':' . $js_file, $this->js_files_ignore ) && ! in_array( $js_file, $this->js_files_ignore ) 
				&& @strlen( $js_src ) && $this->file_exists( $js_src ) ) {
				$this->debug( '     -> found ' . $js_src );
				$this->js_handles_found[$handle] = $js_src;
				unset( $wp_scripts->to_do[$key] );
                        }
                }

                // get name of file (token) based on md5 hash of js handles

                $this->js_token = md5( implode( '', array_keys( $this->js_handles_found ) ) );

                // set paths

                $this->js_path = $this->upload_path . $this->js_token . '.js';
                $this->js_path_footer = $this->upload_path . $this->js_token . '-footer.js';
                $this->js_uri = $this->upload_uri . $this->js_token . '.js';
                $this->js_uri_footer = $this->upload_uri . $this->js_token . '-footer.js';
                $this->js_path_tmp = $this->js_path . '.tmp';
                $this->js_path_tmp_footer = $this->js_path_footer . '.tmp';

		if ( $this->cache_expired( $this->js_path ) && $this->cache_expired( $this->js_path_tmp )
			&& $this->cache_expired( $this->js_path_footer ) && $this->cache_expired( $this->js_path_tmp_footer ) )  {
			$this->create_cache = true;
		}

		// loop through and unset scripts

		foreach ( $to_do as $key => $handle ) {
			$js_src = $this->strip_domain( $wp_scripts->registered[$handle]->src );
			$js_file = $this->get_file_from_src( $js_src );
			$context = $this->get_context( $js_src );
			if( ! in_array( $context . ':' . $js_file, $this->js_files_ignore ) && ! in_array( $js_file, $this->js_files_ignore )  && $this->file_exists( $js_src ) ) {
				$this->debug( 'dereg: ' . $handle );
				wp_deregister_script( $handle );
			}
		}

		foreach ( $wp_scripts->queue as $key => $handle ) {
			if ( isset( $this->js_handles_found[$handle] ) ) {
				$this->debug( 'unset: ' . $handle );
				unset( $wp_scripts->queue[$key] );
			}
		}
        }

	/**
        *Get context function
        *
        *@return string
        *@since 0.1
        */
	function get_context( $js_src = '' ) {
		preg_match( "/(plugins|themes)\/(.*)\/.*/", $js_src, $jmatches );
		$context = '';
		if ( $jmatches ) {
			$context = $jmatches[2];
			if ( strstr( $context, '/' ) ) $context = dirname( $jmatches[2] );
		}
		return $context;
	}

	/**
        *Debug function
        *
        *@return void
        *@since 0.1
        */
	function debug ( $msg ) {
		if ( $this->debug ) error_log( 'DEBUG: ' . $msg );
	}

        /**
        *Combine javascript
        *
        *@return void
        *@since 0.1
        */
	function combine_js () {

		$this->debug( 'Function combine_js' );

		// if no scripts found, return

		if ( ! @count( @array_keys( $this->js_handles_found ) ) ) {
			$this->debug( '     -> no handles found' );
			return;
		}

		// loop through found scripts and cache them to file system

		$header_content = $footer_content = '';
		foreach ( $this->js_handles_found as $handle => $js_src ) {
			$js_file = $this->get_file_from_src( $js_src );
			$context = $this->get_context( $js_src );
			if( $this->file_exists( $js_src ) && ! in_array( $context . ':' . $js_file, $this->js_files_ignore ) 
				&& ! in_array( $js_file, $this->js_files_ignore ) ) {
				if ( $this->create_cache && $this->cache_expired( $this->js_path ) ) {

					$this->debug( "     -> caching $handle" );

					// if file is a PHP script, pull content via curl

					$js_content = '';
					if ( preg_match( "/\.php/", $js_src ) ) $js_content = $this->curl_file_get_contents ( $js_src );
					else $js_content = file_get_contents( ABSPATH . $js_src );
					if ( $this->js_footer_handles_found[$handle] ) {
						$footer_content .= $this->compress( $js_content, $handle, $js_src );
						unset( $this->js_footer_handles_found[$handle] );
                                        }
                                        else $header_content .= $this->compress( $js_content, $handle, $js_src );
					$this->debug( "$in_footer: " . $js_src );
				}
			}
			else $this->debug( 'SRC NOT FOUND: ' . ABSPATH . $js_src );
		}

		// cache content to file system

		$this->cache_content( $header_content, $footer_content );
	}

        /**
        *Cache content
        *
        *@return void
        *@since 0.1
        */
	function cache_content ( $header_content, $footer_content ) {
		$this->debug( 'Function cache_content' );
		if ( @strlen( $header_content ) || @strlen( $footer_content ) ) {
			if ( @strlen( $header_content ) ) $this->cache( 'js_path_tmp', $header_content );
			if ( @strlen( $footer_content ) ) $this->cache( 'js_path_tmp_footer', $footer_content );
		}
	}

        /**
        *Write data to file system
        *
        *@return void
        *@since 0.1
        */
	function cache( $tmp_file, $content ) {
		if ( ! file_exists( $this->$tmp_file ) ) $this->write_file( $this->$tmp_file, $content );
	}

	/**
	*Write file
	*
	*@return void
	*@since 0.3
	*/
	function write_file ( $file, $content ) {
		$fp = fopen( $file, "w" );
		$this->debug( $file . ' created' );
		if ( flock( $fp, LOCK_EX ) ) { // do an exclusive lock
			fwrite( $fp, $content );
			flock( $fp, LOCK_UN ); // release the lock
		}
		fclose( $fp );
	}

        /**
        *Get file content via curl
        *
        *@return string
        *@since 0.1
        */
	function curl_file_get_contents ( $src ) {
                $url = trim( $src );
		$url = preg_replace( "/http(|s):\/\//", "http://" . $this->get_settings_value( 'htaccess_user_pw' ) . "@", $url );
                $c = curl_init();
                curl_setopt( $c, CURLOPT_URL, $url );
                curl_setopt( $c, CURLOPT_FAILONERROR, false );
                curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
                curl_setopt( $c, CURLOPT_VERBOSE, false );
                curl_setopt( $c, CURLOPT_SSL_VERIFYPEER, false );
                curl_setopt( $c, CURLOPT_SSL_VERIFYHOST, false );
                if( count( $header ) ) {
                        curl_setopt ( $c, CURLOPT_HTTPHEADER, $header );
                }
                $contents = curl_exec( $c );
                curl_close( $c );
		return $contents;
	}

        /**
        *Strip domain from path
        *
        *@return string
        *@since 0.1
        */
	function strip_domain( $src ) {
		$src = str_replace( array( 'http://', 'https://' ), array( '', '' ), $src );
		$frags = explode( '/', $src );
		array_shift( $frags );
		return implode( '/', $frags );
	}

        /**
        *Minify content
        *
        *@return string
        *@since 0.1
        */
        function compress( $content='', $handle='', $src='' ) {
                $this->debug( '     -> compress ' . $handle );
                $minify = true;
                if ( preg_match( "/(\-|\.)min/", $src ) ) $minify = false;
                if ( $minify ) {
                        require_once $this->get_plugin_path() . '/classes/jsmin.php';
                        return JSMin::minify( $content );
                }
                else return $content . "\n";
        }

        /**
        *Move temp file cache to actual file cache
        *
        *@return void
        *@since 0.1
        */
	function install_combined_js () {

		// combine javascript

		$this->combine_js();

		// move temp file to real path

		$this->debug( 'Function install_combined_js' );
		if ( $this->create_cache && file_exists( $this->js_path_tmp ) && $this->cache_expired( $this->js_path ) ) {
			$this->debug( '     -> move ' . $this->js_path_tmp . ", " . $this->js_path );
			@rename( $this->js_path_tmp, $this->js_path );
		}
		else $this->debug( '     -> no header install' );

		// add script tag

		$this->debug( 'Function add js to header' );
		if ( file_exists( $this->js_path ) ) {
			$this->debug( '     -> add js tag to header' );
			echo "\t\t" . '<script type="text/javascript" src="' . str_replace( get_option( 'siteurl' ), $this->js_domain, $this->get_plugin_url() . 'js.php?token=' . $this->js_token . '&#038;ver=' . self::version ) . '" charset="UTF-8"></script>' . "\n";
		}
	}

	/**
        *Move temp file cache to actual file cache
        *
        *@return void
        *@since 0.1
        */
        function install_combined_js_footer () {
		$this->debug( 'Function install_combined_js_footer' );
                if ( $this->create_cache && file_exists( $this->js_path_tmp_footer ) && $this->cache_expired( $this->js_path_footer ) ) {
                        $this->debug( '     -> move ' . $this->js_path_tmp_footer . ", " . $this->js_path_footer );
                        @rename( $this->js_path_tmp_footer, $this->js_path_footer );
                }
                else $this->debug( '     -> no footer install' );

		// add script tag

		$this->debug( 'Function add_combined_js_footer' );
		if ( file_exists( $this->js_path_footer ) ) {
			$this->debug( '     -> add js tag to footer' );
			echo "\t\t" . '<script type="text/javascript" src="' . str_replace( get_option( 'siteurl' ), $this->js_domain, $this->get_plugin_url() . 'js.php?token=' . $this->js_token . '&#038;footer=1&#038;ver=' . self::version ) . '"></script>' . "\n";
		}

		// add handles

		foreach ( $this->js_footer_handles_found as $handle => $src ) {
			$this->debug( '     -> add ignore footer handles' );
                        echo "\t\t" . '<script type="text/javascript" src="' . str_replace( get_option( 'siteurl' ), $this->js_domain, $src ) . '"></script>' . "\n";
		}

        }

        /**
        *Add settings page
        *
        *@return void
        *@since 0.1
        */
        function add_settings_page () {
                if ( current_user_can( 'manage_options' ) ) {
                        add_options_page( self::pname, self::pname, 'manage_options', self::nspace . '-settings', array( &$this, 'settings_page' ) );
                }
        }

        /**
        *Settings page
        *
        *@return void
        *@since 0.1
        */
        function settings_page () {
                if($_POST['combine-js_update_settings']) $this->update_settings();
                $this->show_settings_form();
        }

        /**
        *Show settings form
        *
        *@return void
        *@since 0.1
        */
        function show_settings_form () {
                include( $this->get_plugin_path() . '/views/admin_settings_form.php' );
        }

        /**
        *Get single value from unserialized data
        *
        *@return string
        *@since 0.1
        */
        function get_settings_value( $key = '' ) {
                return $this->settings_data[$key];
        }

        /**
        *Remove option when plugin is deactivated
        *
        *@return void
        *@since 0.1
        */
        function delete_settings () {
                delete_option( $this->option_key );
        }

        /**
        *Is associative array function
        *
        *@return string
        *@since 0.1
        */
        function is_assoc ( $arr ) {
                if ( isset ( $arr[0] ) ) return false;
                return true;
        }

        /**
        *Display a select form element
        *
        *@return string
        *@since 0.1
        */
        function select_field( $name, $values, $value, $use_label = false, $default_value = '', $custom_label = '' ) {
                ob_start();
                $label = '-- please make a selection --';
                if (@strlen($custom_label)) {
                        $label = $custom_label;
                }

                // convert indexed array into associative

                if ( ! $this->is_assoc( $values ) ) {
                        $tmp_values = $values;
                        $values = array();
                        foreach ( $tmp_values as $tmp_value ) {
                                $values[$tmp_value] = $tmp_value;
                        }
                }
?>
        <select name="<?php echo $name; ?>" id="<?php echo $name; ?>">
                <?php if ( $use_label ): ?>
                <option value=""><?php echo $label; ?></option>

                <?php endif; ?>
                <?php foreach ( $values as $val => $label ) : ?>
                        <option value="<?php echo $val; ?>"<?php if ($value == $val || ( $default_value == $val && @strlen( $default_value ) && ! @strlen( $value ) ) ) : ?> selected="selected"<?php endif; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
        </select>
<?php
                $content = ob_get_contents();
                ob_end_clean();
                return $content;
        }

        /**
        *Update settings form
        *
        *@return void
        *@since 0.1
        */
        function update_settings () {
                $data = array();
                foreach( $this->settings_fields as $key => $val ) {
                        if( $val['type'] != 'legend' ) $data[$key] = $_POST[$key];
                }
                $this->set_settings( $data );
		$this->delete_cache();
        }

        /**
        *Update serialized array option
        *
        *@return void
        *@since 0.1
        */
        function set_settings ( $data ) {
                update_option( self::nspace . '-settings', serialize( $data ) );
                $this->settings_data = $data;
        }

	/**
        *Delete cache
        *
        *@return void
        *@since 0.1
        */
	function delete_cache () {
		$this->debug( 'Function delete_cache' );
		$this->debug( 'Deleting files in: ' . $this->upload_path );
		foreach( glob( $this->upload_path . "/*.*" ) as $file ) {
			$this->debug( "	" . 'Deleting file: ' . $file );
			unlink( $file );
		}
                if ( function_exists( 'wp_cache_clear_cache' ) ) wp_cache_clear_cache();
	}

        /**
        *Set plugin file
        *
        *@return void
        *@since 0.1
        */
        function set_plugin_file( $plugin_file ) {
                $this->_plugin_file = $plugin_file;
        }

        /**
        *Get plugin file
        *
        *@return string
        *@since 0.1
        */
        function get_plugin_file() {
                return $this->_plugin_file;
        }

        /**
        *Set plugin directory
        *
        *@return void
        *@since 0.1
        */
        function set_plugin_dir( $plugin_dir ) {
                $this->_plugin_dir = $plugin_dir;
        }

        /**
        *Get plugin directory
        *
        *@return string
        *@since 0.1
        */
        function get_plugin_dir() {
                return $this->_plugin_dir;
        }

        /**
        *Set plugin file path
        *
        *@return void
        *@since 0.1
        */
        function set_plugin_path( $plugin_path ) {
                $this->_plugin_path = $plugin_path;
        }

        /**
        *Get plugin file path
        *
        *@return string
        *@since 0.1
        */
        function get_plugin_path() {
                return $this->_plugin_path;
        }

	/**
        *Set plugin URL
        *
        *@return void
        *@since 0.1
        */
        function set_plugin_url( $plugin_url ) {
                $this->_plugin_url = $plugin_url;
        }

        /**
        *Get plugin URL
        *
        *@return string
        *@since 0.1
        */
        function get_plugin_url() {
                return $this->_plugin_url;
        }

}

?>
