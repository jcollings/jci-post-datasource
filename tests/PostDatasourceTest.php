<?php 
/**
 * XML Parser Unit Tests
 */
class PostDatasourceTest extends WP_UnitTestCase{

    var $post_datasource;

	public function setUp(){
        parent::setUp();
        $this->importer = $GLOBALS['jcimporter'];

        require_once WP_PLUGIN_DIR . '/jci-post-datasource/jci-post-datasource.php';
        $this->post_datasource = new JCI_Post_Datasource();

    }

    public function test_basic_400_header(){

        $key = substr(md5(time()), 0, 8 );

        $importer_id = create_importer(null, array(
            'import_type' => 'post',
            'general' => array(
                'post_field_type' => 'post',
                'post_field' => 'file_upload',
                'post_key' => $key
            ),
            'fields' => array(
                'post' => array(
                    'post_title' => '{0}',
                    'post_name' => '{1}',
                    'post_excerpt' => '{3}',
                    'post_content' => '{2}'
                )
            )
        ));

        ImporterModel::clearImportSettings();

        $post_settings = ImporterModel::getImporterMetaArr($importer_id, array('_import_settings', 'general'));

        set_query_var( 'import_key', $post_settings['post_key'] );
        set_query_var( 'importer_id', $importer_id );

        // ob_start();
        // // @$this->post_datasource->template_redirect();
        // $contents = ob_get_contents();
        // ob_clean();

    }

}
?>