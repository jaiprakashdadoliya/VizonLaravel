<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PgsqlTimeToSecondsConversionFunction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared('
                CREATE OR REPLACE FUNCTION to_seconds(t text)
                RETURNS integer AS
                $BODY$ 
                DECLARE 
                    hs INTEGER;
                    ms INTEGER;
                    s INTEGER;
                BEGIN
                    SELECT (EXTRACT( HOUR FROM  t::time) * 60*60) INTO hs; 
                    SELECT (EXTRACT (MINUTES FROM t::time) * 60) INTO ms;
                    SELECT (EXTRACT (SECONDS from t::time)) INTO s;
                    SELECT (hs + ms + s) INTO s;
                    RETURN s;
                END;
                $BODY$
                LANGUAGE "plpgsql";
            ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
