<?php

/**
 *@ShortDescription Define all constants that going to use in the Application.
 *
 * @var Array
 */
return [
    // Sercurity keys
    'ENCRYPTION_KEY1'   =>   env('VIZON_ENCRYPTION_KEY1'),
    'ENCRYPTION_KEY2'   =>   env('VIZON_ENCRYPTION_KEY2'),
    'ENVIRONMENT' 	    =>    "local",
    'FILEPREFIX'        =>    3,
    'FILEPERMISSION'    =>    0755,
    'URLEXPIRY'		    =>    5, // in minutes 
    'API_PREFIX'	    =>    'api',
    'WEB_PREFIX'	    =>    'web',
    'MAIL_FROM'	        =>    'smtp@fxbytes.com',

    //Media Path
    'CSV_PATH'                      => '../storage/csv/',
    'CRYPT_KEY'                     => '$2y$10$Kf6kFKti0hNMNc7s8T0kkOd/z9kLg0I0bLuvVDBs016q.5IshZ6Um',
    'NODE_URL'                      => 'http://52.15.137.203:3001/login',
    'IS_DELETE_YES'                 => 1,
    'IS_DELETE_NO'                  => 0,
    'DEFAULT_COMPANY_ID'            => 0,
    'REASON_TYPE_OTHER'             => 'other',
    'REASON_TYPE_DIRECT'            => 'direct',
    'IMAGE_MIME_TYPE'               => 'png,jpg,jpeg,gif',
    'VEHICLE_MEDIA_PATH'            => 'user_vehicle_picture/',
    'VEHICLE_MEDIA_THUMB_PATH'      => 'user_vehicle_picture/thumb',
    'USER_PROFILE_MEDIA_PATH'       => 'user_picture/',
    'THUMBNAIL_PATH'                => 'video_thumbnail/',
    'DEFAULT_IMAGE_PATH'            => 'images/',
    'DEFAULT_IMAGE_NAME'            => 'default_user.png',
    'AUTH_TOKEN_NAME'               => '_lgbds',
    'USER_PHOTO_PATH'               =>    'user_picture/',
    'USER_VEHICLE_PHOTO_PATH'       =>    'user_vehicle_picture/',
    'GET_USER_PHOTO_PATH'           =>  '/api/media/user_picture/',
    'GET_VEHICLE_PHOTO_PATH'        => '/api/media/user_vehicle_picture/',
    'LOADING_IMAGE'                 =>  'public/images/Loading_icon.gif',
    'BUCKET_NAME'                   => 'com.maps.vizon',
    'S3_EXPIRY_TIME'                =>  '+20 minutes',
    'CURRENTDATE'                   =>  date("Y-m-d H:i:s"),
    // User Type
    'USER_TYPE_DRIVER'              => 'driver',
    'USER_TYPE_ADMIN'               => 'admin',
    'USER_TYPE_USER'                => 'user',
    'USER_TYPE_COMPANY'             => 'company',
    // Email constants
    'MAIL_DRIVER'                   =>  'smtp',
    'MAIL_HOST'                     =>  'ssl://smtp.gmail.com',
    'MAIL_PORT'                     =>  465,
    'MAIL_USERNAME'                 =>  'smtp@fxbytes.com',
    'MAIL_PASSWORD'                 =>  'Fx56@be@ta',
    'MAIL_ENCRYPTION'               =>  null

];
