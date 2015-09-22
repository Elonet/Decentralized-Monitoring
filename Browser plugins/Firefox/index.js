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
/* You must change this url, with your get-conf.php url */
var master_url = "http://your-server.com/php/get-conf.php";

/*
 * TO FIX Changing compiler cfx jpm leading to errors in code
 */
var self = require("sdk/self"),
	data = self.data,
	buttons = require('sdk/ui/button/action'),
	tabs = require("sdk/tabs"),
	ss = require("sdk/simple-storage"),
	tmr = require('sdk/timers');

let { setTimeout } = require('sdk/timers');
const {components, Cc, Ci} = require("chrome");
components.utils.import("resource://gre/modules/XPCOMUtils.jsm");
components.utils.import("resource://gre/modules/Services.jsm");
const {XMLHttpRequest} = require("sdk/net/xhr");
var windows = require("sdk/windows").browserWindows;


var started = false;
windows.open({
    url : "",
    onOpen : function(windows){	
		windows.close();	    
    }
});

windows.on("open",function(){
    if ( !started ) {
		start();
    }
});

/* Startup */
function start() {
    started = true;
    var intervalId = null;
    storage = ss.storage;

    save_option(master_url);
    
}

/* Backup and restore configurations function */
function save_option(master_url) {
	//variable backup
	var url_give,
	    url_result,
	    update_time,
	    elonet_id = randomString(32, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
    //variable restores
	var returnUrl,
	    startUrl,
	    update,
	    applicationId,
	    xhr = new XMLHttpRequest();
	//It retrieves data to back via the storage
	xhr.onreadystatechange = function () {
		if (xhr.readyState === 4) {
			if (xhr.status === 200) {
                //we parse the json to get the data
				data = JSON.parse(xhr.responseText);
				url_give = data.give.replace("/\\/g","");
				url_result = data.result.replace("/\\/g","");
				update_time = parseInt(data.update);                        
				
				//it then checks the storage or the backup is given in storage, either recovering the data in the storage
				if (storage.give === undefined ) {
					storage.give = url_give;
					startUrl = url_give;
				} else {
					startUrl = storage.give;
				}				
				if ( storage.result === undefined ) {
					storage.result = url_result;
					returnUrl = url_result;
				} else {
					returnUrl = storage.result;
				}				
				if ( storage.update  === undefined ) {
					storage.update = update_time;
					update = update_time;
				} else {
					update = update_time;
				}                                
				if ( storage.applicationId_id === undefined ) {
					storage.applicationId_id = applicationId_id;
					applicationId = applicationId_id;
				} else {
					applicationId = storage.applicationId_id;					
				}
				intervalId = tmr.setInterval(function() {
				begin(startUrl,returnUrl,applicationId,update-1000);}, update);
			}
		}
	};        
	//URL configurations recovery
	xhr.open("POST", master_url,true);
	xhr.setRequestHeader("Content-Type","application/json", true);
	xhr.send();
}

/* Provides an id for the extension */
function randomString(length, chars) {
    var result = '';
    for (var i = length; i > 0; --i) result += chars[Math.round(Math.random() * (chars.length - 1))];
    return result;
}

/* Check function items */
function begin(startUrl,returnUrl,applicationId,timeout) {
	//JSON recovery function
	var xhr = new XMLHttpRequest();
	if ( timeout < 0 ) {
		xhr.timeout = timeout;
		xhr.ontimeout = function(){}
	}
	var isConnect = false;
	//global variable
	xhr.onreadystatechange = function () {
		if (xhr.readyState === 4) {
			//If it fails to recover the json , it does nothing
			if (xhr.status === 200) {
				var time = (new Date()).toTimeString(); 
				data = JSON.parse(xhr.responseText);				
				//Assignment of variables
				host = data.host;
				host_sup = data.host;
				if(host.lastIndexOf("/")) {
					host_sup = host;
					host = host.split("/")[0];
				}
				protocole = data.protocole.toLowerCase();
				port = parseInt(data.port);
				string = data.string;
				startDate = Date.now();                      
				if ( protocole.toLowerCase() == 'tcp' && (port == 443 || port == 80)) {
					//Process TCP WebServer				
				    var transportService = components.classes["@mozilla.org/network/socket-transport-service;1"].getService(components.interfaces.nsISocketTransportService);
                    if ( port == 443 ) {
                        var socket = transportService.createTransport(["ssl"],1,host, port, null);
                    } else {
						var socket = transportService.createTransport(null,0,host, port, null);
					}				
					socket.setTimeout(0,2);			
					socket.setEventSink({
						onTransportStatus: function(transport, status, progress, progressMax) {						
							if ( status == 2152398852 ) {
							  isConnect = true;
							  onListenCallbackWebserver(1, host,string, protocole, port, startDate, returnUrl, applicationId);	
							}						
						}
					}, components.classes["@mozilla.org/thread-manager;1"].getService(components.interfaces.nsIThreadManager).mainThread);
					
					var outputData = getSomeRandomByte(),
						outstream = socket.openOutputStream(0,0,0),
						len = outputData.length;
					outstream.write(outputData,len);
					outstream.flush();

					var instream = socket.openInputStream(0,0,0);
					isConnect = false;
				    var dataListener = {
						data : "",
						onStartRequest: function(request, context){},
                        onStopRequest: function(request, context, status){
							if ( !isConnect ) {
								onListenCallbackWebserver(-1, host,string, protocole, port, startDate, returnUrl, applicationId);
							}
							instream.close();
                        },				    
                    };                             
                    var pump = components.classes["@mozilla.org/network/input-stream-pump;1"].createInstance(components.interfaces.nsIInputStreamPump);
                    pump.init(instream, -1, -1, 0, 0, true);
                    pump.asyncRead(dataListener,null);                               
				} else if (protocole.toLowerCase() == "tcp" && (port != 443 || port != 80)) {
					//Process TCP
                    var transportService = components.classes["@mozilla.org/network/socket-transport-service;1"].getService(components.interfaces.nsISocketTransportService);
                    var socket = transportService.createTransport(null,0,host, port, null);
					socket.setTimeout(0,2);				
					socket.setEventSink({
						onTransportStatus: function(transport, status, progress, progressMax) {				    
							if ( status == 2152398852 ) {
								isConnect = true;
								onListenCallback(1, host, string,protocole, port, startDate, returnUrl, applicationId);	
							}			    
						}
					}, components.classes["@mozilla.org/thread-manager;1"].getService(components.interfaces.nsIThreadManager).mainThread);
					
                    var outputData = getSomeRandomByte(),
						outstream = socket.openOutputStream(0,0,0),
						len = outputData.length;
                    outstream.write(outputData,len);
                    outstream.flush();
                                
                    var instream = socket.openInputStream(0,0,0);                              
                    var dataListener = {
                        data : "",
                        onStartRequest: function(request, context){},
                        onStopRequest: function(request, context, status){
							if ( !isConnect ) {
							  onListenCallback(-1, host, string,protocole, port, startDate, returnUrl, applicationId);
							}					
							instream.close();             
                        }
                    };
                    var pump = components.classes["@mozilla.org/network/input-stream-pump;1"].createInstance(components.interfaces.nsIInputStreamPump);
                    pump.init(instream, -1, -1, 0, 0, true);
                    pump.asyncRead(dataListener,null);                        
				} else if (protocole.toLowerCase() == "udp") {
					//Process UDP
                    var transportService = components.classes["@mozilla.org/network/socket-transport-service;1"].getService(components.interfaces.nsISocketTransportService);
                    var socket = transportService.createTransport(["udp"],1,host, port, null);
					socket.setTimeout(0,2);
					socket.setEventSink({
						onTransportStatus: function(transport, status, progress, progressMax) {
							if ( status == 2152398852 ) {
								isConnect = true;
								onListenCallback(2, host, string,protocole, port, startDate, returnUrl, applicationId);	
							}			    
						}
					}, components.classes["@mozilla.org/thread-manager;1"].getService(components.interfaces.nsIThreadManager).mainThread);
                    var outputData = getSomeRandomByte(),
						outstream = socket.openOutputStream(0,0,0),
						len = outputData.length;
                    outstream.write(outputData,len);
                    outstream.flush();
                                
                    var instream = socket.openInputStream(0,0,0);
				    var dataListener = {
                        data : "",
                        onStartRequest: function(request, context){},
                        onStopRequest: function(request, context, status){
							if ( !isConnect ) {
							  onListenCallback(-1, host,string, protocole, port, startDate, returnUrl, applicationId);
							}					
							instream.close();
                        }
                    };
                    var pump = components.classes["@mozilla.org/network/input-stream-pump;1"].createInstance(components.interfaces.nsIInputStreamPump);
                    pump.init(instream, -1, -1, 0, 0, true);
                    pump.asyncRead(dataListener,null);
				}
			} else {}
		}
	};
	xhr.open("POST", startUrl, true);
	xhr.setRequestHeader("Content-Type","application/json", true);
	xhr.send();
}

/* This function return random bytes */
function getSomeRandomByte(){
    var alpha = ['a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z']
    var alpha_random = "";
    var index = 0;
    var buf = 0;
    for( i=0; i<4;i++){
	index = Math.floor(Math.random() * 26);
	
	for( j=0;j<3;j++){
	    buf = buf+(10^j)*Math.floor(Math.random()*9);
	}
	
	alpha_random = alpha_random + alpha[index]+buf;
    }
    
    return "decmon"+alpha_random;
}

/* Sending function server */
function send(url, protocole, port, string, result, startDate,returnUrl, applicationId){
        var version = self.version;
        var url_send = returnUrl;
        var endDate = Date.now();
        var date = parseInt(endDate) - parseInt(startDate);
        var data = "host="+ url +"&protocole="+ protocole +"&port="+ port +"&string="+ string + "&result=" + result + "&id=" + applicationId + "&version=" + "F"+version +"&date="+ date;
        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                        if(xhr.status === 200) {
                        } else {}
                }
        };
        xhr.open("POST", url_send, true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.send(data);
}

/* The response of management function for the request TCP Webserver */
function onListenCallbackWebserver( resultCode, url, string, protocole, port, startDate,returnUrl, applicationId) {
    if (resultCode < 0) {
        send(url, protocole, port, string, "1", startDate,returnUrl, applicationId);
        return;
    } else {
        if (string != '') {
            var url_match_string = "";
			if ( port == 443 ) {
				url_match_string = "https://"+url;
			} else {
				url_match_string = "http://"+url;
			}
            if ( url.indexOf('/?') != -1 ) {
				if ( url.charAt(url.length-1) == '?' ){
                    url_match_string = url_match_string +"random="+ getSomeRandomByte();
                } else {
                    url_match_string = url_match_string+"&random="+getSomeRandomByte();
                }
            } else if ( url.charAt(url.length-1) == "/" ) {
                url_match_string = url_match_string + "?random="+getSomeRandomByte();
            } else {
                url_match_string = url_match_string + "/?random="+getSomeRandomByte();
            }
			var xhr = new XMLHttpRequest();
			xhr.onreadystatechange = function (){
			    if (xhr.readyState === 4 && xhr.status === 200) {
					var find = xhr.responseText.indexOf(string);
					if ( find > 0 ) {
						send(url,protocole,port,string,"0",startDate,returnUrl,applicationId);
					} else {
						send(url,protocole,port,string,"2",startDate,returnUrl,applicationId);
					}
			    }
			};
			xhr.onerror =  function(){
			    send(url,protocole,port,string,"3",startDate,returnUrl,applicationId);
			};
			
			xhr.open("GET",url_match_string);
			xhr.send(null);
        } else {
            send(url, protocole, port, string, "0", startDate,returnUrl, applicationId);
        }
	}
}

/* The response management function for TCP request */
function onListenCallback( resultCode, url, string,protocole, port, startDate, returnUrl, applicationId) {
    if (resultCode < 0) {
        send(url, protocole, port, string, "1", startDate,returnUrl, applicationId);
        return 0;
    } else if( resultCode == 2 ) {
        send(url, protocole, port, string, "0", startDate,returnUrl, applicationId);
	return 1;
    } else {
        send(url, protocole, port, string, "0", startDate,returnUrl, applicationId);
	return 1;
    }
}