<?php

namespace App\Modules\Auth\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Validator;
use App\Libraries\SecurityLib;
use App\Traits\RestApi;
use Config;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Support\Facades\Password;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Mail;
use DB;
use ArrayObject;

class AuthController extends Controller
{

    use RestApi, SendsPasswordResetEmails;
    // @var Array $http_codes
    // This protected member contains Http Status Codes
    protected $http_codes = [];

    public $successStatus = 200;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {        
        // Init security library object
        $this->security_lib_obj = new SecurityLib();
        $this->http_codes = $this->http_status_codes();
        // Init user model object
        $this->user_model_obj = new User();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view("Auth::index");
    }
    
   /**
    * @DateOfCreation        24 August 2018
    * @ShortDescription      Get a validator for an incoming User request
    * @param                 \Illuminate\Http\Request  $request
    * @return                \Illuminate\Contracts\Validation\Validator
    */
    protected function forgot_validations($request_data)
    {
        $errors         = [];
        $error          = false;
        $validation_data = [];
        
        // Check the login type is Email        
        $validation_data = [
            'email' => 'required|email|exists:users',
        ];

        $validation_message = [
            'email.exists' => 'This email is not registered with us.',
        ];

        $validator  = Validator::make(
            $request_data,
            $validation_data,
            $validation_message
        );
        
        if($validator->fails()){
            $error  = true;
            $errors = $validator->errors();
        }
        return ["error" => $error,"errors"=>$errors];
    }

   /**
    * @DateOfCreation        24 August 2018
    * @ShortDescription      This function is responsible for generate and get Reset Password 
    * @param                 Array $request   
    * @return                Array of status and message
    */
    public function get_reset_password(Request $request)
    {
        $request_data =$this->security_lib_obj->decryptInput($request);

        $validate = $this->forgot_validations($request_data);
        if($validate["error"]){
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                $validate['errors'],
                trans('Auth::messages.user_validation_error'), 
                $this->http_codes['HTTP_OK']
            ); 
        }
        
        $login_key = "email";
        $login_value = $request_data['email'];
        $user_details = $this->user_model_obj->get_user_details($request_data['email']);

        $user = User::where($login_key, $login_value)->first();
        
        if (!$user) {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                ["email" => [trans('Auth::messages.not_found')]],
                trans('Auth::messages.not_found'), 
                $this->http_codes['HTTP_NOT_FOUND']
            );
        }
        //Generate random password
        $password =$this->security_lib_obj->genrateRandomPassword();
        $user->password = bcrypt($password);
        $user->save();

        if($user_details[0]->user_type == Config::get('constants.USER_TYPE_COMPANY')){
            $fullName = $user_details[0]->company_name;
        } else {
            $fullName = $user->first_name.' '.$user->last_name;            
        }
        // Send password by email
        $sent = Mail::send('emails.forgotPassword', ['name' => $fullName, 'password' => $password], function($message) use ($user) {
          $message->from(Config::get('constants.MAIL_FROM'), 'Vizon');
          $message->to($user->email);
          $message->subject(trans('Auth::messages.reset_password_subject'));
        });        

        return $this->resultResponse(
            Config::get('restresponsecode.SUCCESS'), 
            [],
            [],
            trans('Auth::messages.password_sent'),
            $this->http_codes['HTTP_OK']
        );
    }

   /**
    * @DateOfCreation        05 sept 2018
    * @ShortDescription      This function is responsible to delete access token for current user
    * @param                 String $request
    * @return                Array of status and message
    */
    public function post_logout(Request $request)
    {
        $request_data = $this->security_lib_obj->decryptInput($request);
        $error = false;
        $validator = Validator::make($request_data, [
            'user_id' => 'required'            
        ]);
        if($validator->fails()){
            $error = true;
            $errors = $validator->errors();
        }
        if($error == true){
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                $errors,
                trans('Auth::messages.user_id'), 
                $this->http_codes['HTTP_OK']
            ); 
        }

        $user_id = $this->security_lib_obj->decrypt($request_data['user_id']);
        $user  = User::find($user_id);

        if($user){
            $whereData   =  [
                                'user_id'=> $user_id
                            ];
            $user->oauth_access_token()->delete();
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'), 
                [], 
                [],
                trans('Auth::messages.logged_out'),
                $this->http_codes['HTTP_OK']
            );
        }
        return $this->resultResponse(
          Config::get('restresponsecode.ERROR'), 
          [], 
          ["user" => [trans('Auth::messages.not_found')]],
          trans('Auth::messages.not_found'), 
          $this->http_codes['HTTP_NOT_FOUND']
        );

    }

   /**
    * @DateOfCreation        24 August 2018
    * @ShortDescription      This function is responsible to delete access token for current user
    * @param                 String $request
    * @return                Array of status and message
    */
    public function admin_logout(Request $request)
    {
        $request_data['user_id'] = $this->security_lib_obj->decrypt($request['user_id']);
        $error = false;
        $validator = Validator::make($request_data, [
            'user_id' => 'required',
        ]);

        if($validator->fails()){
            $error = true;
            $errors = $validator->errors();
        }
        if($error == true){
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                $errors,
                trans('Auth::messages.user_id'), 
                $this->http_codes['HTTP_OK']
            ); 
        }

        $user  = User::find($request_data['user_id']);

        if($user){
          $user->oauth_access_token()->delete();
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'), 
                [], 
                [],
                trans('Auth::messages.logged_out'),
                $this->http_codes['HTTP_OK']
            );
        }
        return $this->resultResponse(
            Config::get('restresponsecode.ERROR'), 
                [], 
                ["user" => [trans('Auth::messages.not_found')]],
                trans('Auth::messages.not_found'), 
                $this->http_codes['HTTP_NOT_FOUND']
            );

    }
    /**
     * @apiDesc This webservice enable Admin login
     * @apiParam string $mobile required  | Mobile number of admin  
     * @apiParam string $email  required         | email of admin
     * @apiParam password $password  required         | password of admin
     * @apiParam string $resource_type  | ('web', 'ios', 'android') From where data is coming 
     * @apiErr 422 | Validation errors
     * @apiErr 403 | Unauthorized access
     * @apiResp 200 | Whatever message is send from backend on sucess
     */ 
    public function admin_login(Request $request)
    {
        $request_data = $request->all();
        $validate = $this->admin_login_validator($request_data);
        if($validate["error"]) {
            return $this->echoResponse(
                    Config::get('restresponsecode.ERROR'), 
                    [], 
                    $validate['errors'],
                    trans('Auth::messages.validation_failed'), 
                    $this->http_codes['HTTP_OK']
                ); 
        }
         
        $user_details = array();
        if(Auth::Attempt(['email' => $request_data['email'], 'password' => $request_data['password'], 'is_deleted' => Config::get('constants.IS_DELETE_NO')])) {
            //Create Token for login user
            $user = Auth::user();
            $user_id = $user->user_id; 

            if($user->user_type == 'user' || $user->user_type == 'driver'){
                return $this->echoResponse(
                        Config::get('restresponsecode.ERROR'), 
                        [], 
                        ["error" => [trans('Auth::messages.invalid_user_type')]],
                        trans('Auth::messages.invalid_user_type'), 
                        $this->http_codes['HTTP_OK']
                      ); 
            }

            $user_details['user'] = User::select('users.user_id', 'users.first_name', 'users.last_name', 'users.mobile','users.user_type', 'users.profile_picture', 'company_details.company_name')
                                    ->leftJoin('company_details', 'users.user_id', '=', 'company_details.company_id')
                                    ->where('users.user_id', '=', $user_id)
                                    ->where('users.is_deleted', '=', Config::get('constants.IS_DELETE_NO'))
                                    ->first()->toArray();


            // Generate & store user short token for socket connection
            $short_token = $this->create_short_token(50);
            DB::table('users')->where("user_id", $user->user_id)->update(["short_token" => $short_token]);

            //Encrypt user_id            
            $user_details['user']['user_id'] = $this->security_lib_obj->encrypt($user->user_id);

            $user_details['user']['profile_picture'] = url('api/driver-image/'.$this->security_lib_obj->encrypt($user_details['user']['profile_picture']));

            $user_details['token'] = $user->createToken('Vison')->accessToken;
            $user_details['short_token'] = $short_token;

            return $this->echoResponse(
                    Config::get('restresponsecode.SUCCESS'), 
                    $user_details, 
                    [],
                    trans('Auth::messages.login_successfull'), 
                    $this->http_codes['HTTP_OK']
                );
        } else {
            return $this->echoResponse(
                    Config::get('restresponsecode.ERROR'), 
                    [], 
                    ["error" => [trans('Auth::messages.invalid_credentials')]],
                    trans('Auth::messages.invalid_credentials'), 
                    $this->http_codes['HTTP_OK']
                );
        }     
    
    }

   /**
    * @DateOfCreation        24 August 2018
    * @ShortDescription      This function is responsible for validating for Admin login
    * @param                 Array $data This contains full input data 
    * @return                Array
    */ 
    protected function admin_login_validator($request_data)
    {
        $error = false;
        $errors = [];
        $validation_data = [];
        // Check the login type is Email or Mobile
        
        $validation_data = [
            'email' => 'required|email|exists:users',
            'password'  =>  'required'
        ];

        $validation_message = [
            'email.exists' => 'This email is not registered with us.',
        ];
        
        $validator  = Validator::make(
            $request_data,
            $validation_data,
            $validation_message
        );

        if($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error,"errors" => $errors];
    }

    /**
     * @apiDesc This webservice enable user to login
     * @apiParam string $mobile required  | Mobile number of passenger  
     * @apiParam string $email  required         | email of passenger
     * @apiParam password $password  required         | password of passenger
     * @apiParam string $resource_type  | ('web', 'ios', 'android') From where data is coming 
     * @apiErr 422 | Validation errors
     * @apiErr 403 | Unauthorized access
     * @apiResp 200 | Whatever message is send from backend on sucess
     */ 
    public function post_login(Request $request)
    {
        $request_data = $request->all();
        
        //Check user login from Mobile or Email
        if(!empty($request_data['mobile'])) {
            $input_column_name = 'mobile';
            $input_column_value = $request_data['mobile'];
        }else {
            $input_column_name = 'email';
            $input_column_value = $request_data['email'];
        }

        // Validate request
        $validate = $this->login_validator($request_data);
        if($validate["error"]) {
            return $this->resultResponse(
                    Config::get('restresponsecode.EMAIL_ERROR'),
                    [], 
                    $validate['errors'],
                    trans('Auth::messages.validation_failed'), 
                    $this->http_codes['HTTP_OK']
                ); 
        }
        $user_details = array();
        if(Auth::Attempt([$input_column_name => $input_column_value, 'password' => $request_data['password']])) {
            //Create Token for login user
            $user = Auth::user();
            $user_id = $user->user_id;

            if($user->user_type == 'driver'){

                $selectData  =  ['users.user_id', 'users.company_id', 'users.first_name', 'users.last_name', 'vehicles.vehicle_name', 'vehicles.registration_number', 'vehicles.vehicle_id', 'vehicles.vehicle_type', 'vehicles.vehicle_year', 'vehicles.vehicle_sate', 'vehicles.vehicle_model', 'users.vehicle_picture', 'users.profile_picture', 'vehicles.vehicle_fuel_unit', 'vehicle_assignments.start_time','vehicle_assignments.end_time','vehicle_assignments.description'];

                $whereData   =  [
                            'users.is_deleted'=>Config::get('constants.IS_DELETE_NO'),
                            'vehicles.is_deleted'=>Config::get('constants.IS_DELETE_NO'),
                            'vehicle_assignments.is_deleted'=>Config::get('constants.IS_DELETE_NO'),
                            'users.user_id'=>$user_id
                        ];

                $user_details['user'] = DB::table('users')
                            ->select($selectData)
                            ->leftJoin('vehicle_assignments', 'users.user_id', '=', 'vehicle_assignments.user_id')
                            ->leftJoin('vehicles', 'vehicle_assignments.vehicle_id', '=', 'vehicles.vehicle_id')
                            ->where($whereData)->first();

                if(!empty($user_details['user'])){                    
                    $user_details['user']->vehicle_id = $this->security_lib_obj->encrypt($user_details['user']->vehicle_id);
                    $user_details['user']->vehicle_assign = true;
                                        
                } else {

                    $whereData   =  [
                            'users.is_deleted'=>Config::get('constants.IS_DELETE_NO'),
                            'users.user_id'=>$user_id
                        ];
                    $user_details['user'] = DB::table('users')
                            ->select('user_id', 'company_id', 'first_name', 'last_name', 'mobile', 'user_type', 'profile_picture', 'vehicle_picture', 'vehicle_model', 'miles')
                            ->where($whereData)->first();

                    $user_details['user']->vehicle_assign = false;
                }

                $user_details['user']->s3_user_id = $user_details['user']->user_id;
                $user_details['user']->s3_company_id = $user_details['user']->company_id;
                $user_details['user']->s3_bucket_name = Config::get('constants.BUCKET_NAME');

                $user_details['user']->user_id = $this->security_lib_obj->encrypt($user_details['user']->user_id);
                $user_details['user']->company_id = $this->security_lib_obj->encrypt($user_details['user']->company_id);

                if(!empty($user_details['user']->vehicle_picture)) {
                        $user_details['user']->vehicle_picture = url('/').Config::get('constants.GET_VEHICLE_PHOTO_PATH').$user_details['user']->vehicle_picture;
                } else {
                    $user_details['user']->vehicle_picture = "";
                }

                if(!empty($user_details['user']->profile_picture)) {
                    $user_details['user']->profile_picture = url('/').Config::get('constants.GET_USER_PHOTO_PATH').$user_details['user']->profile_picture;
                } else {
                    $user_details['user']->profile_picture = "";
                }       

            } else {
                $user_details['user'] = User::select('user_id', 'company_id', 'first_name', 'last_name', 'mobile', 'user_type', 'profile_picture', 'vehicle_picture', 'vehicle_model', 'miles')
                            ->where('user_id', '=', $user_id)
                            ->where('is_deleted', '=', Config::get('constants.IS_DELETE_NO'))
                            ->first()
                            ->toArray();

                if(!empty($user_details['user']['profile_picture'])) {
                    $user_details['user']['profile_picture'] = url('/').Config::get('constants.GET_USER_PHOTO_PATH').$user_details['user']['profile_picture'];
                } else {
                    $user_details['user']['profile_picture'] = "";
                }

                if(!empty($user_details['user']['vehicle_picture'])) {
                    $user_details['user']['vehicle_picture'] = url('/').Config::get('constants.GET_VEHICLE_PHOTO_PATH').$user_details['user']['vehicle_picture'];
                } else {
                    $user_details['user']['vehicle_picture'] = "";
                }

                $user_details['user']['s3_user_id'] = $user_details['user']['user_id'];
                $user_details['user']['s3_company_id'] = '0';
                $user_details['user']['s3_bucket_name'] = Config::get('constants.BUCKET_NAME');

                $user_details['user']['user_id'] = $this->security_lib_obj->encrypt($user_details['user']['user_id']);
                $user_details['user']['company_id'] = '0';

                

            }                   

            //Encrypt user_id          
            $user_details['user_type'] = $user->user_type;  
            $user_details['user_id'] = $this->security_lib_obj->encrypt($user->user_id);
            $user_details['token'] = $user->createToken('Vison')->accessToken;

            return $this->resultResponse(
                    Config::get('restresponsecode.SUCCESS'), 
                    [$user_details], 
                    [],
                    trans('Auth::messages.login_successfull'), 
                    $this->http_codes['HTTP_OK']
                );
        }else {

            $inputdata['email'] = $request_data[$input_column_name];
            $inputdata['password'] = $request_data['password'];
            // check user password is null 
            $get_users_list = DB::table('users')->select('password')->where('email', $inputdata['email'])->get();

            if(empty($get_users_list[0]->password)){
                $data = $this->curl_call(Config::get('constants.NODE_URL'),$inputdata,'post',['header'=>['content-type'=>'*/*']]);
                $user_details = array();
                if(!empty($data)){
                    if($data['data']['code'] == 1000){
                        DB::table('users')->where('email',$inputdata['email'])->update(array(
                        'password'=>bcrypt($inputdata['password']),
                        ));

                        Auth::Attempt([$input_column_name => $input_column_value, 'password' => $request_data['password']]);
                        //Create Token for login user
                        $user = Auth::user();
                        $user_id = $user->user_id;

                        if($user->user_type == 'driver'){

                            $selectData  =  ['users.user_id', 'users.company_id', 'users.first_name', 'users.last_name', 'vehicles.vehicle_name', 'vehicles.registration_number', 'vehicles.vehicle_id', 'vehicles.vehicle_type', 'vehicles.vehicle_year', 'vehicles.vehicle_sate', 'vehicles.vehicle_model', 'users.vehicle_picture', 'users.profile_picture', 'vehicles.vehicle_fuel_unit', 'vehicle_assignments.start_time','vehicle_assignments.end_time','vehicle_assignments.description'];

                            $whereData   =  [
                                'users.is_deleted'=>Config::get('constants.IS_DELETE_NO'),
                                'vehicles.is_deleted'=>Config::get('constants.IS_DELETE_NO'),
                                'vehicle_assignments.is_deleted'=>Config::get('constants.IS_DELETE_NO'),
                                'users.user_id'=>$user_id
                            ];

                            $user_details['user'] =  DB::table('users')
                                ->select($selectData)
                                ->leftJoin('vehicle_assignments', 'users.user_id', '=', 'vehicle_assignments.user_id')
                                ->leftJoin('vehicles', 'vehicle_assignments.vehicle_id', '=', 'vehicles.vehicle_id')
                                ->where($whereData)->first();

                           if(!empty($user_details['user'])){ 
                                $user_details['user']->vehicle_id = $this->security_lib_obj->encrypt($user_details['user']->vehicle_id);
                                $user_details['user']->vehicle_assign = true;

                            } else {
                                $whereData   =  [
                                        'users.is_deleted'=>Config::get('constants.IS_DELETE_NO'),
                                        'users.user_id'=>$user_id
                                    ];
                                $user_details['user'] = DB::table('users')
                                        ->select('user_id', 'company_id', 'first_name', 'last_name', 'mobile', 'user_type', 'profile_picture', 'vehicle_picture', 'vehicle_model', 'miles')
                                        ->where($whereData)->first();
                                
                                $user_details['user']->vehicle_assign = false;
                            }

                            $user_details['user']->s3_user_id = $user_details['user']->user_id;
                            $user_details['user']->s3_company_id = $user_details['user']->company_id;
                            $user_details['user']->s3_bucket_name = Config::get('constants.BUCKET_NAME');

                            $user_details['user']->user_id = $this->security_lib_obj->encrypt($user_details['user']->user_id);
                            $user_details['user']->company_id = $this->security_lib_obj->encrypt($user_details['user']->company_id);

                            if(!empty($user_details['user']->vehicle_picture)) {
                                $user_details['user']->vehicle_picture = url('/').Config::get('constants.GET_VEHICLE_PHOTO_PATH').$user_details['user']->vehicle_picture;
                            } else {
                                $user_details['user']->vehicle_picture = "";
                            }

                            if(!empty($user_details['user']->profile_picture)) {
                                $user_details['user']->profile_picture = url('/').Config::get('constants.GET_USER_PHOTO_PATH').$user_details['user']->profile_picture;
                            } else {
                                $user_details['user']->profile_picture = "";
                            }                          

                        } else {
                            $user_details['user'] = User::select('user_id', 'company_id', 'first_name', 'last_name', 'mobile', 'user_type', 'profile_picture', 'vehicle_picture', 'vehicle_model', 'miles')
                                ->where('user_id', '=', $user_id)
                                ->where('is_deleted', '=', Config::get('constants.IS_DELETE_NO'))
                                ->first()
                                ->toArray();

                            if(!empty($user_details['user']['profile_picture'])) {
                                $user_details['user']['profile_picture'] = url('/').Config::get('constants.GET_USER_PHOTO_PATH').$user_details['user']['profile_picture'];
                            } else {
                                $user_details['user']['profile_picture'] = "";
                            }

                            if(!empty($user_details['user']['vehicle_picture'])) {
                                $user_details['user']['vehicle_picture'] = url('/').Config::get('constants.GET_VEHICLE_PHOTO_PATH').$user_details['user']['vehicle_picture'];
                            } else {
                                $user_details['user']['vehicle_picture'] = "";
                            }

                            $user_details['user']['s3_user_id'] = $user_details['user']['user_id'];
                            $user_details['user']['s3_company_id'] = '0';
                            $user_details['user']['s3_bucket_name'] = Config::get('constants.BUCKET_NAME');

                            $user_details['user']['user_id'] = $this->security_lib_obj->encrypt($user_details['user']['user_id']);
                            $user_details['user']['company_id'] = '0';
                        }

                        //Encrypt user_id
                        $user_details['user_type'] = $user->user_type;
                        $user_details['user_id'] = $this->security_lib_obj->encrypt($user->user_id);
                        $user_details['token'] = $user->createToken('Vison')->accessToken;

                        return $this->resultResponse(
                            Config::get('restresponsecode.SUCCESS'),
                            [$user_details],
                            [],
                            trans('Auth::messages.login_successfull'),
                            $this->http_codes['HTTP_OK']
                        );

                    } else {
                        return $this->resultResponse(
                            Config::get('restresponsecode.ERROR'),
                            [],
                            [],
                            trans('Auth::messages.login_failed'),
                            $this->http_codes['HTTP_OK']
                        );
                    }
                }

            } else {
                return $this->resultResponse(
                    Config::get('restresponsecode.ERROR'),
                    [],
                    [],
                    trans('Auth::messages.password_not_valid'),
                    $this->http_codes['HTTP_OK']
                );
            }
        }
    }

    /**
    * @DateOfCreation        04 September 2018
    * @ShortDescription      This function is responsible for register user
    * @param                 Array $data This contains full input data 
    * @return                Array
    */
    public function post_register(Request $request)
    {
        $request_data = $request->all();
        // Validate request
        $validate = $this->register_validator($request_data);

        if($validate["error"]) {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                $validate['errors'],
                trans('Auth::messages.register_validation_failed'), 
                $this->http_codes['HTTP_OK']
              ); 
        }

        // Create user data and insert into users table
        $user_agent  = $request->server('HTTP_USER_AGENT');
        $ip_address  = $request->ip();
        $created_at  = Config::get('constants.CURRENTDATE');
        $userData = array('first_name' => $request_data['first_name'], 'last_name' => $request_data['last_name'], 'email' => $request_data['email'], 'password' => bcrypt($request_data['password']),  'device_id' => $request_data['device_id'], 'user_agent' => $user_agent, 'ip_address' => $ip_address, 'created_at' => $created_at);

        $id = DB::table('users')->insert($userData);
        // get the last inserted drvier name
        $lastId = DB::getPdo()->lastInsertId();
        // select user data from users table
        if(!empty($lastId)){
            $user_details = array();
            if(Auth::Attempt(['email' => $request_data['email'], 'password' => $request_data['password']])) {
                //Create Token for login user
                $user = Auth::user();
                $user_id = $user->user_id;

                if($user->user_type == 'driver'){

                    $selectData  =  ['users.user_id', 'users.company_id', 'users.first_name', 'users.last_name', 'vehicles.vehicle_name', 'vehicles.registration_number', 'vehicles.vehicle_id', 'vehicles.vehicle_type', 'vehicles.vehicle_year', 'vehicles.vehicle_sate', 'vehicles.vehicle_model', 'users.vehicle_picture', 'users.profile_picture', 'vehicles.vehicle_fuel_unit', 'vehicle_assignments.start_time','vehicle_assignments.end_time','vehicle_assignments.description'];

                    $whereData   =  [
                                'users.is_deleted'=>Config::get('constants.IS_DELETE_NO'),
                                'vehicles.is_deleted'=>Config::get('constants.IS_DELETE_NO'),
                                'vehicle_assignments.is_deleted'=>Config::get('constants.IS_DELETE_NO'),
                                'users.user_id'=>$user_id
                            ];

                    $user_details['user'] =  DB::table('users')
                                ->select($selectData)
                                ->leftJoin('vehicle_assignments', 'users.user_id', '=', 'vehicle_assignments.user_id')
                                ->leftJoin('vehicles', 'vehicle_assignments.vehicle_id', '=', 'vehicles.vehicle_id')
                                ->where($whereData)->first();

                    if(!empty($user_details['user'])){
                        if(!empty($user_details['user']->vehicle_picture)) {
                            $user_details['user']->vehicle_picture = url('/').Config::get('constants.GET_VEHICLE_PHOTO_PATH').$user_details['user']->vehicle_picture;
                        } else {
                            $user_details['user']->vehicle_picture = "";
                        }

                        if(!empty($user_details['user']->profile_picture)) {
                            $user_details['user']->profile_picture = url('/').Config::get('constants.GET_USER_PHOTO_PATH').$user_details['user']->profile_picture;
                        } else {
                            $user_details['user']->profile_picture = "";
                        }
                    } else {
                        $user_details['user'] = "";
                    }

                    $user_details['user']->s3_user_id = $user_details['user']->user_id;
                    $user_details['user']->s3_company_id = $user_details['user']->company_id;
                    $user_details['user']->s3_bucket_name = Config::get('constants.BUCKET_NAME');

                    $user_details['user']->user_id = $this->security_lib_obj->encrypt($user_details['user']->user_id);
                    $user_details['user']->vehicle_id = $this->security_lib_obj->encrypt($user_details['user']->vehicle_id);
                    $user_details['user']->company_id = $this->security_lib_obj->encrypt($user_details['user']->company_id);
                    $user_details['user']->vehicle_assign = true;

                } else {
                    $user_details['user'] = User::select('user_id', 'company_id', 'first_name', 'last_name', 'mobile', 'user_type', 'profile_picture', 'vehicle_picture', 'vehicle_model', 'miles')
                                ->where('user_id', '=', $lastId)
                                ->where('is_deleted', '=', Config::get('constants.IS_DELETE_NO'))
                                ->first()
                                ->toArray();

                    if(!empty($user_details['user']['profile_picture'])) {
                        $user_details['user']['profile_picture'] = url('/').Config::get('constants.GET_USER_PHOTO_PATH').$user_details['user']['profile_picture'];
                    } else {
                        $user_details['user']['profile_picture'] = "";
                    }

                    if(!empty($user_details['user']['vehicle_picture'])) {
                        $user_details['user']['vehicle_picture'] = url('/').Config::get('constants.GET_VEHICLE_PHOTO_PATH').$user_details['user']['vehicle_picture'];
                    } else {
                        $user_details['user']['vehicle_picture'] = "";
                    } 

                    $user_details['user']['s3_user_id'] = $user_details['user']['user_id'];
                    $user_details['user']['s3_company_id'] = '0';
                    $user_details['user']['s3_bucket_name'] = Config::get('constants.BUCKET_NAME');

                    $user_details['user']['user_id'] = $this->security_lib_obj->encrypt($user_details['user']['user_id']);
                    $user_details['user']['company_id'] = '0';
                }                   

                //Encrypt user_id          
                $user_details['user_type'] = $user->user_type;  
                $user_details['user_id'] = base64_encode($this->security_lib_obj->encrypt($lastId));
                $user_details['token'] = $user->createToken('Vison')->accessToken;

                return $this->resultResponse(
                    Config::get('restresponsecode.SUCCESS'), 
                    [$user_details], 
                    [],
                    trans('Auth::messages.register_successfull'), 
                    $this->http_codes['HTTP_OK']
                );
            }
        }
    }

    /**
    * @DateOfCreation        04 September 2018
    * @ShortDescription      This function is responsible for validating for register
    * @param                 Array $data This contains full input data 
    * @return                Array
    */ 
    protected function register_validator($data)
    {        
        $error = false;
        $errors = [];       
        
        $validator = Validator::make($data, [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|unique:users',            
            'password' => 'required|min:8|regex:/^.*(?=.{3,})(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[\d\X])(?=.*[!$#%@]).*$/',
            'device_id' => 'required'
        ]);
        
        if($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error,"errors" => $errors];
    }

    /**
    * @DateOfCreation        04 September 2018
    * @ShortDescription      This function is responsible for validating for login
    * @param                 Array $data This contains full input data 
    * @return                Array
    */ 
    protected function login_validator($data)
    {        
        $error = false;
        $errors = [];
        
        if(!empty($data['mobile'])) {
            $validator = Validator::make($data, [
                'mobile' => 'required',
                'password' => 'required',
            ]);
        }else {
            $validator = Validator::make($data, [
                'email' => 'required|email|exists:users',            
                'password' => 'required',
            ]);
        }
        
        if($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }

        return ["error" => $error,"errors" => $errors];
    }

    /**
    * @DateOfCreation   05 Sept 2018
    * @ShortDescription This is responsible to register device id
    * @param    Device id
    * @return 
    */
    public function device_registration(Request $request)
    {
        $request_data = $request->all();
        // Validate request
        $validate = $this->device_register_validator($request_data);

        if($validate["error"]) {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                $validate['errors'],
                trans('Auth::messages.device_validation_failed'), 
                $this->http_codes['HTTP_OK']
              ); 
        }

        //Device table insert and update data
        if(!empty($request_data['device_id']))
        {
            $device = array();
            $device['user_id'] = $this->random_string(20);
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'), 
                $device, 
                [],
                trans('Auth::messages.device_id_registered'), 
                $this->http_codes['HTTP_OK']
            );
        }

    }

    /**
    * @DateOfCreation        05 September 2018
    * @ShortDescription      This function is responsible for validating for device id
    * @param                 Array $data This contains full input data 
    * @return                Array
    */ 
    protected function device_register_validator($data)
    {
        $error = false;
        $errors = [];        
        
        $validator = Validator::make($data, [
            'device_id' => 'required',
            'resource_type' =>  'required'
        ]);
        
        if($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error,"errors" => $errors];
    }    

    /**
    * @DateOfCreation        24 August 2018
    * @ShortDescription      This function is responsible for generating a unique short token for user.
    * @param                 Integer $len 
    * @return                String
    */
    function create_short_token($len)
    {
        $short_token = $this->random_string($len);
        $check_token = DB::table("users")->where("short_token", $short_token)->count();
        if($check_token > 0){
            $this->create_short_token($len);
        }
        else{
            return $short_token;
        }
    }

    /**
    * @DateOfCreation        24 August 2018
    * @ShortDescription      This function is used to generate random alphanumeric string.
    * @param                 Integor $len 
    * @return                String
    */
    function random_string($len, $type = 'alphanumeric')
    {
        if($type == "number"){
            $chars = "123456789";
        }
        if($type == "alphanumeric"){
            $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        }
        $str = '';
        $max = strlen($chars) - 1;
        for ($i=0; $i < $len; $i++){
            $str .= $chars[mt_rand(0, $max)];
        }
        return $str;
    }

    /**
    * @DateOfCreation   05 Sept 2018
    * @ShortDescription This function is responsible for import database 
    */
    public function importMongoData()
    {
        $path = public_path('dummy-data.csv');
        $data = array_map('str_getcsv', file($path));

        foreach ($data as $key => $d) {
            $resource_type = $d[4];
            $device_id = $d[5];
            $user_agent = $d[6];
            $ip_agent = $d[7];
            $vehicle_latitude = $d[11];
            $vehicle_longitude = $d[12];
            $email = $d[13];
            $first_name = $d[14];
            $last_name = $d[15];
            $profile_picture = $d[16];
            $vehicle_model = $d[17];
            $vehicle_picture = $d[18];
            $miles = $d[19];
            if($key != 0){
                     DB::table('users')->insert(
                    array('first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => $email,
                        'profile_picture' => $profile_picture,
                        'vehicle_model' => $vehicle_model,
                        'vehicle_picture' => $vehicle_picture,
                        'miles' => $miles,
                        'vehicle_latitude' => $vehicle_latitude,
                        'vehicle_longitude' => $vehicle_longitude,
                        'device_id' => $device_id,
                        'resource_type' => $resource_type,
                        'user_agent' => $user_agent,
                        'ip_address' => $ip_agent,
                    )
                );
            
            }
           
            
        }
    
       
    }
}