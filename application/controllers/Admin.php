<?php

include "Util.php";

class Admin extends CI_Controller {

	public function login() {
		$email = $this->input->post('email');
		$password = $this->input->post('password');
		$expiry = $this->input->post('expiry');
		$admins = $this->db->query("SELECT * FROM `admins` WHERE `email`='" . $email . "' AND `password`='" . $password . "'")->result_array();
		if (sizeof($admins) > 0) {
			$admin = $admins[0];
			echo json_encode(array(
				'response_code' => 1,
				'user_id' => intval($admin['id'])
			));
		} else {
			echo json_encode(array(
				'response_code' => -2
			));
		}
	}

	public function get_users() {
		$start = intval($this->input->post('start'));
		$length = intval($this->input->post('length'));
		$users = $this->db->query("SELECT * FROM `users` ORDER BY `email` ASC LIMIT " . $start . "," . $length)->result_array();
		for ($i=0; $i<sizeof($users); $i++) {
		}
		echo json_encode($users);
	}

	public function get_all_users() {
		$users = $this->db->query("SELECT * FROM `users` ORDER BY `email` ASC")->result_array();
		for ($i=0; $i<sizeof($users); $i++) {
		}
		echo json_encode($users);
	}

	public function get_users_by_email() {
		$email = $this->input->post('email');
		$users = $this->db->query("SELECT * FROM `users` WHERE `email`='" . $email . "'")->result_array();
		for ($i=0; $i<sizeof($users); $i++) {
		}
		echo json_encode($users);
	}
	
	public function add_user() {
		$email = $this->input->post('email');
		$role = intval($this->input->post('role'));
		if ($this->db->query("SELECT * FROM `users` WHERE `email`='" . $email . "'")->num_rows() > 0) {
			echo json_encode(array(
				'response_code' => -1
			));
			return;
		}
		$this->db->insert('users', array(
			'email' => $email,
			'role' => $role
		));
		echo json_encode(array(
			'response_code' => 1
		));
	}
	
	public function update_user() {
		$id = intval($this->input->post('id'));
		$email = $this->input->post('email');
		if ($this->db->query("SELECT * FROM `users` WHERE `email`='" . $email . "'")->num_rows() > 0) {
			echo json_encode(array(
				'response_code' => -1
			));
			return;
		}
		$this->db->where('id', $id);
		$this->db->update('users', array(
			'email' => $email
		));
		echo json_encode(array(
			'response_code' => 1
		));
	}
	
	public function delete_user() {
		$id = intval($this->input->post('id'));
		$this->db->where('id', $id);
		$this->db->delete('users');
	}

	public function get_admins() {
		$start = intval($this->input->post('start'));
		$length = intval($this->input->post('length'));
		$admins = $this->db->query("SELECT * FROM `admins` ORDER BY `email` ASC LIMIT " . $start . "," . $length)->result_array();
		for ($i=0; $i<sizeof($admins); $i++) {
		}
		echo json_encode($admins);
	}
	
	public function add_admin() {
		$name = $this->input->post('name');
		$email = $this->input->post('email');
		$password = $this->input->post('password');
		if ($this->db->query("SELECT * FROM `admins` WHERE `email`='" . $email . "'")->num_rows() > 0) {
			echo json_encode(array(
				'response_code' => -1
			));
			return;
		}
		$this->db->insert('admins', array(
			'name' => $name,
			'email' => $email,
			'password' => $password
		));
		echo json_encode(array(
			'response_code' => 1
		));
	}
	
	public function update_admin() {
		$id = intval($this->input->post('id'));
		$changed = intval($this->input->post('changed'));
		$name = $this->input->post('name');
		$email = $this->input->post('email');
		$password = $this->input->post('password');
		if ($changed == 1) {
			if ($this->db->query("SELECT * FROM `admins` WHERE `email`='" . $email . "'")->num_rows() > 0) {
				echo json_encode(array(
					'response_code' => -1
				));
				return;
			}
		}
		$this->db->where('id', $id);
		$this->db->update('admins', array(
			'name' => $name,
			'email' => $email,
			'password' => $password
		));
		echo json_encode(array(
			'response_code' => 1
		));
	}
	
	public function delete_admin() {
		$id = intval($this->input->post('id'));
		$this->db->where('id', $id);
		$this->db->delete('admins');
	}
	
	public function add_nominatif_batalyon() {
		$type = $this->input->post('type');
		$nama = $this->input->post('nama');
		$pangkat = $this->input->post('pangkat');
		$nrp = $this->input->post('nrp');
		$ttl = $this->input->post('ttl');
		$ket = $this->input->post('ket');
		$includePicture = intval($this->input->post('include_picture'));
		if ($includePicture == 1) {
			$config['upload_path']          = './userdata/';
	        $config['allowed_types']        = '*';
	        $config['max_size']             = 2147483647;
	        $config['file_name']            = Util::generateUUIDv4();
	        $this->load->library('upload', $config);
	        if ($this->upload->do_upload('file')) {
				$this->db->insert('nominatif_batalyon', array(
					'type' => $type,
					'nama' => $nama,
					'pangkat' => $pangkat,
					'nrp' => $nrp,
					'ttl' => $ttl,
					'ket' => $ket,
					'profile_picture' => $this->upload->data()['file_name']
				));
	        } else {
	        	echo json_encode($this->upload->display_errors());
	        }
		} else {
			$this->db->insert('nominatif_batalyon', array(
				'type' => $type,
				'nama' => $nama,
				'pangkat' => $pangkat,
				'nrp' => $nrp,
				'ttl' => $ttl,
				'ket' => $ket
			));
		}
	}
	
	public function update_nominatif_batalyon() {
		$id = intval($this->input->post('id'));
		$nama = $this->input->post('nama');
		$pangkat = $this->input->post('pangkat');
		$nrp = $this->input->post('nrp');
		$ttl = $this->input->post('ttl');
		$ket = $this->input->post('ket');
		$includePicture = intval($this->input->post('include_picture'));
		if ($includePicture == 1) {
			$config['upload_path']          = './userdata/';
	        $config['allowed_types']        = '*';
	        $config['max_size']             = 2147483647;
	        $config['file_name']            = Util::generateUUIDv4();
	        $this->load->library('upload', $config);
	        if ($this->upload->do_upload('file')) {
	        	$this->db->where('id', $id);
				$this->db->update('nominatif_batalyon', array(
					'nama' => $nama,
					'pangkat' => $pangkat,
					'nrp' => $nrp,
					'ttl' => $ttl,
					'ket' => $ket,
					'profile_picture' => $this->upload->data()['file_name']
				));
	        } else {
	        	echo json_encode($this->upload->display_errors());
	        }
		} else {
			$this->db->where('id', $id);
			$this->db->update('nominatif_batalyon', array(
				'nama' => $nama,
				'pangkat' => $pangkat,
				'nrp' => $nrp,
				'ttl' => $ttl,
				'ket' => $ket
			));
		}
	}
	
	public function get_nominatif_batalyon() {
		$id = intval($this->input->post('id'));
		echo json_encode($this->db->query("SELECT * FROM `nominatif_batalyon` WHERE `id`=" . $id)->row_array());
	}
	
	public function delete_nominatif_batalyon() {
		$id = intval($this->input->post('id'));
		$this->db->query("DELETE FROM `nominatif_batalyon` WHERE `id`=" . $id);
	}
	
	public function get_all_admins() {
		echo json_encode($this->db->query("SELECT * FROM `admins` ORDER BY `name` ASC")->result_array());
	}
	
	public function get_all_ads() {
		echo json_encode($this->db->query("SELECT * FROM `ads`")->result_array());
	}
	
	public function delete_ad() {
		$id = intval($this->input->post('id'));
		echo json_encode($this->db->query("DELETE FROM `ads` WHERE `id`=" . $id));
	}
	
	public function add_ad() {
		$link = $this->input->post('link');
		$config['upload_path']          = './userdata/';
	        $config['allowed_types']        = '*';
	        $config['max_size']             = 2147483647;
	        $config['file_name']            = Util::generateUUIDv4();
	        $this->load->library('upload', $config);
	        if ($this->upload->do_upload('file')) {
				$this->db->insert('ads', array(
					'img' => $this->upload->data()['file_name'],
					'link' => $link
				));
	        } else {
	        	echo json_encode($this->upload->display_errors());
	        }
	}
	
	public function delete_document() {
		$id = intval($this->input->post('id'));
		$this->db->query("DELETE FROM `other_instances` WHERE `id`=" . $id);
	}
	
	public function add_document() {
		$type = $this->input->post('type');
		$title = $this->input->post('title');
		$config['upload_path']          = './userdata/';
	        $config['allowed_types']        = '*';
	        $config['max_size']             = 2147483647;
	        $config['file_name']            = Util::generateUUIDv4();
	        $this->load->library('upload', $config);
	        if ($this->upload->do_upload('file')) {
				$this->db->insert('other_instances', array(
					'type' => $type,
					'title' => $title,
					'path' => $this->upload->data()['file_name']
				));
	        } else {
	        	echo json_encode($this->upload->display_errors());
	        }
	}
	
	public function add_prayer_time() {
		$religion = $this->input->post('religion');
		$day = $this->input->post('day');
		$hour = $this->input->post('hour');
		$name = $this->input->post('name');
		$tone = intval($this->input->post('tone'));
		$this->db->query("INSERT INTO `prayer_times` (`religion`, `day`, `hour`, `name`, `tone`) VALUES ('" . $religion . "', " . $day . ", '" . $hour . "', '" . $name . "', " . $tone . ")");
	}
	
	public function update_prayer_time() {
		$id = $this->input->post('id');
		$religion = $this->input->post('religion');
		$day = $this->input->post('day');
		$hour = $this->input->post('hour');
		$name = $this->input->post('name');
		$tone = intval($this->input->post('tone'));
		$this->db->query("UPDATE `prayer_times` SET `name`='" . $name . "', `hour`='" . $hour . "', `tone`=" . $tone . " WHERE `id`=" . $id);
	}
	
	public function delete_prayer_time() {
		$id = $this->input->post('id');
		$this->db->query("DELETE FROM `prayer_times` WHERE `id`=" . $id);
	}
	
	public function set_prayer_time_automatic() {
		$religion = $this->input->post('religion');
		$day = intval($this->input->post('day'));
		$automatic = intval($this->input->post('automatic'));
		$this->db->query("UPDATE `prayer_times` SET `automatic`=" . $automatic . " WHERE `religion`='" . $religion . "' AND `day`=" . $day);
	}
	
	public function get_holidays() {
		$religion = $this->input->post('religion');
		echo json_encode($this->db->query("SELECT * FROM `holiday_times` WHERE `religion`='" . $religion . "'")->result_array());
	}
	
	public function add_holiday() {
		$religion = $this->input->post('religion');
		$name = $this->input->post('name');
		$description = $this->input->post('description');
		$day = $this->input->post('day');
		$month = $this->input->post('month');
		$this->db->insert('holiday_times', array(
			'religion' => $religion,
			'name' => $name,
			'description' => $description,
			'day' => $day,
			'month' => $month
		));
	}
	
	public function delete_holiday() {
		$id = $this->input->post('id');
		$this->db->query("DELETE FROM `holiday_times` WHERE `id`=" . $id);
	}
}
