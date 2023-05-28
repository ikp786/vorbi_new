<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use Session;
use App\Models\User;
use App\Rules\MatchOldPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;


class AdminAuthController extends Controller
{
	//

	public function index()
	{
		return view('admin.auth.login');
	}


	public function login(Request $request)
	{
		$request->validate([
			'email' => 'required',
			'password' => 'required',
		]);
		Auth::logout();
		$credentials = $request->only('email', 'password');
		$remember_me = $request->has('remember_me') ? true : false;
		if (Auth::attempt(['email' => $request->email, 'password' => $request->password, 'type' => 'Admin'], $remember_me)) {
			// if (Auth::user()->role_id == 1) {
			//     return redirect('/superadmin/dashboard');
			// }else{
			//     Auth::logout();
			//     return redirect('/superadmin/login')->with('error','You Are Not Allowed!');
			// }
			return redirect('/admin/dashboard');
		}
		return redirect()->back()->with('Failed', 'Invalid username or password.');
	}

	public function changePasswordGet()
	{
		$title         = "Change Password";
		$data          = compact('title');
		return view('admin.settings.change-password', $data);
	}

	public function changePassword(Request $request)
	{
		$error_message =    [
			'current_password.required'    => 'Current Password should be required',
			'new_password.required'        => 'New Password should be required',
			'new_password.regex'   		   => 'Provide password in valid format',
			'min'                  		   => 'Password should be minimum :min character'
		];
		$validatedData = $request->validate([
			'current_password'             => ['required', new MatchOldPassword],
			'new_password'                 => 'required|min:8|max:16|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
		], $error_message);
		try {
			$user_details = auth()->user();
			if ($user_details) {
				\DB::beginTransaction();
				$change = User::find(auth()->user()->id)->update(['password' => Hash::make($request->new_password)]);
				\DB::commit();
				return redirect()->route('admin.dashboard.index')->With('Success', 'Password change succssfully');
			} else {
				return redirect()->back()->With('Failed', 'UNAUTHORIZE ACCESS');
			}
		} catch (\Throwable $e) {
			\DB::rollback();
			return redirect()->back()->With('Failed', $e->getMessage() . ' on line ' . $e->getLine());
		}
	}

	public function forgot_password_view()
	{
		return view('admin.forget-password');
	}
	public function forgot_password(Request $request)
	{
		// $response =  Http::get('https://nbfc-server.herokuapp.com/api/ledger/getLedgers');
		// dd($response->json());
		$error_message = 	[
			'email.required'  	=> 'Email address should be required',
			'email.email'  	    => 'Email address Not a valid format',
			'email.exists'  	=> 'Email address not found.',
		];
		$rules = [
			'email'       => 'required|email|exists:users,email',
		];
		$this->validate($request, $rules, $error_message);
		try {
			$user_detail = User::where('email', $request->email)->first();
			if (!isset($user_detail)) {
				return redirect()->back()->With('Failed', 'Email Address not found');
			}

			$verifaction_otp = rand(111111, 999999);
			$verifaction_otp = 2222;
			$email_data = ['user_name' => $user_detail->first_name, 'verifaction_otp' => $verifaction_otp];
			// \Mail::to($user_detail->email)->send(new \App\Mail\ForgotPassword($email_data));
			Session::put('forget_password_otp', $verifaction_otp);
			Session::put('forget_password_email', $user_detail->email);
			return redirect()->route('admin.reset_password')->With('Success', 'OTP sent successfully. Please check your email and verify');
		} catch (\Throwable $e) {

			return redirect()->back()->With("Failed", $e->getMessage() . ' on line ' . $e->getLine());
		}
	}

	public function reset_password_view()
	{
		return view('admin.reset-password');
	}

	public function reset_password(Request $request)
	{
		$error_message = 	[
			'new_password.required'     => 'New Password should be required',
			'new_password.regex'        => 'Provide password in valid format',
			'min'                       => 'Password should be minimum :min character',
			'otp.required'			    => 'Otp should be required',
		];
		$rules = [
			'new_password'              => 'required|min:8|max:16|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
			'otp'						=> 'required',
		];
		$this->validate($request, $rules, $error_message);
		try {
			$otp   =   Session::get('forget_password_otp');
			$email =   Session::get('forget_password_email');
			if ($otp != $request->otp) {
				return redirect()->back()->With('Failed', 'OTP not match please try a valid OTP');
			}
			// check email
			if (!User::where(['email' => $email, 'role_id' => 1])->first()) {
				return redirect()->back()->With('Failed', 'WE COULD NOT FOUND ANY ACCOUNT');
			}
			$user_detail = User::where('email', $email)->first();

			\DB::beginTransaction();
			$user_detail->password = Hash::make($request->new_password);
			$user_detail->save();
			\DB::commit();
			return redirect()->route('admin.login')->With('Success', 'Password Update successfully');
		} catch (\Throwable $e) {
			\DB::rollback();
			return redirect()->back()->With("Failed", $e->getMessage() . ' on line ' . $e->getLine());
		}
	}
	public function logout()
	{
		Session::flush();
		Auth::logout();
		return redirect()->route('admin.login');
	}
	public function testnotification()
	{
		// $certFile =  '/path/to/certificate.pem';
		$certFile = public_path('vorbi.crt.pem');
		// echo $certFile; exit;
		// Set the passphrase for your certificate file
		// $certPassphrase = public_path('AuthKey_WBV3973Z8S.p8');
		$certPassphrase = '1234';

		// Set the device token of the recipient
		$deviceToken = 'e093571db2ef7affdc1daf401ae90f9c5964f71ca79b01eb1d6b78f5fb9cb829';

		// Set the payload of the notification
		$payload = [
			'aps' => [
				'content-available' => 1,
				'sound' => '',
				'badge' => 0,
				'category' => 'INCOMING_CALL'
			],
			'call_id' => '123456' //room id
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
		print_r($result);
		
		// Handle the response
		if ($status === 200) {
			// Notification sent successfully
		} else {
			// Notification failed to send
		}
	}
}
