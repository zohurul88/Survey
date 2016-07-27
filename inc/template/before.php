class QzTableHandler{

		public $db;
		public $force=false; 
		private $table_question='quize_question';
		private $table_answer='quize_answer';
		private $charlist;
		/*
			table fileds
		*/
		public $id="qid";
		public $aid="aid";
		public $question="question_txt"; 
		public $answer="answer_txt";
		public $ansval="answer_val";  
		public $anspos="answer_pos";  

		private $del_ans_with_question=true;
		private $del_table_on_deactive=true;

		function __construct()
		{
			global $wpdb; $this->db=$wpdb;
			$this->table_question=$this->db->prefix . $this->table_question;  
			$this->table_answer=$this->db->prefix . $this->table_answer;  
		}

		function rp_plugin_activate()
		{
			$charset_collate = $this->db->get_charset_collate(); 
			$ans_fk='';
			if($this->del_ans_with_question)
			$ans_fk=", FOREIGN KEY ($this->id) REFERENCES {$this->table_question} ({$this->id}) ON DELETE CASCADE ON UPDATE CASCADE";

			$sql_data_q = "CREATE TABLE {$this->table_question} (
			  {$this->id} int(5) NOT NULL AUTO_INCREMENT,
			  {$this->question} varchar(500) NOT NULL,
			  PRIMARY KEY({$this->id})
			) $charset_collate ENGINE=INNODB;"; 

			$sql_data_a = "CREATE TABLE {$this->table_answer} (
			  {$this->aid} int(5) NOT NULL AUTO_INCREMENT,
			  {$this->id} int(5) NOT NULL,
			  {$this->answer} varchar(500) NOT NULL,
			  {$this->ansval} int(5) NOT NULL,
			  {$this->anspos} int(2) NOT NULL,
			  PRIMARY KEY({$this->aid})
			  {$ans_fk}
			) $charset_collate ENGINE=INNODB;"; 

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta($sql_data_q);
			dbDelta($sql_data_a);
		}

		function rp_plugin_deactivation()
		{
			if(!$this->del_table_on_deactive)return;  
			$sql="DROP TABLE ".$this->table_answer.",".$this->table_question;
			$this->db->query($sql);
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta($sql);
		}

		function qzAllQuestions($orderby=null,$order="DESC")
		{
			$orderby=empty($orderby)?$this->id:$orderby;
			return $this->db->get_results("SELECT * FROM {$this->table_question} ORDER BY {$orderby} {$order}",ARRAY_A);
		}

		function getQuestion($key)
		{ 
			return $this->db->get_results("SELECT * FROM {$this->table_question} WHERE {$this->id}={$key}");
		}

		function addQuestion($txt,$format=array())
		{  
			$data=array($this->question=>$txt); 
			$ins=$this->db->insert($this->table_question,$data); 
			return $ins; 
		}

		function updateQuestion($qtxt,$where)
		{  
			$data=array($this->question=>$qtxt); 
			$where=array($this->id=>$where); 
			$update=$this->db->update($this->table_question,$data,$where); 
			return $update; 
		}

		function delQuestion($where)
		{   
			$where=array($this->id=>$where); 
			$delete=$this->db->delete($this->table_question,$where); 
			return $delete; 
		}
 	}
