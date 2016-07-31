<?php

class SurveyQuestionAns extends MasterDB
{
    private $table;
    private $distroy_deactive = REMOVE_TABLE_ON_DEACTICE;

    public $id        = "id";
    public $title     = "title";
    public $q_desc    = "q_desc";
    public $q_order   = 'q_order';
    public $sid       = 'sid';
    public $multi_ans = 'multi_ans';
    public $ans_show  = 'ans_show';
    public $ans_list  = 'ans_list';

    protected $ansArrFormat = array(
        'id',
        'title',
        'murk',
        'order',
    );

    protected $fileds;
    protected $db;
    public function __construct($table = "sc_survey_questions")
    {
        global $wpdb;
        $this->db     = $wpdb;
        $this->table  = $wpdb->prefix . $table;
        $this->fileds = self::get();
    }

    public static function get()
    {
        return getClassVars(__CLASS__, 1);
    }

    public function generateTblSql()
    {
        global $wpdb;
        $sv = new SurveyTblSchema();
        return "CREATE TABLE IF NOT EXISTS {$this->table} (
			  {$this->id} int(5) NOT NULL AUTO_INCREMENT,
			  PRIMARY KEY({$this->id}),
			  {$this->title} varchar(500) NOT NULL,
			  {$this->q_desc} varchar(1500) NULL,
			  {$this->q_order} int(5) NOT NULL,
			  {$this->sid} int(5) NOT NULL,
			  {$this->multi_ans} ENUM('no','yes') NOT NULL,
			  {$this->ans_show} ENUM('default','rating','random') NOT NULL,
			  {$this->ans_list} TEXT NOT NULL,
			  FOREIGN KEY ($this->sid) REFERENCES " . $sv->table() . "({$sv->id}) ON DELETE CASCADE ON UPDATE CASCADE
			) " . $wpdb->get_charset_collate() . " ENGINE=INNODB;";

    }

    public function distroyTbl()
    {
        if ($this->distroy_deactive) {
            return $this->table;
        }

    }

    public function newQuestions($data)
    {
        if (empty($data)) {
            return null;
        }

        return $this->insert($this->table, $data);
    }

    public function updateQuestions($data, $id)
    {
        if (empty($data)) {
            return null;
        }

        return $this->update($this->table, $data, array($this->id => $id));
    }

    public function updateOrder($id, $order)
    {
        $r = $this->updateQuestions(array($this->q_order => -9999), $id);
        $r = $this->updateQuestions(array($this->q_order => $order), $id);
        return $r;
    }

    public function removeQuestion($id)
    {
        if (empty($id)) {
            return null;
        }

        return $this->delete($this->table, array($this->id => $id));
    }

    public function surveyQuestions($sid, $order = null)
    {
        if (!empty($order)) {
            $order = array('order_by' => $this->q_order, 'order' => $order);
        }

        $result = $this->result($this->table, array($this->sid => $sid), null, $order);
        return $this->last_result;
    }

    public function question($id, $order = null)
    {
        if (!empty($order)) {
            $order = array('order_by' => $this->q_order, 'order' => $order);
        }

        $result = $this->result($this->table, array($this->id => $id), null, $order);
        return $this;
    }

    public function hasAnswer()
    {
        //var_dump($this);
        if (!empty($this->last_result)) {
            if (!empty(json_decode($this->last_result[0]->ans_list, true))) {
                return true;
            }

            return false;
        }
        return false;
    }

    public function newAnswerFormat($data, $format = null)
    {
        if ($format == null) {
            $format = $this->ansArrFormat;
        }

        return array_combine($format, $data);
    }

    public function sortAnswerList($data, $order = "ASC")
    {
        usort($data, array($this, 'sortByOrder'));
        return $data;
    }

    public function sortByOrder($a, $b)
    {
        return $a['order'] - $b['order'];
    }

    public function removeAnswer($ans_id, $qid)
    {
        $answers  = $this->question($qid)->last_result[0]->ans_list;
        $answers  = json_decode($answers, true);
        $toRemove = null;
        foreach ($answers as $key => $answer) {

            if ($answer['id'] == $ans_id) {
                $toRemove = $key;
            }

        }
        if (!empty($toRemove)) {
            unset($answers[$toRemove]);
            $up = $this->updateQuestions(array($this->ans_list => json_encode($answers)), $qid);
            if ($up->rows_affected) {
                return true;
            }

        }
        return false;
    }

}
