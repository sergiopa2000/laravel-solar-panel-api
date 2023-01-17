<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

use function MNC\Http\fetch;

class ApiAuthController extends Controller
{
    function __construct(){
        $this->middleware('auth:api')->only(['request', 'logout']);
    }
    
    function login(Request $request) {
        $credentials = request(['email', 'password']);
        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $user = Auth::user(); // $request->user();
        $tokenResult = $user->createToken('Access Token');
        $token = $tokenResult->token;
        $token->save();
        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse($token->expires_at)->toDateTimeString()
        ], 200);
    }
    
    public function logout(){
        Auth::user()->tokens->each(function($token, $key) {
            $token->delete();
        });
    
        return response()->json(['message' => 'Successfully logged out'], 200);
    }
    
    public function request(){
        $url = 'https://api.sunrise-sunset.org/json?lat=37.1881700&lng=-3.6066700&date='.date('Y-m-d').'&formatted=0';
        $json = json_decode(file_get_contents($url));
        $sunrise = $json->results->sunrise;
        $sunrise = explode('T', $sunrise);
        $sunrise = explode('+', $sunrise[1]);
        $sunrise = explode(':', $sunrise[0]);
        $sunrise = intval($sunrise[0]) * 60 + intval($sunrise[1]);
        
        $sunset = $json->results->sunset;
        $sunset = explode('T', $sunset);
        $sunset = explode('+', $sunset[1]);
        $sunset = explode(':', $sunset[0]);
        $sunset = intval($sunset[0]) * 60 + intval($sunset[1]);
        
        $variableNumber = (intval(date('H')) * 60) + (intval(date('i')));
        if($variableNumber < $sunrise || $variableNumber > $sunset){ //  Si está fuera del rango devolvemos 0
            $cos = 0;
            $sen = 0;
        }else{
            $interpolated = ((($variableNumber - $sunrise)*((pi() / 2) - (-pi() / 2))) / ($sunset - $sunrise)) + (-pi() / 2);
            $cos = cos($interpolated);
            $sen = sin($interpolated);
        }

        return response()->json(
            [
            'cos' => round($cos, 10), 
            'sen' => $sen, 
            'sensor1' => rand(0, 1),
            'sensor2' => rand(0, 1),
            'sensor3' => rand(0, 1),
            'sensor4' => rand(0, 1),
            ], 401);
    }
}