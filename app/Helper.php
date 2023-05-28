<?php

namespace App\Helpers;

use App\Models\Properties;
use App\Models\Bookings;
use App\Models\Notification;
use Carbon\Carbon;

class Helper
{
	// GENERATE RANDOM REQUEST AND BOOKING NUMBER
	public function random_strings($length_of_string = 6)
	{

		// String of all alphanumeric character
		$str_result = '0123456789';

		// Shuffle the $str_result and returns substring
		// of specified length
		return substr(
			str_shuffle($str_result),
			0,
			$length_of_string
		);
	}
	public function SendNotification($device_token, $title, $body, $user_id, $is_save)
	{

		// $is_save  = 1 mean save or 0 mean not save
		$url = 'https://fcm.googleapis.com/fcm/send';
		$headers = array(
			'Authorization: key=AAAADpacoo8:APA91bETCW_UEQAQoPdqtKFLwRau0DMuSaWF1ysXP8Se8iVzbdLWmd9MVj9Dkfs78YhQICKqaw44UqIc8vqboMXnP9wR-R0Z0hcjrmttOEDWm9of09C2o5BSShjZI6H5XxFOPYasr9p9',
			'Content-Type: application/json',
		);

		$data = array(
			"to" => $device_token,
			"notification" =>
			array(
				"title" 			=> $title,
				"body"  			=> $body,
				"sound" 			=> 'default',
				'badge'             => '1',
				'action_type'       => 'transfer',
			),
			"data" =>
			array(
				"title" 			=> $title,
				"body"  			=> $body,
				"sound" 			=> 'default',
				'badge'             => '1',
				'action_type'       => 'transfer',
			)
		);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		$result = curl_exec($ch);
		curl_close($ch);
		if ($is_save == 1) :
			$notification = new Notification;
			$notification->user_id 	= $user_id;
			$notification->title 	= $title;
			$notification->details  = $body;
			$notification->save();
		endif;
		return $result;
	}

	static function agoDate($date)
	{
		return Carbon::parse($date)->diffForHumans(null, true, true, 2) . ' ago';
		// return Carbon::parse($date)->diffForHumans();
	}
}
