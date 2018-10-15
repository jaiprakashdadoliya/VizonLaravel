<?php
namespace App\Modules\Admin\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Validator;
use Config;
use Mail;
use Carbon\Carbon;
use App\Libraries\SecurityLib;
use App\Libraries\FileLib;
use App\Libraries\ImageLib;
use App\Traits\RestApi;
use App\Models\User;
use App\Modules\Admin\Models\Vehicle;
use Schema;
use Excel;
use DB, File, Response;

/**
 * VehicleController Class
 *
 * @package                Vizon
 * @subpackage             VehicleController
 * @category               Controller
 * @DateOfCreation         24 August 2018
 * @ShortDescription       This controller perform all vehicle table functionality for admin api
 */

class VehicleController extends Controller
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

        // Init user model object
        $this->user_model_obj = new User();

        // Init vehicle object
        $this->vehicle_model_obj = new Vehicle();
    }

    /**
    * @DateOfCreation        29 August 2018
    * @ShortDescription      vehicle list
    * @param                 Object $request This contains full request
    * @return                Array
    */
    public function vehicles(Request $request)
    {
        $company_id = Auth::id();
        $request_data = $this->getRequestData($request);

        // $vehicle_model = new Vehicle;
        $list  = $this->vehicle_model_obj->get_vehicles($request_data, $company_id);

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
    * @DateOfCreation        30 August 2018
    * @ShortDescription      Add new vehicle
    * @param                 Object $request This contains full request
    * @return                Array
    */
    public function add_vehicles(Request $request)
    {
        $company_id = Auth::id();
        $request_data = $this->getRequestData($request);
        $request_data['vehicle_id'] = !empty($request_data['vehicle_id']) ? $this->security_lib_obj->decrypt($request_data['vehicle_id']):NULL;
        
        if(!empty($request_data)){
            foreach ($request_data as $key => $value) {
                if($value == 'null' || $value == 'undefined'){
                    $request_data[$key] = NULL;
                }
            }
        }

        $validate = $this->addVehiclesValidations($request_data);
        if($validate["error"]){
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                $validate['errors'],
                trans('Admin::vehicles.validation_failed'), 
                $this->http_codes['HTTP_OK']
            ); 
        }

        $user_agent  = $request->server('HTTP_USER_AGENT');
        $ip_address  = $request->ip();
        $created_at  = Config::get('constants.CURRENTDATE');
        // $vehicle_model = new Vehicle;
        $data  = $this->vehicle_model_obj->save_vehicles($request_data, $user_agent, $ip_address, $created_at, $company_id);

        if(!empty($data)){

            $isMediaAdded   = false;
            $destination    = Config::get('constants.VEHICLE_MEDIA_PATH');

            $fileUpload = [];
            $imagePath = $data[0]->vehicle_avatar;
            if( !empty($request_data['vehicle_avatar']) && is_object($request_data['vehicle_avatar']) ){

                if(!File::exists(storage_path($destination))) {
                    // path does not exist
                    File::makeDirectory(storage_path($destination), 0777, true, true);
                } 

                $fileUpload = $this->FileLib->fileUpload($request_data['vehicle_avatar'], $destination);                
                
                $mediaData = array();
                $imagePath = storage_path($destination).$fileUpload['uploaded_file'];
                if(($fileUpload['code']) && $fileUpload['code'] == 1000) {
                    try {
                        $updateData = [ 'vehicle_avatar' => $fileUpload['uploaded_file'] ];
                        $whereData  = [ 'vehicle_id'     => $data[0]->vehicle_id ];
                        $updateImageData = $this->vehicle_model_obj->updateVehicleData($updateData, $whereData);
                    } catch (\Exception $e) {
                        if(!empty($fileUpload['uploaded_file']) && file_exists($imagePath)){
                            unlink($imagePath);
                        }

                        if(File::exists($destination.$fileUpload['uploaded_file'])){
                            File::delete($destination.$fileUpload['uploaded_file']);
                        }

                        if(File::exists($thumbPath.$fileUpload['uploaded_file'])){
                            File::delete($thumbPath.$fileUpload['uploaded_file']);
                        }
                    }
                   
                    if(!empty($request_data['vehicle_id']) && !empty($data[0]->vehicle_avatar) && file_exists(storage_path($destination).$data[0]->vehicle_avatar)){
                        unlink(storage_path($destination).$data[0]->vehicle_avatar);
                    }
                }
            }
            
            $list = array(
                "vehicle_id"            => $this->security_lib_obj->encrypt($data[0]->vehicle_id),
                "vehicle_name"          => $data[0]->vehicle_name,
                "vehicle_type"          => $data[0]->vehicle_type,
                "vehicle_model"         => $data[0]->vehicle_model,
                "registration_number"   => $data[0]->registration_number,
                "vehicle_avatar"        => $imagePath
            );
           
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
    }

    /**
     * @DateOfCreation        9 Sept 2018
     * @ShortDescription      Delete vehicle record
     * @param                 Object $request This contains full request
     * @return                Array
     */
    public function delete_vehicle(Request $request)
    {
        // send data to user model for delete driver record
        // $vehicle_model = new Vehicle;
        $request_data = $request->all();

        $updateData = ['is_deleted' => Config::get('constants.IS_DELETE_YES')];
        $whereData  = ['vehicle_id' => $this->security_lib_obj->decrypt($request_data['vehicle_id'])];
        $data  = $this->vehicle_model_obj->updateVehicleData($updateData, $whereData);

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
                trans('Admin::messages.delete_error'),
                $this->http_codes['HTTP_OK']
            );
        }
    }

    /**
     * @DateOfCreation        7 Sept 2018
     * @ShortDescription      Delete vehicle record
     * @param                 Object $request This contains full request
     * @return                Array
     */
    public function delete_vehicles_assignments(Request $request)
    {
        // send data to user model for delete driver record
        // $vehicle_model = new Vehicle;
        $request_data = $request->all();

        $updateData = ['is_deleted' => Config::get('constants.IS_DELETE_YES')];
        $whereData  = ['vehicle_assignment_id' => $this->security_lib_obj->decrypt($request_data['vehicle_assignment_id'])];
        $data       = $this->vehicle_model_obj->updateVehicleAssignments($updateData, $whereData);

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
                trans('Admin::messages.delete_error'),
                $this->http_codes['HTTP_OK']
            );
        }
    }

    /**
    * @DateOfCreation        30 August 2018
    * @ShortDescription      Add new vehicle
    * @param                 Object $request This contains full request
    * @return                Array
    */
    public function add_vehicles_assignments(Request $request)
    {
        $company_id = Auth::id();
        $request_data = $this->getRequestData($request);
        
        $request_data['vehicle_assignment_id'] = !empty($request_data['vehicle_assignment_id']) ? $this->security_lib_obj->decrypt($request_data['vehicle_assignment_id']) : $request_data['vehicle_assignment_id'];
        $user_agent  = $request->server('HTTP_USER_AGENT');
        $ip_address  = $request->ip();
        $created_at  = Config::get('constants.CURRENTDATE');
        
        $validate = $this->addVehiclesAssignmentValidations($request_data);
        if($validate["error"]){
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                $validate['errors'],
                trans('Admin::vehicles.validation_failed'), 
                $this->http_codes['HTTP_OK']
            ); 
        }

        $data  = $this->vehicle_model_obj->save_vehicles_assignments($request_data, $user_agent, $ip_address, $created_at, $company_id);


        if(!empty($data)){

            $list = array(
                "vehicle_assignment_id" => $data[0]->vehicle_assignment_id,
                "vehicle_name" => $data[0]->vehicle_name,
                "first_name" => $data[0]->first_name.' '.$data[0]->last_name,
                "description" => $data[0]->description
            );
           
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
    }
    

    /**
    * @DateOfCreation        30 August 2018
    * @ShortDescription      get vehicle assignment list
    * @param                 Object $request This contains full request
    * @return                Array
    */
    public function get_vehicle_assignments(Request $request)
    {
        $company_id = Auth::id();
        $request_data = $this->getRequestData($request);
        $result = array();
        // $vehicle_model = new Vehicle;
        $result['list']  = $this->vehicle_model_obj->get_vehicle_assignments($request_data, $company_id);

        // get vehicle and driver list
        $result['vehicleList'] = DB::table('vehicles')->select('vehicle_id as value', 'vehicle_name as label')->where('is_deleted', Config::get('constants.IS_DELETE_NO'))->where('company_id', $company_id)->get()->toArray();

        $result['driverList'] = DB::table('users')->select('user_id as value', DB::raw("CONCAT(first_name, ' ' ,last_name) as label"))->where('is_deleted', Config::get('constants.IS_DELETE_NO'))->where('user_type', 'driver')->where('company_id', $company_id)->get()->toArray();
        
        // validate, is query executed successfully
        if ($result) {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                $result,
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
    * @DateOfCreation        30 August 2018
    * @ShortDescription      Get a validator for an incoming add vehicles assignmemt request
    * @param                 \Illuminate\Http\Request  $request
    * @return                \Illuminate\Contracts\Validation\Validator
    */
    protected function addVehiclesAssignmentValidations($requestData){
        $errors         = [];
        $error          = false;
        $validationData = [];

        // Check validations
        if(!empty($requestData['vehicle_assignment_id'])){
            $validationData = [
                'description'       => 'required',
                'user_id'           => 'required',
                'vehicle_id'        => 'required',
                'vehicle_assignment_id' =>  'required',
            ];

        } else {
            $validationData = [
                'description'       => 'required',
                'user_id'           => 'required',
                'vehicle_id'        => 'required',
            ];
        }
        
        if(!empty($requestData['vehicle_avatar']) && is_array($requestData['vehicle_avatar'])){
            $validationData = array_merge($validationData, ['vehicle_avatar' =>  'nullable|file|max:4096|mimes:'.Config::get('constants.IMAGE_MIME_TYPE')]);
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
    * @DateOfCreation        30 August 2018
    * @ShortDescription      Get a validator for an incoming add vehicles request
    * @param                 \Illuminate\Http\Request  $request
    * @return                \Illuminate\Contracts\Validation\Validator
    */
    protected function addVehiclesValidations($requestData){
        $errors         = [];
        $error          = false;
        $validationData = [];

        // Check validations
        if(!empty($requestData['vehicle_id'])){
            $validationData = [
                'registration_number' =>  'required|unique:vehicles,registration_number,'.$requestData['vehicle_id'].',vehicle_id',
                'vehicle_year'        =>  'nullable|numeric|digits:4'
            ];

        } else {
            $validationData = [
                'registration_number' =>  'required|unique:vehicles',
                'vehicle_year'        =>  'nullable|numeric|digits:4'
            ];
        }
        
        if(!empty($requestData['vehicle_avatar']) && is_array($requestData['vehicle_avatar'])){
            $validationData = array_merge($validationData, ['vehicle_avatar' =>  'nullable|file|max:4096|mimes:'.Config::get('constants.IMAGE_MIME_TYPE')]);
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
    public function getVehicleImage($imageName)
    {
        $destination = Config::get('constants.VEHICLE_MEDIA_PATH');
        $imageName = $this->security_lib_obj->decrypt($imageName);
        $imageName = empty($imageName) ? Config::get('constants.DEFAULT_IMAGE_NAME'):$imageName;
        
        return $this->user_model_obj->getImagePath($destination, $imageName);   
    }
}
