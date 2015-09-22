#!/bin/sh

export DISPLAY=:0.0


if [ -e /var/www/LogVideoServer/request.lock ]
then
    
        #selecting from date
	from=`cut -d";" -f 1 /var/www/LogVideoServer/request.lock`
	#remove first quote
	from="${from%\'}"
	#remove last quote
	from="${from#\'}"
	
	#selecting to date
	to=`cut -d";" -f 2 /var/www/LogVideoServer/request.lock`
	#remove first quote
	to="${to%\'}"
	#remove last quote
	to="${to#\'}"
	
	#from="2014-11-13 00:42:40"
	#to="2014-11-13 00:43:40"
	
	
	#width in px ( 1280 or 800 )
	width=`cut -d";" -f 4 /var/www/LogVideoServer/request.lock`
	#remove first quote
	width="${width%\'}"
	#remove last quote
	width="${width#\'}"
	#width=800
	#heigth in px ( 720 or 480 )
	height=`cut -d";" -f 3 /var/www/LogVideoServer/request.lock`
	#remove first quote
	height="${height%\'}"
	#remove last quote
	height="${height#\'}"
	#height=480
	#vitesse comprise entre 0.1 et 30
	speed=`cut -d";" -f 5 /var/www/LogVideoServer/request.lock`
	#remove first quote
	speed="${speed%\'}"
	#remove last quote
	speed="${speed#\'}"
	#speed=1
	#speed=20
       
        echo "${speed}" > /var/www/LogVideoServer/videos/video.log
        root_log_serv=`cut -d";" -f 6 /var/www/LogVideoServer/request.lock`
        
        loglogstalgia_path=`cut -d";" -f 7 /var/www/LogVideoServer/request.lock`
        
	rm /var/www/LogVideoServer/request.lock
        
        echo "Debut test;" >> /var/www/LogVideoServer/videos/video.log
	#logstalgia script that logs on error
	wget  -O "${root_log_serv}LogVideoServer/cron/logstalgia.txt" --user=ninja --password=A1Z2E3R4T5 "${loglogstalgia_path}"
	echo "Dl log ok;" >> /var/www/LogVideoServer/videos/video.log
	
	export LD_LIBRARY_PATH=/usr/lib/
	
	
	#logstalgia calculating the video based on the beginning and the end , the size (width and heigth ) and speed required
	strace -o /var/www/LogVideoServer/videos/logstalgia.error /usr/local/bin/logstalgia  --from "${from}" --to "${to}" -"${width}"x"${height}" -s "${speed}" --output-ppm-stream "${root_log_serv}"LogVideoServer/cron/logstalgia.ppm "${root_log_serv}"LogVideoServer/cron/logstalgia.txt 2>&1 >> ${root_log_serv}LogVideoServer/videos/video.log
	if [ -e /var/www/LogVideoServer/cron/logstalgia.ppm ];then
		echo "Command logstalgia ok" >> /var/www/LogVideoServer/videos/video.log
	fi
	#in ppm converted video format to mp4
	
        ff=`which ffmpeg`
        if [ "${ff}" != "" ];then
            "${ff}" -y -r 60 -f image2pipe -vcodec ppm -i "${root_log_serv}LogVideoServer/cron/logstalgia.ppm" -vcodec libx264 -preset ultrafast -pix_fmt yuv420p -crf 1 -threads 0 -bf 0 "${root_log_serv}LogVideoServer/cron/logstalgia.mp4" >> /var/www/LogVideoServer/videos/video.log 2>&1
	else
            avconv -y -r 60 -f image2pipe -vcodec ppm -i "${root_log_serv}LogVideoServer/cron/logstalgia.ppm" -vcodec libx264 -preset ultrafast -pix_fmt yuv420p -crf 1 -threads 0 -bf 0 "${root_log_serv}LogVideoServer/cron/logstalgia.mp4" >> /var/www/LogVideoServer/videos/video.log
        fi
        echo "Conversion ok" >> /var/www/LogVideoServer/videos/video.log
	
	if [ -f /var/www/LogVideoServer/cron/logstalgia.mp4 ]
	then
		echo "Fichier mp4 ok" >> /var/www/LogVideoServer/videos/video.log
	else
		echo "Fichier mp4 ko" >> /var/www/LogVideoServer/videos/video.log
	fi
	
	
	#echo `date -d "${from}" +%s` >> /var/www/LogVideoServer/videos/video.log
	tmsp_debut=`date -d "${from}" +%s`
	#the final video is moved to /var/www/LogVideoServer/videos/
	echo ${tmps_debut} >> /var/www/LogVideoServer/videos/video.log
	mv ${root_log_serv}LogVideoServer/cron/logstalgia.mp4 ${root_log_serv}LogVideoServer/videos/logstalgia${tmsp_debut}.mp4 >> /var/www/LogVideoServer/videos/video.log
	
	if [ -e /var/www/LogVideoServer/videos/logstalgia${tmsp_debut}.mp4 ]
	then
		echo "Moving logstalgia.mp4 en logstalgia${tmsp_debut}.mp4" >> /var/www/LogVideoServer/videos/video.log
	else
		echo "Bad Moving" >> /var/www/LogVideoServer/videos/video.log
	fi
	
	#deleting temporary video file ppm
	rm ${root_log_serv}LogVideoServer/cron/logstalgia.ppm
	rm ${root_log_serv}LogVideoServer/cron/logstalgia.txt
	echo "Suppression des fichiers ok" >> /var/www/LogVideoServer/videos/video.log
fi
