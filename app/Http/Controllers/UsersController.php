<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\User;

class UsersController extends Controller
{

     public function __construct() {
    	return $this->middleware('guest');
    }
   
    public function create() {
    	return view('users.create');
    }

    public function store(Request $request) {
      $this->validate($request, [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|confirmed|min:6',
        ]);

        $confirmCode = str_random(60);
        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'confirm_code' => $confirmCode,
        ]);

        \Mail::send('emails.auth.confirm', compact('user') , function($message) use($user) {
            $message->to($user->email);
            $message->subject("My 게시판 회원가입 확인");
        });
        
        return redirect(route('sessions.create'))->with('message', '가입 확인 메일을 확인해 주세요.');
    } 

    public function confirm($code) {

        $user = User::whereConfirmCode($code)->first();
        if(!$user) {
            return redirect(route('users.create'))->with('message', '가입 확인 실패! 잘못된 정보입니다. ');
        }
        $user->activated = 1;
        $user->confirm_code = null;
        $user->save();

        \Auth::login($user);

        return redirect(route('bbs.index'));
    }
}
