<?php

namespace App\Modules\User\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Modules\User\Models\UserVideo;
use Carbon\Carbon;
use App\Libraries\SecurityLib;
use App\Traits\RestApi;
use DB, File, Hash, Validator, Schema, Config, Storage;
use App\Libraries\FileLib;
use App\Libraries\ImageLib;
use Image;

class UserVideoController extends Controller
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
        $this->user_video_model_obj = new UserVideo();
         // Init File Library object
        $this->FileLib = new FileLib();

        // Init Image Library object
        $this->ImageLib = new ImageLib();
    }

    /**
    * @DateOfCreation        18 Sept 2018
    * @ShortDescription      This method is responsible for an insert user video request
    * @param                 \Illuminate\Http\Request  $request
    * @return                Array of result
    */
    public function insert_user_video(Request $request)
    {   
        $request_data = $this->getRequestData($request);
        $user_agent = $request->server('HTTP_USER_AGENT');
        $ip_address = $request->ip();

        $validate_data = $this->insert_user_video_validator($request_data);
        // Trip start validate 
        if($validate_data["error"]) {
            return $this->echoResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                $validate_data['errors'],
                trans('User::messages.user_video_validation_failed'), 
                $this->http_codes['HTTP_OK']
            ); 
        }

        $request_data['vehicle_id'] = isset($request_data['vehicle_id']) ? $this->security_lib_obj->decrypt($request_data['vehicle_id']) : null;

        $video_name = $request_data['video_name'];        
        $thumb_image = $request->file('video_thumbnail_url');
        if(empty($thumb_image) && $thumb_image==""){
            $validate_data['errors'] = array("video_thumbnail_url" => "The video thumbnail url field is required.");
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [],
                $validate_data['errors'],
                trans('User::messages.user_video_failed'),
                $this->http_codes['HTTP_OK']
            );            
        }

        // $filename    = $thumb_image->getClientOriginalName();
        $filename = 'thumb_'.time().'_'.$thumb_image->getClientOriginalName();
        $request_data['video_thumbnail_url'] =  url('api/s3_image_url/'.$this->security_lib_obj->encrypt($filename));

        $destination    = Config::get('constants.THUMBNAIL_PATH');

        $fileUpload  = $this->ImageLib->resizeImageBeforeUpload($thumb_image, $destination, $filename, $height=300, $width=300);
        // $fileUpload     = $this->FileLib->fileUpload($thumb_image, $destination, $video_thumbnail_url);

        if(($fileUpload['code']) && $fileUpload['code'] == 1000) {
            try 
            {
                // call userVideo model
                $insert_id = $this->user_video_model_obj->insert_user_video($request_data, $user_agent, $ip_address, $video_name);
            } catch (\Exception $e) {
                if(!empty($fileUpload['uploaded_file']) && file_exists($video_thumbnail_url)){
                    unlink($video_thumbnail_url);
                }

                if(File::exists($destination.$fileUpload['uploaded_file'])){
                    File::delete($destination.$fileUpload['uploaded_file']);
                }

                if(File::exists($thumbPath.$fileUpload['uploaded_file'])){
                    File::delete($thumbPath.$fileUpload['uploaded_file']);
                }
            }
        }
        
        // is query executed successfully
        if ($insert_id) {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                [$insert_id],
                [],
                trans('User::messages.user_video_success'),
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [],
                [],
                trans('User::messages.user_video_failed'),
                $this->http_codes['HTTP_OK']
            );
        }
    }

    /**
     * @DateOfCreation        10 Sept 2018
     * @ShortDescription      This function is responsible to get the image path
     * @param                 String $imageName
     * @return                response
     */
    public function s3_image_url($imageName)
    {
        $destination = Config::get('constants.THUMBNAIL_PATH');
        $imageName = $this->security_lib_obj->decrypt($imageName);
        $imageName = empty($imageName) ? Config::get('constants.DEFAULT_IMAGE_NAME'):$imageName;        
        return $this->user_video_model_obj->get_image_path($destination, $imageName);        
    }

    /**
     * @DateOfCreation        18 Sept 2018
     * @ShortDescription      Get a validator for an incoming get user request
     * @param                 \Illuminate\Http\Request  $request
     * @return                \Illuminate\Contracts\Validation\Validator
    */
    protected function insert_user_video_validator($request_data)
    {
        $errors         = [];
        $error          = false;
        $validation_data = [];
        // Check validations
        $validation_data = [
            'user_id' =>  'required',
            'company_id'    =>  'required',
            'resource_type' =>  'required',
            'vehicle_id'    =>  'required',
            'video_name'    =>  'required',
            'video_thumbnail_url'   =>  'required|mimes:png,jpg,jpeg,gif'

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

    /**
    * @DateOfCreation        18 Sept 2018
    * @ShortDescription      This method is responsible for an get user video request
    * @param                 \Illuminate\Http\Request  $request
    * @return                Array of result
    */
    public function get_user_video(Request $request)
    {   
        $request_data = $this->getRequestData($request);

        $validate_data = $this->get_user_video_validator($request_data);
        // Trip start validate 
        if($validate_data["error"]) {
            return $this->echoResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                $validate_data['errors'],
                trans('User::messages.user_video_validation_failed'), 
                $this->http_codes['HTTP_OK']
            ); 
        }

        // call userVideo model
        $get_data = $this->user_video_model_obj->get_user_video($request_data);

        // is query executed successfully
        if(!empty($get_data)) {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                $get_data,
                [],
                trans('User::messages.success'),
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [],
                [],
                trans('User::messages.error'),
                $this->http_codes['HTTP_OK']
            );
        }
    }

    /**
     * @DateOfCreation        18 Sept 2018
     * @ShortDescription      Get a validator for an incoming get user request
     * @param                 \Illuminate\Http\Request  $request
     * @return                \Illuminate\Contracts\Validation\Validator
    */
    protected function get_user_video_validator($request_data)
    {
        $errors         = [];
        $error          = false;
        $validation_data = [];
        // Check validations
        $validation_data = [
            'user_id' =>  'required',
            'company_id'    =>  'required'          
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

    /**
    * @DateOfCreation        18 Sept 2018
    * @ShortDescription      This method is responsible for an delete user video request
    * @param                 \Illuminate\Http\Request  $request
    * @return                Array of result
    */
    public function delete_user_video(Request $request)
    {
        $request_data = $this->getRequestData($request);

        $validate_data = $this->delete_user_video_validator($request_data);
        // Trip start validate 
        if($validate_data["error"]) {
            return $this->echoResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                $validate_data['errors'],
                trans('User::messages.user_video_validation_failed'), 
                $this->http_codes['HTTP_OK']
            ); 
        }

        // call userVideo model
        $is_deleted = $this->user_video_model_obj->delete_user_video($request_data);

        // is query executed successfully
        if($is_deleted) {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                [],
                [],
                trans('User::messages.user_video_deleted'),
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [],
                [],
                trans('User::messages.error'),
                $this->http_codes['HTTP_OK']
            );
        }
    }

    /**
     * @DateOfCreation        18 Sept 2018
     * @ShortDescription      Get a validator for an incoming delete user request
     * @param                 \Illuminate\Http\Request  $request
     * @return                \Illuminate\Contracts\Validation\Validator
    */
    protected function delete_user_video_validator($request_data)
    {
        $errors         = [];
        $error          = false;
        $validation_data = [];
        // Check validations
        $validation_data = [
            's3_video_id' =>  'required',       
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