<?php

namespace App\Modules\Admin\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\SecurityLib;
use App\Libraries\FileLib;
use App\Libraries\ImageLib;
use App\Libraries\DateTimeLib;
use App\Traits\RestApi;
use App\Models\User;
use DB, Auth, Validator, Config, File, Response, Mail;

/**
 * DriverController class
 *
 * @package Vizon
 * @subpackage DriverController
 * @category Controller
 * @DateOfCreation  22 August 2018
 * @ShortDescription    This class perform allthe operation realted to drivers
*/
class DriverController extends Controller
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

        // Init File Library object
        $this->FileLib = new FileLib();

        // Init Image Library object
        $this->ImageLib = new ImageLib();

        // Init dateTime library object
        $this->dateTimeLibObj = new DateTimeLib();

        // Init user model object
        $this->user_model_obj = new User();
    }

    /**
     * Display a listing of the resource.
     *
     * @DateOfCreation  22 August 2018
     * @param $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request_data = $this->getRequestData($request);
        $company_id   = Auth::id();
        $list         = $this->user_model_obj->get_drivers($request_data, $company_id);

        // validate, is query executed successfully
        if ($list) {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                $list,
                [],
                trans('Admin::messages.success'),
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [],
                [],
                trans('Admin::messages.error'),
                $this->http_codes['HTTP_OK']
            );
        }

    }

    /**
     * Show the form for creating a new resource.
     * 
     * @param $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $request_data =$this->security_lib_obj->decryptInput($request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @DateOfCreation  23 August 2018
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request_data = $request->all();
        $is_admin = 0;
        if($request_data['user_type'] == Config::get('constants.USER_TYPE_ADMIN')){
            $company_id = Config::get('constants.DEFAULT_COMPANY_ID');
            $request_data['user_type'] = Config::get('constants.USER_TYPE_COMPANY');
            $email_subject = trans('Admin::drivers.company_registration');
            $is_admin = 1;
        } else {
            $company_id = Auth::id();
            $request_data['user_type'] = Config::get('constants.USER_TYPE_DRIVER');
            $email_subject = trans('Admin::drivers.driver_registration');
        }
        
        $request_data['user_id']        = !empty($request_data['user_id']) ? $this->security_lib_obj->decrypt($request_data['user_id']) : null;        
        $request_data['date_of_birth']  = isset($request_data['date_of_birth']) && !empty($request_data['date_of_birth']) ? $this->dateTimeLibObj->covertUserDateToServerType($request_data['date_of_birth'],'dd/mm/YY','Y-m-d')['result'] : NULL;

        if(!empty($request_data)){
            foreach ($request_data as $key => $value) {
                if($value == 'null' || $value == 'undefined'){
                    $request_data[$key] = NULL;
                }
            }
        }

        $validate = $this->addDriverValidations($request_data);
        if($validate["error"]){
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                $validate['errors'],
                trans('Admin::drivers.validation_failed'), 
                $this->http_codes['HTTP_OK']
            ); 
        }
        //Generate random password
        $password =$this->security_lib_obj->genrateRandomPassword();
        $randomPassword = bcrypt($password);
        $user_agent   = $request->server('HTTP_USER_AGENT');
        $ip_address   = $request->ip();
        $created_at   = Config::get('constants.CURRENTDATE');
        
        // send data to user model for add / update driver record
        $user_model = new User;
        $data  = $user_model->add_drivers($request_data, $randomPassword, $user_agent, $ip_address, $created_at, $company_id);
        if(!empty($data)){

            if(empty($request_data['user_id'])){
                // send password to registered driver or user
                if($is_admin === 1){
                    $fullName = $data[0]->company_name;
                } else {
                    $fullName = $data[0]->first_name.' '.$data[0]->last_name;                    
                }
                $sent = Mail::send('emails.driverPasswordGenerate', ['name' => $fullName, 'password' => $password], function($message) use ($data, $email_subject) {
                  $message->from(Config::get('constants.MAIL_FROM'), 'Vizon');
                  $message->to($data[0]->email);
                  $message->subject($email_subject);
                });
            }
            
            $destination    = Config::get('constants.USER_PROFILE_MEDIA_PATH');
            $fileUpload     = [];
            $profile_picture= $data[0]->profile_picture;
            if( !empty($request_data['profile_picture']) && is_object($request_data['profile_picture']) ){

                if(!File::exists(storage_path($destination))) {
                    // path does not exist
                    File::makeDirectory(storage_path($destination), 0777, true, true);
                } 

                $fileUpload = $this->FileLib->fileUpload($request_data['profile_picture'], $destination);                
                
                $mediaData = array();
                $profile_picture = storage_path($destination).$fileUpload['uploaded_file'];
                if(($fileUpload['code']) && $fileUpload['code'] == 1000) {
                    try {
                        $updateData = [ 'profile_picture' => $fileUpload['uploaded_file'] ];
                        $whereData  = [ 'user_id'         => $data[0]->user_id ];
                        $updateImageData = $this->user_model_obj->updateUserData($updateData, $whereData);
                    } catch (\Exception $e) {
                        if(!empty($fileUpload['uploaded_file']) && file_exists($profile_picture)){
                            unlink($profile_picture);
                        }

                        if(File::exists($destination.$fileUpload['uploaded_file'])){
                            File::delete($destination.$fileUpload['uploaded_file']);
                        }

                        if(File::exists($thumbPath.$fileUpload['uploaded_file'])){
                            File::delete($thumbPath.$fileUpload['uploaded_file']);
                        }
                    }
                   
                    if(!empty($request_data['user_id']) && !empty($data[0]->profile_picture) && file_exists(storage_path($destination).$data[0]->profile_picture)){
                        unlink(storage_path($destination).$data[0]->profile_picture);
                    }
                }
            }

            $list = array(
                    "user_id"           => $data[0]->user_id,
                    "first_name"        => $data[0]->first_name,
                    "last_name"         => $data[0]->last_name,
                    "user_type"         => $data[0]->user_type,
                    "email"             => $data[0]->email,
                    "mobile"            => $data[0]->mobile,
                    "profile_picture"   => $profile_picture,
                    "company_detail_id" => $data[0]->company_detail_id,
                    "company_name"       => $data[0]->company_name
                );
           
            // validate, is query executed successfully
            if ($list) {
                return $this->resultResponse(
                    Config::get('restresponsecode.SUCCESS'),
                    $list,
                    [],
                    trans('Admin::drivers.success'),
                    $this->http_codes['HTTP_OK']
                );
            } else {
                return $this->resultResponse(
                    Config::get('restresponsecode.ERROR'),
                    [],
                    [],
                    trans('Admin::drivers.error'),
                    $this->http_codes['HTTP_OK']
                );
            }
        }
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        // send data to user model for delete driver record
        $user_model = new User;
        $request_data = $request->all();

        $updateData = ['is_deleted' => Config::get('constants.IS_DELETE_YES')];
        $whereData  = ['user_id' => $this->security_lib_obj->decrypt($request_data['user_id'])];
        $companyData  = ['company_id' => $this->security_lib_obj->decrypt($request_data['user_id'])];
        $data  = $user_model->updateUserData($updateData, $whereData, $companyData);

        if ($data) {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                [],
                [],
                trans('Admin::drivers.delete_success'),
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [],
                [],
                trans('Admin::drivers.delete_error'),
                $this->http_codes['HTTP_OK']
            );
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
    * @DateOfCreation        23 August 2018
    * @ShortDescription      Get a validator for an incoming add driver request
    * @param                 \Illuminate\Http\Request  $request
    * @return                \Illuminate\Contracts\Validation\Validator
    */
    protected function addDriverValidations($requestData){
        $errors         = [];
        $error          = false;
        $validationData = [];

        // Check the login type is Email or Mobile
        if(!empty($requestData['user_id'])){
            $validationData = [
                'email'       => 'required|email|unique:users,email,'.$requestData['user_id'].',user_id',
                'mobile'      => 'required|numeric|regex:/[0-9]{10}/||unique:users,mobile,'.$requestData['user_id'].',user_id',
                'postcode'    => 'nullable|numeric|digits:6'
            ];
        } else {
            $validationData = [
                'email'     => 'required|email|unique:users',
                'mobile'    => 'required|numeric|unique:users',
                'postcode'  => 'nullable|numeric|digits:6'
            ];
        }

        $validator  = Validator::make(
            $requestData,
            $validationData
        );

        if($validator->fails()){
            $error  = true;
            $errors = $validator->errors();
        }
        return ["error" => $error,"errors"=>$errors];
    }

    /**
     * @DateOfCreation        10 Sept 2018
     * @ShortDescription      This function is responsible to get the image path
     * @param                 String $imageName
     * @return                response
     */
    public function getDriverImage($imageName)
    {
        $destination = Config::get('constants.USER_PROFILE_MEDIA_PATH');
        $imageName = $this->security_lib_obj->decrypt($imageName);
        $imageName = empty($imageName) ? Config::get('constants.DEFAULT_IMAGE_NAME'):$imageName;
        
        return $this->user_model_obj->getImagePath($destination, $imageName);        
    }
}
