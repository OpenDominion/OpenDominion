if(typeof __wm==="undefined") __wm={};
(function(){
  var _JSON = typeof __wbhack != 'undefined' ?  __wbhack.JSON : JSON;
  var prettyMonths = [
    "Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
  var $D=document,$=function(n){return document.getElementById(n)};

  function formatNumber(n) {
    return (''+n).replace(/\B(?=(\d{3})+$)/g, ',');
  }
  var ajax=__wm.ajax=function ajax(method, url, callback, headers, data) {
    var xmlhttp;
    if (window.XMLHttpRequest) {
      xmlhttp = new XMLHttpRequest();
    } else {
      xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function() {
      if (this.readyState == 4) {
	callback(xmlhttp);
      }
    };
    xmlhttp.open(method, url, true);
    if (headers) {
      for (var header in headers) {
        if (headers.hasOwnProperty(header)) {
          xmlhttp.setRequestHeader(header, headers[header]);
        }
      }
    }
    // pass cookies for user authorization
    xmlhttp.withCredentials = true;
    xmlhttp.send(data);
  }

  __wm.h=function hideToolbar(ev) {
    $("wm-ipp").style.display="none";
    ev.stopPropagation();
  }

  var $expand, $capinfo;

  __wm.bt=function bootstrap(imgWidth,imgHeight,yearImgWidth,monthImgWidth,
			     coll,wbCurrentUrl,captureDate,firstYear) {
    var wbPrefix='/'+(coll||'web')+'/';
    captureDate = captureDate.split('-');
    var displayDay = captureDate[2];
    var displayMonth = captureDate[1];
    var displayYear = captureDate[0];
    var trackerVal,curYear = -1,curMonth = -1;
    var yearTracker,monthTracker;

    var $spk=$('wm-ipp-sparkline')
    $expand=$('wm-expand');
    $capinfo=$('wm-capinfo');

    function showTrackers(event) {
      var val = event.type=="mouseenter"?1:0;
      if (val===trackerVal) return;
      var $ipp=$("wm-ipp");
      var $y=$("displayYearEl"),$m=$("displayMonthEl"),$d=$("displayDayEl");
      if (val) {
	$ipp.className="hi";
      } else {
	$ipp.className="";
	$y.innerHTML=displayYear;$m.innerHTML=prettyMonths[displayMonth-1];$d.innerHTML=displayDay;
      }
      yearTracker.style.display=val?"inline":"none";
      monthTracker.style.display=val?"inline":"none";
      trackerVal = val;
    }
    function getElementX2(el) {
      var de = $D.documentElement;
      var box = (typeof el.getBoundingClientRect!=='undefied')?
	el.getBoundingClientRect():{top:0,left:0};
      return box.left + (window.pageXOffset||de.scrollLeft)-(de.clientLeft||0);
    }
    function navCaptures(captures) {
      var $e = $("wm-nav-captures");
      var count = 0;
      var years = captures.years;
      var first_ts = captures.first_ts, last_ts = captures.last_ts;
      for (var j = 0; j < years.length; j++) {
	var months = years[j][1];
	for (var i = 0; i < months.length; i++) {
	  count += months[i];
	}
      }
      var html = '<a class="t" href="' + wbPrefix + '*/' + wbCurrentUrl +
	'" title="See a list of every capture for this URL">' +
	formatNumber(count) + ' ' +
	(count > 1 ? "captures" : "capture") + '</a>';
      var timespan = __wbTs.format(first_ts, '%d %b %Y');
      if (last_ts != first_ts) {
	timespan += ' - ' + __wbTs.format(last_ts, '%d %b %Y');
      }
      html += '<div class="r" title="Timespan for captures of this URL">' +
	timespan + '</div>';
      $e.innerHTML = html;
    }
    function trackMouseMove(event) {
      //var element = event.target;
      var element = $spk;
      var eventX = getEventX(event);
      var elementX = getElementX2(element);
      var xOff = Math.min(Math.max(0, eventX - elementX),imgWidth);
      var monthOff = xOff % yearImgWidth;

      var year = Math.floor(xOff / yearImgWidth);
      var monthOfYear = Math.min(11,Math.floor(monthOff / monthImgWidth));
      // 1 extra border pixel at the left edge of the year:
      var month = (year * 12) + monthOfYear;
      var day = monthOff % 2==1?15:1;
      var dateString = zeroPad(year + firstYear) + zeroPad(monthOfYear+1,2) +
	zeroPad(day,2) + "000000";

      $("displayYearEl").innerHTML=year+firstYear;
      $("displayMonthEl").innerHTML=prettyMonths[monthOfYear];
      // looks too jarring when it changes..
      //$("displayDayEl").innerHTML=zeroPad(day,2);
      var url = wbPrefix + dateString + '/' +  wbCurrentUrl;
      $("wm-graph-anchor").href=url;

      if(curYear != year) {
	var yrOff = year * yearImgWidth;
	yearTracker.style.left = yrOff + "px";
	curYear = year;
      }
      if(curMonth != month) {
	var mtOff = year + (month * monthImgWidth) + 1;
	monthTracker.style.left = mtOff + "px";
	curMonth = month;
      }
    }
    function disclaimElement(element) {
      if (window.top == window.self) {
	element.style.display = "block";
	$D.body.insertBefore(element, $D.body.firstChild);
      }
    }
    yearTracker=$D.createElement('div');
    yearTracker.className='yt';
    with(yearTracker.style){
      display='none';width=yearImgWidth+"px";height=imgHeight+"px";
    }
    monthTracker=$D.createElement('div');
    monthTracker.className='mt';
    with(monthTracker.style){
      display='none';width=monthImgWidth+"px";height=imgHeight+"px";
    }
    $spk.appendChild(yearTracker);
    $spk.appendChild(monthTracker);

    var $cv=$('wm-sparkline-canvas');
    $spk.onmouseenter=showTrackers;
    $spk.onmouseleave=showTrackers;
    $spk.onmousemove=trackMouseMove;

    var $ipp=$("wm-ipp");
    $ipp&&disclaimElement($ipp);

    var testCanvas = document.createElement('canvas');
    if(!!(testCanvas.getContext && testCanvas.getContext('2d'))) {
      var sparkline_url = "/__wb/sparkline?output=json&url=" +
	encodeURIComponent(wbCurrentUrl) +
	(coll && "&collection=" + coll || '');
      ajax("GET", sparkline_url, function(response) {
	  if(response.status == 200) {
	    var capnav=_JSON.parse(response.responseText);
	    var yearsobj = capnav.years;
	    var ykeys = Object.getOwnPropertyNames(yearsobj);
	    var years = (capnav.years = []);
	    for (var i = 0; i < ykeys.length; i++) {
	      var y = ykeys[i];
	      if (yearsobj[y]) {
		years.push([y, yearsobj[y]]);
	      }
	    }
	    navCaptures(capnav);
	    sparkline(capnav,imgWidth,imgHeight,'wm-sparkline-canvas',
		      firstYear, displayYear, displayMonth);
	  }
      });
    } else {
      var sparklineImg = new Image();
      sparklineImg.src = "/__wb/sparkline?url=" +
	encodeURIComponent(wbCurrentUrl) +
	"&width=" + imgWidth + "&height=" + imgHeight +
	"&selected_year=" + displayYear + "&selected_month=" + displayMonth +
	(coll && "&collection=" + coll || '');
      sparklineImg.alt= "sparkline";
      sparklineImg.width=imgWidth;
      sparklineImg.height=imgHeight;
      sparklineImg.id="sparklineImgId";
      sparklineImg.border="0";
      $cv.parentNode.replaceChild(sparklineImg, $cv);
    }

    function process_autocomplete(data) {
      var out = []
      var len = data.length;
      for(var i=0; i<len; i++) {
	if(typeof data[i].excluded === 'undefined') {
	  out.push(data[i].display_name);
	}
      }
      return out;
    }
    new wbAutoComplete({
      selector: 'input#wmtbURL',
      delay: 400,
      source: function(query, suggest) {
	ajax("GET", '/__wb/search/host?q=' + encodeURIComponent(query),
	  function(data) {
	    var data = _JSON.parse(data.response);
	    if (typeof data.hosts!=='undefined' && data.hosts.length>0) {
	      var output = process_autocomplete(data.hosts);
	      suggest(output);
	    } else if (typeof data.isUrl!=='undefined' && data.isUrl===true && typeof data.excluded==='undefined') {
	      suggest([query]);
	    } else {
	      ajax("GET", '/__wb/search/anchor?q='+encodeURIComponent(query),
		function(data) {
		  var data = _JSON.parse(data.response);
		  if (typeof data!=='undefined' && data.length>0) {
		    var output = process_autocomplete(data.slice(0,5));
		    suggest(output);
		  }
		});
	    }
	});
      },
      onSelect: function(e, term, item) {
	$("wmtb").submit();
      }
    });
    $("wmtb").onsubmit = function(e) {
      var query = $("wmtbURL").value;
      // if textbox value is not a URL, redirect to search
      if (!(query.indexOf('http://') === 0 || query.indexOf('https://') === 0 ||
	   query.match(/[\w\.]{2,256}\.[a-z]{2,4}/gi))) {
	document.location.href="/web/*/" + $("wmtbURL").value;
	e.preventDefault();
	return false;
      }
    };
  };
  function show_timestamps() {
    // Populate capinfo with capture resources if empty. If not empty, it has
    // already run before so avoid redoing AJAX.
    var $capresources=$('wm-capresources');
    $capresources.innerHTML = '';
    //disable caching to be able to reload list when browsing frames.
    //if($capresources.innerHTML.length !== 0) {
    //  return;
    //}
    var $wmloading=$("wm-capresources-loading");
    $wmloading.style.display='block';

    // calculate datetime difference with capture datetime and return relative
    // value such as "-5 hours, 10 minutes".
    var capture_ts = document.getElementById('wmtb').elements.date.value;
    var capture_msec = __wbTs.timestamp2datetime(capture_ts).getTime();
    function datetime_diff(dt_str) {
      var dt_msec = Date.parse(dt_str);
      var diff = dt_msec - capture_msec;
      var prefix = "";
      if(diff < 0) {
	  prefix += "-";
	  diff = Math.abs(diff);
      } else {
	 prefix += "+";
      }
      var highlight = false;
      if(diff < 1000) {
	  // equal to the page datetime
	  return {delta: diff, text:"", highlight: highlight};
      }
      var total_diff = diff;
      var years_d = Math.floor(diff/1000/60/60/24/30/12);
      diff -= years_d*1000*60*60*24*30*12;
      var months_d = Math.floor(diff/1000/60/60/24/30);
      diff -= months_d*1000*60*60*24*30;
      var days_d = Math.floor(diff/1000/60/60/24);
      diff -= days_d*1000*60*60*24;
      var hours_d = Math.floor(diff/1000/60/60);
      diff -= hours_d*1000*60*60;
      var minutes_d = Math.floor(diff/1000/60);
      diff -= minutes_d*1000*60;
      var seconds_d = Math.floor(diff/1000);

      var parts = [];
      if(years_d > 1) {
	  parts.push(years_d + " years");
	  highlight = true;
      } else if(years_d == 1) {
	  parts.push(years_d + " year");
	  highlight = true;
      }
      if(months_d > 1) {
	  parts.push(months_d + " months");
	  highlight = true;
      } else if(months_d == 1) {
	  parts.push(months_d + " month");
	  highlight = true;
      }
      if(days_d > 1) {
	  parts.push(days_d + " days");
      } else if(days_d == 1) {
	  parts.push(days_d + " day");
      }
      if(hours_d > 1) {
	  parts.push(hours_d + " hours");
      } else if(hours_d  == 1) {
	  parts.push(hours_d + " hour");
      }
      if(minutes_d > 1) {
	  parts.push(minutes_d + " minutes");
      } else if (minutes_d == 1) {
	  parts.push(minutes_d + " minute");
      }
      if(seconds_d > 1) {
	  parts.push(seconds_d + " seconds");
      } else if(seconds_d == 1) {
	  parts.push(seconds_d + " second");
      }
      if(parts.length > 2) {
	  parts = parts.slice(0, 2);
      }
      return {delta: total_diff, text: prefix + parts.join(" "),
	      highlight: highlight};
    }
    // Utility method to find elements in dom (currently only img) using URL.
    // Also look into embedded frames recursively
    // Captured resources urls may have timestamps different from DOM URL
    // so it is not possible to search with original path
    // /web/20120407141544/http://example.com
    // we must search for URLS ENDING WITH http://example.com
    function find_elements_by_url(current_window, url) {
      var orig_url = url.split("/").splice(6).join("/");
      var els=current_window.document.querySelectorAll(
	"img[src$='" + orig_url + "'], iframe[src$='" + orig_url + "'], frame[src$='" + orig_url + "']"
      );
      var els_array=Array.prototype.slice.call(els);
      for(var i=0; i<current_window.frames.length; i++) {
	try {
	  var frame_els_array=find_elements_by_url(current_window.frames[i].window, url);
	  els_array = els_array.concat(frame_els_array);
	} catch(err) {
	  // pass
	}
      }
      return els_array;
    }
    // invoked onmouseover of link to add highlight
    function highlight_elm(e){
      if(e.tagName=='FRAME'||e.tagName=='IFRAME')
	return e.contentWindow.document.documentElement;
      else
	return e;
    }
    function highlight_on(ev) {
      var elements = find_elements_by_url(window, ev.target.href);
      if(elements.length > 0) {
	for(var i=0; i<elements.length; i++) {
	  highlight_elm(elements[i]).classList.add("wb-highlight");
	}
      }
    }
    // invoked onmouseout of link to remove highlight
    function highlight_off(ev) {
      var elements = find_elements_by_url(window, ev.target.href);
      if(elements.length > 0) {
	for(var i=0; i<elements.length; i++) {
	  highlight_elm(elements[i]).classList.remove("wb-highlight");
	}
      }
    }

    // Utility method to show capture elements link, datetime and content-type.
    // AJAX follows redirects automatically, only status=200 responses are handled.
    function get_resource_info(url) {
      ajax("HEAD", url, function(response) {
	if(response.status==200) {
	  $wmloading.style.display='none';
	  var dt=response.getResponseHeader('Memento-Datetime');
	  var dt_span=document.createElement('span');
	  var dt_result = datetime_diff(dt);
	  var style = dt_result.highlight ? "color:red;" : "";
	  dt_span.innerHTML=" " + dt_result.text;
	  dt_span.title=dt;
	  dt_span.setAttribute('style', style);
	  var ct=response.getResponseHeader('Content-Type');
	  var url=response.responseURL.replace(window.location.origin, "");
	  var link=document.createElement('a');
	  // remove /web/timestamp/ from appearance
	  link.innerHTML=url.split("/").splice(3).join("/");
	  link.href=url;
	  link.title=ct;
	  link.onmouseover=highlight_on;
	  link.onmouseout=highlight_off;
	  var el=document.createElement('div');
	  el.setAttribute('data-delta', dt_result.delta);
	  el.appendChild(link);
	  el.append(dt_span);
	  $capresources.appendChild(el);
	  // sort elements by delta in a descending order and update container
	  var items = Array.prototype.slice.call($capresources.childNodes, 0);
	  items.sort(function(a, b) {
	      return b.getAttribute('data-delta') - a.getAttribute('data-delta');
	  });
	  $capresources.innerHTML = "";
	  for(var i=0, len=items.length; i<len; i++) {
	      $capresources.appendChild(items[i]);
	  }
	}
      });
    }
    // utility method to traverse the document and frames recursively to find
    // element with specific tag. Always convert selector result (NodeList)
    // to Array to be able to concat.
    function find_elements_by_tag_name(current_window, tag) {
      var els=current_window.document.getElementsByTagName(tag);
      var els_array=Array.prototype.slice.call(els);
      for(var i=0; i<current_window.frames.length; i++) {
	try {
	  var frame_els_array=find_elements_by_tag_name(current_window.frames[i].window, tag);
	  els_array = els_array.concat(frame_els_array);
	} catch(err) {
	  // pass
	}
      }
      return els_array;
    }

    // images
    var static_prefix=window.location.origin + "/static/";
    var srcList=[];
    var imgs=find_elements_by_tag_name(window, 'img');
    for(var i=0, len=imgs.length; i<len; i++) {
      // exclude WBM /static/images, leaked images and embedded data URIs
      if(!imgs[i].src || imgs[i].src.startsWith(static_prefix) ||
	!imgs[i].src.startsWith(window.location.origin) ||
	imgs[i].src.startsWith("data:")) {
	continue;
      }
      srcList.push(imgs[i].src);
    }
    // frames
    var frames=find_elements_by_tag_name(window, 'frame');
    for(i=0, len=frames.length; i<len; i++) {
      if(!frames[i].src) {
	continue;
      }
      srcList.push(frames[i].src);
    }
    var iframes=find_elements_by_tag_name(window, 'iframe');
    for(i=0, len=iframes.length; i<len; i++) {
      if(!iframes[i].src || (iframes[i].id && iframes[i].id === 'playback')) {
	continue;
      }
      srcList.push(iframes[i].src);
    }
    var scripts=find_elements_by_tag_name(window, 'script');
    for(i=0, len=scripts.length; i<len; i++) {
      if(!scripts[i].src || scripts[i].src.startsWith(static_prefix) ||
	!scripts[i].src.startsWith(window.location.origin)) {
	continue;
      }
      srcList.push(scripts[i].src);
    }
    // link.href (CSS, RSS, etc)
    var links=find_elements_by_tag_name(window, 'link');
    for(i=0, len=links.length; i<len; i++) {
      if(!links[i].href || links[i].href.startsWith(static_prefix) ||
	!links[i].href.startsWith(window.location.origin)) {
	continue;
      }
      if(links[i].rel && links[i].rel=="stylesheet") {
	srcList.push(links[i].href);
      }
    }
    // deduplicate
    var deduped = srcList.filter(function(el, i, arr) {
      return arr.indexOf(el) === i;
    });
    if(deduped.length > 0) {
      deduped.map(get_resource_info);
    } else {
      $capresources.innerHTML = "There are no sub-resources in the page.";
    }
  }
  __wm.ex=function expand(ev) {
    ev.stopPropagation();
    var c=$expand.className;
    if (c.match(/wm-closed/)) { // closed
      $expand.className=c.replace(/wm-closed/,'wm-open');
      $capinfo.style.display='block';
      show_timestamps();
    } else {
      $expand.className=c.replace(/wm-open/,'wm-closed');
      $capinfo.style.display='none';
    }
  };

  function isArray(obj) {
    return (typeof obj !== 'undefined' && obj && obj.constructor === Array);
  }

  function setDisplayStyle(id, display) {
    var el = $(id);
    if (el) {
      el.style.display = display;
    }
  }

  function show(ids) {
    if (!isArray(ids)) {
      ids = [ids];
    }
    for (var i = 0; i < ids.length; i++) {
      setDisplayStyle(ids[i], 'inline-block');
    }
  }

  function hide(ids) {
    if (!isArray(ids)) {
      ids = [ids];
    }
    for (var i = 0; i < ids.length; i++) {
      setDisplayStyle(ids[i], 'none');
    }
  }

  function userIsLoggedIn() {
    show('wm-save-snapshot-open');
    hide('wm-sign-in');
  }

  function userIsNotLoggedIn() {
    hide([
      'wm-save-snapshot-open',
      'wm-save-snapshot-in-progress',
    ]);
    show('wm-sign-in');
  }

  function startSnapShotSaving() {
    hide([
      'wm-save-snapshot-fail',
      'wm-save-snapshot-open',
      'wm-save-snapshot-success',
    ]);
    show([
      'wm-save-snapshot-in-progress',
    ]);
  }

  function successSnapshotSaving() {
    hide([
      'wm-save-snapshot-fail',
      'wm-save-snapshot-in-progress',
    ]);
    show([
      'wm-save-snapshot-open',
      'wm-save-snapshot-success',
    ]);
  }

  function failSnapshotSaving(err) {
    hide([
      'wm-save-snapshot-in-progress',
      'wm-save-snapshot-success',
    ]);
    show([
      'wm-save-snapshot-fail',
      'wm-save-snapshot-open',
    ]);
  }

  /**
   * check whether cookie has field
   *
   * @param name
   * @return boolean
   */
  function hasCookie(name) {
    return document.cookie.search(name) >= 0;
  }

  __wm.saveSnapshot = function (url, timestamp, tags) {
    startSnapShotSaving();
    ajax('POST', '/__wb/web-archive/', function (res) {
      if (res.status === 401) {
        // it seems that user is not logged in
        userIsNotLoggedIn();
      } else if (res.status >= 400) {
        failSnapshotSaving(res.responseText);
        console.log('You have got an error.');
        console.log('If you think something wrong here please send it to support.');
        console.log('Response: "' + res.responseText + '"');
        console.log('status: "' + res.status + '"');
      } else {
        successSnapshotSaving(res);
      }
    }, {
      'Content-Type': 'application/json'
    }, _JSON.stringify({
      url: url,
      snapshot: timestamp,
      tags: tags || [],
    }));

    return false;
  };

  document.addEventListener('DOMContentLoaded', function () {
    if (hasCookie('logged-in-user') && hasCookie('logged-in-sig')) {
      userIsLoggedIn();
    } else {
      userIsNotLoggedIn();
    }
  });
})();
