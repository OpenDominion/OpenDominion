function getEventX(event) {
	var posx = 0;
	if (event.pageX || event.pageY) {
		posx =  event.pageX;
	}
	else if (event.clientX || event.clientY) 	{
		posx = event.clientX + document.body.scrollLeft
			+ document.documentElement.scrollLeft;
	}
	return posx;
}
function getElementX(obj) {
	var x = 0;
	if (obj.offsetParent) {
		do {
			x += obj.offsetLeft;
		} while (obj = obj.offsetParent);
	}
	return x;
}
function zeroPad(v, len) {
  v = v.toString();
  return v.length >= len ? v : "00000000".substring(0, len - v.length) + v;
}

/**
 * Check if the difference between the max and the min non zero capture numbers
 * is larger than 3 orders of magnitude. If yes, we need to scale.
**/
function capturegraph_scale_is_required(captures) {
    var max = 0;
    var min = 1000;
    for (var i = 0; i < captures.length; i++) {
	var year = captures[i];
        max = Math.max(max, Math.max.apply(null, year[1]));
        min = Math.min(min, Math.min.apply(null,
                                           year[1].filter(Boolean)));
    }
    return (Math.log1p(max) - Math.log1p(min) > 3);
}

/**
 * Scale captugraph counts and max maxcount using log1p if necessary.
 */
function capturegraph_scale(captures) {
    var maxcount = 0;
    for (var i = 0; i < captures.length; i++) {
	maxcount = Math.max(maxcount, Math.max.apply(null, captures[i][1]));
    }
    if (capturegraph_scale_is_required(captures)) {
	var scaled = [];
	for (var i = 0; i < captures.length; i++) {
	    var year = captures[i];
	    // XXX map may not be available on all platforms
	    scaled.push([year[0], year[1].map(Math.log1p)]);
	}
	captures = scaled;
	maxcount = Math.log1p(maxcount);
    }
    return [captures, maxcount];
}

/**
 * Draw years, highlight current year, draw capture frequency per month
 */
function sparkline(captures, width, height, canvas_id, start_year,
                   cur_year, cur_month) {
    var c = document.getElementById(canvas_id);
    if (!c || !c.getContext) return;
    var ctx = c.getContext("2d");
    if (!ctx) return;
    ctx.fillStyle = "#FFF";
    var end_year = new Date().getUTCFullYear();
    var year_width = width / (end_year - start_year + 1);

    var scaled = capturegraph_scale(captures.years);
    var years = scaled[0];
    var maxcount = scaled[1];

    var yscale = height / maxcount;

    function year_left(year) {
	return Math.ceil((year - start_year) * year_width) + 0.5;
    }

    if (cur_year >= start_year) {
	var x = year_left(cur_year);
	ctx.fillStyle = "#FFFFA5";
	ctx.fillRect(x, 0, year_width, height);
    }
    for (var year = start_year; year <= end_year; year++) {
	var x = year_left(year);
        ctx.beginPath();
        ctx.moveTo(x, 0);
        ctx.lineTo(x, height);
        ctx.lineWidth = 1;
        ctx.strokeStyle = "#CCC";
        ctx.stroke();
    }
    cur_month = parseInt(cur_month) - 1;
    var month_width = (year_width - 1) / 12;
    for (var i = 0; i < years.length; i++) {
	var year = years[i][0];
	var months = years[i][1];
	var left = year_left(year) + 1.0;
        for (var month = 0; month < 12; month++) {
            var count = months[month];
            if (count > 0) {
                var h = Math.ceil(count * yscale);
		if (year == cur_year && month == cur_month) {
                    ctx.fillStyle = "#EC008C";
                } else {
                    ctx.fillStyle = "#000";
                }
                // must note that when I use width=Math.round(month_width+1),
                // the replay toolbar looks more accurate whereas the
                // bubble calendar looks somehow different.
                ctx.fillRect(Math.round(left), Math.ceil(height - h),
                             Math.ceil(month_width), Math.round(h));
            }
            left += month_width;
        }
    }
}

function clear_canvas(canvas_id) {
    var c = document.getElementById(canvas_id);
    if (!c || !c.getContext) return;
    var ctx = c.getContext("2d");
    if (!ctx) return;
    ctx.clearRect(0, 0, c.width, c.height);
}
