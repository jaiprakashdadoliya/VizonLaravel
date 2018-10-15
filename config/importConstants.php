<?php

return [
    //  Parent Validation

    'parent_validation' => '[{"users":[{"Label":"Parent Reference","Required":"true","Column":"user_reference","adminCheck":"users,user_reference"},{"Label":"Title","Column":"title"},{"Label":"First Name","Column":"first_name"},{"Label":"Middle Name","Column":"middle_name"},{"Label":"Last Name","Column":"last_name"},{"Label":"Email","Required":"true","Column":"email","Unique":"true","DataType":"email"},{"Label":"Contact","Required":"true","Min":"10","Max":"13","Unique":"true","Column":"mobile"},{"Label":"Address","Column":"address"},{"Label":"City","Column":"city"},{"Label":"State","Column":"state"},{"Label":"Postcode","Column":"postcode"},{"Label":"Delete","Column":"is_deleted"}]}]',


    // Import Save
     'parent_save' => ["user_reference,title,first_name,middle_name,last_name,email,mobile,address,city,state,postcode"],

    // Export CSV
    'export_parent' => [
        "header"=>["Parent Reference", "Title", "First Name", "Middle Name", "Last Name", "Email", "Contact", "Address", "City", "State", "Postcode", "Delete"],           
        "column"=>["user_reference as Parent Reference", "title as Title", "first_name as First Name", "middle_name as Middle Name", "last_name as Last Name", "email as Email", "mobile as Contact", "address as Address", "city as City", "state as State", "postcode as Postcode", "is_deleted as Delete"],
        "table"=>"users",
        "joinA"=>"",
        "joinB"=>"",
        "whereA"=>"school_id",
        "whereB"=> ["is_deleted"],
        "whereIn"=>["user_type"=>["parent"]],
        "orderby"=>["user_reference"=>"asc"]
    ],

    'beacons_validation' => '[{"beacons":[{"Label":"Beacon Name","Required":"true","Column":"beacon_name","beconNameCheck":"true"},{"Label":"UUID","Column":"uuid"},{"Label":"Major","Column":"major"},{"Label":"Miner","Column":"miner"},{"Label":"Mac Address","Column":"mac_address"}]},{"students":[{"Label":"Student ID","Required":"true","Column":"student_reference"},{"Label":"Delete","Column":"is_deleted"}]}]',

    // Stoppage Validation
    'stoppage_validation' => '[{"stoppages":[{"Label":"Stoppage Name","Required":"true","Column":"stoppage_name", "stoppageNameCheck":"true"},{"Label":"Stoppage Address","Required":"true","Column":"stoppage_address"},{"Label":"Latitude","Column":"stoppage_latitude","lat":"true"},{"Label":"Longitude","Column":"stoppage_longitude","long":"true"},{"Label":"Delete","Column":"is_deleted"}]}]',

    // Import Save
    'stoppage_save' => ["stoppage_reference,stoppage_name,stoppage_address"],

    //export CSV
    'export_stoppage' => [
        "header"=>["Stoppage Name", "Stoppage Address","Latitude","Longitude","Delete"],
        "column"=>["stoppage_name as Stoppage Name", "stoppage_address as Address","stoppage_latitude","stoppage_longitude" ,"is_deleted as Delete"],
        "table"=>"stoppages",
        "joinA"=>"",
        "joinB"=>"",
        "whereA"=>"school_id",
        "whereB"=> ["is_deleted"],
        "whereIn"=>"",
        "orderby"=>["stoppage_reference"=>"asc"]
    ],

    //  Vehicle Validation
    'vehicle_validation' => '[{"vehicles":[{"Label":"Bus Reference","Required":"true","Column":"vehicle_reference"},{"Label":"Bus Name","Required":"true","Column":"vehicle_name"},{"Label":"Bus Number","Required":"true","Column":"registration_number"},{"Label":"Bus Contact","Required":"true","Column":"emergency_contact_number"},{"Label":"Chassis Number","Required":"true","Column":"chassis_number"},{"Label":"Bus Capacity","Required":"true","Column":"bus_capacity"},{"Label":"Last Fitness Date","Column":"last_maintenance"},{"Label":"Delete","Column":"is_deleted"}]}]',

    // Import Save
    'vehicle_save' => ["vehicle_reference,vehicle_name,registration_number,emergency_contact_number,chassis_number,bus_capacity,last_maintenance"],

    //export CSV
    'export_vehicle' => [
        "header"=>["Bus Reference", "Bus Name", "Bus Number", "Bus Contact", "Chassis Number", "Bus Capacity", "Last Fitness Date", "Delete"],
        "column"=>["vehicle_reference as Bus ID", "vehicle_name as Bus Name", "registration_number as Bus Number","emergency_contact_number as Bus Contact", "chassis_number as Chassis Number", "bus_capacity as Bus Capacity", "last_maintenance as Last Fitness Date", "is_deleted as Delete"],
        "table"=>"vehicles",
        "joinA"=>"",
        "joinB"=>"",
        "whereA"=>"school_id",
        "whereB"=> ["is_deleted"],
        "whereIn"=>"",
        "orderby"=>["vehicle_reference"=>"asc"]
    ],

    //  Staff Validation
    'staff_validation' => '[{"users":[{"Label":"Staff Reference","Required":"true","Column":"user_reference"},{"Label":"Full Name","Required":"true","Column":"name"},{"Label":"Type","Required":"true","Column":"user_type","DataType":{"enum":"assistant,driver"}},{"Label":"Email","Required":"true","Column":"email","Unique":"true","DataType":"email"},{"Label":"Contact","Required":"true","Min":"10","Max":"13","Unique":"true","Column":"mobile"},{"Label":"Aadhaar Number","Required":"true","Column":"aadhaar_number"},{"Label":"Driving licence","Required":"true","Column":"driving_licence_number"},{"Label":"License Expiry","Column":"license_expiry"},{"Label":"Driving Experience","Column":"experience"},{"Label":"Blood Group","Column":"blood_group"},{"Label":"Emergency Contact Number","Column":"emergency_contact_number"},{"Label":"Address","Column":"address"},{"Label":"City","Column":"city"},{"Label":"Post Code","Column":"postcode"},{"Label":"State","Column":"state"},{"Label":"Delete","Column":"is_deleted"}]}]',
    
    // Import Save
    'staff_save' => ["user_reference,name,user_type,email,mobile,aadhaar_number,driving_licence_number,license_expiry,experience,blood_group,emergency_contact_number,address,city,postcode,state"],

    //export CSV
    'export_staff' => [
        "header"=>["Staff Reference", "Full Name", "Type", "Email", "Contact", "Aadhaar Number", "Driving licence", "License Expiry",  "Driving Experience", "Blood Group", "Emergency Contact Number", "Address", "City", "Post Code", "State", "Delete"],
        "column"=>["user_reference as Staff ID", "name as Full Name", "user_type as Type","email as Email", "mobile as Contact", "aadhaar_number as Aadhaar Number", "driving_licence_number as Driving licence", "license_expiry as License Expiry", "experience as Driving Experience", "blood_group as Blood Group", "emergency_contact_number as Emergency Contact Number", "address as Address", "city as City", "postcode as Post Code", "state as State","is_deleted as Delete"],
        "table"=>"users",
        "joinA"=>"",
        "joinB"=>"",
        "whereA"=>"school_id",
        "whereB"=> ["is_deleted"],
        "whereIn"=>["user_type"=>["driver","assistant"]],
        "orderby"=>["user_reference"=>"asc"]
    ],

    // Route stoppage
    'routeStoppage_validation' => '[{"routes":[{"Label":"Route Reference","Required":"true","Column":"route_reference","preventUniqueCheck":"true"},{"Label":"Route Name","Required":"true","Column":"route_name"}]},{"route_stoppages":[{"Label":"Stoppage Reference","Required":"true","Column":"stoppage_id","ForeignKeyCheck":"stoppages"},{"Label":"Delete","Column":"is_deleted"}]}]',


    //export CSV
    'export_route_stoppage' => [
        "header"=>["Route Reference", "Route Name", "Stoppage Reference", "Delete"],
        "column"=>["route_reference as Route Reference", "route_name as Route Name", "stoppage_reference as Stoppage Reference","route_stoppages.is_deleted as Delete"],
        "table"=>"route_stoppages",
        "joinA" => 'routes,routes-route_id,=,route_stoppages-route_id',
        "joinB"=> 'stoppages,stoppages-stoppage_id,=,route_stoppages-stoppage_id',
        "whereA"=>"route_stoppages.school_id",
        "whereB"=> ["routes.is_deleted","stoppages.is_deleted","route_stoppages.is_deleted"],
        "whereIn"=>"",
        "orderby"=>["route_reference"=>"asc"]
    ],

    // Student Validation
    'student_validation' => '[{"students":[{"Label":"Student Reference","Required":"true","Column":"student_reference"},{"Label":"First Name","Required":"true","Column":"first_name"},{"Label":"Last Name","Column":"last_name"},{"Label":"Gender","Column":"gender"},{"Label":"Class","Required":"true","Column":"class"},{"Label":"Blood Group","Column":"blood_group"}]},{"student_parents":[{"Label":"Parent ID 1","Column":"user_id"},{"Label":"Relation","Column":"relation"},{"Label":"Parent ID 2","Column":"user_id"},{"Label":"Relation","Column":"relation"},{"Label":"Parent ID 3","Column":"user_id"},{"Label":"Relation","Column":"relation"},{"Label":"Parent ID 4","Column":"user_id"},{"Label":"Relation","Column":"relation"},{"Label":"Delete","Column":"is_deleted"}]}]',

    'student_save' => ["student_reference,name,class","user_id"],

    //  Staff Validation
    'staffAllocation_validation' => '[{"employee_vehicles":[{"Label":"Employee Vehicle ID","Required":"true","Column":"employee_vehicle_reference"},{"Label":"Bus ID","Required":"true","Column":"vehicle_id","ForeignKeyCheck":"vehicles"},{"Label":"Effective Date","Required":"true","Column":"effective_date"},{"Label":"Driver ID","Required":"true","Column":"user_driver_id","ForeignKeyCheck":"users", "typeCheck":"users,user_reference,driver"},{"Label":"Assistant ID","Required":"true","Column":"user_assistant_id","ForeignKeyCheck":"users","typeCheck":"users,user_reference,assistant"},{"Label":"Delete","Column":"is_deleted"}]}]',
    
    // Import Save
    'staffAllocation_save' => ["employee_vehicle_reference,vehicle_id,effective_date,user_driver_id,user_assistant_id"],

    // Import Save

    //export CSV
    'export_student' => [
        "header"=>["Student Reference", "Student Name", "Class", "Parent ID", "Delete"],
        "column"=>["student_reference as Student ID", "students.name as Student Name", "class as Class", "user_reference as Parent ID", "students.is_deleted as Delete"],
        "table"=>"students",
        "joinA" => 'student_parents,student_parents-student_id,=,students-student_id',
        "joinB"=> 'users,users-user_id,=,student_parents-user_id',
        "whereA"=>"students.school_id",
        "whereB"=> ["students.is_deleted","student_parents.is_deleted","users.is_deleted"],
        "whereIn"=>"",
        "orderby"=>["student_reference"=>"asc"]
    ],

    //export CSV Custom created

    //   Staff Validation

    //export CSV
    'export_staff_allocation' => [
        "header"=>["Employee Vehicle ID" ,"Bus ID", "Effective Date", "Driver ID", "Assistant ID", "Delete"],
        "column"=>["employee_vehicle_reference as Employee Vehicle ID","vehicle_reference as Bus ID", "effective_date as Effective Date", "user_reference as Driver ID", "user_reference as Assistant ID", "is_deleted as Delete"],
        "table"=>"employee_vehicles",
        "joinA" => "",
        "joinB"=> "",
        "whereA"=>"school_id",
        "whereB"=> ["is_deleted"],
        "whereIn"=>"",
        "orderby"=>["employee_vehicle_id"=>"asc"]
    ],

    //  Route Stoppage Validation

    // Import Save

    //  Route Allocation Validation
    'routeAllocation_validation' => '[{"vehicle_routes":[{"Label":"Vehicle Route ID","Required":"true","Column":"vehicle_route_reference"},{"Label":"Route ID","Required":"true","Column":"route_id","ForeignKeyCheck":"routes"},{"Label":"Vehicle ID","Required":"true","Column":"vehicle_id","ForeignKeyCheck":"vehicles"},{"Label":"Start Time","Required":"true","Column":"start_time","DataType":"time"},{"Label":"End Time","Required":"true","Column":"end_time","DataType":"time"},{"Label":"Delete","Column":"is_deleted"}]}]',

    // Import Save
    'routeAllocation_save' => ["vehicle_route_reference,route_id,vehicle_id,start_time,end_time"],

    'studentAllocation_validation' => ["Student ID","Pickup Schedule Route ID","Pickup Stoppage ID","Pickup Time","Drop Schedule Route ID","Drop Stoppage ID","Drop Time","Delete"],

    // Import Save
    'studentAllocation_save' => ["student_vehicle_route_reference,route_id,vehicle_id,start_time,student_id,stoppage_pickup,pickup_time,stoppage_drop,drop_time"],

    //export CSV
    'export_route_allocation' => [
        "header"=>["Vehicle Route ID","Route ID", "Vehicle ID", "Start Time", "End Time", "Delete"],
        "column"=>["vehicle_route_reference as Vehicle Route ID","route_reference as Route ID", "vehicle_reference as Vehicle ID", "start_time as Start Time", "end_time as End Time", "vehicle_routes.is_deleted as Delete"],
        "table"=> "vehicle_routes",
        "joinA" => 'routes,routes-route_id,=,vehicle_routes-route_id',
        "joinB"=> 'vehicles,vehicles-vehicle_id,=,vehicle_routes-vehicle_id',
        "whereA"=> "vehicle_routes.school_id",
        "whereB"=> ["vehicle_routes.is_deleted","routes.is_deleted","vehicles.is_deleted"],
        "whereIn"=> "",
        "orderby"=>["route_reference"=>"asc"]
    ],

    //export CSV
    'export_device' => [
        "header"=>["Device Reference", "Device Name", "Device Token", "OS Version", "Device Model", "Device Type", "User Type"],
        "column"=>["device_reference as Device Reference", "device_name as Device Name", "device_token as Device Token","os_version as OS Version", "device_model as Device Model", "device_type as Device Type", "user_type as User Type"],
        "table"=>"devices",
        "joinA"=>"",
        "joinB"=>"",
        "whereA"=>"device_id",
        "whereB"=> ["is_deleted"],
        "whereIn"=>["user_type"=>["assistant"]],
        "orderby"=>["device_reference"=>"asc"]
    ],

    //  Parent Validation
    'device_allocation_validation' => '[{"device_vehicles":[{"Label":"Device Vehicle Reference","Required":"true","Column":"device_vehicle_reference"},{"Label":"Vehicle Reference","Required":"true","Column":"vehicle_id","ForeignKeyCheck":"vehicles"},{"Label":"Device Reference","Required":"true","Column":"device_id","ForeignKeyCheck":"devices"},{"Label":"Delete","Column":"is_deleted"}]}]',

    'export_device_allocation' => [
        "header"  => ["Device Vehicle Reference", "Vehicle Reference", "Device Reference", "Delete"],
        "column"  => ["device_vehicle_reference as Device Vehicle Reference", "vehicle_reference as Vehicle Reference","device_reference as Device Reference" , "device_vehicles.is_deleted as Delete"],
        "table"   => "device_vehicles",
        "joinA"   => 'vehicles,vehicles-vehicle_id,=,device_vehicles-vehicle_id',
        "joinB"   => 'devices,devices-device_id,=,device_vehicles-device_id',
        "whereA"  => "device_vehicles.school_id",
        "whereB"  => ["device_vehicles.is_deleted","vehicles.is_deleted","devices.is_deleted"],
        "whereIn" => [],
        "orderby" => ["device_vehicle_reference"=>"asc"]
    ],

    'schedule_route_validation' => '["Schedule Route ID" ,"Bus Name", "Route Name", "Shift", "Driver", "Assistant", "Device", "Start Time", "End Time", "Delete"]',

    'type' => ['Vehicle Type', 'Employee Type']
];