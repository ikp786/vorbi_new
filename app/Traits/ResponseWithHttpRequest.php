<?php
namespace App\Traits;

use App\Models\Notification;
use Twilio\Exceptions\RestException;
use Twilio\Rest\Client;
use Twilio\Jwt\ClientToken;

trait ResponseWithHttpRequest{


	protected function sendSuccess($message, $result = NULL)
    {
    	$response = [
            'ResponseCode'      => 200,
            'Status'            => True,
            'Message'           => $message,
        ];

        if(!empty($result)){
            $response['Data'] = $result;
        }
        return response()->json($response, 200);
    }


    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    protected function sendFailed($errorMessages = [], $code = 200)
    {
    	$response = [
            'ResponseCode'      => $code,
            'Status'            => False,
        ];


        if(!empty($errorMessages)){
            $response['Message'] = $errorMessages;
        }


        return response()->json($response, $code);
    }


    public function SendNotification($device_token, $title, $body, $user_id)
	{
		$url = 'https://fcm.googleapis.com/fcm/send';
		$headers = array(
			// 'Authorization: key=AAAAMhZ6hz4:APA91bHhKoaHrV2STpuGK3WxWY6e06iAa6vn28LZ9r9iAuq3cT2geqok4y7evY57rTAcdr0v-ccH1KIvKTykP6wFPKFf_nziS7AWB9-RhW_0uqrEKdagnyf0ZI0wLs2QkU5X8tCshLZr',
			   'Authorization: key=AAAA3qk-DSQ:APA91bFXPfx39o6ZrdK1XdbN4T0qYDQwXLj45-HkQLN9oJtWiJpBPxAoRYAQpipn62WMYNkBbYcS7tUHcpkmHX89-_T1lmnE0GWwEyYTVGTlp_7FlFHZRpdxaS1Ua64-WYaYpZwx_XFl',
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
			$notification = new Notification();
			$notification->user_id 	= $user_id;
			$notification->title 	= $title;
			$notification->details  = $body;
			$notification->save();
		return $result;
	}


	public function generateToken()
{
    $account_sid = 'C1lUCshlOcNsfMcwcPVMb1tA9vM3cUDi';
    $auth_token = '41d413081d90f505719863de40eb5b88';
    $twilio_number = '+18885976002';

    $capability = new ClientToken($account_sid, $auth_token);
    $capability->allowClientOutgoing($twilio_number);
    $token = $capability->generateToken();

    return  $token;
}

}