<?php

namespace App\Modules\Search\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use DB, Config;
use App\Libraries\SecurityLib;

class Search extends Model {

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
     * @DateOfCreation        25 Sept 2018
     * @ShortDescription      This function is responsible to get user videos
     * @param                 String $searchData
     * @return                drivers
     */
    public function get_user_videos($search_data, $company_id)
    {

        $selectData =  ['user_videos.user_id', 'user_videos.video_id', 'user_videos.bucket_name', 'user_videos.video_name', 'user_videos.video_url', 'user_videos.video_thumbnail_url',  'user_videos.created_at'];
        $query =     DB::table('user_videos')
            ->select($selectData)
            ->where('company_id', $company_id);

        if(!empty($search_data['driver_id'])){
            $query->where('user_id', $search_data['driver_id']);
        }

        if(!empty($search_data['seleted_date_modified'])){
            $query->whereDate(DB::raw("user_videos.created_at::timestamp::date"), $search_data['seleted_date_modified']);
        }

        return  $videos =
            $query
                ->get()
                ->map(function($list){
                    $list->user_id   = $this->securityLibObj->encrypt($list->user_id);
                    $list->video_id   = $this->securityLibObj->encrypt($list->video_id);
                    return $list;
                });
    }

    /**
     * @DateOfCreation        25 sept 2018
     * @ShortDescription      This function is responsible to get vehicles
     * @param                 String $searchData
     * @return                vehicles
     */
    public function getVehicles($searchData, $company_id)
    {
        $selectData =  ['users.user_id','users.user_firstname','users.user_lastname'];
        $whereData   =  array(
            'users.is_deleted'      => Config::get('constants.IS_DELETED_NO'),
            'users.company_id'       => $company_id
        );
        $query =     DB::table('doctors')
            ->select($selectData)
            ->where($whereData)
            ->where(function ($query) use ($searchData){
                $query->orWhere('users.user_firstname', 'ilike', '%'.$searchData.'%');
                $query->orWhere('users.user_lastname', 'ilike', '%'.$searchData.'%');
            });
        return  $doctors =
            $query
                ->get()
                ->map(function($doctors){
                    $doctors->doc_spec_detail = $this->doctorObj->getDoctorSpecialisation($doctors->user_id);
                    unset($doctors->user_id);
                    return $doctors;
                });
    }

}
