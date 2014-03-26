<?php
/*
Plugin Name: JC Importer - Post Datasource
Description: Add Post Datasource to JC Importer
Author: James Collings <james@jclabs.co.uk>
Version: 0.0.1
*/
		
class JCI_Post_Datasource{

	public function __construct(){

		add_filter( 'jci/setup_forms', array($this, 'setup_forms'), 10, 2 );
		add_filter( 'jci/process_create_form', array($this, 'process_create_form'), 10, 2 );
        add_filter( 'jci/process_create_file', array($this, 'process_create_file'), 10, 2 );
        add_filter( 'jci/process_edit_form', array($this, 'process_edit_form'), 10, 1 );
        add_filter( 'jci/importer_save', array($this, 'importer_save'), 10, 3);

        add_action( 'jci/output_datasource_option', array($this, 'add_datasource_option'));
        add_action( 'jci/output_datasource_section', array($this, 'output_create_form'));

		add_action( 'jci/importer_setting_section', array($this, 'output_edit_form'), 10);

		// run post
		add_filter( 'rewrite_rules_array', array( $this , 'rewrite_url' ) );
        add_action( 'query_vars' , array( $this, 'register_query_vars' ) );
        add_action( 'template_redirect' , array( $this, 'template_redirect' ) );

	}

	/**
	 * Setup Post Datasource form validation
	 * @param  array  $forms       
	 * @param  string $import_type 
	 * @return array
	 */
	public function setup_forms($forms = array(), $import_type){

		if($import_type == 'post'){
	        $forms['CreateImporter']['validation']['post_field'] = array(
	            'rule' => array('required'),
	            'message' => 'This Field is required',
	        );
	    }

        return $forms;
	}

	/**
	 * Save Add Datasource Settings
	 * @param  array  $general     
	 * @param  string $import_type 
	 * @return array
	 */
	public function process_create_form($general = array(), $import_type){

		if($import_type == 'post'){
			$general['post_field_type'] = $_POST['jc-importer_post_field_type'];
	        $general['post_field'] = $_POST['jc-importer_post_field'];
	        $general['post_key'] = substr(md5(time()), 0, 8 );
		}
		return $general;
	}

	/**
	 * Process file create to set import type
	 * @param  array  $result      
	 * @param  string $import_type 
	 * @return array
	 */
	public function process_create_file($result = array(), $import_type){

		if($import_type == 'post'){
			$result = array(
	            'type' => $_POST['jc-importer_post_type'],
	            'dest' => '', //$_POST['jc-importer_post_field']
	            'id' => 0
	        );
		}
		return $result;
	}

	/**
	 * Add Datasource to datasource list
	 * @return  void
	 */
	public function add_datasource_option(){
		echo JCI_FormHelper::radio('import_type', array('label' => 'POST' , 'value' => 'post', 'class' => 'toggle-fields'));
	}

	/**
	 * Display Datasource settings
	 * @return void
	 */
	public function output_create_form(){

		echo '<div class="hidden show-post toggle-field">';
		echo '<p>Download a file Posted to a url</p>';
		echo JCI_FormHelper::select('post_field_type', array('label' => 'Field Type', 'options' => array('post' => '$_POST', 'file' => '$_FILES')));
		echo JCI_FormHelper::text('post_field', array('label' => 'Field Name'));
		echo JCI_FormHelper::select('post_type', array('label' => 'Import Type', 'options' => array('csv' => 'CSV', 'xml' => 'XML'), 'default' => 'csv'));
		echo '</div>';
	}

	/**
	 * Process Datasource fields
	 * @param  array  $settings 
	 * @return array
	 */
	public function process_edit_form($settings = array()){

		if(isset($_POST['jc-importer_post_field'])){
            $settings['post_field'] = $_POST['jc-importer_post_field'];
        }
        if(isset($_POST['jc-importer_post_field_type'])){
            $settings['post_field_type'] = $_POST['jc-importer_post_field_type'];
        }
        if(isset($_POST['jc-importer_template_type'])){
            $settings['template_type'] = $_POST['jc-importer_template_type'];
        }

		return $settings;
	}

	/**
	 * Display Edit Section
	 * @return void
	 */
	public function output_edit_form(){

		global $jcimporter;
		$id = $jcimporter->importer->get_ID();
		$import_type = $jcimporter->importer->get_import_type();

		if($import_type != 'post')
			return;

		?>
		<div class="jci-group-post jci-group-section" data-section-id="post">
			<div class="post_settings">
				<h4>Post Settings</h4>
				<?php
				$post_settings = ImporterModel::getImporterMetaArr($id, array('_import_settings', 'general'));

				if ( get_option('permalink_structure') ) {
					echo site_url( '/jcimporter/'.$id.'/'.$post_settings['post_key'] );
				}else{
					echo site_url( "?import_key=".$post_settings['post_key']."&importer_id=$id");
				}
				
				echo JCI_FormHelper::text('post_field', array('label' => 'Field Name', 'default' => $post_settings['post_field']));
				echo JCI_FormHelper::select('post_field_type', array('label' => 'Field Type', 'options' => array('post' => '$_POST', 'file' => '$_FILES'), 'default' => $post_settings['post_field_type']));
				?>
			</div>
		</div>
		<?php
	}

	/**
     * Setup Import Url
     * @param  array
     * @return array
     */
    public function rewrite_url( $rules = array() ){
        return array_merge(array('jcimporter/(.+?)/(.+?)/?$' => 'index.php?importer_id=$matches[1]&import_key=$matches[2]'), $rules);
    }

    /**
     * Add import key to WP query vars
     * @param  array
     * @return array
     */
    public function register_query_vars( $public_query_vars ){
        $public_query_vars[] = 'import_key';
        $public_query_vars[] = 'importer_id';
        return $public_query_vars;
    }

    /**
     * Add url to run POST Import
     * @return void
     */
    public function template_redirect(){

        global $wp_query;
        global $jcimporter;

        $import_key = get_query_var( 'import_key' );
        $importer_id = get_query_var( 'importer_id' );

        if(isset($import_key) && !empty($import_key)){

        	$importer = ImporterModel::getImporter($importer_id);
        	$settings = ImporterModel::getImportSettings($importer_id);
            
            $field = $settings['general']['post_field'];
            $key = $settings['general']['post_key'];
            $field_type = $settings['general']['post_field_type'];

            if($settings['import_type'] == 'post' && $import_key == $key && $importer && (isset($_FILES[$field]) || isset($_POST[$field]) )){
                if($importer->have_posts()){

                    if($field_type == 'file' && isset($_FILES[$field])){

                        // POSTED FILE
                        $attach = new JC_Upload_Attachments();
                        $result = $attach->attach_upload( $importer_id, $_FILES[$field]);

                    }elseif($field_type == 'post' && isset($_POST[$field])){

                        // POSTED STRING
                        $attach = new JC_String_Attachments();
                        $result = $attach->attach_string($importer_id, $_POST[$field]);
                    }
                    
                    $settings['import_file'] = $result['id'];
                    update_post_meta( $importer_id, '_import_settings', $settings, ImporterModel::getImportSettings($importer_id) );

                    ImporterModel::clearImportSettings();

                    $jcimporter->importer = new JC_Importer_Core($importer_id);
                    if($import_result = $jcimporter->importer->run_import()){

                        $errors = array();
                        foreach($import_result as $record => $result){
                            if($result['_jci_status'] == 'E'){
                                $errors[] = array(
                                    'record' => $record,
                                    'msg' => $result['_jci_msg']
                                );
                            }
                        }

                        if(!empty($errors)){
                            header("HTTP/1.0 400 Bad Request");
                            echo "<!DOCTYPE html>
                            <html>
                            <head>
                                <title>Import Errors: ".count($errors)."</title>
                            </head>
                            <body>";
                            echo "<h1>Import Errors: ".count($errors)."</h1>";
                            echo "<ul>";
                            foreach($errors as $error){
                                echo "<li>Record: ".$error['record']." , ".$error['msg']."</li>";
                            }
                            echo "</ul>";

                            echo "</body>
                            </html>";
                            die();
                        }
                        
                        // import successful
                        do_action( 'jci/after_import_run' );    
                        die();
                    }
                }
            }

            header("HTTP/1.0 400 Bad Request");
            die(); 
        }
    }

    public function importer_save($settings, $import_type, $data){

    	if($import_type == 'post'){

    		// save post_field
			if(isset($data['settings']['post_field'])){
				$settings['general']['post_field'] = $data['settings']['post_field'];
			}

			if(isset($data['settings']['post_field_type'])){
				$settings['general']['post_field_type'] = $data['settings']['post_field_type'];
			}

    	}

    	return $settings;
    }
}

// init after JC Importer 
add_action( 'jci/init', 'jci_init_post_datasource');
function jci_init_post_datasource(){
	new JCI_Post_Datasource();
}