<?php namespace Northstar\Models;

use Jenssegers\Mongodb\Model as Eloquent;

class ApiKey extends Eloquent
{

    protected $primaryKey = "_id";

    /**
     * The database collection used by the model.
     *
     * @var string
     */
    protected $collection = 'api_keys';

    /**
     *
     */
    public static function current() {
        $app_id = Request::header('X-DS-Application-Id');
        $api_key = Request::header('X-DS-REST-API-Key');
        return ApiKey::where("app_id", '=', $app_id)->where("api_key", '=', $api_key);
    }

    /**
     *
     */
    public static function exists($app_id, $api_key) {
        return ApiKey::where("app_id", '=', $app_id)->where("api_key", '=', $api_key)->exists();
    }

    /**
     *
     */
    public function checkScope($scope) {
        return $this->scope === $scope;
    }

}
