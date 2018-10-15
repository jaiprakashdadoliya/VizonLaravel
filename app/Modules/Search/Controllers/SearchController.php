<?php

namespace App\Modules\Search\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Validator;
use Config;
use Mail;
use App\Modules\User\Models\Trip;
use Carbon\Carbon;
use App\Libraries\SecurityLib;
use App\Traits\RestApi;
use App\Models\User;
use App\Modules\Search\Models\Search;
use App\Modules\User\Models\Location;
use Schema;
use Excel;
use DB, File, Hash;
use Storage;


class SearchController extends Controller
{

    use RestApi;
    // @var Array $http_codes
    // This protected member contains Http Status Codes
    protected $http_codes = [];

    /**
     * Create a new controller instance.
     * @return void
     */
    public function __construct()
    {
        // Json array of http codes.
        $this->http_codes = $this->http_status_codes();
        // Init security library object
        $this->security_lib_obj = new SecurityLib();
        // Init user model object
        $this->user_model_obj = new User();
        // Init search model object
        $this->search = new Search();
    }


    public  function get_user_videos(Request $request){
        $company_id = Auth::id();
        $request_data = $this->getRequestData($request);
        $video_lists =  $this->search->get_user_videos($request_data, $company_id);
        if ($video_lists) {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                $video_lists,
                [],
                trans('Search::messages.success'),
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [],
                [],
                trans('Search::messages.error'),
                $this->http_codes['HTTP_OK']
            );
        }

    }

    /**
     * @DateOfCreation        21 Sept 2018
     * @ShortDescription      This method is responsible for an get get video from s3
     * @param                 \Illuminate\Http\Request  $request
     * @return                Array
     */
    public function get_user_video_url(Request $request)
    {
        $company_id = Auth::id();
        $request_data = $this->getRequestData($request);
        $validate_data = $this->s3_validator($request_data);
        // s3 validate
        if($validate_data["error"]) {
            return $this->echoResponse(
                Config::get('restresponsecode.ERROR'),
                [],
                $validate_data['errors'],
                trans('User::messages.s3_validation_failed'),
                $this->http_codes['HTTP_OK']
            );
        }

        $user_id = $this->security_lib_obj->decrypt($request_data['user_id']);
        $created_date = date('Y-m-d', strtotime($request_data['created_at']));
        $video_name = $request_data['video_name'];

        $video_file_path = $company_id."/".$user_id."/".$created_date."/".$video_name;

        $s3 = Storage::disk('s3');
        $client = $s3->getDriver()->getAdapter()->getClient();
        $expiry = Config::get('constants.S3_EXPIRY_TIME');
        $command = $client->getCommand('GetObject', [ 'Bucket' => Config::get('constants.BUCKET_NAME'), 'Key' => $video_file_path ]);
        $request = $client->createPresignedRequest($command, $expiry);
        $video_url = (string) $request->getUri();
        if ($video_url) {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                ($video_url),
                [],
                trans('Search::messages.success'),
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [],
                [],
                trans('Search::messages.error'),
                $this->http_codes['HTTP_OK']
            );
        }
    }

    /**
     * @DateOfCreation        24 Sept 2018
     * @ShortDescription      Get a validator for an incoming s3 request
     * @param                 \Illuminate\Http\Request  $request
     * @return                \Illuminate\Contracts\Validation\Validator
     */
    protected function s3_validator($request_data)
    {
        $errors         = [];
        $error          = false;
        $validation_data = [];
        // Check validations
        $validation_data = [
            'user_id'   =>  'required',
            'created_at'  =>  'required',
            'video_name'    =>  'required'
        ];
        $validator  = Validator::make(
            $request_data,
            $validation_data
        );
        if($validator->fails()){
            $error  = true;
            $errors = $validator->errors();
        }
        return ["error" => $error,"errors"=>$errors];
    }
}
