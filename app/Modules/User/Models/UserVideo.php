<?php

namespace App\Modules\User\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use DB, Config, File, Response;
use App\Libraries\SecurityLib;

class UserVideo extends Model
{
    use HasApiTokens, Notifiable;

    /** @var String $primaryKey
     *  This protected member contains table primary key
     */
    protected $primaryKey = 'video_id';
    protected $table = 'user_videos';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
            'user_id', 'company_id', 'bucket_name'
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
    * @DateOfCreation        18 Sept 2018
    * @ShortDescription      Insert User video data.
    * @param 2               $array
    * @return                Array of object
    */
    public function insert_user_video($request_data, $user_agent, $ip_address, $video_name)
    {
        $data = array();
        // prepare user data array
        $inserted_data = array(
            'user_id'   =>  $request_data['user_id'],
            'company_id'    =>  $request_data['company_id'],
            'vehicle_id'    =>  isset($request_data['vehicle_id']) ? $request_data['vehicle_id'] : null, 
            'bucket_name'   =>  Config::get('constants.BUCKET_NAME'),
            'video_name'    =>  isset($video_name) ? $video_name : null,
            'video_url'     =>  isset($request_data['video_url']) ? $request_data['video_url'] : null,
            'video_size'    =>  isset($request_data['video_size']) ? $request_data['video_size'] : null,
            'resource_type' =>  strtolower($request_data['resource_type']),
            'user_agent'    =>  $user_agent,
            'ip_address'    =>  $ip_address,
            'is_deleted'    =>  Config::get('constants.IS_DELETE_NO'),
            'created_at'    =>  Config::get('constants.CURRENTDATE'),
            'updated_at'    =>  Config::get('constants.CURRENTDATE'),
            'video_thumbnail_url'   =>  isset($request_data['video_thumbnail_url']) ? $request_data['video_thumbnail_url'] : null,
        );

        $ifExist = DB::table($this->table)->where(array('user_id' => $request_data['user_id'], 'company_id' => $request_data['company_id'], 'video_name' => $video_name, 'is_deleted' => Config::get('constants.IS_DELETE_NO')))->count();

        if($ifExist > 0){
            // update
            unset($request_data['created_at']);
            $affected = DB::table($this->table)->where(array('user_id' =>$request_data['user_id'], 'video_name' => $video_name, 'is_deleted' => Config::get('constants.IS_DELETE_NO')))->update($inserted_data);
            if($affected){
                $last_id = DB::table($this->table)->select('video_id')->where(array('user_id' => $request_data['user_id'], 'company_id' => $request_data['company_id'], 'video_name' => $video_name, 'is_deleted' => Config::get('constants.IS_DELETE_NO')))->first();

                $id = $last_id->video_id;                
                $data['s3_video_id'] = $id;
            }
        } else {
            // insert into user video table
            $inserted = DB::table($this->table)->insert($inserted_data);
            // get the last inserted drvier name
            $id = DB::getPdo()->lastInsertId(); 
            $data['s3_video_id'] = $id;
        }
        return $data;
    }

    /**
    * @DateOfCreation        18 Sept 2018
    * @ShortDescription      Get User video data.
    * @param 2               $array
    * @return                Array of object
    */
    public function get_user_video($request_data)
    {
        $where_data = array('user_id' => $request_data['user_id'], 'company_id' => $request_data['company_id'], 'is_deleted' => Config::get('constants.IS_DELETE_NO'));

        $selectData =  ['user_videos.user_id', 'user_videos.video_id', 'user_videos.vehicle_id', 'user_videos.bucket_name', 'user_videos.video_name', 'user_videos.video_url', 'user_videos.video_thumbnail_url',  'user_videos.created_at'];

        $query  =    DB::table($this->table)
            ->select($selectData)
            ->where($where_data);

        $result =   $query
                        ->get()
                        ->map(function($list){
                            $list->vehicle_id = $this->security_lib_obj->encrypt($list->vehicle_id);
                            return $list;
                        });

        if(!empty($result)){       
            return $result;
        } else {
            return false;
        }

    }

    /**
    * @DateOfCreation        18 Sept 2018
    * @ShortDescription      Delete User video data.
    * @param 2               $array
    */
    public function delete_user_video($request_data)
    {
        $s3_video_id = $request_data['s3_video_id'];
        $where_data = array('video_id' => $s3_video_id);
        $update_data = array('is_deleted' => Config::get('constants.IS_DELETE_YES'), 'updated_at' => Config::get('constants.CURRENTDATE'));
        // update user video record
        $isDeleted = DB::table($this->table)->where($where_data)->update($update_data);

        return true;
    }

    /**
     * @DateOfCreation        10 Sept 2018
     * @ShortDescription      This function is responsible to get the image path
     * @param                 String $imageName
     * @return                response
     */
    public function get_image_path($destination, $imageName)
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
    

}