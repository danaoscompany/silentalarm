<?php
require ('fcm.php');
require ('Util.php');

class User extends CI_Controller
{

    public function upload_video()
    {
        $uploaderID = intval($this
            ->input
            ->post('uploader_id'));
        $date = $this
            ->input
            ->post('date');
        $lat = doubleval($this
            ->input
            ->post('lat'));
        $lng = doubleval($this
            ->input
            ->post('lng'));
        $title = $this->input->post('title');
        $config = array(
            'upload_path' => './userdata',
            //'allowed_types' => "mp4|avi|ogg|flv|wmv|3gp",
            'allowed_types' => "*",
            'overwrite' => true,
            'max_size' => "2097152"
        );
        $this
            ->load
            ->library('upload', $config);
        $this
            ->upload
            ->initialize($config);
        if ($this
            ->upload
            ->do_upload('video'))
        {
            $videoFileName = $this
                ->upload
                ->data() ['file_name'];
            echo $videoFileName . ",";
            $this
                ->upload
                ->initialize($config);
            if ($this
                ->upload
                ->do_upload('screenshot'))
            {
                $screenshotFileName = $this
                    ->upload
                    ->data() ['file_name'];
                echo $screenshotFileName;
                $this
                    ->db
                    ->insert('videos', array(
                    'uploader_id' => $uploaderID,
                    'title' => $title,
                    'video' => $videoFileName,
                    'screenshot' => $screenshotFileName,
                    'date' => $date,
                    'lat' => $lat,
                    'lng' => $lng
                ));
            }
            else
            {
                echo json_encode($this
                    ->upload
                    ->display_errors());
            }
        }
    }

    public function unsubscribe_alarm()
    {
        $soldierID = intval($this
            ->input
            ->post('soldier_id'));
        $commanderID = intval($this
            ->input
            ->post('commander_id'));
        $this
            ->db
            ->where('commander_id', $commanderID)->where('soldier_id', $soldierID);
        $this->db->delete('subscribed_commanders');
    }

    public function subscribe_alarm()
    {
        $soldierID = intval($this
            ->input
            ->post('soldier_id'));
        $oldCommanderID = intval($this
            ->input
            ->post('old_commander_id'));
        $newCommanderID = intval($this
            ->input
            ->post('new_commander_id'));
        $this
            ->db
            ->where('commander_id', $oldCommanderID)->where('soldier_id', $soldierID);
        $this
            ->db
            ->delete('subscribed_commanders');
        $this
            ->db
            ->where('commander_id', $newCommanderID)->where('soldier_id', $soldierID);
        $subscribedCommanders = $this->db->get('subscribed_commanders')->result_array();
        if (sizeof($subscribedCommanders) == 0) {
	        $this
	            ->db
	            ->insert('subscribed_commanders', array(
	            'commander_id' => $newCommanderID,
	            'soldier_id' => $soldierID
	        ));
        }
    }
    
    public function check_email_exists() {
    	$email = $this->input->post('email');
    	$this->db->where('email', $email);
    	$users = $this->db->get('users')->result_array();
    	if (sizeof($users) > 0) {
    		echo 1;
    	} else {
    		echo -1;
    	}
    }

    public function set_alarm()
    {
        $commanderID = intval($this
            ->input
            ->post('commander_id'));
        $alarmType = intval($this
            ->input
            ->post('alarm_type'));
        $pangkalan = intval($this
            ->input
            ->post('pangkalan'));
        $on = intval($this
            ->input
            ->post('on'));
        $color = $this->input->post('color');
        $commanderName = $this
            ->db
            ->get_where('users', array(
            'id' => $commanderID
        ))->row_array() ['name'];
        $users = $this
            ->db
            ->get_where('subscribed_commanders', array(
            'commander_id' => $commanderID
        ))->result_array();
        $title = "";
        $clickAction = "";
        $showNotification = 0;
        if ($on == 0)
        {
            $title = "Alarm mati";
            $clickAction = "alertoff";
            if (isset($_POST['img_name'])) {
            	if (file_exists($this->input->post('img_name'))) {
	            	unlink("userdata/" . $this->input->post('img_name'));
            	}
            }
        }
        else if ($on == 1)
        {
            $title = "Alarm menyala";
            $clickAction = "alerton";
            $showNotification = 1;
        }
        $topic = "/topics/all_pangkalan";
        if ($pangkalan == 1) {
        	$topic = "/topics/pangkalan_1";
        } else if ($pangkalan == 2) {
        	$topic = "/topics/pangkalan_2";
        } else if ($pangkalan == 3) {
        	$topic = "/topics/pangkalan_3";
        } else if ($pangkalan == 4) {
        	$topic = "/topics/pangkalan_4";
        }
        FCM::send_message($topic, 1, $showNotification, 'Pesan baru', "Sedang ada pelaksanaan alarm dari komandan " . $commanderName,
                	array(
                	    'alarm_on' => $on,
                	    'alarm_type' => $alarmType,
                	    'commander_id' => $commanderID,
                	    'color' => $color,
	                    'pangkalan' => "" . $pangkalan
                	)
                );
        /*for ($i = 0;$i < sizeof($users);$i++)
        {
            $user = $users[$i];
            $fcmToken = $this
                ->db
                ->get_where('users', array(
                'id' => intval($user['soldier_id'])
            ))->row_array()['fcm_id'];
            $receiveAlerts = intval($user['receive_alerts']);
            if ($receiveAlerts != 2)
            {
                FCM::send_message($fcmToken, 1, $showNotification, 'Pesan baru', "Sedang ada pelaksanaan alarm dari komandan " . $commanderName,
                	array(
                	    'alarm_on' => $on,
                	    'alarm_type' => $alarmType,
                	    'receive_alerts' => $receiveAlerts,
                	    'commander_id' => $commanderID,
                	    'color' => $color
                	)
                );
            }
        }*/
        echo $topic;
    }

    public function set_alarm_with_image()
    {
    	$config['upload_path']          = './userdata/';
        $config['allowed_types']        = '*';
        $config['max_size']             = 2147483647;
        //$config['file_name']            = Util::generateUUIDv4();
        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('file')) {
        	return;
        }
        $imgFileName = $this->upload->data()['file_name'];
        $commanderID = intval($this
            ->input
            ->post('commander_id'));
        $alarmType = intval($this
            ->input
            ->post('alarm_type'));
        $pangkalan = intval($this
            ->input
            ->post('pangkalan'));
        $on = intval($this
            ->input
            ->post('on'));
        $color = $this->input->post('color');
        $commanderName = $this
            ->db
            ->get_where('users', array(
            'id' => $commanderID
        ))->row_array() ['name'];
        $users = $this
            ->db
            ->get_where('subscribed_commanders', array(
            'commander_id' => $commanderID
        ))->result_array();
        $title = "";
        $clickAction = "";
        $showNotification = 0;
        if ($on == 0)
        {
            $title = "Alarm mati";
            $clickAction = "alertoff";
        }
        else if ($on == 1)
        {
            $title = "Alarm menyala";
            $clickAction = "alerton";
            $showNotification = 1;
        }
        $topic = "/topics/all_pangkalan";
        if ($pangkalan == 1) {
        	$topic = "/topics/pangkalan_1";
        } else if ($pangkalan == 2) {
        	$topic = "/topics/pangkalan_2";
        } else if ($pangkalan == 3) {
        	$topic = "/topics/pangkalan_3";
        } else if ($pangkalan == 4) {
        	$topic = "/topics/pangkalan_4";
        }
        FCM::send_message_without_notification($topic, 5, array(
                    'alarm_on' => $on,
                    'alarm_type' => $alarmType,
                    'commander_id' => $commanderID,
                    'color' => $color,
                    'img_name' => $imgFileName,
                    'pangkalan' => "" . $pangkalan
                ));
        /*for ($i = 0;$i < sizeof($users);$i++)
        {
            $user = $users[$i];
            $userData = $this
                ->db
                ->get_where('users', array(
                'id' => intval($user['soldier_id'])
            ))->row_array();
            $fcmToken = $userData['fcm_id'];
            $receiveAlerts = intval($userData['receive_alerts']);
            if ($receiveAlerts != 2)
            {
                FCM::send_message_without_notification($fcmToken, 5, array(
                    'alarm_on' => $on,
                    'alarm_type' => $alarmType,
                    'receive_alerts' => $receiveAlerts,
                    'commander_id' => $commanderID,
                    'color' => $color,
                    'img_name' => $imgFileName
                ));
            }
        }*/
        /*$fcmToken = $this->db->query("SELECT * FROM `users` WHERE `email`='danaoscompany@gmail.com'")->row_array()['fcm_id'];
        FCM::send_message_without_notification($fcmToken, 5, array(
                    'alarm_on' => $on,
                    'alarm_type' => $alarmType,
                    'receive_alerts' => 1,
                    'img_name' => $imgFileName,
                    'color' => $this->input->post('color')
                ));*/
        echo $topic;
    }

    public function get_private_messages()
    {
        $myUserID = intval($this
            ->input
            ->post('my_user_id'));
        $opponentUserID = intval($this
            ->input
            ->post('opponent_user_id'));
        $start = intval($this
            ->input
            ->post('start'));
        $length = intval($this
            ->input
            ->post('length'));
        $messages = $this
            ->db
            ->query("SELECT * FROM private_messages WHERE (sender_id=" . $myUserID . " AND receiver_id=" . $opponentUserID . ") OR (sender_id=" . $opponentUserID . " AND receiver_id=" . $myUserID . ") ORDER BY date DESC LIMIT " . $start . "," . $length)->result_array();
        for ($i = 0;$i < sizeof($messages);$i++)
        {
            $messages[$i]['sender_name'] = $this
                ->db
                ->get_where('users', array(
                'id' => intval($messages[$i]['sender_id'])
            ))->row_array() ['name'];
            $messages[$i]['receiver_name'] = $this
                ->db
                ->get_where('users', array(
                'id' => intval($messages[$i]['receiver_id'])
            ))->row_array() ['name'];
        }
        echo json_encode($messages);
    }

    public function send_image_to_user()
    {
        $senderID = intval($this
            ->input
            ->post('sender_id'));
        $receiverID = intval($this
            ->input
            ->post('receiver_id'));
        $blockedUsers = $this
            ->db
            ->query("SELECT * FROM `blocked_users` WHERE (`blocked_user_id`=" . $senderID . " AND `user_id`=" . $receiverID . ") OR (`blocked_user_id`=" . $receiverID . " AND `user_id`=" . $senderID . ")")->result_array();
        if (sizeof($blockedUsers) > 0)
        {
            return;
        }
        $date = $this
            ->input
            ->post('date');
        $config = array(
            'upload_path' => './userdata',
            'allowed_types' => "gif|jpg|png|jpeg",
            'overwrite' => true,
            'max_size' => "2048000"
        );
        $this
            ->load
            ->library('upload', $config);
        if ($this
            ->upload
            ->do_upload('file'))
        {
            $this
                ->db
                ->insert('private_messages', array(
                'sender_id' => $senderID,
                'receiver_id' => $receiverID,
                'message' => "",
                'image' => $this
                    ->upload
                    ->data() ['file_name'],
                'date' => $date
            ));
            $lastID = intval($this
                ->db
                ->insert_id());
            $receiverToken = $this
                ->db
                ->get_where('users', array(
                'id' => $receiverID
            ))->row_array()['fcm_id'];
            $receiveAlerts = intval($this
                ->db
                ->get_where('users', array(
                'id' => $receiverID
            ))->row_array() ['receive_alerts']);
            if ($receiveAlerts != 2)
            {
                FCM::send_message($receiverToken, 1, $showNotification, 'Pesan baru', "Anda mendapat pemberitahuan dari komandan " . $commanderName, array(
                    'alarm_on' => $on,
                    'alarm_type' => $alarmType,
                    'receive_alerts' => $receiveAlerts
                ));
            }
            $row = $this
                ->db
                ->get_where('private_messages', array(
                'id' => $lastID
            ))->row_array();
            $row['sender_name'] = $this
                ->db
                ->get_where('users', array(
                'id' => $senderID
            ))->row_array() ['name'];
            $row['receiver_name'] = $this
                ->db
                ->get_where('users', array(
                'id' => $receiverID
            ))->row_array() ['name'];
            echo json_encode($row);
        }
    }

    public function send_message_to_user()
    {
        $senderID = intval($this
            ->input
            ->post('sender_id'));
        $receiverID = intval($this
            ->input
            ->post('receiver_id'));
        $blockedUsers = $this
            ->db
            ->query("SELECT * FROM `blocked_users` WHERE (`blocked_user_id`=" . $senderID . " AND `user_id`=" . $receiverID . ") OR (`blocked_user_id`=" . $receiverID . " AND `user_id`=" . $senderID . ")")->result_array();
        if (sizeof($blockedUsers) > 0)
        {
            return;
        }
        $message = $this
            ->input
            ->post('message');
        $shortMessage = $message;
        if (strlen($message) > 60)
        {
            $shortMessage = substr($message, 0, 60);
        }
        $date = $this
            ->input
            ->post('date');
        $this
            ->db
            ->insert('private_messages', array(
            'sender_id' => $senderID,
            'receiver_id' => $receiverID,
            'message' => $message,
            'date' => $date
        ));
        $lastID = intval($this
            ->db
            ->insert_id());
        $receiverToken = $this
            ->db
            ->get_where('users', array(
            'id' => $receiverID
        ))->row_array()['fcm_id'];
        $receiveAlerts = intval($this
            ->db
            ->get_where('users', array(
            'id' => $receiverID
        ))->row_array() ['receive_alerts']);
        if ($receiveAlerts != 2)
        {
            FCM::send_message($receiverToken, 1, $showNotification, 'Pesan baru', "Anda mendapat pesan baru", array(
                'alarm_on' => $on,
            	'alarm_type' => $alarmType,
            	'receive_alerts' => $receiveAlerts
            ));
        }
        $messageObj = $this
            ->db
            ->get_where('private_messages', array(
            'id' => $lastID
        ))->row_array();
        $messageObj['sender_name'] = $this
            ->db
            ->get_where('users', array(
            'id' => $senderID
        ))->row_array() ['name'];
        echo json_encode($messageObj);
    }

    public function delete_video()
    {
        $id = intval($this
            ->input
            ->post('id'));
        $videoName = $this
            ->db
            ->get_where('videos', array(
            'id' => $id
        ))->row_array() ['video_path'];
        unlink('./userdata/' . $videoName);
        $this
            ->db
            ->where('id', $id);
        $this
            ->db
            ->delete('videos');
    }

    public function get_contacts()
    {
        $start = intval($this
            ->input
            ->post('start'));
        $length = intval($this
            ->input
            ->post('length'));
        $this
            ->db
            ->order_by('name', 'ASC');
        $this
            ->db
            ->limit($length, $start);
        echo json_encode($this
            ->db
            ->get('contacts')
            ->result_array());
    }

    public function get_latest_videos()
    {
        $start = intval($this
            ->input
            ->post('start'));
        $length = intval($this
            ->input
            ->post('length'));
        $this
            ->db
            ->limit($length, $start);
        $this
            ->db
            ->order_by('date', 'DESC');
        $videos = $this
            ->db
            ->get('videos')
            ->result_array();
        for ($i = 0;$i < sizeof($videos);$i++)
        {
        	$uploaderID = intval($videos[$i]['uploader_id']);
        	$users = $this->db->get_where('users', array(
               	'id' => $uploaderID
           	))->result_array();
           	if (sizeof($users) > 0) {
           		$user = $users[0];
           		$videos[$i]['uploader'] = $user['name'];
            }
        }
        echo json_encode($videos);
    }

    public function get_most_liked_videos()
    {
        $start = intval($this
            ->input
            ->post('start'));
        $length = intval($this
            ->input
            ->post('length'));
        $this
            ->db
            ->limit($length, $start);
        $this
            ->db
            ->order_by('likes', 'DESC');
        $videos = $this
            ->db
            ->get('videos')
            ->result_array();
        for ($i = 0;$i < sizeof($videos);$i++)
        {
            $videos[$i]['uploader'] = $this
                ->db
                ->get_where('users', array(
                'id' => intval($videos[$i]['uploader_id'])
            ))->row_array() ['name'];
        }
        echo json_encode($videos);
    }

    public function get_most_viewed_videos()
    {
        $start = intval($this
            ->input
            ->post('start'));
        $length = intval($this
            ->input
            ->post('length'));
        $this
            ->db
            ->limit($length, $start);
        $this
            ->db
            ->order_by('viewers', 'DESC');
        $videos = $this
            ->db
            ->get('videos')
            ->result_array();
        for ($i = 0;$i < sizeof($videos);$i++)
        {
            $videos[$i]['uploader'] = $this
                ->db
                ->get_where('users', array(
                'id' => intval($videos[$i]['uploader_id'])
            ))->row_array() ['name'];
        }
        echo json_encode($videos);
    }

    public function get_featured_videos()
    {
        $start = intval($this
            ->input
            ->post('start'));
        $length = intval($this
            ->input
            ->post('length'));
        $this
            ->db
            ->limit($length, $start);
        $this
            ->db
            ->where('featured', 1);
        $videos = $this
            ->db
            ->get('videos')
            ->result_array();
        for ($i = 0;$i < sizeof($videos);$i++)
        {
            $videos[$i]['uploader'] = $this
                ->db
                ->get_where('users', array(
                'id' => intval($videos[$i]['uploader_id'])
            ))->row_array() ['name'];
        }
        echo json_encode($videos);
    }

    public function get_nearest_videos()
    {
        $start = intval($this
            ->input
            ->post('start'));
        $length = intval($this
            ->input
            ->post('length'));
        $lat = doubleval($this
            ->input
            ->post('lat'));
        $lng = doubleval($this
            ->input
            ->post('lng'));
        $this
            ->db
            ->limit($length, $start);
        $videos = $this
            ->db
            ->query('SELECT *, SQRT(
    POW(69.1 * (lat - ' . $lat . '), 2) +
    POW(69.1 * (' . $lng . '- lng) * COS(lat / 57.3), 2)) AS distance
FROM videos HAVING distance < 25 ORDER BY distance;')->result_array();
        for ($i = 0;$i < sizeof($videos);$i++)
        {
            $videos[$i]['uploader'] = $this
                ->db
                ->get_where('users', array(
                'id' => intval($videos[$i]['uploader_id'])
            ))->row_array() ['name'];
        }
        echo json_encode($videos);
    }

    public function get_favorite_videos()
    {
        $userID = intval($this
            ->input
            ->post('user_id'));
        $start = intval($this
            ->input
            ->post('start'));
        $length = intval($this
            ->input
            ->post('length'));
        $this
            ->db
            ->limit($length, $start);
        $this
            ->db
            ->where('user_id', $userID);
        $videos = $this
            ->db
            ->get('videos')
            ->result_array();
        for ($i = 0;$i < sizeof($videos);$i++)
        {
            $videos[$i]['uploader'] = $this
                ->db
                ->get_where('users', array(
                'id' => intval($videos[$i]['uploader_id'])
            ))->row_array() ['name'];
        }
        echo json_encode($videos);
    }

    public function get_my_videos()
    {
        $userID = intval($this
            ->input
            ->post('user_id'));
        $start = intval($this
            ->input
            ->post('start'));
        $length = intval($this
            ->input
            ->post('length'));
        $this
            ->db
            ->limit($length, $start);
        $this
            ->db
            ->where('uploader_id', $userID);
        $videos = $this
            ->db
            ->get('videos')
            ->result_array();
        for ($i = 0;$i < sizeof($videos);$i++)
        {
            $videos[$i]['uploader'] = $this
                ->db
                ->get_where('users', array(
                'id' => intval($videos[$i]['uploader_id'])
            ))->row_array() ['name'];
        }
        echo json_encode($videos);
    }

    /*public function send_message_to_user() {
    $userID = intval($this->input->post('user_id'));
    $messageID = intval($this->input->post('message_id'));
    $message = $this->db->get_where('messages', array(
      'id' => $messageID
    ))->row_array();
    $shortMessage = $message['message'];
    if (strlen($message['message']) > 60) {
      $shortMessage = substr($message['message'], 0, 60);
    }
    $token = $this->db->get_where('users', array(
      'id' => $userID
    ))->row_array()['pushy_token'];
    PushyAPI::send_message($token, 'Pesan baru', $shortMessage, array(
      'sender_id' => $userID,
      'message_id' => $messageID,
      ''
    ));
    }*/

    public function update_pushy_token()
    {
        $userID = intval($this
            ->input
            ->post('user_id'));
        $token = $this
            ->input
            ->post('token');
        $this
            ->db
            ->where('id', $userID);
        $this
            ->db
            ->update('users', array(
            'pushy_token' => $token
        ));
    }

    public function update_fcm_id()
    {
        $userID = intval($this
            ->input
            ->post('user_id'));
        $fcmID = $this
            ->input
            ->post('fcm_id');
        $this
            ->db
            ->where('id', $userID);
        $this
            ->db
            ->update('users', array(
            'fcm_id' => $fcmID
        ));
    }

    public function send_image()
    {
        $senderID = intval($this
            ->input
            ->post('sender_id'));
        $date = $this
            ->input
            ->post('date');
        $config = array(
            'upload_path' => './userdata',
            'allowed_types' => "gif|jpg|png|jpeg",
            'overwrite' => true,
            'max_size' => "2048000"
        );
        $this
            ->load
            ->library('upload', $config);
        if ($this
            ->upload
            ->do_upload('file'))
        {
            $this
                ->db
                ->insert('messages', array(
                'sender_id' => $senderID,
                'message' => '',
                'image' => $this
                    ->upload
                    ->data() ['file_name'],
                'date' => $date
            ));
            $lastID = intval($this
                ->db
                ->insert_id());
            $users = $this
                ->db
                ->get('users')
                ->result_array();
            /*for ($i=0; $i<sizeof($users); $i++) {
            $user = $users[$i];
            if (intval($user['id']) == $senderID) {
              continue;
            }
            PushyAPI::send_message($user['pushy_token'], 3, 1, 'Pesan baru', $shortMessage);
            }*/
            $row = $this
                ->db
                ->get_where('messages', array(
                'id' => $lastID
            ))->row_array();
            $row['name'] = $this
                ->db
                ->get_where('users', array(
                'id' => $senderID
            ))->row_array() ['name'];
            echo json_encode($row);
        }
    }

    public function send_message()
    {
        $message = $this
            ->input
            ->post('message');
        $shortMessage = $message;
        if (strlen($message) > 60)
        {
            $shortMessage = substr($message, 0, 60);
        }
        $senderID = intval($this
            ->input
            ->post('sender_id'));
        $date = $this
            ->input
            ->post('date');
        $this
            ->db
            ->insert('messages', array(
            'sender_id' => $senderID,
            'message' => $message,
            'date' => $date
        ));
        $lastID = intval($this
            ->db
            ->insert_id());
        $users = $this
            ->db
            ->get('users')
            ->result_array();
		$message = $this
            ->db
            ->get_where('messages', array(
            'id' => $lastID
        ))->row_array();
        $message['name'] = $this
            ->db
            ->get_where('users', array(
            'id' => $senderID
        ))->row_array() ['name'];
        FCM::send_message_without_notification('/topics/chat', 4, array(
        	'message_id' => "" . $lastID,
        	'message' => json_encode($message)
        ));
        
        echo json_encode($message);
    }

    public function get_messages()
    {
        $start = intval($this
            ->input
            ->post('start'));
        $length = intval($this
            ->input
            ->post('length'));
        $this
            ->db
            ->limit($length, $start);
        $this
            ->db
            ->order_by('date', 'DESC');
        $messages = $this
            ->db
            ->get('messages')
            ->result_array();
        for ($i = 0;$i < sizeof($messages);$i++)
        {
            $messages[$i]['name'] = $this
                ->db
                ->get_where('users', array(
                'id' => intval($messages[$i]['sender_id'])
            ))->row_array() ['name'];
        }
        echo json_encode($messages);
    }

    public function update_profile()
    {
    	$email = $this->input->post('email');
        $name = $this
            ->input
            ->post('name');
        $description = $this
            ->input
            ->post('description');
        $birthYear = intval($this
            ->input
            ->post('birth_year'));
        $gender = intval($this
            ->input
            ->post('gender'));
        $religion = $this
            ->input
            ->post('religion');
        $pangkalan = intval($this
            ->input
            ->post('pangkalan'));
        $allowComments = intval($this
            ->input
            ->post('allow_comments'));
        $allowPrivateChats = intval($this
            ->input
            ->post('allow_private_chats'));
        $receiveAlerts = intval($this
            ->input
            ->post('receive_alerts'));
        $role = intval($this
            ->input
            ->post('role'));
        if (!empty($_FILES['file']['name']))
        {
            $config = array(
                'upload_path' => './userdata',
                'allowed_types' => "gif|jpg|png|jpeg",
                'overwrite' => true,
                'max_size' => "2048000"
            );
            $this
                ->load
                ->library('upload', $config);
            if ($this
                ->upload
                ->do_upload('file'))
            {
            	$this->db->where('email', $email);
                $this
                    ->db
                    ->update('users', array(
                    'name' => $name,
                    'description' => $description,
                    'birth_year' => $birthYear,
                    'gender' => $gender,
                    'religion' => $religion,
                    'pangkalan' => $pangkalan,
                    'photo' => $this
                        ->upload
                        ->data('file_name') ,
                    'allow_comments' => $allowComments,
                    'allow_private_chats' => $allowPrivateChats,
                    'receive_alerts' => $receiveAlerts,
                    'role' => $role,
                    'profile_completed' => 1
                ));
            }
            $this->db->where('email', $email);
            $userID = intval($this
                ->db
                ->get('users')->row_array()['id']);
            $user = $this
                ->db
                ->get_where('users', array(
                'id' => $userID
            ))->row_array();
            $role = intval( $user['role']);
            echo json_encode(array(
                'response_code' => 1,
                'data' => array(
                    'user_id' => $userID,
                    'role' => $role,
                    'pangkalan' => intval($user['pangkalan'])
                )
            ));
        }
        else
        {
        	$this->db->where('email', $email);
            $this
                ->db
                ->update('users', array(
                'name' => $name,
                'description' => $description,
                'birth_year' => $birthYear,
                'gender' => $gender,
                'religion' => $religion,
                'pangkalan' => $pangkalan,
                'allow_comments' => $allowComments,
                'allow_private_chats' => $allowPrivateChats,
                'receive_alerts' => $receiveAlerts,
                'role' => $role,
                'profile_completed' => 1
            ));
            $this->db->where('email', $email);
            $userID = intval($this
                ->db
                ->get('users')->row_array()['id']);
            $user = $this
                ->db
                ->get_where('users', array(
                'id' => $userID
            ))->row_array();
            $role = intval( $user['role']);
            echo json_encode(array(
                'response_code' => 1,
                'data' => array(
                    'user_id' => $userID,
                    'role' => $role,
                    'pangkalan' => intval($user['pangkalan'])
                )
            ));
        }
    }
    
    public function get_random_ad() {
    	echo json_encode($this->db->query("SELECT * FROM `ads` ORDER BY RAND() LIMIT 1")->row_array());
    }
    
    public function refresh_alarm() {
		$url = "https://fcm.googleapis.com/fcm/send";
	    $serverKey = 'AAAAvB1532o:APA91bG6nIch54bG8iwHxWGu0QmNru3piqFqu5n3M_pKXofcuXW2NhMos4a9p8JtO_5WisBJLIYvE5007HoKrs0u-UctvoLkYAppchSUefuVENEl5Vnx6mx70ZQVTJ1MiWWG3EHmH73l';
	    $arrayToSend = array('to' => '/topics/refresh_alarm', 'priority'=>'high', 'data' => array(
	    	'notification_type' => 6,
	    	'show_notification' => 0,
	    	'receive_alerts' => 1
	    ));
	    $json = json_encode($arrayToSend);
	    $headers = array();
	    $headers[] = 'Content-Type: application/json';
	    $headers[] = 'Authorization: key='. $serverKey;
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"POST");
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER,TRUE);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
	    curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
	    //Send the request
	    $response = curl_exec($ch);
	    //echo $response;
	    //Close request
	    if ($response === FALSE) {
	    	die('FCM Send Error: ' . curl_error($ch));
	    }
	    curl_close($ch);
	}
	
	public function get_documents() {
		echo json_encode($this->db->query("SELECT * FROM `other_instances`")->result_array());
	}
	
	public function get_documents_by_type() {
		$type = $this->input->post('type');
		echo json_encode($this->db->query("SELECT * FROM `other_instances` WHERE `type`='" . $type . "'")->result_array());
	}
	
	public function get_prayer_times() {
		$religion = $this->input->post('religion');
		$day = $this->input->post('day');
		echo json_encode($this->db->query("SELECT * FROM `prayer_times` WHERE `religion`='" . $religion . "' AND `day`=" . $day)
			->result_array());
	}
	
	public function get_prayer_times_by_user_id() {
		$userID = intval($this->input->post('user_id'));
		$day = intval($this->input->post('day'));
		$user = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $userID)->row_array();
		$religion = $user['religion'];
		echo json_encode($this->db->query("SELECT * FROM `prayer_times` WHERE `religion`='" . $religion . "' AND `day`=" . $day)
			->result_array());
	}
	
	public function get_holidays_by_user_id() {
		$userID = intval($this->input->post('user_id'));
		$user = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $userID)->row_array();
		$religion = $user['religion'];
		echo json_encode($this->db->query("SELECT * FROM `holiday_times` WHERE `religion`='" . $religion . "'")
			->result_array());
	}
	
	public function get_user_by_id() {
		$userID = intval($this->input->post('id'));
		echo json_encode($this->db->query("SELECT * FROM `users` WHERE `id`=" . $userID)->row_array());
	}
	
	public function send_complaint() {
		$userID = intval($this->input->post('user_id'));
		$report = $this->input->post('report');
		$this->db->insert('reports', array(
			'user_id' => $userID,
			'report' => $report
		));
		$user = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $userID)->row_array();
		$pangkalan = intval($user['pangkalan']);
		$commanders = $this->db->query("SELECT * FROM `users` WHERE `pangkalan`=" . $pangkalan . " AND `role`=0")->result_array();
		for ($i=0; $i<sizeof($commanders); $i++) {
			$fcmID = $commanders[$i]['fcm_id'];
			FCM::send_message($fcmID, 7, 1, 'Pengaduan baru', "Baru saja ada pengaduan baru dari komandan " . $commanders[$i]['name'],
               	array(
                	'commander_id' => $commanders[$i]['id'],
	                'pangkalan' => "" . $pangkalan
                )
            );
		}
	}
	
	public function get_complaints() {
		$userID = intval($this->input->post('user_id'));
		echo json_encode($this->db->query("SELECT * FROM `reports` WHERE `user_id`=" . $userID)->result_array());
	}
	
	public function get_nominatif_batalyon_document() {
		$type = $this->input->post('type');
		echo json_encode($this->db->query("SELECT * FROM `nominatif_batalyon` WHERE `type`='" . $type . "' LIMIT 1")->row_array());
	}
	
	public function upload_nominatif_batalyon_document() {
		$type = $this->input->post('type');
		$config['upload_path']          = './userdata/';
        $config['allowed_types']        = '*';
        $config['max_size']             = 2147483647;
        $config['file_name']            = Util::generateUUIDv4();
        $this->load->library('upload', $config);
        if ($this->upload->do_upload('file')) {
        	$documentCount = $this->db->query("SELECT * FROM `nominatif_batalyon` WHERE `type`='" . $type . "' LIMIT 1")->num_rows();
        	if ($documentCount > 0) {
        		$this->db->query("UPDATE `nominatif_batalyon` SET `path`='" . $this->upload->data()['file_name'] . "' WHERE `type`='" . $type . "'");
        	} else {
        		$this->db->insert('nominatif_batalyon', array(
        			'path' => $this->upload->data()['file_name'],
        			'type' => $type
        		));
        	}
        } else {
        	echo json_encode($this->upload->display_errors());
        }
	}
}
