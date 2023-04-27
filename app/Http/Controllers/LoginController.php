<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\LoginNeedsVerification;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function submit(Request $request)
    {
        //validate phone number
        $request->validate([
            'phone' => 'required|numeric|min:10'
        ]);

        //find or create user model
        $user = User::firstOrCreate([
            'phone' => $request->phone
        ]);

        if(!$user){
            return response()->json(['message' => 'Could not process a user with that phone number.'], 401);
        }

        //commented because the process of sending notification is not working and giving error as 401 unable to create record in vue and not api in vs code.

        //send the user a one time use code
        // $user->notify( new LoginNeedsVerification());

        //start: this code should be in loginneedsverification but sms is not working so making it work form this controller.
        $loginCode = rand(111111, 999999);

        $user->update([
            'login_code' => $loginCode
        ]);

        // return (new TwilioSmsMessage())->content("Your ridesharing login code is {$loginCode}, don't share it with anyone!!");

        //end:


        //return back a response
        return response()->json(['message' => 'Text message notification sent. The message needs to be this but unfortunately twilio is not working as it`s not paid so please look the login_code in database/users to verify your phone number and please proceed to login/verify api. After verification the login_code will be set to null.'], 200);

    }

    public function verify(Request $request)
    {
        //validate the incoming request
        $request->validate([
            'phone' => 'required|numeric|min:10',
            'login_code' => 'required|numeric|between:111111,999999'
        ]);

        //find the user
        $user = User::where('phone', $request->phone)
                    ->where('login_code', $request->login_code)
                    ->first();

        //is the code provided the same one saved?
        //if so, return back an auth token
        if($user){
            $user->update([
                'login_code' => null
            ]);

            return $user->createToken($request->login_code)->plainTextToken;
        }

        //if not, return back a message

        return response()->json(['message' => 'Invalid verification code.'], 401);
    }
}
