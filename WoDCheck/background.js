var DSC		= parseInt('0001', 2);
var MSG		= parseInt('0010', 2);
var GRP		= parseInt('0100', 2);
var CLN		= parseInt('1000', 2);

var STATE = 0;
var STATE_OLD = 0;

var MSG_NB = 0;

var URL = "http://ezantoh.world-of-dungeons.fr/";

function notify() {
	var text = "";
	var list = [];
	if (STATE & DSC)
	{
		if (text == "") text = "???";
		list.push({title:"*", message:"Vous n'êtes pas connecté."});
	}
	if (STATE & MSG)
	{
		if (text == "") text = ""+MSG_NB;
		list.push({title:"*", message:"Vous avez "+MSG_NB+" nouveaux messages."});
	}
	if (STATE & GRP)
	{
		if (text == "") text = "GRP";
		list.push({title:"*", message:"Nouveaux messages de groupe."});
	}
	if (STATE & CLN)
	{
		if (text == "") text = "CLN";
		list.push({title:"*", message:"Nouveaus messages de clan."});
	}
		
	chrome.browserAction.setBadgeText({text:text});
	chrome.notifications.onClicked.addListener(function (notificationId) {
		chrome.tabs.create({'url': URL}, function(tab) {});
	});
	if (STATE != STATE_OLD)
	{
		chrome.notifications.create("42", {type:"list", iconUrl:"WOD.png", title:"Notifications", message:"", items:list}, function(notificationId) {});
		chrome.notifications.clear("42", function (wasCleared) {});
		STATE_OLD = STATE;
	}
}
function checkMessages() {
	MSG_NB = 0;
	var xhr = new XMLHttpRequest();
	xhr.onreadystatechange = function() {
		if (xhr.readyState == 4) {
			var html = xhr.responseText;
			var matches = html.match(/\(\d\)" class="folder/ig);
			for (i in matches)
				MSG_NB += parseInt(matches[i].substr(1));
		}
	}
	xhr.open("GET", 'http://ezantoh.world-of-dungeons.fr/wod/spiel/pm/pm.php', false);
	xhr.send();
}

function checkStates() {
	STATE = 0;
	var xhr = new XMLHttpRequest();
	xhr.onreadystatechange = function() {
		if (xhr.readyState == 4) {
			var html = xhr.responseText;
			if (html.search("Connexion") != -1)
				STATE |= DSC;
			if (html.search("/wod/css//skins/skin-8/images/icons/mail_new") != -1)
				STATE |= MSG;
			if (html.search("/wod/css//skins/skin-8/images/icons/forum_group_new") != -1)
				STATE |= GRP;
			if (html.search("/wod/css//skins/skin-8/images/icons/forum_clan_new") != -1)
				STATE |= CLN;
		}
	}
	xhr.open("GET", 'http://ezantoh.world-of-dungeons.fr/', false);
	xhr.send();
}

function setUrl()
{
	URL = "http://ezantoh.world-of-dungeons.fr/";
	if (STATE & MSG)
		URL = "http://ezantoh.world-of-dungeons.fr/wod/spiel/pm/pm.php";
	else if (STATE & GRP)
		URL = "http://ezantoh.world-of-dungeons.fr/wod/spiel/forum/?board=gruppe";
	else if (STATE & CLN)
		URL = "http://ezantoh.world-of-dungeons.fr/wod/spiel/forum/?board=clan";
}

function check() {
	chrome.browserAction.setBadgeText({text:"..."});
	checkStates();
	checkMessages();
	setUrl();
	notify();
}

chrome.alarms.create("check", {
	when: Date.now() + 1000,
	periodInMinutes: 1
});

chrome.alarms.onAlarm.addListener(function(alarm){
	check();
});

chrome.browserAction.onClicked.addListener(function(tab) {
	chrome.tabs.create({'url': URL}, function(tab) {});
});