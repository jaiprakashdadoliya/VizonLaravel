<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use App\Libraries\SecurityLib;
use DB, File, Response, Config;

/**
 * User
 *
 * @subpackage             User
 * @category               Model
 * @DateOfCreation         22 August 2018
 * @ShortDescription       This model connect with the Users table 
 */
class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /** @var String $primaryKey
     *  This protected member contains talbe primary key
     */
    protected $primaryKey = 'user_id';
    protected $table = 'users'; 
    protected $companyDetailsTable = 'company_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'password', 'email', 'mobile', 'address', 'state', 'city', 'postcode','driver_license_number', 'user_type', 'resource_type', 'ip_address', 'user_agent', 'is_deleted'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
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
    * @DateOfCreation        18 July 2018
    * @ShortDescription      Get the Access token on behalf of user id 
    * @return                Array
    */
    public function oauth_access_token(){
        return $this->hasMany('\App\Models\OauthAccessToken','user_id','user_id');
    }

    /**
    * @DateOfCreation        22 August 2018
    * @ShortDescription      Get the Driver list 
    * @return                Array
    */
    public function get_drivers($requestData, $company_id){

        $selectData  =  [
                            'user_id', 
                            'first_name', 
                            'last_name',
                            'company_name',
                            'user_type', 
                            'email', 
                            'mobile', 
                            'profile_picture',
                            'gender',
                            'blood_group',
                            'user_group',
                            'address',
                            'state',
                            'city',
                            'postcode',
                            'date_of_birth',
                            'job_title',
                            'employee_number',
                            'employee_start_date',
                            'employee_end_date',
                            'driver_license_number',
                            'driver_license_class',
                            'driver_license_state',
                            'license_expiry'
                        ];

        if($requestData['user_type'] == Config::get('constants.USER_TYPE_ADMIN')){
            $whereData   =  ['user_type' => Config::get('constants.USER_TYPE_COMPANY'), 'company_details.is_deleted'=> Config::get('constants.IS_DELETE_NO')]; 

        } else {
            $whereData   =  [ 'users.user_type'=> Config::get('constants.USER_TYPE_DRIVER'), 'users.company_id' => $company_id, 'users.is_deleted'=> Config::get('constants.IS_DELETE_NO')];

        }

        $query =  DB::table($this->table)
                    ->select($selectData)
                    ->leftJoin($this->companyDetailsTable, 'users.user_id', '=', 'company_details.company_id')
                    ->where($whereData);

        /* Condition for Filtering the result */
        if(!empty($requestData['filtered'])){
            foreach ($requestData['filtered'] as $key => $value) {
                $query = $query->where(function ($query) use ($value){
                                $query
                                ->orWhere(DB::raw('CAST(first_name AS TEXT)'), 'ilike', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(last_name AS TEXT)'), 'ilike', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(company_name AS TEXT)'), 'ilike', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(user_type AS TEXT)'), 'ilike', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(email AS TEXT)'), 'ilike', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(mobile AS TEXT)'), 'ilike', '%'.$value['value'].'%');
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
            $query = $query->orderBy('first_name', 'asc');
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
                    ->map(function ($driverList) {
                        $driverList->user_id = $this->securityLibObj->encrypt($driverList->user_id);
                        $driverList->profile_picture = url('api/driver-image/'.$this->securityLibObj->encrypt($driverList->profile_picture)) ;
                        return $driverList;
                    });
        return $Data;
    }

    /**
    * @DateOfCreation        23 August 2018
    * @ShortDescription      add new drivers list.
    * @param 1               $requestedData                
    * @return                Array of object
    */
    public function add_drivers($requestData, $randomPassword, $user_agent, $ip_address, $created_at, $company_id){

        
        $insertDriverData = array(
            'first_name'            => isset($requestData['first_name']) ? $requestData['first_name'] : null,
            'last_name'             => isset($requestData['last_name']) ? $requestData['last_name'] : null,
            'company_id'            => $company_id,
            'email'                 => $requestData['email'],
            'password'              => $randomPassword,
            'mobile'                => $requestData['mobile'],
            'user_type'             => $requestData['user_type'],
            'gender'                => isset($requestData['gender']) ? $requestData['gender'] : 'NA',
            'blood_group'           => isset($requestData['blood_group']) ? $requestData['blood_group'] : null,
            'user_group'            => isset($requestData['user_group']) ? $requestData['user_group'] : null,
            'address'               => isset($requestData['address']) ? $requestData['address'] : null,
            'state'                 => isset($requestData['state']) ? $requestData['state'] : null,
            'city'                  => isset($requestData['city']) ? $requestData['city'] : null,
            'postcode'              => isset($requestData['postcode']) ? $requestData['postcode'] : null,
            'date_of_birth'         => isset($requestData['date_of_birth']) ? $requestData['date_of_birth'] : null,
            'job_title'             => isset($requestData['job_title']) ? $requestData['job_title'] : null,
            'employee_number'       => isset($requestData['employee_number']) ? $requestData['employee_number'] : null,
            'employee_start_date'   => isset($requestData['employee_start_date']) ? $requestData['employee_start_date'] : null,
            'employee_end_date'     => isset($requestData['employee_end_date']) ? $requestData['employee_end_date'] : null,
            'driver_license_number' => isset($requestData['driver_license_number']) ? $requestData['driver_license_number'] : null,
            'driver_license_class'  => isset($requestData['driver_license_class']) ? $requestData['driver_license_class'] : null,
            'driver_license_state'  => isset($requestData['driver_license_state']) ? $requestData['driver_license_state'] : null,
            'license_expiry'        => isset($requestData['license_expiry']) ? $requestData['license_expiry'] : null,
            'resource_type'         => 'web',
            'user_agent'            => $user_agent,
            'ip_address'            => $ip_address,
            'created_at'            => $created_at
        );
        

        if(!empty($requestData['user_id'])){
            unset($insertDriverData['password']);
            $userId     = $requestData['user_id'];
            $isUpdate   = $this->updateUserData($insertDriverData, ['user_id' => $userId]);
            $lastId     = $userId;

            //  user user type is admin then we have update entry into company_details table
            $company_name = isset($requestData['company_name']) ? $requestData['company_name'] : null;
            if(!empty($company_name) && $company_name != null){
                DB::table($this->companyDetailsTable)->where(array('company_id' => $lastId, 'is_deleted' => Config::get('constants.IS_DELETE_NO')))->update(array('company_name'  => $company_name, 'user_agent'    => $user_agent, 'ip_address' => $ip_address, 'updated_at' => $created_at));
            }

        } else {
            if($requestData['user_type'] = Config::get('constants.USER_TYPE_DRIVER')){
                $id = DB::table($this->table)->insert($insertDriverData);            
                // get the last inserted drvier name
                $lastId = DB::getPdo()->lastInsertId();                
            }

            //  user user type is admin then we have insert entry into company_details table
            $company_name = isset($requestData['company_name']) ? $requestData['company_name'] : null;
            if(!empty($company_name) && $company_name != null){
                DB::table($this->companyDetailsTable)->insert(array('company_id' => $lastId, 'company_name' => $company_name, 'resource_type' => 'web', 'user_agent' => $user_agent, 'ip_address' => $ip_address, 'created_at' => $created_at));
            }
        }

        // select user data from users table
        return $data = DB::table($this->table)
                        ->select('user_id', 'first_name', 'last_name', 'user_type', 'email', 'mobile', 'profile_picture', 'company_detail_id', 'company_name')
                        ->leftJoin($this->companyDetailsTable, 'users.user_id', '=', 'company_details.company_id')
                        ->where('user_id', $lastId)
                        ->get();
    }
    
    /**
    * @DateOfCreation        04 sept 2018
    * @ShortDescription      Get the user list 
    * @return                Array
    */
    public function get_user_list($user_id){
        // return $requestData;
        $selectData  =  ['user_id', 'first_name', 'last_name', 'user_type', 'email', 'mobile', 'profile_picture', 'vehicle_picture'];
        $whereData   =  [
                        'user_id'=>$user_id,
                        'user_type'=> 'user',
                        'is_deleted'=>  0
                        ];
        $query =  DB::table($this->table)
                    ->select($selectData)
                    ->where($whereData)->get();
        
        return $query;
    }
    
    /**
    * @DateOfCreation        08 August 2018
    * @ShortDescription      Get user driver detail.
    * @param 1               $user_reference number                
    * @param 2               $school_id number                
    * @param 3               $user_type string
    * @return                Array of object
    */
    public function get_user_data_by_type($user_reference, $school_id, $user_type) {
        return DB::table('users')
                ->select('user_id')    
                ->where('user_reference', '=', $user_reference)
                ->where('school_id', '=', $school_id)
                ->where('user_type', '=', $user_type)
                ->where('is_deleted', '=', 0)
                ->first(); 
    }

    /**
    * @DateOfCreation        7 Sept 2018
    * @ShortDescription      Delete user records.
    * @param 1               $array user record                
    * @param 2               $array where condition
    * @return                response
    */
    public function updateUserData($updateData, $whereData, $companyData=''){
        if(!empty($companyData)){
            DB::table($this->companyDetailsTable)->where( $companyData )->update($updateData);
            return DB::table($this->table)->where( $whereData )->update($updateData);            
        } else {
            return DB::table($this->table)->where( $whereData )->update($updateData);
        }
    }

    /**
     * @DateOfCreation        10 Sept 2018
     * @ShortDescription      This function is responsible to get the image path
     * @param                 String $imageName
     * @return                response
     */
    public function getImagePath($destination, $imageName)
    {
        $imagePath =  $destination;
        $imageName = empty($imageName) ? Config::get('constants.DEFAULT_IMAGE_NAME'):$imageName;
        $path = storage_path($imagePath) . $imageName; 

        if(!File::exists($path)){
            $path = public_path(Config::get('constants.DEFAULT_IMAGE_PATH')).$imageName;
        }
        
        $file = File::get($path);
        $type = File::mimeType($path);
        $response = Response::make($file, 200);
        $response->header("Content-Type", $type);
        return $response;
    }

    /**
     * @DateOfCreation        17 Sept 2018
     * @ShortDescription      This function is responsible to get the user miles
     * @param miles
     * @return 
    */
    public function ifMilesExist($request_data)
    {
        $result = DB::table($this->table)->select('miles')->where(array('user_id' => $request_data['user_id'], 'is_deleted' => Config::get('constants.IS_DELETE_NO')))->first();

        $data = [];
        if(!empty($result)){
            
            if(!empty($result->miles)){
                $totalMiles = $result->miles + $request_data['miles'];            
            } else {
                $totalMiles = $request_data['miles'];
            }

            // udpate miles
            DB::table($this->table)->where(array('user_id' => $request_data['user_id'], 'is_deleted' => Config::get('constants.IS_DELETE_NO')))->update(array('miles' => $totalMiles));

            $data['miles'] = floatval($totalMiles);
            return $data;
        } else {
            return $data;            
        }
    }

    /**
     * @DateOfCreation        21 Sept 2018
     * @ShortDescription      This function is responsible to get the total companies
     * @param miles
     * @return 
    */
    public function get_total_company()
    {
        return $result = DB::table($this->table)->where(array('company_details.is_deleted' => Config::get('constants.IS_DELETE_NO'), 'users.is_deleted' => Config::get('constants.IS_DELETE_NO'), 'user_type' => Config::get('constants.USER_TYPE_COMPANY')))->join($this->companyDetailsTable, 'users.user_id', '=', 'company_details.company_id')->count();
    }

    /**
     * @DateOfCreation        24 Sept 2018
     * @ShortDescription      This function is responsible to get the name of user
     * @param miles
     * @return 
    */
    public function get_user_details($email)
    {
        // select user data from users table
        $data = DB::table($this->table)
                    ->select('first_name', 'last_name', 'company_name', 'user_type')
                    ->leftJoin($this->companyDetailsTable, 'users.user_id', '=', 'company_details.company_id')
                    ->where(array('email' => $email, 'users.is_deleted' => Config::get('constants.IS_DELETE_NO')))
                    ->get();
        return $data;
    }
}