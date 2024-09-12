<?php 
global $wpdb;
$table_employees = $wpdb->prefix . 'dyme_employees';
$result = $wpdb -> get_var(
    "SELECT mission FROM $table_employees WHERE ID = 12"
);
//var_dump($result);exit;
if ($result) {
    echo $result . 'mission';
}elseif($result == NULL){
    echo 'no record found';
}
else{
    echo 'no mission';;
}
print_r( $result );
exit;