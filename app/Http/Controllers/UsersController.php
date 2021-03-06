<?php

namespace App\Http\Controllers;

use Mail;
use Auth;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\User;


class UsersController extends Controller
{
    //__construct
    public function __construct()
    {
        //未登陆用户也可以访问个人中心，注册、保存用户
        $this->middleware('auth', [
            'except' => ['show', 'create', 'store', 'index','confirmEmail']
        ]);
        //只让未登录用户可以访问注册页面
        $this->middleware('guest', [
            'only' => ['create']
        ]);
    }
    //用户列表
    public function index()
    {
         $users = User::paginate(10);
        return view('users.index', compact('users'));
    }
    /**
    *注册用户
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
        $statuses = $user->statuses()->orderBy('created_at', 'desc')->paginate(30);
        return view('users.show', compact('user','statuses'));
    }
    /**
    *保存注册用户
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
        //Auth::login($user);
        $this->sendEmailConfirmationTo($user);
        session()->flash('success', '验证邮件已发送到你的注册邮箱上，请注意查收。');
        //return redirect()->route('users.show', [$user]);
        return redirect('/');
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
        $data = [];
        $data['name'] = $request->name;
        $user->update([
            'name' => $request->name,
            'password' => bcrypt($request->password),
        ]);
        $user->update($data);
        session()->flash('success', '个人资料更新成功！');
        return redirect()->route('users.show', $user->id);
    }
    //删除用户
    public function destroy(User $user)
    {
        $this->authorize('destroy', $user);
        $user->delete();
        session()->flash('success', '成功删除用户！');
        return back();
    }
    //发送邮件
    protected function sendEmailConfirmationTo($user)
    {
        $view = 'emails.confirm';
        $data = compact('user');
        $from = 'aufree@yousails.com';
        $name = 'Aufree';
        $to = $user->email;
        $subject = "感谢注册 Sample 应用！请确认你的邮箱。";

        Mail::send($view, $data, function ($message) use ($from, $name, $to, $subject) {
            $message->from($from, $name)->to($to)->subject($subject);
        });
    }
    //激活账户
    public function confirmEmail($token)
    {
        $user = User::where('activation_token', $token)->firstOrFail();

        $user->activated = true;
        $user->activation_token = null;
        $user->save();

        Auth::login($user);
        session()->flash('success', '恭喜你，激活成功！');
        return redirect()->route('users.show', [$user]);
    }
}
