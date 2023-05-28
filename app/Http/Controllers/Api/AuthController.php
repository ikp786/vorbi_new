<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Resources\DriverProfile;
use App\Http\Resources\NotificationCollection;
use App\Http\Resources\TopicResource;
use App\Http\Resources\UserProfileCollection;
use App\Models\CallHistory;
use App\Models\CouponCartMapping;
use App\Models\TempCall;
use App\Models\Language;
use App\Models\Ratting;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Traits\ResponseWithHttpRequest;
use App\Models\User;
use App\Models\Version;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;
use Validator;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Http;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\VoiceGrant;


class AuthController extends Controller
{
	use ResponseWithHttpRequest;


	public function __construct()
	{
		// $this->Helper = new Helper;

		// $this->Helper->SendNotification(
		// 	$token,
		// 	$title,
		// 	$details,
		// 	$id,
		// 	1,
		// );
	}

	// UNAUTHORIZED ACCESS
	public function unauthorized_access()
	{
		return $this->sendFailed('YOU ARE NOT UNAUTHORIZED TO ACCESS THIS URL, PLEASE LOGIN AGAIN', 401);
	}

	public function loginOtpVerify(Request $request)
	{
		$error_message = 	[
			'user_id.required'			 	  => 'User Id should be required',
			'otp.required'					  => 'OTP should be required',
		];
		$rules = [
			'user_id'						  => 'required|integer|exists:users,id',
			'otp'						      => 'required|integer',
			'device_token'					  => 'required'
		];
		$validator = Validator::make($request->all(), $rules, $error_message);
		if ($validator->fails()) {
			return $this->sendFailed($validator->errors()->first(), 201);
		}
		try {

			\DB::beginTransaction();
			$user_detail = User::find($request->user_id);

			if ($request->otp != $user_detail->otp) {
				return $this->sendFailed("wrong otp", 201);
			}
			Auth::loginUsingId($user_detail->id);

			$access_token	  = $user_detail->createToken("API TOKEN")->plainTextToken;
			$access_token 	  = explode('|', $access_token)[1];
			$user = auth()->user()->fill($request->only(['device_token']));
			$token = $this->generateToken();
			$user->unique_id  = self::uniqueNumber();
			$user->type 	  = 'User';
			$user->login_type = 'Mobile';
			$user->twilio_token = $token;
			$user->firebase_token = $request->firebase_token;
			$user->save();
			\DB::commit();
			return $this->sendSuccess('LOGGED IN SUCCESSFULLY', ['access_token' => $access_token, 'profile_data' => new UserProfileCollection(auth()->user())]);
		} catch (\Throwable $e) {
			\DB::rollback();
			return $this->sendFailed($e->getMessage() . ' on line ' . $e->getLine(), 400);
		}
	}

	private function uniqueNumber()
	{
		$unique = 'VB-' . rand(1111, 9999);
		if (User::whereUniqueId($unique)->count() > 0) {
			self::uniqueNumber();
		}
		return $unique;
	}




	// CREATE ACCOUNT API
	public function login(Request $request)
	{
		$error_message = 	[
			'mobile.required'            	  => 'Mobile should be required',
		];
		$rules = [
			'mobile'                          => 'required|min:6|max:15'
		];
		$validator = Validator::make($request->all(), $rules, $error_message);
		if ($validator->fails()) {
			return $this->sendFailed($validator->errors()->first(), 201);
		}

		try {
			\DB::beginTransaction();
			$user = User::updateOrCreate(
				$request->only('mobile')
			);

			if ($request->mobile == '917023934474') :
				$verifaction_otp = 1234;

			else :
				$verifaction_otp = rand(1000, 9999);
			endif;
			$user->otp = $verifaction_otp;
			$user->save();

			$sid    = env('TWILIwO_SID', "AC5eb1cd9b22a4694cc84112fe0172a731");
			$token  = env('TWILIO_TOKEN', "41d413081d90f505719863de40eb5b88");
			$twilio = new Client($sid, $token);
			$message = $twilio->messages->create(
				$request->mobile,
				array(
					"messagingServiceSid" => "MG7bcb74b276ce253279624fcc8dd1cf33",
					"body" => "One time password for Vorbi is " . $verifaction_otp
				)
			);

			\DB::commit();
			return $this->sendSuccess('OTP SENT SUCCESSFULLY', ['user_id' => $user->id, 'otp' => $verifaction_otp]);
		} catch (\Throwable $e) {
			\DB::rollback();
			return $this->sendFailed($e->getMessage() . ' on line ' . $e->getLine(), 400);
		}
	}
	public function deletefromtemp()
	{
		TempCall::whereDate("created_at",date("Y-m-d"))->delete();
	}



	public function loginWithSocialMedia(Request $request)
	{
		$error_message = 	[
			'email.required'   			=> 'Email address should be required',
			'social_media_id.required'  => 'Social media id should be required',
			'device_token.required'     => 'Device token should be required',
			'login_type.required'     	=> 'User type should be required',
		];

		$rules = [
			// 'email'        			=> 'required',
			'social_media_id'       => 'required',
			'device_token'          => 'required',
			'login_type'          	=> 'required',
		];
		$validator = Validator::make($request->all(), $rules, $error_message);
		if ($validator->fails()) {
			return $this->sendFailed($validator->errors()->first(), 201);
		}
		try {
			$user_detail = User::where('social_media_id', $request->social_media_id)->first();
			if (!isset($user_detail) || $user_detail->social_media_id == '') {
				$user_detail = User::create($request->only('email', 'social_media_id', 'device_token', 'login_type'));
			}
			if (auth()->loginUsingId($user_detail->id)) {
				\DB::beginTransaction();
				$access_token 	  = $user_detail->createToken("API TOKEN")->plainTextToken;
				$access_token 	  = explode('|', $access_token)[1];
				User::where('device_token', $request->device_token)->where('email', '!=', $request->email)->update(['device_token' => null]);
				if ($user_detail->unique_id == '') {
					$unique_id  = self::uniqueNumber();
					$request->unique_id = $unique_id;
					$request['unique_id'] = $unique_id;
				} else {
					$request->unique_id = $user_detail->unique_id;
					$request['unique_id'] = $user_detail->unique_id;
				}
				auth()->user()->fill($request->only('device_token', 'unique_id'))->save();
				\DB::commit();
				return $this->sendSuccess('LOGGED IN SUCCESSFULLY', ['access_token' => $access_token, 'profile_data' => new UserProfileCollection(auth()->user())]);
			} else {
				return $this->sendFailed('WE COULD NOT FOUND ANY ACCOUNT', 201);
			}
		} catch (\Throwable $e) {
			\DB::rollback();
			return $this->sendFailed($e->getMessage() . ' on line ' . $e->getLine(), 400);
		}
	}

	function sentVoipToken(Request $request)
	{

		$device_token = $request->device_token;
		$matchedUsers     = User::where('device_token', $device_token)->first();
		$roomId		  = '';
		$deviceToken  = '';
		$userId	      = '';
		$Id           = '';
		$token		  = '';
		$device_token = auth()->user()->device_token;
		$type		  = $request->type;
		self::testnotification($roomId, $deviceToken, $userId, $token, $device_token, $id, $type);

		$myData = User::find(auth()->user()->id);

		return $this->sendSuccess('TOKEN GENERATE SUCCESSFULLY!', [
			'room_id' => $roomId,
			'id' => $matchedUsers->$id,
			'unique_id' => $matchedUsers->unique_id, 'token' => $myData->twilio_token, 'device_token' => $device_token
		]);
	}

	public function testnotification($roomId, $deviceToken, $userId, $token, $device_token, $type, $secondUser = '',$notificationType='')
	{
		// $certFile =  '/path/to/certificate.pem';
		if (empty($secondUser)) {
			$secondUser = $userId;
		}
		$certFile = public_path('vorbi.crt.pem');
		// echo $certFile; exit;
		// Set the passphrase for your certificate file
		// $certPassphrase = public_path('AuthKey_WBV3973Z8S.p8');
		$certPassphrase = '1234';

		// Set the device token of the recipient
		// $device_token = 'e093571db2ef7affdc1daf401ae90f9c5964f71ca79b01eb1d6b78f5fb9cb829';

		// Set the payload of the notification
		$payload = [
			'aps' => [
				'content-available' => 1,
				'sound' => '',
				'badge' => 0,
				'category' => 'INCOMING_CALL'
			],
			'call_id'		 => $userId, //room id
			'room_id'		 => $roomId,
			'user_id'	 	 => auth()->user()->id,
			'token'			 =>  $token,
			'device_token'   =>  $device_token,
			'type'   		 =>  $notificationType ?  $notificationType:  $type,

		];
		// \Log::info("payload",json_encode($payload));
		// print_R($payload); exit;
		// Encode the payload as JSON
		$jsonPayload = json_encode($payload);

		// Set the URL for the APNS HTTP/2 API endpoint
 		$url = 'https://api.development.push.apple.com/3/device/' . $deviceToken;
//		$url = 'https://api.push.apple.com/3/device/' . $deviceToken;




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

	function callHistory()
	{
		$allCalls = auth()->user()->allCallsId()
			->pluck('callee_id');
		$allCalls = $allCalls->unique();
		$specificValueToRemove = auth()->user()->id;
		$allCalls = $allCalls->filter(function ($calleeId) use ($specificValueToRemove) {
			return $calleeId != $specificValueToRemove;
		});
		// Reset the collection keys
		$allCalls = $allCalls->values();

		$users = User::find($allCalls);
		$result = UserProfileCollection::collection($users);
		$finalResult = array();
		foreach ($result as $key => $val) {
			$rating = Ratting::where("to_user_id", $val->id)->avg("rating_star");
			$finalResult[$key] = $val;
			$finalResult[$key]['rating'] = round($rating);
		}

		return $this->sendSuccess('HISTORY GET SUCCESSFULLY', ['data' => $finalResult]);
	}

	function findMyMatchConnection(Request $request)
	{
		$type="";

		if (!isset($request->user_id)) {
		
			$allCalls = auth()->user()->allCallsId->pluck('callee_id');
			$historyCall=TempCall::where("from_user_id",auth()->user()->id)->pluck("to_user_id");
			$allCalls->push(auth()->user()->id);
			$allCalls->unique();
			$authUserTopicIds = auth()->user()->topics()->pluck('topics.id');
			$primaryId = auth()->user()->id;

			// Retrieve all users who have matched topics with the authenticated user
			$matchedUsers = auth()->user()->whereHas('topics', function ($query) use ($authUserTopicIds, $allCalls, $primaryId,$historyCall) {
				$query->whereIn('topic_id', $authUserTopicIds)
					->where('user_id', "!=", $primaryId)
					->whereNotIn("user_id",$historyCall);

			})->inRandomOrder()->first();

	            if(!isset($matchedUsers->id)){
					TempCall::where("from_user_id",auth()->user()->id)->delete();
		        	return $this->sendFailed('Sorry!! Currently no users are available',201);
		        }
		} else {
			$matchedUsers = User::find($request->user_id);
			if(empty($matchedUsers)){
			    return $this->sendFailed('Sorry!! Currently Matched Users are available',201);
			}
			$type="history";
		}
		// START
		$user_id = 'user_id';
		$streaming_id = rand(100000, 999999);
		$roomId 	= "room1" . time();
		$unique_id = auth()->user()->unique_id;


        $accountSid = 'AC5eb1cd9b22a4694cc84112fe0172a731';
        $authToken = '41d413081d90f505719863de40eb5b88';
        $twilioAppSid = 'SKfd7acca3e9a2833bdd265a69efaf00ff';
        $identity = auth()->user()->id;

        $accessToken = new AccessToken($accountSid, $authToken, $twilioAppSid, $identity);
        $grant = new VoiceGrant();
        $grant->setOutgoingApplicationSid('SKfd7acca3e9a2833bdd265a69efaf00ff');
        $accessToken->addGrant($grant);
        $token = $accessToken->toJWT();
        
        $user_id 	= auth()->user()->id;
		// $kitToken2 = json_decode($response2->getBody());
        $matchedUsersId = $matchedUsers->id;
        $accessToken = new AccessToken($accountSid, $authToken, $twilioAppSid, $matchedUsersId);

        $grant = new VoiceGrant();
        $grant->setOutgoingApplicationSid('SKfd7acca3e9a2833bdd265a69efaf00ff');
        $accessToken->addGrant($grant);
        $token2 = $accessToken->toJWT();

		// dd($token,$token2);
		// $deviceToken 	= 'e093571db2ef7affdc1daf401ae90f9c5964f71ca79b01eb1d6b78f5fb9cb829';
		$deviceToken = $matchedUsers->device_token;
		// dd($deviceToken);
		// $user_id = auth()->user()->unique_id;

      


		$streaming_id 	= 'ds';//$kitToken['stream_id_list'];
		$matchedUsers2 = User::find($matchedUsersId);
		$matchToken = $token2;
		$device_token = $matchedUsers2->device_token;
		self::testnotification($roomId, $deviceToken, $user_id, $token2, auth()->user()->device_token, '', $matchedUsers->id,$type);

        // self::testnotification($roomId, $deviceToken, $user_id, $matchToken, auth()->user()->device_token, '', $matchedUsers->id,$type);
		$myData = User::find(auth()->user()->id);
		TempCall::insert(array("from_user_id"=>auth()->user()->id,"to_user_id"=>$matchedUsers->id,"room_id" => $roomId, "streaming_id" => $streaming_id));


		return $this->sendSuccess('TOKEN GENERATE SUCCESSFULLY!', [
			'room_id' => $roomId, 
			'unique_id' => $matchedUsers->unique_id,
			'user_id' => $matchedUsers->id,
			'twilio_token' => $token, 
			'device_token' => $device_token
		]);
		//END
		return $this->sendSuccess('MATCHED USER GET SUCCESSFULLY', ['data' => new UserProfileCollection($matchedUsers)]);
	}

	function saveRatting(Request $request)
	{

		$validator  = \Validator::make(
			$request->all(),
			[
				'to_user_id'		=> 'required|exists:users,id',
				'rating_star'		=> 'required|integer|min:1|max:5'
			]
		);

		if ($validator->fails()) {
			return $this->sendFailed($validator->errors()->first(), 201);
		}

		$from_user_id = auth()->user()->id;
		$to_user_id = $request->to_user_id;
		$rating_star = $request->rating_star;

		// Create a new instance of Rating model and save the data to the database
		$checkRating = Ratting::where("from_user_id", $from_user_id)->where("to_user_id", $to_user_id)->first();
		if (!empty($checkRating)) {
			$rating =  Ratting::find($checkRating->id);
		} else {
			$rating = new Ratting;
		}

		$rating->from_user_id = $from_user_id;
		$rating->to_user_id = $to_user_id;
		$rating->rating_star = $rating_star;
		$rating->save();

		// save history
		$callHistory = new CallHistory();
		$callHistory->caller_id = auth()->user()->id;
		$callHistory->callee_id = $request->to_user_id;
		$callHistory->save();
		$userfirst=User::find(auth()->user()->id);
		$userfirst->twilio_token=null;
		$userfirst->save();

		$usersec=User::find( $request->to_user_id);
		$usersec->twilio_token=null;
		$usersec->save();

		TempCall::where("from_user_id", auth()->user()->id)->delete();
		$receiver=User::find($to_user_id);
		$sender=User::find($from_user_id);
		$serverKey = 'AAAA4EfHTiA:APA91bEooHWKb66VMZfYtM8YKj35K40LeY4rcZzrXbIGUR4gUyheZcdu6eKt8Evo6MI3fxBuCUCZKR_JF4qRqP5XqtDnMtQpHNNcz-1TzIJXcacFgO5vXNqya7VPlyI5Rd7Zbxov1flf';
		$token = $receiver->firebase_token;
		$body=' You got '. $rating_star.' star on the conversation with '.$sender->unique_id.' at '.date("d M ,Y h:i a");
		$message = [
			'title' => 'Vorbi',
			'body' => $body,
			// 'icon' => 'your_icon',
			'sound' => 'default',
			// 'click_action' => 'your_action'
		];

		$Notification = new Notification();
		$Notification->title = "Vorbi";
		$Notification->details = $body;
		$Notification->user_id = $to_user_id;
		$Notification->save();

		$fields = [
			'to' => $token,
			'notification' => $message
		];

		$headers = [
			'Authorization: key=' . $serverKey,
			'Content-Type: application/json'
		];

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

		$result = curl_exec($ch);
		/* if ($result === false) {
			echo 'Curl error: ' . curl_error($ch);
		} else {
			echo $result;
		} */

		curl_close($ch);

		return $this->sendSuccess('RATTING SAVE SUCCESSFULLY');
	}

	function getRatting()
	{
		try {
			$user = User::find(4); //fetch the specific user using user id
			$averageRating = $user->ratings()->avg('rating_star'); //calculate the average rating of the user
			$data  = ["averageRating"  => round($averageRating)];
			return $this->sendSuccess('RATTING GET SUCCESSFULLY', $data);
		} catch (\Throwable $e) {
			return $this->sendFailed($e->getMessage() . "On Line " . $e->getLine(), 400);
		}
	}


	public function getUserProfile()
	{
		return $this->sendSuccess('LOGGED IN SUCCESSFULLY', ['profile_data' => new UserProfileCollection(auth()->user())]);
	}

	public function getDriverProfile()
	{
		$delivery_charge                = Setting::value('deliver_charge');
		return $this->sendSuccess('LOGGED IN SUCCESSFULLY', ['profile_data' => new DriverProfile(auth()->user())]);
	}

	public function sentRegisterOtp(Request $request)
	{
		$error_message = 	[
			'mobile.required'  	=> 'Mobile address should be required',
		];
		$rules = [
			'mobile'       		=> 'required',
		];
		$validator = Validator::make($request->all(), $rules, $error_message);
		if ($validator->fails()) {
			return $this->sendFailed($validator->errors()->first(), 201);
		}
		try {
			$verifaction_otp = rand(1000, 9999);
			$email_data = ['otp' => $verifaction_otp];
			// \Mail::to($request->email)->send(new \App\Mail\LoginOtp($email_data));
			return $this->sendSuccess('OTP SENT SUCCESSFULLY', ['verifaction_otp' => $verifaction_otp, 'mobile' => $request->mobile]);
		} catch (\Throwable $e) {
			return $this->sendFailed($e->getMessage() . ' on line ' . $e->getLine(), 400);
		}
	}

	public function reSentOtpMobileUpdate(Request $request)
	{
		$error_message = 	[
			'mobile.required'  	=> 'Mobile address should be required',
		];
		$rules = [
			'mobile' => 'required|unique:users,mobile,' . auth()->user()->id,
		];
		$validator = Validator::make($request->all(), $rules, $error_message);
		if ($validator->fails()) {
			return $this->sendFailed($validator->errors()->first(), 201);
		}
		$mobileExist = User::where('id', '!=', auth()->user()->id)->where(['role' => auth()->user()->role, 'mobile' => $request->mobile])->count();
		if ($mobileExist > 0) {
			return $this->sendFailed("Mobile number has been already taken", 201);
		}
		try {
			$verifaction_otp = rand(1000, 9999);
			Self::send_sms_otp($request->mobile, $verifaction_otp);
			$user = auth()->user();
			$user->otp = $verifaction_otp;
			$user->save();
			return $this->sendSuccess('OTP SENT SUCCESSFULLY', ['verifaction_otp' => $verifaction_otp, 'mobile' => $request->mobile]);
		} catch (\Throwable $e) {
			return $this->sendFailed($e->getMessage() . ' on line ' . $e->getLine(), 400);
		}
	}

	public function registerReSentOtp(Request $request)
	{
		$error_message = 	[
			// 'mobile.unique'  	              => 'mobile has been already taken',
			'mobile.required'            	  => 'Mobile should be required',
			'user_id.required'			 	  => 'User Id should be required',
		];
		$rules = [
			'mobile'                          => 'required|min:10|max:10|exists:users,mobile',
			'user_id'						  => 'required|integer|exists:users,id',
		];
		$validator = Validator::make($request->all(), $rules, $error_message);
		if ($validator->fails()) {
			return $this->sendFailed($validator->errors()->first(), 201);
		}
		$user_detail = User::find($request->user_id);
		if ($user_detail->mobile != $request->mobile) {
			return $this->sendFailed("Mobile number does not exist", 201);
		}
		try {
			$verifaction_otp = rand(1000, 9999);
			// Self::send_sms_otp($request->mobile, $verifaction_otp);
			$user_detail->otp = $verifaction_otp;
			$user_detail->save();
			Self::send_sms_otp($request->mobile, $verifaction_otp);
			\DB::commit();
			return $this->sendSuccess('OTP SENT SUCCESSFULLY', ['user_id' => $user_detail->id, 'otp' => $verifaction_otp]);
		} catch (\Throwable $e) {
			return $this->sendFailed($e->getMessage() . ' on line ' . $e->getLine(), 400);
		}
	}


	// UPDATE PROFILE
	public function updateUserProfile(Request $request)
	{
		$error_message = 	[
			'profile_pic.mimes'  => 'Profile photo format jpg,jpeg,png',
			'profile_pic.max'    => 'Profile photo max size 2 MB',
			'dob.required'		 => 'Date Of Birth should be required.',

		];
		$rules = [
			'city'            => 'required|max:50',
			'language_id'  	  => 'required|exists:languages,id',
			'gender'          => 'required|In:male,female',
		];
		if (!empty($request->profile_pic)) {
			$rules['profile_pic'] = 'mimes:jpg,jpeg,png|max:2000';
		}
		$validator = Validator::make($request->all(), $rules, $error_message);
		if ($validator->fails()) {
			return $this->sendFailed($validator->errors()->first(), 201);
		}

		try {
			\DB::beginTransaction();
			$user_details = auth()->user();
			$user_details->fill($request->only('city', 'language_id', 'gender'));
			if ($request->hasFile('profile_pic')) {
				$filename = time() . '.' . $request->profile_pic->extension();
				$request->profile_pic->move(public_path('user_images'), $filename);
				$user_details->profile_pic = $filename;
			}
			$user_details->profile_complete = 1;
			$user_details->save();
			\DB::commit();
			return $this->sendSuccess('PROFILE UPDATE SUCCESSFULLY', ['profile_data' => new UserProfileCollection(auth()->user())]);
		} catch (\Throwable $e) {
			\DB::rollback();
			return $this->sendFailed($e->getMessage() . ' on line ' . $e->getLine(), 400);
		}
	}

	public function updateTopic(Request $request)
	{
		$error_message = 	[
			'topic.required'  => 'topic is required'
		];
		$rules = [
			'topic'        	 => 'required'
		];
		$validator = Validator::make($request->all(), $rules, $error_message);
		if ($validator->fails()) {
			return $this->sendFailed($validator->errors()->first(), 201);
		}

		try {
			\DB::beginTransaction();
			$user = auth()->user();
			$topic = explode(',', $request->topic);
			// dd($topic);
			$user->topics()->sync($topic);
			\DB::commit();
			return $this->sendSuccess('UPDATE TOPICS SUCCESSFULLY');
		} catch (\Throwable $e) {
			\DB::rollback();
			return $this->sendFailed($e->getMessage() . ' on line ' . $e->getLine(), 400);
		}
	}

	/*
        |--------------------------------------------------------------------------
        | GET TOPICS
        |--------------------------------------------------------------------------
        */
	function getTopic()
	{
		$topic_list = Topic::OrderBy('title', 'asc')->where('status', 'active')->get(['id', 'title']);

		return $this->sendSuccess('TOPIC GET SUCCESSFULLY', ['topics' => TopicResource::collection($topic_list)]);
	}

	function getMyTopic()
	{
		$topic_list = auth()->user()->topics()->get();

		return $this->sendSuccess('TOPIC GET SUCCESSFULLY', ['topics' => TopicResource::collection($topic_list)]);
	}

	/*
        |--------------------------------------------------------------------------
        | GET LANGUAGE
        |--------------------------------------------------------------------------
        */
	function getLanguage()
	{
		$language = Language::OrderBy('title', 'asc')->where('status', 'active')->get(['id', 'title']);

		return $this->sendSuccess('LANGUAGE GET SUCCESSFULLY', ['language' => ($language)]);
	}


	/*
        |--------------------------------------------------------------------------
        | GET NOTIFICATION LIST
        |--------------------------------------------------------------------------
        */
	function getNotification()
	{
		try {
			$notification_list     = auth()->user()->notification()->orderBy('id', 'desc')->get();
			if (count($notification_list) == 0) {
				return $this->sendFailed('NOTIFICATION NOT FOUND', 201);
			}
			return $this->sendSuccess('NOTIFICATION GET SUCCESSFULLY', NotificationCollection::collection($notification_list));
		} catch (\Throwable $e) {
			return $this->sendFailed($e->getMessage() . ' on line ' . $e->getLine(), 400);
		}
	}


	public function forgotPassword(Request $request)
	{
		$error_message = 	[
			'email.required'    => 'Email address should be required',
			'email.exists'      => 'WE COULD NOT FOUND ANY EMAIL'
		];
		$rules = [
			'email'       		=> 'required|email|exists:users,email',
		];
		$validator = Validator::make($request->all(), $rules, $error_message);
		if ($validator->fails()) {
			return $this->sendFailed($validator->errors()->first(), 201);
		}
		try {
			$user_detail = User::where('email', $request->email)->first();
			if (!isset($user_detail)) {
				return $this->sendFailed('WE COULD NOT FOUND ANY ACCOUNT', 201);
			}
			$verifaction_otp = rand(1000, 9999);
			$email_data = ['user_name' => $user_detail->first_name, 'verifaction_otp' => $verifaction_otp];
			\Mail::to($user_detail->email)->send(new \App\Mail\ForgotPassword($email_data));
			return $this->sendSuccess('OTP SENT SUCCESSFULLY', ['user_id' => $user_detail->id, 'verifaction_otp' => $verifaction_otp, 'email' => $user_detail->email]);
		} catch (\Throwable $e) {
			return $this->sendFailed($e->getMessage() . ' on line ' . $e->getLine(), 400);
		}
	}

	public function reset_password(Request $request)
	{
		$error_message = 	[
			'id.required'  		=> 'Id should be required',
			'password.required' => 'Password should be required',
		];
		$rules = [
			'id'        		=> 'required|numeric|exists:users,id',
			'password'      	=> 'required',
		];
		$validator = Validator::make($request->all(), $rules, $error_message);
		if ($validator->fails()) {
			return $this->sendFailed($validator->errors()->first(), 201);
		}
		try {
			$user_detail = User::find($request->id);
			if (!isset($user_detail)) {
				return $this->sendFailed('WE COULD NOT FOUND ANY ACCOUNT', 201);
			}
			\DB::beginTransaction();
			$user_detail->password = Hash::make($request->user_password);
			$user_detail->save();
			\DB::commit();
			return $this->sendSuccess('PASSWORD UPDATED SUCCESSFULLY');
		} catch (\Throwable $e) {
			\DB::rollback();
			return $this->sendFailed($e->getMessage() . ' on line ' . $e->getLine(), 400);
		}
	}

	public function send_sms_otp($mobile_number, $verification_otp)
	{
		// 		return;
		// die;
		// $opt_url = "https://2factor.in/API/V1/fd9c6a99-19d7-11ec-a13b-0200cd936042/SMS/" . $mobile_number . "/" . $verification_otp . "/OTP_TAMPLATE";
		//$opt_url = "https://2factor.in/API/V1/786547ea-bbc8-11ec-9c12-0200cd936042/SMS/" . $mobile_number . "/" . $verification_otp . "/OTP_TAMPLATE";
		$opt_url = "https://2factor.in/API/V1/eaf9b2b6-d5b4-11ec-9c12-0200cd936042/SMS/" . $mobile_number . "/" . $verification_otp . "/FinalTemplate";
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $opt_url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_PROXYPORT, "80");

		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($curl);
		// echo $result;die;
		return;
	}


	public function sendsms2factorotp($numbers, $otp)
	{

		/*   phone = '+918949529301';
        $otp = '7777'; */

		$curl = curl_init();

		curl_setopt_array($curl, array(

			CURLOPT_URL => 'https://2factor.in/API/V1/786547ea-bbc8-11ec-9c12-0200cd936042/SMS/+' . $numbers . '/' . $otp . 'otptemplate',

			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",

			CURLOPT_MAXREDIRS => 10,

			CURLOPT_TIMEOUT => 30,

			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_POSTFIELDS => "{}",

		));

		$response = curl_exec($curl);

		$err = curl_error($curl);

		$respons = json_decode($response, true);
		// print_r($respons); exit;
		return $respons;
		// echo "<pre>";
		// print_r($respons['Status']);

		curl_close($curl);
	}
	public function changeVisibleStatus()
	{
		try {
			\DB::beginTransaction();
			$change = User::find(auth()->user()->id)->update(['visible_status' => auth()->user()->visible_status == 'visible' ? 'invisible' : 'visible']);
			\DB::commit();
			return $this->sendSuccess('visible status change succssfully');
		} catch (\Throwable $e) {
			\DB::rollback();
			return $this->sendFailed($e->getMessage() . ' on line ' . $e->getLine(), 400);
		}
	}

	public function logout()
	{
		try {
			\DB::beginTransaction();
			auth()->user()->carts()->delete();
			CouponCartMapping::where('user_id', auth()->user()->id)->delete();
			auth()->user()->token()->revoke();
			\DB::commit();
			return $this->sendSuccess('Logout succssfully');
		} catch (\Throwable $e) {
			\DB::rollback();
			return $this->sendFailed($e->getMessage() . ' on line ' . $e->getLine(), 400);
		}
	}

	public function deleteMyAccount()
	{
		try {
			DB::beginTransaction();
			auth()->user()->update([

				'email'				=> 'Deleted User',
				'password'			=> 'Deleted User',
				'unique_id' 		=> 'Deleted User',
				'social_media_id'	=> 'Deleted User',
				'mobile' 			=> 'Deleted User',
				'profile_pic' 		=> 'Deleted User',
				'device_token' 		=> 'Deleted User',
			]);
			auth()->user()->delete();

			// $user = auth()->user()->token();
			// $user->revoke();
			DB::commit();
			return $this->sendSuccess('YOUR ACCOUNT PERMANENTLY DELETE SUCCESSFULLY');
		} catch (\Throwable $th) {
			DB::rollBack();
			return $this->sendFailed($th->getMessage() . ' On line ' . $th->getLine(), 400);
		}
	}



	function version()
	{
		$version = Version::first();
		return $this->sendSuccess('VERSION GET SUCESS', ['version' => @$version->version]);
	}
	public function sendVoip(Request $request)
	{
		$type = $request->type;
		$loginUserId = $request->loginUserId;
		$userId = $request->userId;
		$LoginUserDetail = User::find($loginUserId);
		$userDetail = User::find($userId);


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

			'user_id'	 	 => $loginUserId,
			'token'			 =>  $LoginUserDetail->twilio_token,
			'device_token'   => $LoginUserDetail->device_token,
			'type'   		 =>  $type,
		];
		// print_R($payload); exit;
		// Encode the payload as JSON
		$jsonPayload = json_encode($payload);

		// Set the URL for the APNS HTTP/2 API endpoint
		if(empty($userDetail->device_token))
		{
			return $this->sendFailed("Call Receiver Not Logged in", 201);
		}
 		$url = 'https://api.development.push.apple.com/3/device/' . $userDetail->device_token;
//		$url = 'https://api.push.apple.com/3/device/' . $userDetail->device_token;

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
		return $this->sendSuccess('TOKEN GENERATE SUCCESSFULLY!', [
			'id' => $userDetail->id,
			'unique_id' => $userDetail->unique_id, 'token' => $userDetail->twilio_token
		]);
	}
	public function clearNotification(Request $request)
	{
		$userId = auth()->user()->id;
		Notification::where("user_id", $userId)->delete();
		return $this->sendSuccess('Notification deleted successfully', []);
	}
}
