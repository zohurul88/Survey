<?php 
	/*
	Plugin Name: WP SC Survey 
	Plugin URI:  http://URI_Of_Page_Describing_Plugin_and_Updates
	Description: This describes my plugin in a short sentence
	Version:     1.0.0
	Author:      John Smith
	Author URI:  http://URI_Of_The_Plugin_Author
	License:     GPL2
	License URI: https://www.gnu.org/licenses/gpl-2.0.html
	Domain Path: /languages
	Text Domain: quize-quize 
	*/
	define(SCSURVEY_NAME,'WP SC Survey');
	define(SCSURVEY_TITLE,'Survey');
	define(SCSURVEY_TITLE_P,'Surveys');
	define(SCSURVEY_SLUG,'wp-sc-survey');
	define(SCSURVEY_VERSION,'1.0.0');
	define(SCSURVEY_PATH, plugin_dir_path(__FILE__));
	define(SCSURVEY_URI, plugin_dir_url(__FILE__));
	define(REMOVE_TABLE_ON_DEACTIVE,false);
	if(!class_exists('ScSurvey'))
	{ 
		require(SCSURVEY_PATH.'/inc/extfunc.php');
		class ScSurvey{
			public $svdb;
			public $svadmin;
			private $registerSchema=array();
			function ScSurvey()
			{
				$this->svadmin=new SurveyAdminHandler();
				$this->registerSchema[]=new SurveyTblSchema();
				$this->registerSchema[]=new SurveyQuestionAns();
			}

			function plugin_active()
			{
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' ); 
				foreach ($this->registerSchema as $tbl)  dbDelta($tbl->generateTblSql());  
			}

			function plugin_deactivation()
			{
				if(!REMOVE_TABLE_ON_DEACTIVE) return null;
				global $wpdb;
				$droplist=array();
				foreach ($this->registerSchema as $tbl) $droplist[]=$tbl->distroyTbl(); 
				$sql="DROP TABLE ".implode(' , ', array_reverse($droplist));
				$wpdb->query($sql);
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta($sql);
			}
		}
		$scsv=new ScSurvey(); 
		register_activation_hook( __FILE__, array($scsv,'plugin_active' ));
		register_deactivation_hook( __FILE__, array($scsv,'plugin_deactivation'));
	}
