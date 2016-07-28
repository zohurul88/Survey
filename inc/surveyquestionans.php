<?php 

	class SurveyQuestionAns extends MasterDB{
		private $table;
		private $distroy_deactive=REMOVE_TABLE_ON_DEACTICE;

		public $id="id";
		public $title="title";
		public $q_desc="q_desc";
		public $q_order='q_order'; 
		public $sid='sid';
		public $multi_ans='multi_ans';
		public $ans_show='ans_show';
		public $ans_list='ans_list';

		protected $fileds;
		protected $db;
		function __construct($table="sc_survey_questions")
		{
			global $wpdb;
			$this->db=$wpdb; 
			$this->table=$wpdb->prefix.$table;
			$this->fileds=self::get(); 
		}

		static function get()
		{
			return getClassVars(__CLASS__,1);
		}

		function generateTblSql()
		{
			global $wpdb;
			$sv=new SurveyTblSchema();
			return "CREATE TABLE {$this->table} (
			  {$this->id} int(5) NOT NULL AUTO_INCREMENT,
			  PRIMARY KEY({$this->id}),
			  {$this->title} varchar(500) NOT NULL,
			  {$this->q_desc} varchar(1500) NULL, 
			  {$this->q_order} int(5) NOT NULL, 
			  {$this->sid} int(5) NOT NULL,
			  {$this->multi_ans} ENUM('no','yes') NOT NULL,
			  {$this->ans_show} ENUM('default','rating','random') NOT NULL,
			  {$this->ans_list} TEXT NOT NULL,
			  FOREIGN KEY ($this->sid) REFERENCES ".$sv->table()."({$sv->id}) ON DELETE CASCADE ON UPDATE CASCADE
			) ".$wpdb->get_charset_collate()." ENGINE=INNODB;"; 

		}

		function distroyTbl()
		{
			if($this->distroy_deactive) return $this->table;
		}

		function newQuestions($data)
		{
			if(empty($data)) return null;
			return $this->insert($this->table,$data);
		}
		

		function updateQuestions($data,$id)
		{
			if(empty($data)) return null;
			return $this->update($this->table,$data,array($this->id=>$id));
		}

		function removeQuestion($id)
		{
			if(empty($id)) return null;
			return $this->delete($this->table,array($this->id=>$id));
		}

		function surveyQuestions($sid)
		{
			$result=$this->result($this->table,array($this->sid=>$sid));
			return $this->last_result;
		}

		function question($id)
		{
			$result=$this->result($this->table,array($this->id=>$id));
			return $this;
		}
		
		function hasAnswer()
		{
			if($this->rows_affected && $this->rows_affected==1 && !empty($this->last_result))
			{
				if(!empty($this->last_result[0]->ans_list))
					return true;
				return false;
			}
			return false;
		}

	}