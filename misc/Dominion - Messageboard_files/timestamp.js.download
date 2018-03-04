/** timestamp namespaced methods **/
var __wbTs = (function() {
    function _split_timestamp(timestamp) {
        if(typeof timestamp == "number") {
            timestamp = timestamp.toString();
        }
        return [
            timestamp.slice(-14, -10),
            timestamp.slice(-10, -8),
            timestamp.slice(-8, -6),
            timestamp.slice(-6, -4),
            timestamp.slice(-4, -2),
            timestamp.slice(-2)
        ];
    }
    var MONTHS_LONG = [
	"January", "February", "March", "April", "May", "June",
	"July", "August", "September", "October", "November", "December"
    ];
    var MONTHS_SHORT = [
	"Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep",
	"Oct", "Nov", "Dec"
    ];
    var FIELDS = {
	'Y': function(d) { return d.getUTCFullYear() },
	'm': function(d) { return d.getUTCMonth() + 1 },
	'b': function(d) { return MONTHS_SHORT[d.getUTCMonth()] },
	'B': function(d) { return MONTHS_LONG[d.getUTCMonth()] },
	'd': function(d) { return d.getUTCDate() },
	'H': function(d) { return ('0'+d.getUTCHours()).slice(-2) },
	'M': function(d) { return ('0'+d.getUTCMinutes()).slice(-2) },
	'S': function(d) { return ('0'+d.getUTCSeconds()).slice(-2) },
	'%': function() { return '%' }
    };
    function timestamp2datetime(timestamp) {
            var ts_array = _split_timestamp(timestamp);
            return new Date(Date.UTC(
		ts_array[0], ts_array[1]-1, ts_array[2],
		ts_array[3], ts_array[4], ts_array[5]
	    ));
    }
    return {
	timestamp2datetime: timestamp2datetime,
	getMonthName: function(mon) {
	    return MONTHS_LONG[mon];
	},
	format: function(timestamp, fmt) {
	    return fmt.replace(/%./g, function(ph) {
		var field = FIELDS[ph[1]];
		return field ? field(timestamp2datetime(timestamp)) : ph;
	    });
	}
    }
})();
