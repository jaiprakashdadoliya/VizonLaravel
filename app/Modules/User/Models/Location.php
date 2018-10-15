<?php

namespace App\Modules\User\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use DB, Config;
use App\Libraries\SecurityLib;
use Carbon\Carbon;

class Location extends Model
{
    use HasApiTokens, Notifiable;

    /** @var String $primaryKey
     *  This protected member contains talbe primary key
     */
    protected $primaryKey = 'history_id';
    protected $table = 'location_history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'company_id', 'user_latitude', 'user_longitude', 'resource_type', 'user_agent', 'ip_address', 'is_deleted', 'created_at', 'updated_at'];

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
     * @DateOfCreation        14 Aug 2018
     * @ShortDescription      Use for trip API.
     * @param 1               $requestedData
     * @return                Array of object
     */
    public function insert_location($request_data) {
        $insert_data = array(
            'user_id' =>  $this->security_lib_obj->decrypt($request_data['user_id']),
            'user_lattitude' =>  $request_data['lat'],
            'user_longitude' =>  $request_data['long'],
            'user_agent'            => $request_data['user_agent'],
            'ip_address'            => $request_data['ip_address'],
            'created_at'            => $request_data['created_at']
        );
        DB::table($this->table)->insert($insert_data);
        // get the last inserted drvier name
        $lastId = DB::getPdo()->lastInsertId();
        return $lastId;
    }


    public function get_location_list($trip_id, $user_id, $start_time, $end_time) {
        $user_id = $this->security_lib_obj->decrypt($user_id);
        $start_time = new Carbon($start_time);
        $end_time = new Carbon($end_time);
        $selectData  =  [
            'user_lattitude',
            'user_longitude',
        ];
        $whereData   =  [
            'user_id'=> $user_id,
        ];

        $output['locations'] =  DB::table($this->table)
            ->select($selectData)
            ->where($whereData)
            ->orderBy('history_id', 'asc')
            ->whereBetween('created_at', array(($start_time), ($end_time)))
            ->get()
            ->map(function($list){
                $list->lat = (float)$list->user_lattitude;
                $list->lng =  (float)$list->user_longitude;
                unset($list->user_lattitude);
                unset($list->user_longitude);
                return $list;
            });
        return $output;
    }
}
