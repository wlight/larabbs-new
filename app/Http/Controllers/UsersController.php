<?php

namespace App\Http\Controllers;

use App\Handlers\ImageUploadHandler;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(UserRequest $request, User $user, ImageUploadHandler $uploader)
    {
        $data = $request->all();

//        dd($data);
        if ($request->avatar){
            $result = $uploader->save($request->avatar, 'avatar', $user->id, 362);
            if ($result){
                $data['avatar'] = $result['path'];
            }
        }
        $user->update($data);
//        dd($request->avatar);
        return redirect()->route('users.show', $user->id)->with('success', '个人资料更新成功！');
    }
}
