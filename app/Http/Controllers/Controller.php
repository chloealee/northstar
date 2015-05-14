<?php namespace Northstar\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class Controller extends BaseController
{

    use DispatchesCommands, ValidatesRequests;

    /**
     * Method to standardize responses sent from child controllers.
     *
     * @param mixed $data - Data to send in the response
     * @param int $code - Status code
     * @param string $status - When $data is a message string, this is the name of the object enclosing the message
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function respond($data, $code = 200, $status = 'success') {
        $response = [];
        if (is_string($data)) {
            $response[$status] = ['message' => $data];
        } elseif (is_object($data) || is_array($data)) {
            $response['data'] = $data;
        } else {
            $response = $data;
        }

        return response()->json($response, $code);
    }

}
