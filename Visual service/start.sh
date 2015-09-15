ll=`which logstalgia`
if [ "${ll}" != "" ];then
	tail -f /var/www/decmon/tmp/logstalgia.txt | logstalgia &
else
	echo "Please install logstalgia."
fi
sleep 3
posX=`wmctrl -d | cut -d ' ' -f11 | cut -d ',' -f1`

posY=`wmctrl -d | cut -d ' ' -f11 | cut -d ',' -f2`

width=`wmctrl -d | cut -d ' ' -f12 | cut -d 'x' -f1`

height=`wmctrl -d | cut -d ' ' -f12 | cut -d 'x' -f2`

widthLog=`echo "(${width}/100)*40" | bc`

widthCh=`echo ${width}-${widthLog}-15 | bc`

posCh=`echo ${posX}+${widthLog}+13 | bc`


wmctrl -r "Logstalgia" -e 0,${posX},${posY},${widthLog},${height}
ch=`which chromium-browser`
if [ "${ch}" != "" ];then
	chromium-browser --app=http://192.168.5.112/decmon/ &
else
	echo "Please install chromium."
fi
sleep 2
wmctrl -r "Decentralized Monitoring" -e 0,${posCh},${posY},${widthCh},${height}
