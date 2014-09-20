var DSC		= -1;
var NONE	= 0;
var MSG		= 1;
var GRP		= 2;
var CLN		= 3;

var STATE = NONE;

function setBadge() {
	var text = "";
	switch (STATE) {
		case DSC:
			text = "???"; break;
		case MSG:
			text = "MSG"; break;
		case GRP:
			text = "GRP"; break;
		case CLN:
			text = "CLN"; break;
		default:
			text = ""; break;
	}
	chrome.browserAction.setBadgeText({text:text});
}

function check() {
	chrome.browserAction.setBadgeText({text:"..."});
	var xhr = new XMLHttpRequest();
	xhr.onreadystatechange = function() {
		if (xhr.readyState == 4) {
			var html = xhr.responseText;
			if (html.search("Connexion") != -1)
				STATE = DSC;
			else if (html.search("/wod/css//skins/skin-8/images/icons/mail_new") != -1)
				STATE = MSG;
			else if (html.search("/wod/css//skins/skin-8/images/icons/forum_group_new") != -1)
				STATE = GRP;
			else if (html.search("/wod/css//skins/skin-8/images/icons/forum_clan_new") != -1)
				STATE = CLN;
			else
				STATE = NONE;
			setBadge();
		}
	}
	xhr.open("GET", 'http://ezantoh.world-of-dungeons.fr/', true);
	xhr.send();
}

chrome.alarms.create("check", {
	when: Date.now() + 1000,
	periodInMinutes: 1
});

chrome.alarms.onAlarm.addListener(function(alarm){
	check();
});

chrome.browserAction.onClicked.addListener(function(tab) {
	var url;
	switch (STATE) {
		case MSG:
			url = "http://ezantoh.world-of-dungeons.fr/wod/spiel/pm/pm.php"; break;
		case GRP:
			url = "http://ezantoh.world-of-dungeons.fr/wod/spiel/forum/?board=gruppe"; break;
		case CLN:
			url = "http://ezantoh.world-of-dungeons.fr/wod/spiel/forum/?board=clan"; break;
		default:
			url = "http://ezantoh.world-of-dungeons.fr/"; break;
	}
	chrome.tabs.create({'url': url}, function(tab) {});
});