<?php

namespace App\Modules\Admin\Models;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use App\Libraries\SecurityLib;
use DB;
use Config;

/**
 * Vehicle
 *
 * @subpackage             Vehicle
 * @category               Model
 * @author                 fxbytes
 * @DateOfCreation         29 August 2018
 * @ShortDescription       This model connect with the vehicles table 
 */
class Vehicle extends Model
{
    /** @var String $primaryKey
     *  This protected member contains talbe primary key
     */
    protected $primaryKey = 'vehicle_id';
    protected $table = 'vehicles';
    protected $vehicle_assignments = 'vehicle_assignments';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'vehicle_name', 'insurance_number', 'registration_number', 'vehicle_type', 'vehicle_year', 'vehicle_model', 'vehicle_avatar', 'resource_type', 'ip_address', 'user_agent', 'is_deleted'
    ];

    /**
     * Create a new model instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Init security library object
        $this->securityLibObj = new SecurityLib();
    }

    /**
    * @DateOfCreation        29 August 2018
    * @ShortDescription      Get vehicle list.
    * @param 1               $request_data
    * @return                Array of object
    */
    public function get_vehicles($requestData, $company_id) {
        $selectData  =  [
                            'vehicle_id',
                            'vehicle_name',
                            'registration_number',
                            'vehicle_type',
                            'vehicle_avatar',
                            'vehicle_model',
                            'insurance_number',
                            'vehicle_year',
                            'vehicle_make',
                            'vehicle_trim',
                            'vehicle_sate',
                            'vehicle_primary_meter',
                            'vehicle_fuel_unit'
                        ];
        $whereData   =  [
                        'is_deleted'=>  Config::get('constants.IS_DELETE_NO'),
                        'company_id' => $company_id
                        ];
        $query =  DB::table($this->table)
                    ->select($selectData)
                    ->where($whereData);

        /* Condition for Filtering the result */

        if(!empty($requestData['filtered'])){
            foreach ($requestData['filtered'] as $key => $value) {
                $query = $query->where(function ($query) use ($value){
                                $query
                                ->orWhere(DB::raw('CAST(vehicles.vehicle_name AS TEXT)'), 'ILIKE', $value['value'])
                                ->orWhere(DB::raw('CAST(vehicles.registration_number AS TEXT)'), 'ILIKE', $value['value'])
                                ->orWhere(DB::raw('CAST(vehicles.vehicle_type AS TEXT)'), 'ILIKE', $value['value'])
                                ->orWhere(DB::raw('CAST(vehicles.vehicle_avatar AS TEXT)'), 'ILIKE', $value['value'])
                                ->orWhere(DB::raw('CAST(vehicles.vehicle_model AS TEXT)'), 'ILIKE', $value['value']);
                            });
            }
        }

        /* Condition for Sorting the result */
        if(!empty($requestData['sorted'])){
            foreach ($requestData['sorted'] as $key => $value) {
                $orderBy = $value['desc'] ? 'desc' : 'asc';
                $query = $query->orderBy($value['id'], $orderBy);
            }
        }else{
            $query = $query->orderBy('vehicle_name', 'asc');
        }

        if($requestData['page'] > 0){
            $offset = $requestData['page']*$requestData['pageSize'];
        }else{
            $offset = 0;
        }
        
        $Data['pages'] = ceil($query->count()/$requestData['pageSize']);
        $Data['data'] = $query
                    ->offset($offset)
                    ->limit($requestData['pageSize'])
                    ->get()
                    ->map(function ($vehicleList) {
                        $vehicleList->vehicle_id     = $this->securityLibObj->encrypt($vehicleList->vehicle_id);
                        $vehicleList->vehicle_avatar = url('api/vehicle-image/'.$this->securityLibObj->encrypt( !empty($vehicleList->vehicle_avatar) ? $vehicleList->vehicle_avatar : Config::get('constants.DEFAULT_IMAGE_NAME')))  ;
                        return $vehicleList;
                    });
        return $Data;
    }    
    
    /**
    * @DateOfCreation        30 August 2018
    * @ShortDescription      save vehicle list for dropdow selection.
    * @param 1               $request_data, user_agent, ip_address, created_at
    * @return                Array of object
    */
    public function save_vehicles($request_data, $user_agent, $ip_address, $created_at, $company_id) {
        
        $insertData = array(
            'company_id'            =>  $company_id,
            'vehicle_name'          => $request_data['vehicle_name'],
            'insurance_number'      => $request_data['insurance_number'],
            'registration_number'   => $request_data['registration_number'],
            'vehicle_type'          => $request_data['vehicle_type'],
            'vehicle_year'          => isset($request_data['vehicle_year']) ? $request_data['vehicle_year'] : null,
            'vehicle_make'          => isset($request_data['vehicle_make']) ? $request_data['vehicle_make'] : null,
            'vehicle_model'         => isset($request_data['vehicle_model']) ? $request_data['vehicle_model'] : null,
            'vehicle_trim'          => isset($request_data['vehicle_trim']) ? $request_data['vehicle_trim'] : null,
            'vehicle_sate'          => isset($request_data['vehicle_sate']) ? $request_data['vehicle_sate'] : null,
            'vehicle_primary_meter' => isset($request_data['vehicle_primary_meter']) ? $request_data['vehicle_primary_meter'] : null,
            'vehicle_fuel_unit'     => isset($request_data['vehicle_fuel_unit']) ? $request_data['vehicle_fuel_unit'] : null,
            'resource_type'         => 'web',
            'user_agent'            => $user_agent,
            'ip_address'            => $ip_address,
            'created_at'            => $created_at
        );

        if(!empty($request_data['vehicle_id'])){
            $vehicleId  = $request_data['vehicle_id'];
            $isUpdate   = $this->updateVehicleData($insertData, ['vehicle_id' => $vehicleId]);
            $lastId     = $vehicleId;
        }else{
            $id = DB::table($this->table)->insert($insertData);
            
            // get the last inserted drvier name
            $lastId = DB::getPdo()->lastInsertId();            
        }

        // select user data from users table
        return $data = DB::table($this->table)->select('vehicle_id', 'vehicle_name', 'vehicle_type', 'vehicle_model', 'registration_number', 'vehicle_avatar')->where('vehicle_id', $lastId)->get();
    }

    /**
    * @DateOfCreation        29 August 2018
    * @ShortDescription      Get get_vehicle_assignments list.
    * @param 1               $request_data
    * @return                Array of object
    */
    public function get_vehicle_assignments($requestData, $company_id) {
        $selectData  =  ['vehicle_assignment_id', 
                        'vehicle_name', 
                        'first_name',
                        'last_name',
                        'description',
                        $this->vehicle_assignments.'.vehicle_id',
                        $this->vehicle_assignments.'.user_id'
                    ];
        $whereData   =  ['vehicle_assignments.is_deleted'=>Config::get('constants.IS_DELETE_NO'), 'vehicle_assignments.company_id' => $company_id];

        $query =  DB::table($this->vehicle_assignments)
                    ->select($selectData)
                    ->leftJoin($this->table, 'vehicle_assignments.vehicle_id', '=', 'vehicles.vehicle_id')
                    ->leftJoin('users', 'vehicle_assignments.user_id', '=', 'users.user_id')
                    ->where($whereData);

        /* Condition for Filtering the result */

        if(!empty($requestData['filtered'])){
            foreach ($requestData['filtered'] as $key => $value) {
                $query = $query->where(function ($query) use ($value){
                                $query
                                ->orWhere(DB::raw('CAST(vehicles.vehicle_name AS TEXT)'), 'ILIKE', $value['value'])
                                ->orWhere(DB::raw('CAST(users.first_name AS TEXT)'), 'ILIKE', $value['value'])
                                ->orWhere(DB::raw('CAST(vehicle_assignments.description AS TEXT)'), 'ILIKE', $value['value']);
                            });
            }
        }

        /* Condition for Sorting the result */
        if(!empty($requestData['sorted'])){
            foreach ($requestData['sorted'] as $key => $value) {
                $orderBy = $value['desc'] ? 'desc' : 'asc';
                $field = $value['id'];
                $query = $query->orderBy($field, $orderBy);
            }
        } else{
            $query = $query->orderBy('vehicle_assignment_id', 'asc');
        }

        if($requestData['page'] > 0){
            $offset = $requestData['page']*$requestData['pageSize'];
        }else{
            $offset = 0;
        }
        
        $Data['pages'] = ceil($query->count()/$requestData['pageSize']);
        $Data['data'] = $query
                    ->offset($offset)
                    ->limit($requestData['pageSize'])
                    ->get()
                    ->map(function ($assignmentsList) {
                        $assignmentsList->vehicle_assignment_id = $this->securityLibObj->encrypt($assignmentsList->vehicle_assignment_id);
                        $assignmentsList->first_name = $assignmentsList->first_name.' '.$assignmentsList->last_name;
                        return $assignmentsList;
                    });
        return $Data;
    }

    /**
    * @DateOfCreation        30 August 2018
    * @ShortDescription      save vehicle list for dropdow selection.
    * @param 1               $request_data, user_agent, ip_address, created_at
    * @return                Array of object
    */
    public function save_vehicles_assignments($request_data, $user_agent, $ip_address, $created_at, $company_id) {
       $insertData = array(
            'company_id'    => $company_id,
            'vehicle_id'    => $request_data['vehicle_id'],
            'user_id'       => $request_data['user_id'],
            'description'   => $request_data['description'],
            'resource_type' => 'web',
            'user_agent'    => $user_agent,
            'ip_address'    => $ip_address,
            'created_at'    => $created_at
        );
        
        if(!empty($request_data['vehicle_assignment_id'])){
            $vehicle_assignment_id  = $request_data['vehicle_assignment_id'];
            $isUpdate   = $this->updateVehicleAssignments ( $insertData, ['vehicle_assignment_id' => $vehicle_assignment_id] );
            $lastId     = $vehicle_assignment_id;
        }else{
            $id = DB::table($this->vehicle_assignments)->insert($insertData);
            $lastId = DB::getPdo()->lastInsertId();
        }

        // select user data from vehicle assignmnet table
        $selectData  =  ['vehicle_assignment_id', 'vehicle_name', 'first_name', 'last_name', 'description'];
        $whereData   =  [
                            'vehicle_assignments.is_deleted'=>Config::get('constants.IS_DELETE_NO'),
                            'vehicle_assignments.vehicle_assignment_id'=>$lastId
                        ];

        return $data = DB::table($this->vehicle_assignments)
                    ->select($selectData)
                    ->leftJoin($this->table, 'vehicle_assignments.vehicle_id', '=', 'vehicles.vehicle_id')
                    ->leftJoin('users', 'vehicle_assignments.user_id', '=', 'users.user_id')
                    ->where($whereData)->get();
    }

    /**
    * @DateOfCreation        29 August 2018
    * @ShortDescription      Get vehicle list for dropdow selection.
    * @param 1               $school_id number
    * @return                Array of object
    */
    public function get_vehicles_list_dropdown($requestData) {
        $selectData  =  ['vehicle_id as value', 'registration_number as label'];
        $whereData   =  [
                        'school_id'=> $requestData['school_id'],
                        'is_deleted'=>  Config::get('constants.IS_DELETE_NO')
                        ];
        return $query =  DB::table($this->table)
                    ->select($selectData)
                    ->where($whereData)
                    ->get();
    }

    /**
    * @DateOfCreation        7 Sept 2018
    * @ShortDescription      Delete Vehicle records.
    * @param 1               $array Vehicle record                
    * @param 2               $array where condition
    * @return                response
    */
    public function updateVehicleData($updateData, $whereData){
        return DB::table($this->table)->where( $whereData )->update($updateData);
    }

    /**
    * @DateOfCreation        7 Sept 2018
    * @ShortDescription      Delete Vehicle Assignments records.
    * @param 1               $array Vehicle Assignments record                
    * @param 2               $array where condition
    * @return                response
    */
    public function updateVehicleAssignments($updateData, $whereData){
        return DB::table($this->vehicle_assignments)->where( $whereData )->update($updateData);
    }
}
