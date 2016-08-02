<?php  
if(class_exists('wpdb')){

	class SurveyTblSchema extends MasterDB{
		private $table;
		private $distroy_deactive=REMOVE_TABLE_ON_DEACTICE;
		public $id="id";
		public $title="title";
		public $short_desc="short_desc";
		public $settings="settings";
		public $state="state";
		public $active="active";
		public $inactive="inactive";
		public $inactive_txt="inactive_txt";
		public $result_txt="result_txt";
		public $intor_txt="intor_txt";
		protected $fileds;
		protected $db;
		function __construct($table="sc_survey")
		{
			global $wpdb;
			$this->db=$wpdb; 
			$this->table=$wpdb->prefix.$table;
			$this->fileds=self::get(); 
		}

		function table()
		{
			return $this->table;
		}

		function generateTblSql()
		{
			global $wpdb;
			return "CREATE TABLE IF NOT EXISTS {$this->table} (
			  {$this->id} int(5) NOT NULL AUTO_INCREMENT,
			  PRIMARY KEY({$this->id}),
			  {$this->title} varchar(500) NOT NULL,
			  {$this->short_desc} varchar(1500) NULL,
			  {$this->settings} varchar(4000) NOT NULL,
			  {$this->state} ENUM('active','inactive') NOT NULL,
			  {$this->active} DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  {$this->inactive} DATE NULL,
			  {$this->inactive_txt} TEXT NULL,
			  {$this->result_txt} TEXT NULL,
			  {$this->intor_txt} TEXT NULL
			) ".$wpdb->get_charset_collate()." ENGINE=INNODB;"; 
		}

		function findID($id=null,$set=true)
		{
			if(empty($id)) return false;
				$result=$this->result($this->table,array($this->id=>$id));
			if($set)$this->setIt($result[0]);
			return $result[0];
		}

		function setIt($result)
		{
			$sv=new SurveyTblSchema();
			$this->id=isset($result->{$sv->id})?$result->{$sv->id}:0;
			$this->title=isset($result->{$sv->title})?$result->{$sv->title}:'';
			$this->short_desc=isset($result->{$sv->short_desc})?$result->{$sv->short_desc}:'';
			$this->settings=isset($result->{$sv->settings})?json_decode($result->{$sv->settings}):0;
			$this->state=isset($result->{$sv->state})?$result->{$sv->state}:0;
			$this->active=isset($result->{$sv->active})?$result->{$sv->active}:'';
			$this->inactive=isset($result->{$sv->inactive})?$result->{$sv->inactive}:'';
			$this->inactive_txt=isset($result->{$sv->inactive_txt})?$result->{$sv->inactive_txt}:'';
			$this->result_txt=isset($result->{$sv->result_txt})?$result->{$sv->result_txt}:'';
			$this->intor_txt=isset($result->{$sv->intor_txt})?$result->{$sv->intor_txt}:'';
		}

		function distroyTbl()
		{
			if($this->distroy_deactive) return $this->table;
		}

		function save($req,$id=null)
		{
			if($id==null)
			{  
				return $this->insert($this->table,$req);
			}
			else 
			{
				return $this->update($this->table,$req,array($this->id=>$id));
			}
			return false;
		}

		function all($filter=null,$ext=array())
		{
			if(empty($ext) && $ext!==false) $ext=array('order_by'=>$this->id,'order'=>'DESC');
			if(empty($filter))
				$this->result($this->table,null,null,$ext);
			else $this->result($this->table,$filter,null,$ext);
			return $this->db;
		}

		static function get()
		{
			return getClassVars(__CLASS__,1);
		}

		function activeSurvey()
		{
			$result=$this->result($this->table,array($this->state=>'active'));
			return $this->db;
		}

		function inActiveSurvey()
		{
			$result=$this->result($this->table,array($this->state=>'inactive'));
			return $this->db;
		}

		function flipState($id)
		{
			$state=$this->findID($id,false)->state;
			if($state=="active")
				{if($this->save(array($this->state=>'inactive'),$id)) return true;}
			else
				{if($this->save(array($this->state=>'active'),$id)) return true;}
			return false;
		}

		function removeSurvey($id)
		{
			$del=$this->delete($this->table,array($this->id=>$id));
			if($del->rows_affected) return true;
			return false;
		}

		function surveyQuestions($survey,$qObj)
		{
			$setting=json_decode($survey->settings,true);
			$order="ASC";
			if($setting['question-order']=="random")
			{
				$order="random";
			}
			$sq=$qObj->surveyQuestions($survey->id,$order);

			if(!empty($sq))
				return $sq;
			return false;
		}

		function getSurveyPageByResult($id,$result=null)
		{
			return (int)$this->findID($id)->result_txt;
		}

	} 

}