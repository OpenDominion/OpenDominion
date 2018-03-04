// Wayback Machine hacks
var __wbhack = new function(){
  var prefix;
  var orig_document = {
    createElementNS: document.createElementNS
  };
  this.init = function(_prefix) {
    this.checkCookiesNumber();
    prefix = _prefix;
    document.createElementNS = function(ns,name) {
      if (ns.indexOf(prefix)==0) {
	ns = ns.substring(prefix.length).replace(/\/?[0-9]+\//, '');
      }
      return orig_document.createElementNS.call(this, ns, name);
    };
  };
  this.createCookie = function(name,value,days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime()+(days*24*60*60*1000));
        var expires = "; expires="+date.toGMTString();
    }
    else var expires = "";
    document.cookie = name+"="+value+expires+"; path=/";
  };
  this.eraseCookie = function(name) {
    this.createCookie(name, "", -1);
  };
  this.checkCookiesNumber = function() {
    var cookies = document.cookie.split(";");
    if(cookies.length > 40) {
        for(var i=0; i<cookies.length; i++) {
            var name = cookies[i].split("=")[0].trim();
            this.eraseCookie(name);
        }
    }
  };
  // save JSON object for our use - some target pages redefine JSON with their
  // own version that may not be compatible with (now) standard JSON.
  this.JSON = JSON;
};
