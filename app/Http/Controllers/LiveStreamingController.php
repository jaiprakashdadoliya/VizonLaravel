<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Libraries\SecurityLib;
use Config;

/**
 * LiveStreamingController class
 *
 * @package 			Vizon
 * @subpackage 			LiveStreamingController
 * @category 			Controller
 * @DateOfCreation  	13 Sept 2018
 * @ShortDescription    This class perform live streaming operation
 */
class LiveStreamingController extends Controller
{
	/**
    * Create a new controller instance.
    * @return void
    */
    public function __construct()
    {
        // Init security library object
        $this->security_lib_obj = new SecurityLib();
    }

    /**
     * Display a listing of the resource.
     *
     * @DateOfCreation  13 Sept 2018
     * @param $request
     * @return \Illuminate\Http\Response
     */
    public function doLiveStreaming($id=NULL)
    {
    	if($id) {
    		// $id = $this->security_lib_obj->decrypt($id);
	    	$url = 'http%3A%2F%2F18.188.210.10%3A1935%2Flive%2F'.$id.'%2FdoPublish%3D425132%2Fplaylist.m3u8';
	    	$loadingImg = url(Config::get('constants.LOADING_IMAGE'));
	    ?>
		    <script type="text/javascript" src="//player.wowza.com/player/latest/wowzaplayer.min.js"></script>
		    <div id="playerElement" style="width:380px; height:250px; padding:0">

		        <p id="wloading" style="text-align: center;margin-top: 50px;"><img src="<?php echo $loadingImg; ?>" ></p>
		    </div>
		    <script type="text/javascript">
		        setTimeout(function () {
		            WowzaPlayer.create('playerElement',
		                {
		                    "license":"PLAY1-ePE3z-ndtm3-rWdtG-VDPb4-YQNzv",
		                    "sourceURL":"<?php echo $url ;?>",
		                }
		            );
		            document.getElementById('wloading').style.display = "none";
		        }, 5000);

		        /*http://18.188.210.10:1935/live/5b7c129f8ffdef1d898115cb/doPublish=425132/playlist.m3u8*/
		    </script>
	    <?php
		} else {
		    echo "No Streaming Found";
		}

    }
}
