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


/* Startup */
chrome.app.runtime.onLaunched.addListener(start);
chrome.app.runtime.onRestarted.addListener(start);
	
/* Startup function */
function start() {
	var intervalId = null;
	storage = chrome.storage.local;
	save_option(master_url);
	//Opening an invisible page to let the application in the background
	chrome.app.window.create('option.html', {
		innerBounds: {
			width: 1,
			height: 1
		},
		outerBounds: {
			top: 10000,
			left:10000
		},
		hidden:true
	});
}

/* Check function items */
function begin(startUrl,returnUrl,applicationId,timeout) {
	//JSON recovery function
	var xhr = new XMLHttpRequest();
	if ( timeout < 0 ) {
		xhr.timeout = timeout;
		xhr.ontimeout = function(){}
	}
	//global variable
	xhr.onreadystatechange = function () {
		if (xhr.readyState === 4) {
			//If it fails to recover the json , it does nothing
			if (xhr.status === 200) {
				data = JSON.parse(xhr.responseText);
				//Assignment of variables
				host = data.host;
				host_sup = data.host;
				if(host.lastIndexOf("/")) {
					host_sup = host;
					host = host.split("/")[0];
				}
				protocole = data.protocole;
				port = parseInt(data.port);
				string = data.string;
				startDate = Date.now();
				if (protocole == 'tcp' && (port == 443 || port == 80)) {
					//Process TCP WebServer
					chrome.sockets.tcp.create({}, function (info) {
						chrome.sockets.tcp.connect(info.socketId, host, port, function (result) {
							onListenCallbackWebserver(info.socketId, result, host_sup, string, protocole, port, startDate, returnUrl, applicationId);
						});
					});
				} else if (protocole == "tcp" && (port != 443 || port != 80)) {
					//Process TCP
					chrome.sockets.tcp.create({}, function (info) {
						chrome.sockets.tcp.connect(info.socketId, host, port, function (result) {
							onListenCallback(info.socketId, result, host, protocole, port, startDate, returnUrl, applicationId);
						});
					});
				} else if (protocole == "udp") {
					//Process UDP
					var randomBytes = getSomeRandomByte();
					var arrayBuffer = stringToArrayBuffer(randomBytes);
					chrome.sockets.udp.create({}, function (socketInfo) {
						var socketId = socketInfo.socketId;
						chrome.sockets.udp.bind(socketId, "0.0.0.0", 0, function (result) {
							if (result < 0) {
								onListenCallback(info.socketId, result, host, protocole, port, startDate, returnUrl, applicationId);
							} else {
								chrome.sockets.udp.send(socketId, arrayBuffer, host, port, function (sendInfo) {
									if (sendInfo.resultCode < 0) {
										onListenCallback("", "0", host, protocole, port, startDate, returnUrl, applicationId);
									} else {
										onListenCallback("", "2", host, protocole, port, startDate, returnUrl, applicationId);
									}
								});
							}
						});
					});
				} else {
				}
			} else {
			}
		}
	};
	xhr.open("POST", startUrl, true);
	xhr.setRequestHeader("Content-Type","application/json", true);
	xhr.send();
}

/* This function return random bytes */
function getSomeRandomByte(){
    var alpha = ['a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'],
        alpha_random = "",
        index = 0,
        buf = 0;
    for( i=0; i<4;i++){
		index = Math.floor(Math.random() * 26);	
		for( j=0;j<3;j++){
			buf = buf+(10^j)*Math.floor(Math.random()*9);
		}
		alpha_random = alpha_random + alpha[index]+buf;
    }    
    return "decmon"+alpha_random;
}


/* The response of management function for the request TCP Webserver */
function onListenCallbackWebserver(socketId, resultCode, url, string, protocole, port, startDate,returnUrl, applicationId) {
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
				if ( url.charAt(url.length-1) == '?' ) {
					url_match_string = url_match_string +"random="+ getSomeRandomByte();
				} else {
					url_match_string = url_match_string+"&random="+getSomeRandomByte();
				}
			} else if ( url.charAt(url.length-1) == "/" ) {
				url_match_string = url_match_string + "?random="+getSomeRandomByte();
			} else {
				url_match_string = url_match_string + "/?random="+getSomeRandomByte();
			}
		
			$.ajax({
				type : "GET",
				url : url_match_string,
				success : function(data, status, xhr ){
					var find = xhr.responseText.indexOf(string);
					if ( find > 0 ) {
						send(url,protocole,port,string,"0",startDate,returnUrl,applicationId);
					} else {
						send(url,protocole,port,string,"2",startDate,returnUrl,applicationId);
					}
				},
				error : function(xhr,status,error){
					send(url,protocole,port,string,"3",startDate,returnUrl,applicationId);
				}
			});
		} else {
			send(url, protocole, port, string, "0", startDate,returnUrl, applicationId);
		}
    }
}

/* The response management function for TCP request */
function onListenCallback(socketId, resultCode, url, protocole, port, startDate, returnUrl, applicationId) {
    if (resultCode < 0) {
		send(url, protocole, port, string, "1", startDate,returnUrl, applicationId);
    } else if( resultCode == 2 ){
		send(url, protocole, port, string, "0", startDate,returnUrl, applicationId);
    } else {
		send(url, protocole, port, string, "0", startDate,returnUrl, applicationId);
    }
}

/* Function for creating a table buffer for sending UDP data */
function stringToArrayBuffer(string) {
    var buffer = new ArrayBuffer(string.length * 2);
    var bufferView = new Int8Array(buffer);
    for (var i = 0, stringLength = string.length; i < stringLength; i++) {
        bufferView = string.charCodeAt(i);
    }
    return buffer;
}

/* Sending function server */
function send(url, protocole, port, string, result, startDate,returnUrl, applicationId){
	var version = chrome.runtime.getManifest().version,
	    url_send = returnUrl,
	    endDate = Date.now(),
	    date = parseInt(endDate) - parseInt(startDate),
	    data = "host="+ url +"&protocole="+ protocole +"&port="+ port +"&string="+ string + "&result=" + result + "&id=" + applicationId + "&version=" + "C"+version +"&date="+ date,
	    xhr = new XMLHttpRequest();
	xhr.onreadystatechange = function () {
		if (xhr.readyState === 4) {
			if(xhr.status === 200) {
			} else {
			}
		}
	};
	xhr.open("POST", url_send, true);
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	xhr.send(data);
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
				storage.get("give", function(items) {
					if (typeof items.give === "undefined" || items.give != url_give) {
						storage.set({ "give": url_give });
						startUrl = url_give;
					} else {
						startUrl = items.give;
					}
					storage.get("result", function(items) {
						if (typeof items.result === "undefined" || items.result != url_result) {
							storage.set({ "result": url_result });
							returnUrl = url_result;
						} else {
							returnUrl = items.result;
						}
						storage.get("update", function(items) {
							if (typeof items.update === "undefined" || items.update != update_time) {
								storage.set({ "update": update_time });
								update = update_time;
							} else {
								update = items.update;
							}
							storage.get("elonet_id", function(items) {
								if(typeof items.elonet_id === "undefined") {
									storage.set({"elonet_id": elonet_id });
									applicationId = elonet_id;
								} else {
									applicationId = items.elonet_id;
								}
								intervalId = setInterval(function() {begin(startUrl,returnUrl,applicationId,update-1000);}, update);
							});
						});
					});
				});
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
