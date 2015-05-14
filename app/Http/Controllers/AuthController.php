<?php namespace Northstar\Http\Controllers;

use Northstar\Models\User;
use Northstar\Models\Token;
use Illuminate\Http\Request;
use Hash;

class AuthController extends Controller
{

    /**
     * Authenticate a registered user
     *
     * @param Request $request
     * @return Response
     */
    public function login(Request $request)
    {
        $input = $request->only('email', 'mobile', 'password');

        $this->validate($request, [
            'email' => 'email',
            'password' => 'required'
        ]);

        if ($request->has('email')) {
            $user = User::where('email', '=', $input['email'])->first();
        } elseif ($request->has('mobile')) {
            $user = User::where('mobile', '=', $input['mobile'])->first();
        }

        $code = 200;
        $status = '';

        if (!($user instanceof User)) {
            $data = 'User is not registered.';
            $status = 'error';
        } elseif (Hash::check($input['password'], $user->password)) {
            $token = $user->login();
            $token->user = $user->toArray();

            // Return the session token with the user.
            $user->session_token = $token->key;
            $data = $user;
        } else {
            $data = 'Incorrect password.';
            $status = 'error';
            $code = 412;
        }

        return $this->respond($data, $code, $status);


    }

    /**
     * Logout the current user by invalidating their session token.
     * @return Response
     */
    public function logout(Request $request)
    {
        if (!$request->header('Session')) {
            return $this->respond('No token given.', 200, 'error');
        }

        $input_token = $request->header('Session');
        $token = Token::where('key', '=', $input_token)->first();
        $user = Token::userFor($input_token);

        if (empty($token)) {
            $status = 'error';
            $data = 'No active session found.';
        } elseif ($token->user_id !== $user->_id) {
            $status = 'error';
            $data = 'You do not own this token.';
        } elseif ($token->delete()) {
            $status = 'success';
            $data = 'User logged out successfully.';
        } else {
            $status = 'error';
            $data = 'User could not log out. Please try again.';
        }

        return $this->respond($data, 200, $status);

    }

}
