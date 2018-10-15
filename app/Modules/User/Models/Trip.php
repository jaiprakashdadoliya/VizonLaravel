<?php

namespace App\Modules\User\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use DB, Config;
use App\Libraries\SecurityLib;

class Trip extends Model
{
    use HasApiTokens, Notifiable;

    /** @var String $primaryKey
     *  This protected member contains talbe primary key
     */
    protected $primaryKey = 'trip_id';
    protected $table = 'trips';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'company_id', 'vehicle_id', 'start_time', 'end_time', 'start_address', 'end_address', 'start_latitude', 'start_longitude', 'end_latitude', 'end_longitude','distance', 'created_by',
        'updated_by', 'resource_type', 'user_agent', 'ip_address', 'is_deleted', 'created_at', 'updated_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Create a new model instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Init security library object
        $this->security_lib_obj = new SecurityLib();
    }

    /**
    * @DateOfCreation        05 Sept 2018
    * @ShortDescription      Trip data.
    * @param 2               $array
    * @return                Array of object
    */
    public function get_trip_data($selectedDate, $company_id, $vehicle_id, $user_id) {
        $selectData  =  [
            'users.user_id',
            'users.first_name',
            'users.last_name',
            'trips.trip_id',
            'trips.created_at',
            'trips.start_time',
            'trips.end_time',
            'trips.distance',
            'trips.start_address',
            'trips.end_address',
            'trips.start_latitude',
            'trips.start_longitude',
            'trips.end_latitude',
            'trips.end_longitude'
        ];

        $whereData   =  [
            'trips.company_id'=> $company_id,
            'trips.is_deleted'=> Config::get('constants.IS_DELETE_NO')
        ];

        $query = DB::table($this->table)
            ->select($selectData)
            ->where($whereData)
            ->whereDate(DB::raw("trips.created_at::timestamp::date"), $selectedDate)
            ->whereNotNull('trips.end_time');
            
        if(!empty($user_id)){
            $query->where('trips.user_id', $user_id);
        }

        if(!empty($vehicle_id)){
            $query->where('trips.vehicle_id', $vehicle_id);            
        }

        $output['trips'] =  $query
            ->join('users', 'users.user_id', '=', 'trips.user_id')
            ->get()
            ->map(function($list){
                $list->user_id = $this->security_lib_obj->encrypt($list->user_id);
                $list->getDiffBetweenTime = '';
                return $list;
            });

        $output['users'] =  DB::table($this->table)
            ->select('users.user_id', DB::raw("CONCAT(users.first_name, ' ' ,users.last_name) as fullname"), DB::raw("SUM(trips.distance) as totaldistance"), DB::raw("TO_CHAR((SUM(to_seconds(trips.end_time::text) - to_seconds(trips.start_time::text)) || ' second')::interval, 'HH24:MI:SS') AS timediff"))
            ->where($whereData)
            ->where(DB::raw("trips.created_at::timestamp::date"), $selectedDate)
            ->whereNotNull('trips.end_time')
            ->join('users', 'users.user_id', '=', 'trips.user_id')
            ->groupBy('users.user_id')
            ->groupBy('users.first_name')
            ->groupBy('users.last_name')
            ->get()
            ->map(function($list){
                $list->user_id = $this->security_lib_obj->encrypt($list->user_id);
                return $list;
            });

        return $output;
    }


    /**
    * @DateOfCreation        14 sept 2018
    * @ShortDescription      Use for trip API.
    * @param 1               $school_id number
    * @return                Array of object
    */
    public function get_list($request_data) {
        $selectData  =  [
                            'users.name',
                            'trips.created_at',
                            'trips.start_time',
                            'trips.end_time',
                            'trips.distance'
                        ];
        $whereData   =  [
                            'trips.school_id'=> $request_data['school_id'],
                            'trips.is_deleted'=>  Config::get('constants.IS_DELETE_NO')
                        ];

        if(!empty($request_data['vehicle_id']))
        {
            $whereData['vehicle_routes.vehicle_id'] = $request_data['vehicle_id'];
        }

        $query =  DB::table($this->table)
                        ->select($selectData)
                        ->where($whereData)
                        ->join('users', 'users.user_id', '=', 'trips.driver_id')
                        ->join('vehicle_routes', 'vehicle_routes.vehicle_route_id', '=', 'trips.vehicle_route_id');

        /* Condition for Filtering the result */
        if(!empty($request_data['filtered'])){
            foreach ($request_data['filtered'] as $key => $value) {
                $query = $query->where(function ($query) use ($value){
                                $query
                                ->orWhere(DB::raw('CAST(users.name AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(trips.start_time AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(trips.end_time AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(trips.distance AS TEXT)'), 'like', '%'.$value['value'].'%');
                            });
            }
        }

        /* Condition for Sorting the result */
        if(!empty($request_data['sorted'])){
            foreach ($request_data['sorted'] as $key => $value) {
                $orderBy = $value['desc'] ? 'desc' : 'asc';
                $query = $query->orderBy($value['id'], $orderBy);
            }
        }
        
        if($request_data['page'] > 0){
            $offset = $request_data['page']*$request_data['pageSize'];
        } else {
            $offset = 0;
        }

        $Data['pages'] = ceil($query->count()/$request_data['pageSize']);
        $Data['data'] = $query
                        ->offset($offset)
                        ->limit($request_data['pageSize'])
                        ->get()
                        ->map(function ($list) {
                            $list->created_at = date("d/m/Y", strtotime($list->created_at));
                            $list->start_time = date("g:i A", strtotime($list->start_time));
                            if(!empty($list->end_time)){
                                $list->end_time = date("g:i A", strtotime($list->end_time));
                                $list->duration = round(abs(strtotime($list->end_time) - strtotime($list->start_time)) / 60). " minute";
                            }else{
                                $list->end_time = '';
                                $list->duration = round(abs(time() - strtotime($list->start_time)) / 60). " minute";
                            }
                            return $list;
                        });
        return $Data;
    }
}
