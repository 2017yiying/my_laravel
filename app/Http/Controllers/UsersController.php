<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\User;

class UsersController extends Controller
{
    //__construct
    public function __construct()
    {
        //登陆用户才允许访问个人中心，注册、保存用户
        $this->middleware('auth', [
            'except' => ['show', 'create', 'store']
        ]);
        //未登录用户可以注册
        $this->middleware('guest', [
            'only' => ['create']
        ]);
    }
    /**
    *创建用户
    */
    public function create()
    {
        return view('users.create');
    }
    /**
    *展示用户
    */
    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }
    /**
    *保存用户
    */
    public function store(Request $request)
   {
       $this->validate($request, [
           'name' => 'required|max:50',
           'email' => 'required|email|unique:users|max:255',
           'password' => 'required|confirmed|min:6'
       ]);

       $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);
        Auth::login($user);
        session()->flash('success', '欢迎，您将在这里开启一段新的旅程~');
        return redirect()->route('users.show', [$user]);
   }
   //编辑用户
   public function edit(User $user)
   {
       $this->authorize('update', $user);
       return view('users.edit', compact('user'));
   }
   //编辑提交
   public function update(User $user, Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:50',
            'password' => 'required|confirmed|min:6'
        ]);
        $this->authorize('update', $user);
        //$data = [];
        //$data['name'] = $request->name;
        $user->update([
            'name' => $request->name,
            'password' => bcrypt($request->password),
        ]);
        session()->flash('success', '个人资料更新成功！');
        return redirect()->route('users.show', $user->id);
    }
}
