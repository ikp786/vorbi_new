<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ResponseWithHttpRequest;
use Illuminate\Http\Request;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\SyncGrant;
// use Twilio\Rest\Client;
use Twilio\TwiML\VoiceResponse;
use GuzzleHttp\Client;
use App\Services\ZegoTokenGenerator;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;

use Pushok\AuthProvider\Token;
use Pushok\Client as PushokClient;
use Pushok\Notification;
use Pushok\Payload;
use Pushok\Payload\Alert;
use Pushok\Payload\Aps;




use Twilio\Jwt\Grants\VoiceGrant;
class TwilioController extends Controller

{
    use ResponseWithHttpRequest;
    // use GuzzleHttp\Client;

    protected $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.jiocloud.com'
        ]);
    }


    public function sendNotification(Request $request)
    {



        $appId = 'YOUR_APP_ID';
        $deviceVoipToken = 'DEVICE_VOIP_TOKEN';
        $appId = '6447087343';
    $deviceVoipToken = 'e093571db2ef7affdc1daf401ae90f9c5964f71ca79b01eb1d6b78f5fb9cb829';
    
        $client = new Client();
        $response = $client->post('https://onesignal.com/api/v1/players', [
            'json' => [
                'app_id' => $appId,
                'identifier' => $deviceVoipToken,
                'device_type' => 0,
                'test_type' => 1,
            ],
        ]);
    
        $result = json_decode($response->getBody()->getContents(), true);
        dd($result);
        $appId = 6447087343;
    $deviceVoipToken = 'e093571db2ef7affdc1daf401ae90f9c5964f71ca79b01eb1d6b78f5fb9cb829';

    $client = new Client();
    $response = $client->post('https://onesignal.com/api/v1/players', [
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'app_id' => $appId,
            'identifier' => $deviceVoipToken,
            'device_type' => 0,
            'test_type' => 1,
        ],
    ]);

    $result = json_decode($response->getBody()->getContents(), true);

    // Do something with the $result or return it as a response
dd($result);

        $ch = curl_init();

curl_setopt($ch, CURLOPT_URL, 'https://onesignal.com/api/v1/players');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"app_id\" : \"6447087343\",\n\"identifier\":\"e093571db2ef7affdc1daf401ae90f9c5964f71ca79b01eb1d6b78f5fb9cb829\",\n\"device_type\":0,\n\"test_type\":1\n}");

$headers = array();
$headers[] = 'Content-Type: application/json';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close($ch);



        dd($result);
        $deviceToken = $request->input('deviceToken');
        
        if ($deviceToken) {
            $authProvider = Token::create([

                'key_id' => 'WBV3973Z8S',
                'team_id' => 'SJLCJ5R644',
                'app_bundle_id' => 'in.the.VorbiAppcallApp',
                'private_key_path' => asset('AuthKey_WBV3973Z8S.p8'),
            ]);           

            $alert = Alert::create()
            ->setTitle('You have a new message')
            ->setPriority(10); // Set the priority within the alert object
        
        $payload = Payload::create()
            ->setAlert($alert)
            ->setBadge(3)
            ->setSound('ping.aiff')
            ->setCustomValue('payload', $request->payload);
        
        $notification = new Notification($payload, $request->deviceToken);
        
        // Set the topic when creating the Pushok\Client instance
        $client = new Client($authProvider, $production = false);
        $client->addNotification($notification);
        $client->setTopic('in.the.VorbiAppcallApp');
        
        $response = $client->push();
        
            
            $responses = $client->push();
            
            foreach ($responses as $response) {
                if ($response->getStatusCode() !== 200) {
                    return response()->json($response->getReasonPhrase(), 400);
                }
            }
            
            return response()->json($responses, 200);
        } else {
            return response()->json(['message' => 'APN Token is required'], 400);
        }
    }
    public function generateTokens_beckup(Request $request)
    {

        return view('front.zego_uikit');


    }


    public static function generateKitToken2($appID, $serverSecret, $roomID, $userID, $userName)
    {
        $iat = time();
        $exp = $iat + 3600;

        $payload = [
            'iat' => $iat,
            'exp' => $exp,
            'app_id' => $appID,
            'room_id' => $roomID,
            'user_id' => $userID,
            'user_name' => $userName,
        ];

        return JWT::encode($payload, $serverSecret, 'HS256');
    }

    public function generateTokens(Request $request)
    {
        $error_message = 	[
			'user_id.required'   			=> 'User should be required',			
		];

		$rules = [			
			'user_id'          	=> 'required',
		];
		$validator = \Validator::make($request->all(), $rules, $error_message);
		if ($validator->fails()) {
			return $this->sendFailed($validator->errors()->first(), 201);
		}
        $response = Http::get('https://aksasoftware.com/vorbi/public/zeocloud/test/test.php?user_id='.$request->user_id);
        $kitToken = $response->body();
        $kitToken = json_decode($response->getBody(), true);
        // dd($kitToken);

        // dd($kitToken['room_id']);
        $deviceToken = 'e093571db2ef7affdc1daf401ae90f9c5964f71ca79b01eb1d6b78f5fb9cb829';
        self::testnotification($kitToken['room_id'],$deviceToken);
        
return $this->sendSuccess('TOKEN GENERATE SUCCESSFULLY!', ['access_token' => $kitToken['token']]);
    }

    public function testnotification($roomId,$deviceToken)
	{
		// $certFile =  '/path/to/certificate.pem';
		$certFile = public_path('vorbi.crt.pem');
		// echo $certFile; exit;
		// Set the passphrase for your certificate file
		// $certPassphrase = public_path('AuthKey_WBV3973Z8S.p8');
		$certPassphrase = '1234';

		// Set the device token of the recipient
		// $deviceToken = 'e093571db2ef7affdc1daf401ae90f9c5964f71ca79b01eb1d6b78f5fb9cb829';

		// Set the payload of the notification
		$payload = [
			'aps' => [
				'content-available' => 1,
				'sound' => '',
				'badge' => 0,
				'category' => 'INCOMING_CALL'
			],
			'call_id' => $roomId //room id
		];

		// Encode the payload as JSON
		$jsonPayload = json_encode($payload);

		// Set the URL for the APNS HTTP/2 API endpoint
		$url = 'https://api.development.push.apple.com/3/device/' . $deviceToken ;
		// $url = 'https://api.push.apple.com/3/device/' . $deviceToken;

		// Set the headers for the HTTP/2 request
		$headers = [
			'apns-topic: in.the.VorbiAppcallApp.voip',
			'apns-push-type: voip',
			'apns-expiration: 0',
			'apns-priority: 10'
		];

		// Create a new cURL resource
		$ch = curl_init();

		// Set the cURL options
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_SSLCERT, $certFile);
		curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $certPassphrase);

		// Execute the cURL request
		$result = curl_exec($ch);

		// Check for errors
		if ($result === false) {
			$error = curl_error($ch);
			// Handle the error
			print_r($error);
		}

		// Get the HTTP status code
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		// Close the cURL resource
		curl_close($ch);
		// print_r($result);die;
		
		// Handle the response
		if ($status === 200) {
			// echo 'Notification sent successfully';
		} else {
			// echo  'Notification failed to send';
		}
	}
}
