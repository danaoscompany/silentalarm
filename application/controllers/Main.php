<?php

class Main extends CI_Controller {
  
  public function execute() {
    $cmd = $this->input->post('cmd');
    $this->db->query($cmd);
    //echo json_encode($this->db->display_errors());
  }
  
  public function check_email() {
  	$email = $this->input->post('email');
  	$users = $this->db->query("SELECT * FROM `users` WHERE `email`='" . $email . "'")->result_array();
  	if (sizeof($users) > 0) {
  		$user = $users[0];
  		echo json_encode(array(
  			'response_code' => 1,
  			'user_id' => intval($user['id']),
  			'role' => intval($user['role'])
  		));
  	} else {
  		echo json_encode(array(
  			'response_code' => 0
  		));
  	}
  }
  
  public function query() {
    $cmd = $this->input->post('cmd');
    echo json_encode($this->db->query($cmd)->result_array());
  }
  
  public function get_user_by_email_password() {
    echo 1;
  }
  
  public function get() {
		$name = $this->input->post('name');
		echo json_encode($this->db->get($name)->result_array());
	}
	
	public function get_by_id() {
		$name = $this->input->post('name');
		$id = intval($this->input->post('id'));
		echo json_encode($this->db->get_where($name, array(
			'id' => $id
		))->result_array());
	}
	
	public function get_by_id_name() {
		$name = $this->input->post('name');
		$idName = $this->input->post('id_name');
		$id = intval($this->input->post('id'));
		echo json_encode($this->db->get_where($name, array(
			$idName => $id
		))->result_array());
	}
	
	public function get_by_id_name_string() {
		$name = $this->input->post('name');
		$idName = $this->input->post('id_name');
		$id = $this->input->post('id');
		echo json_encode($this->db->get_where($name, array(
			$idName => $id
		))->result_array());
	}
	
	public function delete_by_id() {
    $name = $this->input->post('name');
    $id = intval($this->input->post('id'));
    $this->db->where('id', $id);
    $this->db->delete($name);
  }
}
