<?php

namespace App\Modules\User\Controllers;

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
use App\Modules\Admin\Models\Vehicle;
use App\Modules\User\Models\Location;
use Schema;
use Excel;
use DB, File, Hash;

class UserController extends Controller
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
        // Init user model object
        $this->location = new Location();
    }

    /**
    * @DateOfCreation        04 Sept 2018
    * @ShortDescription      This function is responsible to get user details
    * @param                 String $request
    * @return                Array of status and message
    */
    public function get_user(Request $request)
    {
        $user_id = Auth::id();
        // $request_data = $this->getRequestData($request);
        $user_model = new User;
        $list  = $user_model->get_user_list($user_id);

        foreach ($list as &$value) {
            if (!empty($value->profile_picture)) {
                $value->profile_picture = url('/').Config::get('constants.GET_USER_PHOTO_PATH').$value->profile_picture;
            } else {
                $value->profile_picture = url('/').Config::get('constants.GET_USER_PHOTO_PATH').'default_user.png';
            }

            if (!empty($value->vehicle_picture)) {
                $value->vehicle_picture = url('/').Config::get('constants.GET_VEHICLE_PHOTO_PATH').$value->vehicle_picture;
            } else {
                $value->vehicle_picture = url('/').Config::get('constants.GET_VEHICLE_PHOTO_PATH').'bus.png';
            }
        }

        // validate, is query executed successfully
        if ($list) {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                $list,
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
    * @DateOfCreation        04 Sept 2018
    * @ShortDescription      This function is responsible to update user details
    * @param                 String $request
    * @return                Array of status and message
    */
    public function update_user(Request $request)
    {
        $user_id = Auth::id();
        $request_data = $request->all();
        // $request_data['user_id'] = $this->security_lib_obj->decrypt(base64_decode($request->user_id));
        $validate = $this->update_user_validator($request_data);

        // Validate first name and last name
        if($validate["error"]) {
            return $this->echoResponse(
                    Config::get('restresponsecode.ERROR'), 
                    [], 
                    $validate['errors'],
                    trans('User::messages.update_user_validation_failed'), 
                    $this->http_codes['HTTP_OK']
                ); 
        }        

        $list = array();

        // image request process
        if($request->hasFile('profile_picture')){
            // validate image
            $this->validate($request, [
                'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif',
            ]);

            $image = $request->file('profile_picture'); 
            $name = time().'_'.$image->getClientOriginalName();
            $destinationPath = storage_path(Config::get('constants.USER_PHOTO_PATH'));
            $image->move($destinationPath, $name); 

            DB::table('users')->where(array('user_id' => $user_id, 'is_deleted' => Config::get('constants.IS_DELETE_NO')))->update(array('profile_picture' => $name));

            $list['profile_picture'] = url('/').Config::get('constants.GET_USER_PHOTO_PATH').$name;
            
        }

        if($request_data['user_type'] == 'user'){            

            if($request->hasFile('vehicle_picture')) {
                // validate image
                $this->validate($request, [
                    'vehicle_picture' => 'required|image|mimes:jpeg,png,jpg,gif',
                ]);

                $image = $request->file('vehicle_picture'); 
                $name = time().'_'.$image->getClientOriginalName();
                $destinationPath = storage_path(Config::get('constants.USER_VEHICLE_PHOTO_PATH'));
                $image->move($destinationPath, $name); 

                DB::table('users')->where(array('user_id' => $user_id, 'is_deleted' => Config::get('constants.IS_DELETE_NO')))->update(array('vehicle_picture' => $name));

                $list['vehicle_picture'] = url('/').Config::get('constants.GET_VEHICLE_PHOTO_PATH').$name;           
            }
        }

        $vehicle_model = isset($request->vehicle_model) ? $request->vehicle_model : null;
        DB::table('users')->where(array('user_id' => $user_id, 'is_deleted' => Config::get('constants.IS_DELETE_NO')))->update(array('first_name' => $request->first_name, 'last_name' => $request->last_name, 'vehicle_model' => $vehicle_model));
        return $this->echoResponse(
                        Config::get('restresponsecode.SUCCESS'), 
                        $list, 
                        [],
                        trans('User::messages.update_user_successfull'), 
                        $this->http_codes['HTTP_OK']
                    );

    }

    /**
    * @DateOfCreation        04 September 2018
    * @ShortDescription      This function is responsible for validating for update user
    * @param                 Array $data This contains full input data 
    * @return                Array
    */ 
    protected function update_user_validator(array $data)
    {        
        $error = false;
        $errors = [];       
        
        $validator = Validator::make($data, [
            'first_name' => 'required',
            'last_name' => 'required',
            'user_type' =>  'required'
        ]);
        
        if($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error,"errors" => $errors];
    }

    /**
    * @DateOfCreation        05 Sept 2018
    * @ShortDescription      This is used for change password
    * @param                 Object $request This contains full request 
    * @return                Array of status and message
    */
    function change_password(Request $request)
    {
        $user_id = Auth::id();
        $request_data = $this->security_lib_obj->decryptInput($request);
        // $request_data = $request->all();

        $user_agent = $request->server('HTTP_USER_AGENT');
        $error = false;
        $validator = Validator::make($request_data, [
            'old_password' => 'required',
            'new_password'         => 'required|min:8|regex:/^.*(?=.{3,})(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[\d\X])(?=.*[!$#%@]).*$/',
        ]);

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }
        if ($error == true) {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                $errors,
                trans('User::messages.change_password_validation'), 
                $this->http_codes['HTTP_OK']
            ); 
        }

        $old_password = $request_data['old_password'];
        $password = $request_data['new_password'];
        if (!(Hash::check($old_password, Auth::user()->password))) {
            // The passwords matches      
            return $this->resultResponse(
                    Config::get('restresponsecode.ERROR'), 
                    [], 
                    [],
                    trans('User::messages.change_password_match'), 
                    $this->http_codes['HTTP_OK']
                  );
        }

        if (strcmp($old_password, $password) == 0) {
            //Current password and new password are same   
            return $this->resultResponse(
                    Config::get('restresponsecode.ERROR'), 
                    [], 
                    [],
                    trans('User::messages.change_password_same_password_retype'), 
                    $this->http_codes['HTTP_OK']
                  );
        }

        //Change Password
        $user = Auth::user();
        $user->password = Hash::make($password);
        // $user->resource_type = $request_data['resource_type'];
        $user->ip_address = $request->ip();
        $user->user_agent = $user_agent;  
        $user->save();
        
        return $this->resultResponse(
            Config::get('restresponsecode.SUCCESS'), 
            [], 
            [],
            trans('User::messages.change_password_successfull'), 
            $this->http_codes['HTTP_OK']
        );
    }

    /**
    * @DateOfCreation        05 Sept 2018
    * @ShortDescription      This method is responsible for an save trip request
    * @param                 \Illuminate\Http\Request  $request
    * @return                \Illuminate\Contracts\Validation\Validator
    */
    public function save_trip(Request $request)
    {
        $user_id = Auth::id();
        $request_data = $this->getRequestData($request);
        $trip_details['user_agent'] = $request->server('HTTP_USER_AGENT');
        $trip_details['ip_address'] = $request->ip();

        if($request_data['type'] == "start") {

            $tripStartValidate = $this->trip_start_validator($request_data);
            // Trip start validate 
            if($tripStartValidate["error"]) {
                return $this->echoResponse(
                        Config::get('restresponsecode.ERROR'), 
                        [], 
                        $tripStartValidate['errors'],
                        trans('User::messages.trip_validation_failed'), 
                        $this->http_codes['HTTP_OK']
                    ); 
            }

            $trip_details['company_id'] = $this->security_lib_obj->decrypt($request_data['company_id']);
            $trip_details['user_id'] = $user_id;
            $trip_details['vehicle_id'] = $this->security_lib_obj->decrypt($request_data['vehicle_id']);
            $trip_details['start_time'] = Config::get('constants.CURRENTDATE');
            $trip_details['start_address'] = $request_data['start_address'];
            $trip_details['start_latitude'] = $request_data['start_latitude'];
            $trip_details['start_longitude'] = $request_data['start_longitude'];
            $trip_details['created_by'] = $user_id;
            $trip_details['updated_by'] = $user_id;
            $trip_details['resource_type'] = $request_data['resource_type'];
            $trip_details['created_at'] = Config::get('constants.CURRENTDATE');
            $trip_details['updated_at'] = Config::get('constants.CURRENTDATE');


            $id = DB::table('trips')->insert($trip_details);
            // get the last inserted drvier name
            $trip_id = DB::getPdo()->lastInsertId();

            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                [array("trip_id" => $this->security_lib_obj->encrypt($trip_id))],
                [],
                trans('User::messages.trip_start_success'),
                $this->http_codes['HTTP_OK']
            );
        }
        else if($request_data['type'] == "end"){

            $tripEndValidate = $this->trip_end_validator($request_data);
            // Trip end validate 
            if($tripEndValidate["error"]) {
                return $this->echoResponse(
                    Config::get('restresponsecode.ERROR'), 
                    [], 
                    $tripEndValidate['errors'],
                    trans('User::messages.trip_validation_failed'), 
                    $this->http_codes['HTTP_OK']
                ); 
            } 

            $trip_details['end_time'] = Config::get('constants.CURRENTDATE');
            $trip_details['end_address'] = $request_data['end_address'];
            $trip_details['end_latitude'] = $request_data['end_latitude'];
            $trip_details['end_longitude'] = $request_data['end_longitude'];
            $trip_details['distance'] = $request_data['distance'];
            $trip_details['updated_by'] = $user_id;
            $trip_details['updated_at'] = Config::get('constants.CURRENTDATE');
            Trip::where("trip_id", $this->security_lib_obj->decrypt($request_data['trip_id']))->update($trip_details);

            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                [],
                [],
                trans('User::messages.trip_end_success'),
                $this->http_codes['HTTP_OK']
            );
        }
        
    }

    /**
     * @DateOfCreation        05 Sept 2018
     * @ShortDescription      Get a validator for an incoming get start trip request
     * @param                 \Illuminate\Http\Request  $request
     * @return                \Illuminate\Contracts\Validation\Validator
     */
    protected function trip_start_validator($requestData)
    {
        $errors         = [];
        $error          = false;
        $validationData = [];
        // Check validations
        $validationData = [
            'user_id' =>  'required',
            'company_id'    =>  'required',
            'vehicle_id' => 'required',
            'type'  =>  'required',
            'start_address' =>  'required',
            'start_latitude'    =>  'required',
            'start_longitude'   =>  'required',
            'resource_type' =>  'required'            
        ];
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
     * @DateOfCreation        05 Sept 2018
     * @ShortDescription      Get a validator for an incoming get end trip request
     * @param                 \Illuminate\Http\Request  $request
     * @return                \Illuminate\Contracts\Validation\Validator
     */
    protected function trip_end_validator($requestData)
    {
        $errors         = [];
        $error          = false;
        $validationData = [];
        // Check validations
        $validationData = [
            'trip_id' =>  'required',
            'type'  =>  'required',
            'end_address' =>  'required',
            'end_latitude'    =>  'required',
            'end_longitude'   =>  'required',
            'distance'  =>  'required'
        ];
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
     * @DateOfCreation        05 Sept 2018
     * @ShortDescription      This method is responsible for get trip
     * @param                 \Illuminate\Http\Request  $request
     * @return                \Illuminate\Contracts\Validation\Validator
     */
    public function get_trip(Request $request)
    {
        $company_id =   Auth::id();
        $request_data = $this->getRequestData($request);
        $vehicle_id =   $request_data['vehicle_id'];
        $user_id =   $request_data['driver_id'];
        $seleted_date = str_replace('/' , '-', $request_data['seleted_date']);
        $convertDate = date('Y-m-d', strtotime($seleted_date));
        $trip_model = new Trip;
        $output =  $trip_model->get_trip_data($convertDate, $company_id, $vehicle_id, $user_id);
        if(!empty($output)){
            $userDistanceArray = array();
            $userArray = [];
            foreach ($output['trips'] as &$value){
                if($value->start_time != null && $value->end_time != null){
                    $datetime1 = new Carbon($value->start_time);
                    $datetime2 = new Carbon($value->end_time);
                    $interval = $datetime1->diff($datetime2);
                    if($interval->format('%h') == 0){
                        $value->getDiffBetweenTime = $interval->format('%i mins');
                    } else {
                        $value->getDiffBetweenTime = $interval->format('%h hours %i mins');                        
                    }
                } else {
                    $value->getDiffBetweenTime = '0 mins';
                }
                
                if($value->start_time != null){
                    $value->start_time_db = $value->start_time;
                    $value->start_time = date("h:i A", strtotime($value->start_time));
                }
                if($value->end_time != null){
                    $value->end_time_db = $value->end_time;
                    $value->end_time = date("h:i A", strtotime($value->end_time));
                }
                $userArray[$value->user_id][] = $value;
            }

            foreach ($output['users'] as $value){
                $userDistanceArray[$value->user_id] = $value;
                $userDistanceArray[$value->user_id]->userdetails = $userArray[$value->user_id];
            }

            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                array_values($userDistanceArray),
                [],
                trans('User::messages.success'),
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'), 
                [], 
                [],
                trans('User::messages.error'), 
                $this->http_codes['HTTP_OK']
            );
        }
    }

    /**
     * @DateOfCreation        10 Sept 2018
     * @ShortDescription      This method is responsible for get count of dashboard
     * @param                 \Illuminate\Http\Request  $request
     * @return                Result of array data
     */
    public function get_dashboard_count(Request $request)
    {
        $company_id = Auth::id();
        $request_data = $this->getRequestData($request);
        // Get total of trips, vehicles and drviers
        $data = array();
        
        if($request_data['user_type'] == Config::get('constants.USER_TYPE_ADMIN')){
            // if user type is admin the we will get total companies count
            $data['totalCompanies'] = $this->user_model_obj->get_total_company();
        } else {
            // Get total of trips, vehicles and drviers
            $data['totalVehicles'] = Vehicle::where(array('is_deleted' => Config::get('constants.IS_DELETE_NO'), 'company_id' => $company_id))->count();
            $data['totalDrivers'] = User::where(array('is_deleted' => Config::get('constants.IS_DELETE_NO'), 'user_type' => Config::get('constants.USER_TYPE_DRIVER'), 'company_id' => $company_id))->count();
            $data['totalRunningTrips'] = Trip::where(array('is_deleted' => Config::get('constants.IS_DELETE_NO'), 'end_time' => null, 'company_id' => $company_id))->count();
            $data['totalClosedTrips'] = Trip::where(array('is_deleted' => Config::get('constants.IS_DELETE_NO'), 'company_id' => $company_id))->whereNotNull('end_time')->count();
        }

        return $this->resultResponse(
            Config::get('restresponsecode.SUCCESS'),
            $data,
            [],
            trans('User::messages.success'),
            $this->http_codes['HTTP_OK']
        );
    }

    /**
     * @DateOfCreation        13 Sept 2018
     * @ShortDescription      This method is responsible for block and unblock users
     * @param                 \Illuminate\Http\Request  $request
     * @return                Result of array data
     */
    public function block_user(Request $request)
    {
        $company_id = Auth::id();
        $request_data = $this->getRequestData($request);
        $blockUserValidate = $this->block_user_validator($request_data);

        // user block validate 
        if($blockUserValidate["error"]) {
            return $this->echoResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                $blockUserValidate['errors'],
                trans('User::messages.block_user_validation_failed'), 
                $this->http_codes['HTTP_OK']
            ); 
        }

        if($request_data['reason'] == Config::get('constants.REASON_TYPE_OTHER'))
        {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                [],
                [],
                trans('User::messages.reason_success'),
                $this->http_codes['HTTP_OK']
            );
        }
        else
        {            
            $block_users = array();
            $user_id = $this->security_lib_obj->decrypt($request_data['user_id']);
            $user_to_be_blocked = $this->security_lib_obj->decrypt($request_data['user_to_be_blocked']);

            $block_users['user_agent'] = $request->server('HTTP_USER_AGENT');
            $block_users['ip_address'] = $request->ip();
            $block_users['user_id'] = $user_id;
            $block_users['user_to_be_blocked'] = $user_to_be_blocked;
            $block_users['is_blocked'] = $request_data['is_blocked'];
            $block_users['reason'] = $request_data['reason'];
            $block_users['created_at'] = Config::get('constants.CURRENTDATE');
            $block_users['updated_at'] = Config::get('constants.CURRENTDATE');

            $ifUserExist = DB::table('block_users')->where(array('user_id' => $user_id, 'user_to_be_blocked' => $user_to_be_blocked))->count();

            if($ifUserExist > 0){
                return $this->resultResponse(
                    Config::get('restresponsecode.SUCCESS'),
                    [],
                    [],
                    trans('User::messages.block_user_success'),
                    $this->http_codes['HTTP_OK']
                );
            } else {
                $id = DB::table('block_users')->insert($block_users);
                // get the last block user id
                // $blocked_user_id = DB::getPdo()->lastInsertId();
                return $this->resultResponse(
                    Config::get('restresponsecode.SUCCESS'),
                    [],
                    [],
                    trans('User::messages.block_user_success'),
                    $this->http_codes['HTTP_OK']
                );
            }            
        }
    } 

    /**
     * @DateOfCreation        13 Sept 2018
     * @ShortDescription      Get a validator for an incoming block user request
     * @param                 \Illuminate\Http\Request  $request
     * @return                \Illuminate\Contracts\Validation\Validator
     */
    protected function block_user_validator($requestData)
    {
        $errors         = [];
        $error          = false;
        $validationData = [];
        // Check validations
        $validationData = [
            'user_id' =>  'required',
            'user_to_be_blocked'    =>  'required',
            'is_blocked' => 'required',
            'reason'  =>  'required',       
        ];
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
     * @DateOfCreation        14 Sept 2018
     * @ShortDescription      This method is responsible for update user miles
     * @param                 \Illuminate\Http\Request  $request
     * @return                Result of array data
     */
    public function update_miles(Request $request)
    {
        $request_data = $this->getRequestData($request);
        $userMilesValidate = $this->update_user_miles($request_data);

        $request_data['user_id'] = $this->security_lib_obj->decrypt($request_data['user_id']);
        // user miles validate 
        if($userMilesValidate["error"]) {
            return $this->echoResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                $userMilesValidate['errors'],
                trans('User::messages.update_miles_validation_failed'), 
                $this->http_codes['HTTP_OK']
            ); 
        }

        $totalMiles = $this->user_model_obj->ifMilesExist($request_data);
        if(!empty($totalMiles)){
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                [$totalMiles],
                [],
                trans('User::messages.miles_updated_successfully'),
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [],
                [],
                trans('User::messages.not_found'),
                $this->http_codes['HTTP_OK']
            );
        }
    }

    /**
     * @DateOfCreation        14 Sept 2018
     * @ShortDescription      Get a validator for an incoming user miles request
     * @param                 \Illuminate\Http\Request  $request
     * @return                \Illuminate\Contracts\Validation\Validator
     */
    protected function update_user_miles($requestData)
    {
        $errors         = [];
        $error          = false;
        $validationData = [];
        // Check validations
        $validationData = [
            'user_id' =>  'required',
            'miles'   =>  'required'      
        ];
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
     * @DateOfCreation        18 Sept 2018
     * @ShortDescription      get_user_trip_locations request
     * @param                 \Illuminate\Http\Request  $request
     * @return                Response
     */
    public function get_user_trip_locations(Request $request)
    {
       $request_data = $this->getRequestData($request);
        $location_data = $this->location->get_location_list($request_data['trip_id'], $request_data['user_id'], $request_data['start_time'], $request_data['end_time']);
        if($location_data){
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                array_values($location_data),
                [],
                trans('User::messages.success'),
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                [],
                [],
                trans('User::messages.error'),
                $this->http_codes['HTTP_OK']
            );
        }
    }

    /**
     * @DateOfCreation        19 Sept 2018
     * @ShortDescription      This method is responsible for update user location
     * @param                 \Illuminate\Http\Request  $request
     * @return                Result of array data
    */
    public function save_location(Request $request)
    {
        $request_data = $this->getRequestData($request);
        $request_data['user_agent'] =  $request->server('HTTP_USER_AGENT');
        $request_data['ip_address'] =  $request->ip();
        $request_data['created_at'] =  Config::get('constants.CURRENTDATE');
        $location_id = $this->location->insert_location($request_data);
        if(!empty($location_id)){
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                [],
                [],
                trans('User::messages.location_insert_successfully'),
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [],
                [],
                trans('User::messages.not_found'),
                $this->http_codes['HTTP_OK']
            );
        }
    }
    
}
