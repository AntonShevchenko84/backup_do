#!/usr/bin/php
<?php


function get_snapshots($page) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://api.digitalocean.com/v2/snapshots?page=".$page);
	/* There were some error during testing, that's why I removed this cert verification*/
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json",
				"Authorization: Bearer "
			));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,TRUE);
	if (! $result = curl_exec($ch)) {
		trigger_error(curl_error($ch));
	}
	return json_decode($result);
	//return ($result);

}

function _create_snapshot($name) {
	return true;
}

function create_snapshot($name) {
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,"https://api.digitalocean.com/v2/droplets/ /actions");	curl_setopt($ch,CURLOPT_HTTPHEADER,array("Content-Type: application/json","Authorization: Bearer "));
								curl_setopt($ch,CURLOPT_POST,'1');
								$data = json_encode(array('type' => 'snapshot',
															'name' => $name));
								
								curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
/* There were some error during testing, that's why I removed this cert verification*/
								curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
								curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
								curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
								
	if (! $result = curl_exec($ch)) {
		trigger_error(curl_error($ch));
		return false;
	}
	return $result;
}
function delete_snapshot($id) {
	$output=exec("curl -k -X DELETE -H 'Content-Type: application/json' -H 'Authorization: Bearer ' https://api.digitalocean.com/v2/snapshots/".$id);
 	return $output;
	}

function get_all_snapshots() {
	$snapshots=[];
	$page = $last = $i= 1;
	$page_obj = get_snapshots($page);
	
	preg_match('/page=(\d)/',$page_obj->{'links'}->{'pages'}->{'last'},$match);
	$last=$match[1];
	do {
		//printf('%s page\n',$page);
		//if ($page >1 ) {	$page_obj = get_list_snapshot($page); printf('iteration %s', $page);}
		$page_obj = get_snapshots($page);
		foreach($page_obj->{'snapshots'} as $key => $item) {
			$snapshots[] = $item;
		}
		$page++;
	} while ($page <= $last);
return $snapshots;
}

function write_log($fh,$string) {

	fwrite($fh, $string);
	printf("%s\n",$string);
}

function _find_and_delete($log_file,$snapshots, $del_name) {	
	
		foreach ($snapshots as $id => $item) {
				if ($item->{'name'} == $del_name) {
				
					$message = date('d h m s:').': snapshot '.$item->{'name'}.' has been deleted';
					write_log($log_file,$message);
					
					//delete_snapshot($item->{'id'});

					return true;

				}
		}
			
		return false;
}		
/*
	$message = date('d h m s:').': snapshot '.$item->{'name'}.' has been created';
					write_log($message);
					
					//create_snapshot($current_name);


*/
/*** BODY OF PROGRAM ***/
date_default_timezone_set('Europe/Minsk');
$snapshots=get_all_snapshots();

if (empty($argv[1])) {
	write_log(date('Y m j H:i:s').': No Arguments!');
	exit('');
}

$day = $argv[1];
$log_file=fopen('operations.log','w+');

if (date('j') != $day) {
	write_log($log_file,date('Y m j H:i:s').': Current day is not the same that script was given an argument');
	exit('');
}

$current_name = 'AG: '.date('Y F ').$day;
$_month_del_name = 'AG: '.date('Y F', strtotime('-2 month'));
$_week_del_name = 'AG: '.date('Y F j', strtotime('-2 weeks'));

if (date('t', strtotime('-1 month')) == 30) {
	$_daily_del_name = 'AG: '.date('Y F j', strtotime('-9 days'));	
} else if (date('t',strtotime('-1 month')) == 31) {
	if ($day < 12) {
		$_daily_del_name = 'AG: '.date('Y F j', strtotime('-10 days'));	
	} else {
		$_daily_del_name = 'AG: '.date('Y F j', strtotime('-9 days'));
	}

} else if (date('t',strtotime('-1 month')) == 29) {
	if ($day <12 ){
	$_daily_del_name = 'AG: '.date('Y F j', strtotime('-8 days'));	
	} else {
		$_daily_del_name = 'AG: '.date('Y F j', strtotime('-9 days'));
	}
} else if (date('t',strtotime('-1 month')) == 28) {
	if ($day < 12) {
	$_daily_del_name = 'AG: '.date('Y F j', strtotime('-7 days'));	
	} else {
		$_daily_del_name = 'AG: '.date('Y F j', strtotime('-9 days'));
	}
} else {
	write_log($log_file,date('Y m j H:i:s').': Error: Date conversion error\n');
	exit();
}





if ((sizeof($argv) == 2) && in_array($argv[1], array(1,3,6,7,9,12,14,15,18,21,24,27,28))) {
//move	

  if ($day == '1') {
  	if (create_snapshot($current_name)) {
		write_log($log_file, date('Y m j H:i:s').': Monthly '.$current_name.' snapshot is created');
		$del_name = $_month_del_name.' 01';
		
		if (!_find_and_delete($log_file,$snapshots, $del_name)) {
			write_log($log_file,date('Y m j H:i:s').': Monthly snapshot '.$del_name. ' has not been found neither deleted');
		};
		} else {
			write_log($log_file, date('Y m j H:i:s').": Error creating ".$current_name.' snapshot. Something went wrong.');
		}
  } else if ($day == '7' || $day == '14' || $day == '21' || $day == '28') {
  	if (create_snapshot($current_name)) {
			write_log($log_file, date('Y m j H:i:s').': Weekly '.$current_name.' snapshot is created');
			$del_name = $_week_del_name;
			if (!_find_and_delete($log_file,$snapshots, $del_name)) {
				write_log($log_file,date('Y m j H:i:s').': Weekly snapshot '.$del_name. ' has not been found neither deleted');
			}
		} else if ($day == '30') {
			$_daily_del_name = 'AG: '.date('Y F j', strtotime('-12 days'));
		} else {
			write_log($log_file, date('Y m j H:i:s').": Error creating ".$current_name.' snapshot. Something went wrong.');
		}
  } else {
  	if (create_snapshot($current_name)) {
		write_log($log_file, date('Y m j H:i:s').': Daily '.$current_name.' snapshot is created');
		$del_name = $_daily_del_name;
				if (!_find_and_delete($log_file,$snapshots, $del_name)) {
				write_log($log_file,date('Y m j H:i:s').': Daily snapshot '.$del_name. ' has not been found neither deleted');
			};
		} else {
			write_log($log_file, date('Y m j H:i:s').": Error creating ".$current_name.' snapshot. Something went wrong.');
		}
  }


} else {
	
	write_log($log_file, date('Y m j H:i:s').': Error, Neither sizeof($argv) not in 2 range or argumnet '.$argv[1].' are out of range'); 
}
fclose($log_file);
//var_dump(get_list_snapshot());
?>
