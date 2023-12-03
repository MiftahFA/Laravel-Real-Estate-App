<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Schedule;

class UserController extends Controller
{
    public function Index()
    {
        return view('frontend.index');
    }

    public function UserProfile()
    {

        $id = Auth::user()->id;
        $userData = User::find($id);
        return view('frontend.dashboard.edit_profile', compact('userData'));
    }

    public function UserProfileStore(Request $request)
    {
        try {
            DB::beginTransaction();
            $id = Auth::user()->id;
            $data = User::find($id);
            $data->username = $request->username;
            $data->name = $request->name;
            $data->email = $request->email;
            $data->phone = $request->phone;
            $data->address = $request->address;

            if ($request->file('photo')) {
                $file = $request->file('photo');
                @unlink(public_path('upload/user_images/' . $data->photo));
                $filename = date('YmdHi') . $file->getClientOriginalName();
                $file->move(public_path('upload/user_images'), $filename);
                $data['photo'] = $filename;
            }
            $data->save();
            DB::commit();
            $notification = array(
                'message' => 'User Profile Updated Successfully',
                'alert-type' => 'success'
            );
        } catch (\Throwable $th) {
            DB::rollback();
            $notification = array(
                'message' => $th->getMessage(),
                'alert-type' => 'error'
            );;
        }

        return redirect()->back()->with($notification);
    }

    public function UserChangePassword()
    {
        return view('frontend.dashboard.change_password');
    }


    public function UserPasswordUpdate(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|confirmed'
        ]);

        if (!Hash::check($request->old_password, auth::user()->password)) {
            $notification = array(
                'message' => 'Old Password Does not Match!',
                'alert-type' => 'error'
            );
            return back()->with($notification);
        }

        try {
            DB::beginTransaction();
            User::whereId(auth()->user()->id)->update([
                'password' => Hash::make($request->new_password)
            ]);
            DB::commit();
            $notification = array(
                'message' => 'Password Change Successfully',
                'alert-type' => 'success'
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            $notification = array(
                'message' => $th->getMessage(),
                'alert-type' => 'error'
            );
        }

        return back()->with($notification);
    }

    public function UserLogout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $notification = array(
            'message' => 'User Logout Successfully',
            'alert-type' => 'success'
        );
        return redirect('/login')->with($notification);
    }

    public function UserScheduleRequest()
    {
        $id = Auth::user()->id;
        $userData = User::find($id);
        $srequest = Schedule::where('user_id', $id)->get();
        return view('frontend.message.schedule_request', compact('userData', 'srequest'));
    }

    public function LiveChat()
    {
        $id = Auth::user()->id;
        $userData = User::find($id);
        return view('frontend.dashboard.live_chat', compact('userData'));
    }
}
