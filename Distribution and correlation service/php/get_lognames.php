<?php
/*
 * Decentralized Monitoring source code
 * https://github.com/Elonet/Decentralized-Monitoring
 *
 * Copyright 2015, Leo Leroy
 * https://elonet.fr/
 *
 * Licensed under the GPLv3 license:
 * http://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
 
include('/etc/decentralized_monitoring/config.conf');
if($conf['active_logstalgia'] == 0) {
	exit;
}
//should received 2 timestamp and retreive the good logfile
$start = trim(htmlspecialchars($_GET['start']));
$stop = trim(htmlspecialchars($_GET['stop']));
$deb = trim(htmlspecialchars($_GET['deb']));
    
if( isset($start) && isset($stop) ){
    $files = scandir($conf['log_backup_directory']);
    if( count($files) > 0 ){        
        $trouver_start = false;
        $trouver_stop = false;
        $valid_files = array();
        
        foreach( $files as $file ){
            if( preg_match('/logstalgia-[0-9]{10}-[0-9]{10}.txt/',$file) ){
                $valid_files[] = $file;
            }        
        }
        if( count($valid_files) > 0 ){
            $i=0;
            $file_name = explode('.',$valid_files[0]);
            $file_name = $file_name[0];
            
            $filename_start = explode('-',$file_name);
            $filename_start = $filename_start[1];
            $filename_stop = explode('-',$file_name);
            $filename_stop = $filename_stop[2];
            
            
            $file_indexes = array();
            while( $i < count($valid_files) && ( !$trouver_stop || !$trouver_start ) ){                
                $file_name = explode('.',$valid_files[$i]);
				$file_name = $file_name[0];	
                $filename_start = explode('-',$file_name);
                if( isset( $filename_start[1]) ){
					$filename_start = $filename_start[1];
                }
				$filename_stop = explode('-',$file_name);
                if ( isset( $filename_stop[2] ) ){
					$filename_stop = $filename_stop[2];
				}               
                //if start and stop are included in the logs of the current file
                if( $filename_start <= $start && $filename_stop >= $stop ){
                    $trouver_start = true;
                    $trouver_stop = true;
                    $file_indexes[] = $valid_files[$i];
                }
                
                //if start is in the current file and not an stop
                if( $filename_start <= $start && !$trouver_start && $filename_stop >= $start && $filename_stop < $stop){
                    $trouver_start = true;
                    $file_indexes[] = $valid_files[$i];
                }
                
                if( $filename_start > $start && ( $filename_start < $stop && $filename_stop > $stop ) && $trouver_start ){
                    $trouver_stop = true;
                    $file_indexes[] = $valid_files[$i];
                }
                
                if( $trouver_start && $start < $filename_start && !$trouver_stop && $stop > $filename_stop ){
                    $file_indexes[] = $valid_files[$i];
                }
                
                $i++;
            
            }           
            
            if( !$trouver_stop ){
                $filename = "logstalgia.txt";
            } else {
                $usefull_logs = "";
                foreach( $file_indexes as $file ){
                    $usefull_logs .= file_get_contents($conf['log_backup_directory'].$file);
                }                
                
                if( !file_exists($conf['root_directory']."tmp/"."logstalgia_compil.txt") ){
                    $filename ="logstalgia_compil.txt";
                } else {
                    $filename ="logstalgia_compil_".time().".txt";
                }                
                file_put_contents($conf['root_directory']."tmp/".$filename,$usefull_logs);
            }
        } else {
            $filename = "logstalgia.txt";
        }
    } else {
        $filename = "logstalgia.txt";
    }
    
    echo $filename;
} else if( isset($deb) ) {
    unlink($conf['root_directory']."tmp/"."logstalgia_compil_".$deb.".txt");
}
?>
