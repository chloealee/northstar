<?php namespace Northstar\Http\Controllers;

use Illuminate\Http\Request;
use Northstar\Services\DrupalAPI;
use Northstar\Models\User;
use Input;
use Response;

class UserController extends Controller
{

    /**
     * Display a listing of the resource.
     * GET /users
     *
     * @return Response
     */
    public function index()
    {
        //@TODO: set sensible limit here.
        $limit = Input::get('limit') ?: 20;
        $users = User::paginate($limit);
        return Response::json($users, 200);
    }


    /**
     * Store a newly created resource in storage.
     * POST /users
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $check = $request->only('email', 'mobile');
        $input = $request->all();

        $user = false;

        // Does this user exist already?
        if (Input::has('email')) {
            $user = User::where('email', '=', $check['email'])->first();
        } elseif (Input::has('mobile')) {
            $user = User::where('mobile', '=', $check['mobile'])->first();
        }

        // If there is no user found, create a new one.
        if (!$user) {
            $user = new User;

            // This validation might not be needed, the only validation happening right now
            // is for unique email or phone numbers, and that should return a user
            // from the query above.
            $this->validate($request, [
                'email' => 'email|unique:users|required_without:mobile',
                'mobile' => 'unique:users|required_without:email'
            ]);
        }
        // Update or create the user from all the input.
        try {
            $user->fill($input);

            // Do we need to forward this user to drupal?
            // If query string exists, make a drupal user.
            // @TODO: we can't create a Drupal user without an email. Do we just create an @mobile one like we had done previously?
            if (Input::has('create_drupal_user') && Input::has('password') && !$user->drupal_id) {
                try {
                    $drupal = new DrupalAPI;
                    $drupal_id = $drupal->register($user, Input::get('password'));
                    $user->drupal_id = $drupal_id;
                } catch (\Exception $e) {
                    // If user already exists, find the user to get the uid.
                    if ($e->getCode() == 403) {
                        try {
                            $drupal_id = $drupal->getUidByEmail($user->email);
                            $user->drupal_id = $drupal_id;
                        } catch (\Exception $e) {
                            // @TODO: still ok to just continue and allow the user to be saved?
                        }
                    }
                }
            }

            $user->save();

            // Log the user in & attach their session token to response.
            $token = $user->login();
            $user->session_token = $token->key;

            return $user;
        } catch (\Exception $e) {
            return Response::json($e, 401);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param $term - string
     *   term to search by (eg. mobile, drupal_id, id, email, etc)
     * @param $id - string
     *  the actual value to search for
     *
     * @return Response
     */
    public function show($term, $id)
    {
        // Find the user.
        $user = User::where($term, $id)->get();
        if (!$user->isEmpty()) {
            return Response::json($user, 200);
        }
        return Response::json('The resource does not exist', 404);

    }


    /**
     * Update the specified resource in storage.
     * PUT /users
     *
     * @param $id - User ID
     * @return Response
     */
    public function update($id)
    {
        $input = Input::all();

        $user = User::where('_id', $id)->first();

        if ($user instanceof User) {
            foreach ($input as $key => $value) {
                if ($key == 'interests') {
                    $interests = array_map('trim', explode(',', $value));
                    $user->push('interests', $interests, true);
                } // Only update attribute if value is non-null.
                elseif (isset($key) && !is_null($value)) {
                    $user->$key = $value;
                }
            }

            $user->save();

            $response = array('updated_at' => $user->updated_at);

            return Response::json($response, 202);
        }

        return Response::json("The resource does not exist", 404);
    }

    /**
     * Delete a user resource.
     * DELETE /users/:id
     *
     * @param $id - User ID
     * @return Response
     */
    public function destroy($id)
    {
        $user = User::where('_id', $id)->first();
        $message = 'The resource does not exist';
        $code = 404;
        $status = 'error';


        if ($user instanceof User) {
            $user->delete();

            $message = 'No Content';
            $code = 204;
            $status = 'success';
        }

        return $this->respond($message, $code, $status);
    }

}
