<?php

namespace App\Http\Controllers\Front;

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

    public function generateTokens(Request $request)
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
    public function generateKitToken(Request $request)
    {

        dd($request->all());

        $roomID = $request->input('roomID');
        $userID = $request->input('userID');
        $userName = $request->input('userName');
        $appID = 1557610397;
        $serverSecret = "ac3e778d4f1636f06223e2ca04c5236a";

        // Assuming you have included the ZegoUIKitPrebuilt library in your project
        $kitToken = self::generateKitToken2($appID, $serverSecret, $roomID, $userID, $userName);

        return response()->json(['kitToken' => $kitToken]);


        $response = $this->client->post('/path/to/api', [
            'query' => [
                'api_key' => 'your_api_key',
                'secret_key' => 'your_secret_key',
                'other_parameters' => 'value'
            ]
        ]);
        dd($response);
        return $response->getBody();
    


        // $accountSid = 'ACc0b60a5e2a3aface096fa46c7d987c73';
        // $authToken = '4517b20b0ba4bdeba437cd81313ed03b';
        // $twilioAppSid = 'SK8193e946380682930eb69a2441df0655';
        // $apiKey = 'SK14e8b336c5bf4a70630738d555a1298e';
        // $apiSecret = 'LNDZdzt86vyTIAmmHl0OiH1Ni7WUGoFp';

// echo 'sdfd';die;
        $ACCOUNT_SID = 'AC5eb1cd9b22a4694cc84112fe0172a731';
        $API_KEY = 'SKbc24dcf24edf899c75ce9fa00355e21a'; 
        $API_KEY_SECRET = 'C1lUCshlOcNsfMcwcPVMb1tA9vM3cUDi';             
        $identity = 'alice';
        $PUSH_CREDENTIAL_SID= '';
        $APP_SID = 'SKfd7acca3e9a2833bdd265a69efaf00ff';
        $token = new AccessToken($ACCOUNT_SID, 
                         $API_KEY, 
                         $API_KEY_SECRET, 
                         3600, 
                         $identity
);

// Grant access to Video
$grant = new VoiceGrant();
$grant->setOutgoingApplicationSid($APP_SID);
// $grant->setPushCredentialSid($PUSH_CREDENTIAL_SID);
$token->addGrant($grant);

$access_token = $token->toJWT();


return $this->sendSuccess('TOKEN GENERATE SUCCESSFULLY!', ['access_token' => $access_token]);

$ttl = 3600;
// $accessToken = new AccessToken($accountSid, $twilioAppSid, $apiKey, $apiSecret);
$accessToken = new AccessToken($accountSid, $apiKey, $apiSecret, $ttl);

$grant = new VoiceGrant();
$grant->setOutgoingApplicationSid($twilioAppSid);
$accessToken->addGrant($grant);
$token = $accessToken->toJWT();
return $this->sendSuccess('TOKEN GENERATE SUCCESSFULLY!', ['access_token' => $token]);
return response()->json(['token' => $token]);

        // $twilio_token = $this->generateToken();

        // return $this->sendSuccess('TOKEN GENERATE SUCCESSFULLY', ['access_token' => $twilio_token]);
        
        
        $identity = 'Ibrahim Khan';//$request->query('username');

        throw_if(
            !$identity, 
            new \Exception('Please Provide a Username query string')
        );

        // Create a grant identifying the Sync service instance for this app
        $syncGrant = new SyncGrant();

        $syncGrant->setServiceSid('MG7bcb74b276ce253279624fcc8dd1cf33');
        $sid    = 'SKbc24dcf24edf899c75ce9fa00355e21a';
        $token  = "41d413081d90f505719863de40eb5b88";
        /**
         * Create an access token which we will sign and return to the client,
         * containing the grant we just created and specifying his identity.
         */

        //  $token = new AccessToken(
        //     $sid, 
        //     config('services.twilio.api_key'), 
        //     config('services.twilio.api_secret')
        // );

        $token = new AccessToken(
            $sid, 
            'SKbc24dcf24edf899c75ce9fa00355e21a', 
            'C1lUCshlOcNsfMcwcPVMb1tA9vM3cUDi'
        );

        $token->addGrant($syncGrant);

        $token->setIdentity($identity);

        return $this->sendSuccess('TOKEN GENERATE SUCCESSFULLY!', ['access_token' => $token->toJWT()]);
        return response(['identity' => $identity, 'token' => $token->toJWT()]);
    }
}
