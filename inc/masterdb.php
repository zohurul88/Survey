<?php 

	class MasterDB{
		public $last_query;
		public $last_error;
		public $insert;
		public $last_result;
		public $num_rows;
		public $rows_affected;
		
		function  insert($tbl,$data,$format=array())	{
			global $wpdb;
			$this->beforeQuery();
			$insert=$wpdb->insert($tbl,$data,$format);
			$this->afterQuery($wpdb);
			return $this;
		}

		function result($tbl,$where=null,$fields=null,$ext=null)	{
			global $wpdb; 
			if (!empty($ext)) { 
				$ext=array_filter($ext);
				$extSqlTxt='';
				if(isset($ext['order_by']))
				{
					$extSqlTxt.=' ORDER BY '.(is_array($ext['order_by'])?implode(',',$ext['order_by']):$ext['order_by']);
				}
				
				if(isset($ext['order']))
				{
					$extSqlTxt.=' '.$ext['order'];
				}

				if(isset($ext['limit']))
				{
					$extSqlTxt.=' LIMIT '.$ext['limit'];
				}
			}

			$sqlTxt=$this->selectSqlTxt($tbl,$where,$fields,$extSqlTxt);
			$result=$wpdb->get_results($sqlTxt);
			$this->afterQuery($wpdb);
			return $result;
		}

		function  update($tbl,$data,$where,$format=array(),$where_format=array())	{
			global $wpdb;
			if(empty($format))
			{
				$f=array('string'=>'%s','integer'=>'%d','double'=>'%f');
				foreach($data as $d)
					$format[]=$f[gettype($d)];
			}
			$wpdb->update($tbl,$data,$where,$format,$where_format);
			$this->afterQuery($wpdb);
			return $this;
		}

		function delete($tbl,$where)
		{
			global $wpdb;
			$wpdb->delete($tbl,$where);
			$this->afterQuery($wpdb);
			return $this;
		}

		function  selectSqlTxt($tbl,$where=null,$fields=null,$ext=null){
			$cond=" AND ";
			if(isset($where['cond']))
			{
				$cond=$where['cond'];
				unset($where['cond']);
			}
			if($tbl==null) return false; 
			if($where==null && $fileds=-null) return false;
			$select="SELECT ";
			if($fileds==null)  $select.=" * FROM ".$tbl;
			elseif(is_array($fileds)) $select.="(".implode(",",$fileds).") {$tbl}";
			elseif($fileds===true)
			$select.=implode(",",array_keys($where))." FROM {$tbl} ";
			else $select.="($fileds) FROM {$tbl}";

			if(is_array($where) && count($where)>0){ 
				$tmp=array();
				foreach($where as $k=>$v)$tmp[]="{$k}='{$v}'";
				$select.=" WHERE ".implode($cond,$tmp);
			}
			if($ext!=null) $select.=$ext;
			return $select;
		}


		function beforeQuery()
		{

		}
		function afterQuery($wpdb)
		{
			$this->last_query=$wpdb->last_query;
			$this->last_error=$wpdb->last_error;
			$this->last_result=$wpdb->last_result;
			$this->insert=$wpdb->insert_id;
			$this->num_rows=$wpdb->num_rows;
			$this->rows_affected=$wpdb->rows_affected;
		}
	}