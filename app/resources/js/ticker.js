'use strict';

class Ticker {
    /**
     * Ticker constructor.
     */
    constructor() {
        this.tickerServerElement = null;
        this.tickerNextHourElement = null;
        this.tickerNextRoundElement = null;
        this.nextRoundStartDate = null;
    }

    /**
     * Starts the ticker.
     */
    start() {
        this.tickerServerElement = document.getElementById('ticker-server');
        this.tickerNextHourElement = document.getElementById('ticker-next-tick');
        this.tickerNextRoundElement = document.getElementById('ticker-next-round');

        // Only tick if the ticker element is visible; i.e. not on the homepage
        if (this.tickerServerElement !== null) {
            const self = this;
            setInterval(() => self.tick(), 1000);
        }

        if(this.tickerNextRoundElement !== null) {
            this.nextRoundStartDate = new Date(this.tickerNextRoundElement.dataset.value);
        }
    }

    /**
     * Ticks the ticker.
     *
     * @private
     */
    tick() {
        const currentServerTime = this.tickerServerElement.innerHTML;
        const currentTime = new Date('1970-01-01T' + currentServerTime + 'Z');
        currentTime.setUTCSeconds(currentTime.getUTCSeconds() + 1);

        this.tickerServerElement.innerHTML = Ticker.hms(Ticker.utc(currentTime));
        if (this.tickerNextHourElement !== null) {
            const nextHour = new Date(currentTime.toString());
            nextHour.setUTCHours(currentTime.getUTCHours() + 1);
            nextHour.setMinutes(0);
            nextHour.setSeconds(0);

            const diffDate = (nextHour - currentTime);
            this.tickerNextHourElement.innerHTML = Ticker.hms(diffDate);
        } else if(this.tickerNextRoundElement !== null){
            const diffDate = (this.nextRoundStartDate - new Date());

            if(diffDate > 0) {
                this.tickerNextRoundElement.innerHTML = Ticker.hms(diffDate);
            }
        }
    }

    /**
     * Formats a Date or numeric value.
     *
     * @param {Date|number} value
     * @return {string}
     * @private
     */
    static hms(value) {
        let hours = 0;
        let minutes = 0;
        let seconds = 0;

        if (value instanceof Date) {
            hours = value.getHours();
            minutes = value.getMinutes();
            seconds = value.getSeconds();

        } else if (typeof value === 'number') {
            value /= 1000;

            if(value < 3600) {
                seconds = value % 60;
                minutes = (value - seconds) / 60;
                hours = (value - (minutes * 60) - seconds) / 60;
            } else {
                let tempValue = value;
                hours = Math.floor(tempValue / 3600);
                tempValue -= hours * 3600;
                minutes = Math.floor(tempValue / 60);
                tempValue -= minutes * 60;
                seconds = Math.floor(tempValue);
            }
        }

        return (
            Ticker.pad(hours) +
            ':' + Ticker.pad(minutes) +
            ':' + Ticker.pad(seconds)
        );
    }

    /**
     * Left-pads a numeric value with two zeroes.
     *
     * @param {number} value
     * @returns {string}
     * @private
     */
    static pad(value) {
        return ('00' + value).slice(-2);
    }

    /**
     * Converts a Date into UTC.
     *
     * @param {Date} date
     * @returns {Date}
     * @private
     */
    static utc(date) {
        return new Date(
            date.getUTCFullYear(),
            date.getUTCMonth(),
            date.getUTCDate(),
            date.getUTCHours(),
            date.getUTCMinutes(),
            date.getUTCSeconds()
        );
    }
}

module.exports = new Ticker;
